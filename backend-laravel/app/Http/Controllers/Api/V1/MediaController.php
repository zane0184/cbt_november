<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExamQuestionSnapshot;
use App\Models\MediaAsset;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class MediaController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole('GURU'),
            ResponseCode::HTTP_FORBIDDEN
        );

        $query = MediaAsset::query()->where('tenant_id', $user->tenant_id);

        if ($user->hasRole('GURU')) {
            // A teacher's library is limited to assets uploaded by that teacher.
            $query->where('uploaded_by', $user->id);
        } else {
            // Admins can manage tenant question media, but USER media remains private.
            $query->where('owner_type', 'QUESTION');
        }

        $assets = $query->latest()->paginate(30);
        $assets->getCollection()->each(fn (MediaAsset $asset) => $asset->makeHidden(['storage_path']));

        return response()->json(['success' => true, 'data' => $assets]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,mp3,mp4,pdf'],
            'owner_type' => ['required', 'string', 'in:QUESTION,USER'],
            'owner_id' => ['nullable', 'uuid'],
        ]);

        $user = $request->user();
        $tenantId = $user->tenant_id;
        abort_unless($tenantId, ResponseCode::HTTP_FORBIDDEN);

        if ($validated['owner_type'] === 'USER') {
            // A client cannot upload a private asset on behalf of another account.
            if (!empty($validated['owner_id']) && $validated['owner_id'] !== $user->id) {
                abort(ResponseCode::HTTP_FORBIDDEN, 'Media USER hanya boleh dimiliki pengguna yang mengunggah.');
            }
            $ownerId = $user->id;
        } else {
            abort_unless(
                $user->hasRole('ADMIN_MADRASAH') || $user->hasRole('GURU'),
                ResponseCode::HTTP_FORBIDDEN
            );

            abort_unless(!empty($validated['owner_id']), ResponseCode::HTTP_UNPROCESSABLE_ENTITY, 'Question wajib ditentukan.');
            $question = Question::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($validated['owner_id']);

            Gate::authorize('update', $question);
            $ownerId = $question->id;
        }

        $file = $request->file('file');
        $fileName = (string) Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
        $disk = Storage::disk('local');
        $path = $file->storeAs("private/tenants/{$tenantId}/media", $fileName, 'local');
        abort_unless(is_string($path) && $path !== '', ResponseCode::HTTP_INTERNAL_SERVER_ERROR, 'Gagal menyimpan file media.');

        try {
            $media = DB::transaction(function () use ($user, $tenantId, $validated, $file, $fileName, $path, $ownerId) {
                $media = MediaAsset::create([
                    'tenant_id' => $tenantId,
                    'owner_type' => $validated['owner_type'],
                    'owner_id' => $ownerId,
                    'file_name' => $fileName,
                    'original_name' => $file->getClientOriginalName(),
                    'storage_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'extension' => strtolower($file->getClientOriginalExtension()),
                    'file_size' => $file->getSize(),
                    'disk' => 'local',
                    'uploaded_by' => $user->id,
                ]);

                $this->auditService->log(
                    'UPLOAD_MEDIA',
                    'MediaAsset',
                    $media->id,
                    null,
                    ['owner_type' => $media->owner_type, 'owner_id' => $media->owner_id, 'name' => $media->original_name]
                );

                return $media;
            });
        } catch (\Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }

        return response()->json([
            'success' => true,
            'data' => $media->makeHidden(['storage_path']),
        ], ResponseCode::HTTP_CREATED);
    }

    /**
     * Attach a tenant-owned QUESTION media asset to a draft question version.
     * The version lock serializes writes to one version; the media lock is
     * shared with destroy(), so attach-vs-delete has a deterministic winner.
     */
    public function attachToQuestionVersion(Request $request, string $questionId, string $versionId)
    {
        $validated = $request->validate([
            'media_id' => ['required', 'uuid'],
            'role' => ['sometimes', 'required', 'string', 'in:STEM,OPTION,EXPLANATION'],
            'sequence' => ['sometimes', 'required', 'integer', 'min:0', 'max:10000'],
        ]);

        $user = $request->user();
        $tenantId = $user->tenant_id;
        abort_unless($tenantId, ResponseCode::HTTP_FORBIDDEN);
        abort_unless($user->hasRole('ADMIN_MADRASAH') || $user->hasRole('GURU'), ResponseCode::HTTP_FORBIDDEN);

        $result = DB::transaction(function () use ($questionId, $versionId, $validated, $user, $tenantId) {
            // Consistent lock order: Question -> QuestionVersion -> MediaAsset.
            $question = Question::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($questionId)
                ->lockForUpdate()
                ->firstOrFail();
            Gate::authorize('update', $question);

            $version = QuestionVersion::query()
                ->where('tenant_id', $tenantId)
                ->where('question_id', $question->id)
                ->whereKey($versionId)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($version->status === 'DRAFT', ResponseCode::HTTP_CONFLICT, 'Media hanya dapat diubah pada versi soal berstatus DRAFT.');
            abort_if($question->status === 'ARCHIVED', ResponseCode::HTTP_CONFLICT, 'Soal telah diarsipkan.');

            $media = MediaAsset::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($validated['media_id'])
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $media->owner_type === 'QUESTION' && $media->owner_id === $question->id,
                ResponseCode::HTTP_UNPROCESSABLE_ENTITY,
                'Media harus dimiliki oleh soal yang sama.'
            );

            $exists = DB::table('question_version_media')
                ->where('tenant_id', $tenantId)
                ->where('question_version_id', $version->id)
                ->where('media_asset_id', $media->id)
                ->exists();
            abort_if($exists, ResponseCode::HTTP_CONFLICT, 'Media sudah terpasang pada versi soal ini.');

            $pivotId = (string) Str::uuid();
            $role = $validated['role'] ?? 'STEM';
            $sequence = $validated['sequence'] ?? 0;

            DB::table('question_version_media')->insert([
                'id' => $pivotId,
                'tenant_id' => $tenantId,
                'question_version_id' => $version->id,
                'media_asset_id' => $media->id,
                'role' => $role,
                'sequence' => $sequence,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->auditService->log(
                'ATTACH_QUESTION_VERSION_MEDIA',
                'QuestionVersion',
                $version->id,
                null,
                ['media_id' => $media->id, 'role' => $role, 'sequence' => $sequence]
            );

            return ['version_id' => $version->id, 'media_id' => $media->id, 'role' => $role, 'sequence' => $sequence];
        });

        return response()->json(['success' => true, 'data' => $result], ResponseCode::HTTP_CREATED);
    }

    /** Detach from a mutable draft only; historical exam snapshots are immutable copies. */
    public function detachFromQuestionVersion(Request $request, string $questionId, string $versionId, string $mediaId)
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;
        abort_unless($tenantId, ResponseCode::HTTP_FORBIDDEN);
        abort_unless($user->hasRole('ADMIN_MADRASAH') || $user->hasRole('GURU'), ResponseCode::HTTP_FORBIDDEN);

        DB::transaction(function () use ($questionId, $versionId, $mediaId, $user, $tenantId) {
            // Keep the same lock order as attach to reduce deadlock risk.
            $question = Question::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($questionId)
                ->lockForUpdate()
                ->firstOrFail();
            Gate::authorize('update', $question);

            $version = QuestionVersion::query()
                ->where('tenant_id', $tenantId)
                ->where('question_id', $question->id)
                ->whereKey($versionId)
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($version->status === 'DRAFT', ResponseCode::HTTP_CONFLICT, 'Media hanya dapat dilepas dari versi soal berstatus DRAFT.');
            abort_if($question->status === 'ARCHIVED', ResponseCode::HTTP_CONFLICT, 'Soal telah diarsipkan.');

            $media = MediaAsset::query()
                ->where('tenant_id', $tenantId)
                ->where('owner_type', 'QUESTION')
                ->where('owner_id', $question->id)
                ->whereKey($mediaId)
                ->lockForUpdate()
                ->firstOrFail();

            $pivot = DB::table('question_version_media')
                ->where('tenant_id', $tenantId)
                ->where('question_version_id', $version->id)
                ->where('media_asset_id', $media->id)
                ->lockForUpdate()
                ->first();
            abort_unless($pivot, ResponseCode::HTTP_NOT_FOUND, 'Relasi media tidak ditemukan.');

            DB::table('question_version_media')->where('id', $pivot->id)->delete();
            $this->auditService->log(
                'DETACH_QUESTION_VERSION_MEDIA',
                'QuestionVersion',
                $version->id,
                ['media_id' => $media->id, 'role' => $pivot->role, 'sequence' => $pivot->sequence],
                null
            );
        });

        return response()->json(['success' => true]);
    }

    public function show(Request $request, MediaAsset $media)
    {
        $user = $request->user();

        abort_unless($media->tenant_id === $user->tenant_id, ResponseCode::HTTP_FORBIDDEN);

        if ($media->owner_type === 'USER') {
            abort_unless(
                $media->owner_id === $user->id && $media->uploaded_by === $user->id,
                ResponseCode::HTTP_FORBIDDEN
            );
        } elseif ($media->owner_type === 'QUESTION') {
            $question = Question::query()
                ->where('tenant_id', $media->tenant_id)
                ->find($media->owner_id);

            $questionReadable = false;
            if ($question && $user->hasRole('GURU')) {
                $questionReadable = Gate::allows('view', $question);
            }

            $activeAttemptReadable = $this->isReferencedByOwnActiveAttempt($media, $user->id, $user->tenant_id);

            abort_unless($questionReadable || $activeAttemptReadable, ResponseCode::HTTP_FORBIDDEN);
        } else {
            abort(ResponseCode::HTTP_FORBIDDEN);
        }

        abort_unless(
            $media->disk === 'local' && $this->isSafeTenantMediaPath($media->storage_path, $media->tenant_id),
            ResponseCode::HTTP_NOT_FOUND,
            'Lokasi penyimpanan media tidak valid.'
        );

        $disk = Storage::disk('local');
        abort_unless($disk->exists($media->storage_path), ResponseCode::HTTP_NOT_FOUND, 'File not found.');

        return response()->file($disk->path($media->storage_path), [
            'Content-Type' => $media->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroy(Request $request, MediaAsset $media)
    {
        $user = $request->user();
        abort_unless($media->tenant_id === $user->tenant_id, ResponseCode::HTTP_FORBIDDEN);

        if ($media->owner_type === 'USER') {
            abort_unless(
                $media->owner_id === $user->id && $media->uploaded_by === $user->id,
                ResponseCode::HTTP_FORBIDDEN
            );
        } elseif ($media->owner_type === 'QUESTION') {
            $question = Question::query()
                ->where('tenant_id', $media->tenant_id)
                ->findOrFail($media->owner_id);

            Gate::authorize('update', $question);
            if ($user->hasRole('GURU')) {
                abort_unless($media->uploaded_by === $user->id, ResponseCode::HTTP_FORBIDDEN);
            } else {
                abort(ResponseCode::HTTP_FORBIDDEN);
            }
        } else {
            abort(ResponseCode::HTTP_FORBIDDEN);
        }

        $storageDisk = $media->disk;
        $storagePath = $media->storage_path;
        abort_unless(
            $storageDisk === 'local' && $this->isSafeTenantMediaPath($storagePath, $media->tenant_id),
            ResponseCode::HTTP_CONFLICT,
            'Lokasi penyimpanan media tidak valid; penghapusan dibatalkan.'
        );

        DB::transaction(function () use ($media) {
            $lockedMedia = MediaAsset::query()
                ->where('tenant_id', $media->tenant_id)
                ->whereKey($media->id)
                ->lockForUpdate()
                ->firstOrFail();

            $questionVersionReference = DB::table('question_version_media')
                ->where('tenant_id', $lockedMedia->tenant_id)
                ->where('media_asset_id', $lockedMedia->id)
                ->exists();

            $snapshotReference = ExamQuestionSnapshot::query()
                ->where('tenant_id', $lockedMedia->tenant_id)
                ->whereJsonContains('snapshot_media', ['id' => $lockedMedia->id])
                ->exists();

            if ($questionVersionReference || $snapshotReference) {
                abort(ResponseCode::HTTP_CONFLICT, 'Media masih digunakan oleh versi soal atau snapshot ujian historis.');
            }

            $this->auditService->log(
                'DELETE_MEDIA',
                'MediaAsset',
                $lockedMedia->id,
                ['name' => $lockedMedia->original_name],
                null
            );

            $lockedMedia->delete();
        });

        // The database reference is soft-deleted first. A failed physical delete
        // leaves an orphan for a later cleanup job, never a broken historical snapshot.
        $deleted = Storage::disk($storageDisk)->delete($storagePath);

        return response()->json([
            'success' => true,
            'file_deleted' => $deleted,
        ]);
    }

    /**
     * Media uploaded by this API is stored as one flat file under the tenant's
     * private media directory. Reject traversal, alternate separators and
     * unexpected nested paths before any filesystem read/delete operation.
     */
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

    private function isReferencedByOwnActiveAttempt(MediaAsset $media, string $userId, string $tenantId): bool
    {
        $now = now();

        return ExamQuestionSnapshot::query()
            ->where('exam_question_snapshots.tenant_id', $tenantId)
            ->whereJsonContains('snapshot_media', ['id' => $media->id])
            ->join('exams', function ($join) use ($tenantId) {
                $join->on('exams.id', '=', 'exam_question_snapshots.exam_id')
                    ->where('exams.tenant_id', '=', $tenantId)
                    ->whereNull('exams.deleted_at');
            })
            ->join('exam_attempts', function ($join) use ($tenantId) {
                $join->on('exam_attempts.exam_id', '=', 'exams.id')
                    ->where('exam_attempts.tenant_id', '=', $tenantId)
                    ->where('exam_attempts.status', '=', 'IN_PROGRESS');
            })
            ->join('exam_participants', function ($join) use ($tenantId, $userId) {
                $join->on('exam_participants.id', '=', 'exam_attempts.participant_id')
                    ->where('exam_participants.tenant_id', '=', $tenantId)
                    ->where('exam_participants.user_id', '=', $userId);
            })
            ->whereNotNull('exams.locked_at')
            ->where('exams.start_at', '<=', $now)
            ->where('exams.end_at', '>=', $now)
            ->whereNotIn('exams.status', ['CLOSED', 'ARCHIVED'])
            ->exists();
    }
}
