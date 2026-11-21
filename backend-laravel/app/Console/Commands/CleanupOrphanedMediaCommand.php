<?php

namespace App\Console\Commands;

use App\Models\ExamQuestionSnapshot;
use App\Models\MediaAsset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CleanupOrphanedMediaCommand extends Command
{
    protected $signature = 'cbt:cleanup-orphaned-media {--age-hours=24 : Minimum age of soft-deleted media} {--limit=500 : Maximum records to inspect}';

    protected $description = 'Remove storage files and soft-deleted media rows that are no longer referenced.';

    public function handle(): int
    {
        $ageHours = filter_var($this->option('age-hours'), FILTER_VALIDATE_INT);
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);

        if ($ageHours === false || $ageHours < 1 || $limit === false || $limit < 1 || $limit > 10000) {
            $this->error('age-hours minimal 1 dan limit harus antara 1 sampai 10000.');
            return self::INVALID;
        }

        $cutoff = now()->subHours($ageHours);
        $candidates = MediaAsset::withoutGlobalScopes()
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '<=', $cutoff)
            ->orderBy('deleted_at')
            ->limit($limit)
            ->get(['id', 'tenant_id', 'storage_path', 'disk', 'deleted_at']);

        $deleted = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($candidates as $candidate) {
            try {
                $record = DB::transaction(function () use ($candidate, $cutoff) {
                    $media = MediaAsset::withoutGlobalScopes()
                        ->whereKey($candidate->id)
                        ->where('tenant_id', $candidate->tenant_id)
                        ->whereNotNull('deleted_at')
                        ->where('deleted_at', '<=', $cutoff)
                        ->lockForUpdate()
                        ->first();

                    if (!$media || $this->hasReferences($media)) {
                        return null;
                    }

                    // Only the configured local disk and a flat, tenant-owned path
                    // are eligible. Prefix-only checks are unsafe because ../ can
                    // still escape the tenant directory after path normalization.
                    if ($media->disk !== 'local' || !$this->isSafeTenantMediaPath($media->storage_path, $media->tenant_id)) {
                        Log::warning('CBT orphan media cleanup skipped unsafe storage location', [
                            'tenant_id' => $media->tenant_id,
                            'media_id' => $media->id,
                            'disk' => $media->disk,
                            'storage_path' => $media->storage_path,
                        ]);
                        return null;
                    }

                    return [
                        'id' => $media->id,
                        'tenant_id' => $media->tenant_id,
                        'disk' => $media->disk,
                        'storage_path' => $media->storage_path,
                    ];
                });

                if (!$record) {
                    $skipped++;
                    continue;
                }

                $disk = Storage::disk($record['disk']);
                if ($disk->exists($record['storage_path'])) {
                    $disk->delete($record['storage_path']);
                    // Verify physical absence regardless of the adapter's return value.
                    if ($disk->exists($record['storage_path'])) {
                        $failed++;
                        continue;
                    }
                }

                $rowDeleted = DB::transaction(function () use ($record) {
                    $media = MediaAsset::withoutGlobalScopes()
                        ->whereKey($record['id'])
                        ->where('tenant_id', $record['tenant_id'])
                        ->whereNotNull('deleted_at')
                        ->lockForUpdate()
                        ->first();

                    if (!$media || $this->hasReferences($media)) {
                        return false;
                    }

                    $media->forceDelete();
                    return true;
                });

                if ($rowDeleted) {
                    $deleted++;
                    Log::info('CBT orphan media cleaned', [
                        'tenant_id' => $record['tenant_id'],
                        'media_id' => $record['id'],
                        'disk' => $record['disk'],
                        'storage_path' => $record['storage_path'],
                    ]);
                } else {
                    $skipped++;
                    Log::warning('CBT orphan media row retained after storage cleanup; reference recheck failed', [
                        'tenant_id' => $record['tenant_id'],
                        'media_id' => $record['id'],
                    ]);
                }
            } catch (Throwable $exception) {
                $failed++;
                report($exception);
            }
        }

        $this->info("Selesai: {$deleted} record dibersihkan, {$skipped} dilewati, {$failed} gagal.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function isSafeTenantMediaPath(?string $path, string $tenantId): bool
    {
        if (!is_string($path) || str_contains($path, "\0") || str_contains($path, '\\')) {
            return false;
        }

        $segments = explode('/', $path);
        if (count($segments) !== 5
            || $segments[0] !== 'private'
            || $segments[1] !== 'tenants'
            || $segments[2] !== $tenantId
            || $segments[3] !== 'media') {
            return false;
        }

        return preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]{0,254}\z/D', $segments[4]) === 1
            && $segments[4] !== '.'
            && $segments[4] !== '..';
    }

    private function hasReferences(MediaAsset $media): bool
    {
        $pivotReference = DB::table('question_version_media')
            ->where('tenant_id', $media->tenant_id)
            ->where('media_asset_id', $media->id)
            ->exists();

        if ($pivotReference) {
            return true;
        }

        return ExamQuestionSnapshot::withoutGlobalScopes()
            ->where('tenant_id', $media->tenant_id)
            ->whereJsonContains('snapshot_media', ['id' => $media->id])
            ->exists();
    }
}
