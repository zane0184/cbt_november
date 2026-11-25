<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Read-only inventory of legacy platform SUPER_ADMIN assignments in a CBT tenant DB.
 * This command never changes users, roles, pivots, tenants, or authentication state.
 */
class InventoryLegacySuperAdminCommand extends Command
{
    protected $signature = 'cbt:inventory-legacy-super-admin {--output= : Optional JSON filename under storage/app/private/security-reports}';

    protected $description = 'Create a read-only inventory of legacy SUPER_ADMIN accounts; does not modify database records.';

    public function handle(): int
    {
        try {
            $rows = DB::table('users')
                ->leftJoin('tenants', 'tenants.id', '=', 'users.tenant_id')
                ->where(function ($query): void {
                    $query->where('users.role', 'SUPER_ADMIN')
                        ->orWhereExists(function ($subquery): void {
                            $subquery->selectRaw('1')
                                ->from('role_user')
                                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                                ->whereColumn('role_user.user_id', 'users.id')
                                ->where('roles.name', 'SUPER_ADMIN');
                        });
                })
                ->orderBy('users.tenant_id')
                ->orderBy('users.id')
                ->get([
                    'users.id as user_id',
                    'users.tenant_id',
                    'users.name as user_name',
                    'users.email',
                    'users.role as legacy_role_column',
                    'users.deleted_at as user_deleted_at',
                    'tenants.code as tenant_code',
                    'tenants.name as tenant_name',
                    'tenants.deleted_at as tenant_deleted_at',
                ]);

            $userIds = $rows->pluck('user_id')->all();
            $roleMap = [];
            if ($userIds !== []) {
                $roleMap = DB::table('role_user')
                    ->join('roles', 'roles.id', '=', 'role_user.role_id')
                    ->whereIn('role_user.user_id', $userIds)
                    ->orderBy('roles.name')
                    ->get(['role_user.user_id', 'roles.name'])
                    ->groupBy('user_id')
                    ->map(fn ($items) => $items->pluck('name')->values()->all())
                    ->all();
            }

            $records = $rows->map(function ($row) use ($roleMap): array {
                $email = (string) ($row->email ?? '');
                $maskedEmail = $email === '' ? null : $this->maskEmail($email);
                return [
                    'user_id' => (string) $row->user_id,
                    'tenant_id' => (string) $row->tenant_id,
                    'tenant_code' => $row->tenant_code,
                    'tenant_name' => $row->tenant_name,
                    'user_name' => $row->user_name,
                    'email_masked' => $maskedEmail,
                    'email_sha256_prefix' => $email === '' ? null : substr(hash('sha256', strtolower($email)), 0, 16),
                    'legacy_role_column' => $row->legacy_role_column,
                    'assigned_roles' => $roleMap[$row->user_id] ?? [],
                    'matched_by_role_column' => $row->legacy_role_column === 'SUPER_ADMIN',
                    'matched_by_role_pivot' => in_array('SUPER_ADMIN', $roleMap[$row->user_id] ?? [], true),
                    'user_soft_deleted_at' => $row->user_deleted_at,
                    'tenant_soft_deleted_at' => $row->tenant_deleted_at,
                    'remediation_status' => 'REVIEW_REQUIRED_NO_AUTOMATIC_CHANGE',
                ];
            })->values()->all();

            $report = [
                'schema_version' => 1,
                'generated_at_utc' => now()->utc()->toIso8601String(),
                'mode' => 'READ_ONLY',
                'database_name' => (string) config('database.connections.mysql.database', ''),
                'matched_user_count' => count($records),
                'records' => $records,
                'safety' => [
                    'database_mutations' => false,
                    'role_changes' => false,
                    'user_deletions' => false,
                    'email_addresses_fully_disclosed' => false,
                    'owner_approval_required_before_remediation' => true,
                ],
            ];

            $this->info('Inventaris read-only akun SUPER_ADMIN legacy selesai.');
            $this->line('Jumlah akun yang cocok: ' . count($records));
            $this->line('Tidak ada akun, role, tenant, atau pivot yang diubah.');

            $output = (string) $this->option('output');
            if ($output !== '') {
                if (basename($output) !== $output || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,100}\.json$/', $output)) {
                    $this->error('Nama output tidak valid. Gunakan nama file JSON sederhana tanpa path.');
                    return self::INVALID;
                }

                $directory = storage_path('app/private/security-reports');
                File::ensureDirectoryExists($directory, 0700, true);
                $target = $directory . DIRECTORY_SEPARATOR . $output;
                if (File::exists($target)) {
                    $this->error('File laporan sudah ada. Gunakan nama baru agar laporan sebelumnya tidak tertimpa.');
                    return self::FAILURE;
                }
                $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                if (File::put($target, $json . PHP_EOL, true) === false) {
                    throw new RuntimeException('REPORT_WRITE_FAILED');
                }
                @chmod($target, 0600);
                $this->line('Laporan tersimpan di: ' . $target);
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Inventaris gagal. Tidak ada remediasi otomatis yang dijalankan.');
            return self::FAILURE;
        }
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return '[invalid-email-format]';
        }

        $local = $parts[0];
        $visible = '';
        if ($local !== '' && preg_match('/^./us', $local, $match) === 1) {
            $visible = $match[0];
        }
        return $visible . '***@' . $parts[1];
    }
}
