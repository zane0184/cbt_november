<?php

namespace App\Services;

use App\Contexts\TenantContext;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use PDO;
use RuntimeException;
use Throwable;

class InstallerService
{
    public function __construct(
        protected InstallerStateStore $stateStore,
        protected RolePermissionBootstrapper $permissionBootstrapper
    ) {
    }

    public function isInstalled(): bool
    {
        if (File::exists(storage_path('installed.lock')) || config('app.installed') === true) {
            return true;
        }

        try {
            return $this->stateStore->read()['state'] === 'LOCKED';
        } catch (Throwable $exception) {
            // A corrupt state file must never reopen the installer.
            Log::critical('CBT installer state is unreadable; installer access is blocked.');
            return true;
        }
    }

    public function checkRequirements(): array
    {
        $minPhpVersion = '8.3.0';
        $currentPhpVersion = PHP_VERSION;
        $phpSatisfied = version_compare($currentPhpVersion, $minPhpVersion, '>=');

        // Laravel 13 runtime requirements plus extensions used by this CBT application.
        // Keep this gate aligned with the documented deployment/runtime contract.
        $requiredExtensions = [
            'bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'filter', 'gd', 'hash',
            'json', 'mbstring', 'openssl', 'pcre', 'pdo', 'pdo_mysql', 'session',
            'tokenizer', 'xml', 'xmlwriter', 'zip', 'sodium',
        ];

        $extensionResults = [];
        $allExtensionsSatisfied = true;
        foreach ($requiredExtensions as $extension) {
            $loaded = extension_loaded($extension);
            $extensionResults[$extension] = $loaded;
            $allExtensionsSatisfied = $allExtensionsSatisfied && $loaded;
        }

        $directories = [
            'storage' => storage_path(),
            'storage/framework' => storage_path('framework'),
            'storage/logs' => storage_path('logs'),
            'storage/app/private' => storage_path('app/private'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        $permissionResults = [];
        $allPermissionsSatisfied = true;
        foreach ($directories as $name => $path) {
            $writable = is_dir($path) ? is_writable($path) : is_writable(dirname($path));
            $permissionResults[$name] = ['path' => $path, 'writable' => $writable];
            $allPermissionsSatisfied = $allPermissionsSatisfied && $writable;
        }

        $isHttps = request()->secure();
        $appKeyConfigured = is_string(config('app.key')) && strlen((string) config('app.key')) >= 32;
        $isReady = $phpSatisfied && $allExtensionsSatisfied && $allPermissionsSatisfied && $isHttps && $appKeyConfigured;

        return [
            'is_ready' => $isReady,
            'php' => ['current' => $currentPhpVersion, 'required' => $minPhpVersion, 'satisfied' => $phpSatisfied],
            'extensions' => ['list' => $extensionResults, 'satisfied' => $allExtensionsSatisfied],
            'permissions' => ['list' => $permissionResults, 'satisfied' => $allPermissionsSatisfied],
            'app_key' => ['configured' => $appKeyConfigured],
            'environment' => [
                'https' => $isHttps,
                'upload_max' => ini_get('upload_max_filesize'),
                'post_max' => ini_get('post_max_size'),
                'memory_limit' => ini_get('memory_limit'),
            ],
        ];
    }

    /** Connection check is read-only and never stores database credentials in installer state. */
    public function testDatabaseConnection(array $params): array
    {
        $inspection = $this->inspectDatabase($params);
        if (!$inspection['success']) {
            return $inspection;
        }

        return [
            'success' => true,
            'message' => $inspection['is_empty']
                ? 'Koneksi database berhasil; database target terdeteksi kosong.'
                : 'Koneksi berhasil, tetapi database target sudah memiliki objek database dan tidak aman untuk instalasi baru.',
            'version' => $inspection['version'],
            'engine' => $inspection['engine'] ?? null,
            'database_empty' => $inspection['is_empty'],
            'object_count' => count($inspection['objects']),
        ];
    }

    /** Fail-closed database inspection: any table/view or inspection error is unsafe. */
    public function inspectDatabase(array $params): array
    {
        try {
            $normalized = $this->normalizeDatabaseParams($params);
            $pdo = $this->connectPdo($normalized);
            $version = (string) ($pdo->query('SELECT VERSION()')->fetchColumn() ?: 'unknown');
            $numericVersion = preg_match('/(\d+\.\d+(?:\.\d+)?)/', $version, $versionMatch) ? $versionMatch[1] : '0.0.0';
            if (stripos($version, 'mariadb') !== false || version_compare($numericVersion, '8.0.0', '<')) {
                return [
                    'success' => false,
                    'is_empty' => false,
                    'objects' => [],
                    'version' => $version,
                    'engine' => null,
                    'fingerprint' => null,
                    'message' => 'CBT Madrasah mensyaratkan MySQL 8.0+; server database yang terdeteksi tidak memenuhi persyaratan.',
                ];
            }
            $engine = strtolower((string) $pdo->query('SELECT @@default_storage_engine')->fetchColumn());
            if ($engine !== 'innodb') {
                return [
                    'success' => false,
                    'is_empty' => false,
                    'objects' => [],
                    'version' => $version,
                    'engine' => $engine,
                    'fingerprint' => null,
                    'message' => 'Storage engine default database harus InnoDB. Instalasi diblokir sampai konfigurasi MySQL diperbaiki.',
                ];
            }
            $objects = $pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM);
            $names = array_values(array_map(static fn (array $row) => (string) $row[0], $objects));
            sort($names, SORT_STRING);

            return [
                'success' => true,
                'is_empty' => $names === [],
                'objects' => $names,
                'version' => $version,
                'engine' => $engine,
                'fingerprint' => $this->databaseFingerprint($normalized),
            ];
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'is_empty' => false,
                'objects' => [],
                'version' => null,
                'engine' => null,
                'fingerprint' => null,
                'message' => 'Database tidak dapat diverifikasi. Periksa koneksi dan hak inspeksi database; instalasi ditolak demi keamanan.',
            ];
        }
    }

    public function inspectCurrentDatabase(): array
    {
        $connection = config('database.connections.mysql', []);
        $inspection = $this->inspectDatabase([
            'host' => $connection['host'] ?? '',
            'port' => $connection['port'] ?? 3306,
            'database' => $connection['database'] ?? '',
            'username' => $connection['username'] ?? '',
            'password' => $connection['password'] ?? '',
        ]);

        $state = $this->safeReadState();
        $stateMatches = $state !== null
            && $state['state'] === 'DB_CONFIG_VERIFIED'
            && hash_equals((string) ($state['db_fingerprint'] ?? ''), (string) ($inspection['fingerprint'] ?? ''));

        return [
            'success' => $inspection['success'],
            'has_tables' => !$inspection['success'] || !$inspection['is_empty'],
            'safe_to_migrate' => $inspection['success'] && $inspection['is_empty'] && $stateMatches,
            'state' => $state['state'] ?? 'UNKNOWN',
            'message' => $inspection['message'] ?? ($stateMatches
                ? 'Database kosong dan konfigurasi terverifikasi.'
                : 'Konfigurasi database belum diverifikasi pada sesi installer ini.'),
        ];
    }

    /** Backward-compatible fail-closed predicate used by older views/tests. */
    public function checkExistingTables(array $params = []): bool
    {
        if ($params === []) {
            $connection = config('database.connections.mysql', []);
            $params = [
                'host' => $connection['host'] ?? '',
                'port' => $connection['port'] ?? 3306,
                'database' => $connection['database'] ?? '',
                'username' => $connection['username'] ?? '',
                'password' => $connection['password'] ?? '',
            ];
        }

        $inspection = $this->inspectDatabase($params);
        return !$inspection['success'] || !$inspection['is_empty'];
    }

    /** Save a verified empty MySQL target into private runtime storage using atomic replacement. */
    public function saveDatabaseConfig(array $params): array
    {
        if (!$this->checkRequirements()['is_ready']) {
            return ['success' => false, 'message' => 'Persyaratan server belum terpenuhi. Selesaikan pemeriksaan PHP, ekstensi, dan permission sebelum konfigurasi database.'];
        }

        $inspection = $this->inspectDatabase($params);
        if (!$inspection['success']) {
            return ['success' => false, 'message' => $inspection['message']];
        }
        if (!$inspection['is_empty']) {
            return [
                'success' => false,
                'message' => 'Database target tidak kosong. Installer hanya menerima database baru yang benar-benar kosong; tidak ada tabel yang akan dihapus.',
            ];
        }

        try {
            $normalized = $this->normalizeDatabaseParams($params);
            return $this->stateStore->synchronized(function (array $state) use ($normalized, $inspection): array {
                if (!in_array($state['state'], ['UNINITIALIZED', 'DB_CONFIG_VERIFIED'], true)) {
                    return ['success' => false, 'message' => 'Konfigurasi database tidak dapat diubah setelah migrasi dimulai.'];
                }

                $this->writeDatabaseEnvironment($normalized);
                config([
                    'database.default' => 'mysql',
                    'database.connections.mysql.host' => $normalized['host'],
                    'database.connections.mysql.port' => $normalized['port'],
                    'database.connections.mysql.database' => $normalized['database'],
                    'database.connections.mysql.username' => $normalized['username'],
                    'database.connections.mysql.password' => $normalized['password'],
                ]);
                DB::purge('mysql');

                $this->stateStore->write([
                    'state' => 'DB_CONFIG_VERIFIED',
                    'db_fingerprint' => $inspection['fingerprint'],
                    'database' => $normalized['database'],
                    'host' => $normalized['host'],
                    'port' => $normalized['port'],
                    'username' => $normalized['username'],
                ]);

                return ['success' => true, 'message' => 'Konfigurasi database disimpan dan target kosong terverifikasi.'];
            });
        } catch (Throwable $exception) {
            Log::error('CBT installer failed to save database configuration.', ['exception' => get_class($exception)]);
            return ['success' => false, 'message' => 'Konfigurasi database gagal disimpan secara aman. Periksa izin storage/app/private dan bootstrap/cache.'];
        }
    }

    /** Run migrations once, under an exclusive lock, and verify the migration ledger. */
    public function runMigrations(): array
    {
        if (!$this->checkRequirements()['is_ready']) {
            return ['success' => false, 'message' => 'Persyaratan server belum terpenuhi. Migrasi diblokir.'];
        }

        try {
            return $this->stateStore->synchronized(function (array $state): array {
                if ($state['state'] !== 'DB_CONFIG_VERIFIED') {
                    return ['success' => false, 'message' => 'Tahap konfigurasi database belum terverifikasi atau migrasi sebelumnya gagal. Periksa checkpoint installer.'];
                }

                $params = $this->currentDatabaseParams();
                $fingerprint = $this->databaseFingerprint($params);
                if (!hash_equals((string) ($state['db_fingerprint'] ?? ''), $fingerprint)) {
                    return ['success' => false, 'message' => 'Konfigurasi database berubah setelah verifikasi. Ulangi verifikasi database sebelum migrasi.'];
                }

                $inspection = $this->inspectDatabase($params);
                if (!$inspection['success'] || !$inspection['is_empty']) {
                    return ['success' => false, 'message' => 'Database target tidak kosong atau tidak dapat diperiksa. Migrasi diblokir; tidak ada tabel yang dihapus.'];
                }

                $this->stateStore->write(array_merge($state, ['state' => 'SCHEMA_MIGRATING', 'last_error_code' => null]));
                $exitCode = null;
                try {
                    $exitCode = Artisan::call('migrate', ['--force' => true]);
                    $output = Artisan::output();
                    if ($exitCode !== 0) {
                        throw new RuntimeException('MIGRATION_EXIT_' . $exitCode);
                    }

                    $migrationCheck = $this->verifyMigrationLedger();
                    if (!$migrationCheck['success']) {
                        throw new RuntimeException('MIGRATION_LEDGER_INCOMPLETE');
                    }

                    $schemaFingerprint = $this->schemaFingerprint();
                    $this->stateStore->write(array_merge($state, [
                        'state' => 'SCHEMA_READY',
                        'migration_count' => $migrationCheck['applied_count'],
                        'schema_fingerprint' => $schemaFingerprint,
                        'last_error_code' => null,
                    ]));

                    return [
                        'success' => true,
                        'message' => 'Migrasi selesai dan ledger seluruh migration telah diverifikasi.',
                        'migration_count' => $migrationCheck['applied_count'],
                    ];
                } catch (Throwable $exception) {
                    $code = $exception instanceof RuntimeException ? $exception->getMessage() : 'MIGRATION_EXCEPTION';
                    $this->stateStore->write(array_merge($state, [
                        'state' => 'SCHEMA_FAILED',
                        'last_valid_state' => 'DB_CONFIG_VERIFIED',
                        'last_error_code' => $code,
                        'last_error_at' => date(DATE_ATOM),
                    ]));
                    Log::error('CBT installer migration failed; manual recovery required.', [
                        'exception' => get_class($exception),
                        'exit_code' => $exitCode,
                    ]);
                    return ['success' => false, 'message' => 'Migrasi gagal atau tidak lengkap. Database mungkin berada pada kondisi parsial; installer tidak akan mengulang atau menghapus tabel otomatis. Lakukan pemeriksaan dan recovery terisolasi.'];
                }
            });
        } catch (Throwable $exception) {
            Log::error('CBT installer migration lock failed.', ['exception' => get_class($exception)]);
            return ['success' => false, 'message' => 'Proses migrasi tidak dapat memperoleh penguncian installer. Coba kembali setelah memastikan tidak ada proses instalasi lain.'];
        }
    }

    /** Create a new tenant and a new Admin Madrasah account only.
     *
     * If the database transaction committed but the filesystem checkpoint did not,
     * a retry may reconcile the exact tenant/admin pair created by the prior attempt.
     * Any partial or mismatched pair fails closed and is never modified.
     */
    public function createInitialDataAndAdmin(array $data): array
    {
        $madrasahName = trim((string) ($data['madrasah_name'] ?? ''));
        $requestedCode = strtoupper(trim((string) ($data['madrasah_code'] ?? '')));
        $adminName = trim((string) ($data['admin_name'] ?? ''));
        $adminUsername = strtolower(trim((string) ($data['admin_username'] ?? '')));
        $adminEmail = strtolower(trim((string) ($data['admin_email'] ?? '')));
        $adminPassword = (string) ($data['admin_password'] ?? '');

        if ($madrasahName === '' || $adminName === '' || $adminUsername === '' || $adminPassword === '') {
            return ['success' => false, 'message' => 'Nama madrasah, nama Admin, username, dan password wajib diisi.'];
        }
        if (strlen($madrasahName) > 255 || strlen($adminName) > 255
            || ($requestedCode !== '' && ! preg_match('/^[A-Z0-9_-]{1,50}$/', $requestedCode))
            || ! preg_match('/^[a-z0-9._-]{3,80}$/', $adminUsername)
            || ($adminEmail !== '' && ! filter_var($adminEmail, FILTER_VALIDATE_EMAIL))
            || strlen($adminPassword) < 14) {
            return ['success' => false, 'message' => 'Format nama, username, email, atau panjang password tidak memenuhi ketentuan.'];
        }

        try {
            return $this->stateStore->synchronized(function (array $state) use ($madrasahName, $requestedCode, $adminName, $adminUsername, $adminEmail, $adminPassword): array {
                if (! in_array($state['state'], ['SCHEMA_READY', 'TENANT_CREATED'], true)) {
                    return ['success' => false, 'message' => 'Database belum mencapai tahap SCHEMA_READY. Pembuatan tenant dan Admin Madrasah diblokir.'];
                }
                if (! hash_equals((string) ($state['db_fingerprint'] ?? ''), $this->databaseFingerprint($this->currentDatabaseParams()))) {
                    return ['success' => false, 'message' => 'Database aktif berbeda dari database yang telah dimigrasikan.'];
                }
                $migrationCheck = $this->verifyMigrationLedger();
                if (! $migrationCheck['success']) {
                    return ['success' => false, 'message' => 'Migration ledger tidak lengkap. Pembuatan tenant/admin diblokir.'];
                }

                $madrasahCode = (string) ($state['tenant_code'] ?? $state['pending_tenant_code'] ?? $requestedCode);
                if ($madrasahCode === '') $madrasahCode = 'MD'.strtoupper(substr(bin2hex(random_bytes(8)), 0, 10));
                $madrasahCode = strtoupper($madrasahCode);
                if (! preg_match('/^[A-Z0-9_-]{1,50}$/', $madrasahCode)) return ['success' => false, 'message' => 'Kode instalasi internal tidak valid.'];

                foreach ([
                    'pending_tenant_code' => $madrasahCode,
                    'pending_madrasah_name' => $madrasahName,
                    'pending_admin_name' => $adminName,
                    'pending_admin_username' => $adminUsername,
                    'pending_admin_email' => $adminEmail,
                ] as $key => $value) {
                    if (isset($state[$key]) && (string) $state[$key] !== (string) $value) {
                        return ['success' => false, 'message' => 'Data installer berbeda dari percobaan yang belum selesai. Gunakan data yang sama atau lakukan audit pemulihan manual.'];
                    }
                }
                $pendingState = array_merge($state, [
                    'pending_tenant_code' => $madrasahCode,
                    'pending_madrasah_name' => $madrasahName,
                    'pending_admin_name' => $adminName,
                    'pending_admin_username' => $adminUsername,
                    'pending_admin_email' => $adminEmail,
                ]);
                $this->stateStore->write($pendingState);

                $tenantId = null;
                $adminId = null;
                $recovered = false;

                try {
                    DB::transaction(function () use ($pendingState, $madrasahName, $madrasahCode, $adminName, $adminUsername, $adminEmail, $adminPassword, &$tenantId, &$adminId, &$recovered): void {
                        $existingTenant = Tenant::withoutGlobalScopes()->where('code', $madrasahCode)->lockForUpdate()->first();
                        $existingUserQuery = User::withoutGlobalScopes()->where('username', $adminUsername)->lockForUpdate();
                        $existingUser = $existingUserQuery->first();
                        $existingEmailUser = $adminEmail !== ''
                            ? User::withoutGlobalScopes()->where('email', $adminEmail)->lockForUpdate()->first()
                            : null;

                        if ($existingTenant || $existingUser || $existingEmailUser) {
                            if (! $existingTenant || ! $existingUser || ($existingEmailUser && (string) $existingEmailUser->id !== (string) $existingUser->id)
                                || $existingTenant->name !== $madrasahName || $existingUser->name !== $adminName
                                || strtolower((string) $existingUser->username) !== $adminUsername
                                || ($adminEmail !== '' && strtolower((string) $existingUser->email) !== $adminEmail)
                                || (string) $existingUser->tenant_id !== (string) $existingTenant->id
                                || ! Hash::check($adminPassword, (string) $existingUser->password)) {
                                throw new RuntimeException('INSTALL_RECOVERY_CONFLICT');
                            }
                            if (($pendingState['state'] ?? null) === 'TENANT_CREATED'
                                && ((string) ($pendingState['tenant_id'] ?? '') !== (string) $existingTenant->id
                                    || ($pendingState['tenant_code'] ?? null) !== $madrasahCode)) throw new RuntimeException('INSTALL_RECOVERY_CONFLICT');
                            $existingRoleNames = $existingUser->roles()->pluck('name')->sort()->values()->all();
                            if ($existingRoleNames !== ['ADMIN_MADRASAH']) throw new RuntimeException('INSTALL_RECOVERY_CONFLICT');
                            $tenantId = $existingTenant->id; $adminId = $existingUser->id; $recovered = true;
                            return;
                        }

                        if (($pendingState['state'] ?? null) === 'TENANT_CREATED') throw new RuntimeException('INSTALL_RECOVERY_CONFLICT');
                        $tenant = Tenant::create(['name' => $madrasahName, 'code' => $madrasahCode]);
                        $tenantId = $tenant->id;
                        app(TenantContext::class)->setTenantId($tenant->id);
                        $roleDefinitions = [
                            'ADMIN_MADRASAH' => 'Admin Madrasah', 'OPERATOR' => 'Operator Madrasah',
                            'KAMAD' => 'Kepala Madrasah', 'GURU' => 'Guru / Pengajar', 'SISWA' => 'Siswa / Peserta Ujian',
                        ];
                        $roles = [];
                        foreach ($roleDefinitions as $name => $label) $roles[$name] = Role::firstOrCreate(['name' => $name], ['label' => $label]);
                        $this->permissionBootstrapper->bootstrap();
                        $admin = User::forceCreate([
                            'tenant_id' => $tenant->id, 'name' => $adminName, 'username' => $adminUsername,
                            'email' => $adminEmail !== '' ? $adminEmail : null, 'password' => Hash::make($adminPassword),
                        ]);
                        $admin->roles()->sync([$roles['ADMIN_MADRASAH']->id]);
                        $adminId = $admin->id;
                    });
                } finally {
                    app(TenantContext::class)->setTenantId(null);
                }

                $this->stateStore->write(array_merge($pendingState, [
                    'state' => 'ADMIN_CREATED', 'tenant_id' => $tenantId, 'tenant_code' => $madrasahCode,
                    'admin_id' => $adminId, 'admin_username' => $adminUsername,
                    'last_error_code' => null, 'last_error_at' => null,
                ]));

                return [
                    'success' => true,
                    'message' => $recovered ? 'Data tenant dan Admin Madrasah dari transaksi sebelumnya berhasil diverifikasi.' : 'Profil madrasah dan akun Admin Madrasah berhasil dibuat.',
                    'admin_id' => $adminId, 'tenant_id' => $tenantId, 'recovered' => $recovered,
                ];
            });
        } catch (Throwable $exception) {
            $message = match ($exception instanceof RuntimeException ? $exception->getMessage() : '') {
                'INSTALL_RECOVERY_CONFLICT' => 'Data tenant/admin tidak cocok atau tidak lengkap. Installer dihentikan; tidak ada data existing yang diubah. Audit database diperlukan.',
                default => 'Gagal membuat tenant dan Admin Madrasah. Tidak ada akun existing yang akan diperbarui.',
            };
            Log::error('CBT installer tenant/admin creation failed.', ['exception' => get_class($exception)]);
            return ['success' => false, 'message' => $message];
        }
    }

    public function currentState(): string
    {
        try {
            return $this->stateStore->read()['state'];
        } catch (Throwable $exception) {
            return 'UNKNOWN';
        }
    }

    public function completionStatus(): array
    {
        try {
            $state = $this->stateStore->read();
            return [
                'state' => $state['state'],
                'can_finalize' => in_array($state['state'], ['LICENSE_ACTIVATED', 'INSTALL_VERIFIED'], true) || (config('license.pre_activation_2026') && $state['state'] === 'PRE_ACTIVATION_READY'),
                'message' => in_array($state['state'], ['LICENSE_ACTIVATED', 'INSTALL_VERIFIED'], true) || (config('license.pre_activation_2026') && $state['state'] === 'PRE_ACTIVATION_READY')
                    ? 'Data instalasi telah dibuat. Lakukan verifikasi akhir dan penguncian installer.'
                    : 'Instalasi belum memenuhi seluruh prasyarat finalisasi.',
            ];
        } catch (Throwable $exception) {
            return ['state' => 'UNKNOWN', 'can_finalize' => false, 'message' => 'Status instalasi tidak dapat diverifikasi.'];
        }
    }

    /** Finalize only after postconditions have been verified. */
    public function lockInstaller(): bool
    {
        try {
            return $this->stateStore->synchronized(function (array $state): bool {
                $isPreActivation = config('license.pre_activation_2026')
                    && ($state['license_mode'] ?? null) === 'PRE_ACTIVATION_2026';
                $allowedStates = $isPreActivation
                    ? ['PRE_ACTIVATION_READY', 'INSTALL_VERIFIED']
                    : ['LICENSE_ACTIVATED', 'INSTALL_VERIFIED'];
                if (!in_array($state['state'], $allowedStates, true)) {
                    return false;
                }

                $verification = $this->verifyInstallationPostconditions($state);
                if (!$verification['success']) {
                    return false;
                }

                $verifiedState = array_merge($state, [
                    'state' => 'INSTALL_VERIFIED',
                    'migration_count' => $verification['migration_count'],
                    'schema_fingerprint' => $verification['schema_fingerprint'],
                ]);
                $this->stateStore->write($verifiedState);

                $runtimeConfigPath = storage_path('app/private/installer-runtime.php');
                $runtimeConfigExisted = is_file($runtimeConfigPath);
                $originalRuntimeConfig = $runtimeConfigExisted ? file_get_contents($runtimeConfigPath) : null;
                $originalBootstrapToken = config('installer.bootstrap_token');
                $lockPath = storage_path('installed.lock');
                $lockContent = json_encode([
                    'installed_at' => date(DATE_ATOM),
                    'version' => config('app.version', '1.0.0'),
                    'tenant_id' => $state['tenant_id'],
                    'admin_id' => $state['admin_id'],
                    'installation_identity_id' => $state['installation_identity_id'] ?? null,
                    'license_id' => $state['license_id'] ?? null,
                    'license_revision' => $state['license_revision'] ?? null,
                    'license_mode' => $isPreActivation ? 'PRE_ACTIVATION_2026' : 'PUBLISHER',
                    'pre_activation_issued_at' => $isPreActivation ? ($state['pre_activation_issued_at'] ?? null) : null,
                    'pre_activation_expires_at' => $isPreActivation ? ($state['pre_activation_expires_at'] ?? null) : null,
                    'schema_fingerprint' => $verification['schema_fingerprint'],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;

                try {
                    // Disable the bootstrap token in a private writable runtime override; .env remains read-only.
                    $runtimeConfig = ['bootstrap_token' => null];
                    $runtimeContents = "<?php\\n\\nreturn " . var_export($runtimeConfig, true) . ";\\n";
                    $this->writeAtomicFile($runtimeConfigPath, $runtimeContents, 0600);
                    config(['installer.bootstrap_token' => null]);
                    $configClearExit = Artisan::call('config:clear');
                    if ($configClearExit !== 0) {
                        throw new RuntimeException('INSTALLER_CONFIG_CACHE_CLEAR_FAILED');
                    }
                    $this->writeAtomicFile($lockPath, $lockContent, 0600);
                    $lockedState = array_merge($verifiedState, ['state' => 'LOCKED']);
                    $this->stateStore->write($lockedState);
                    config(['app.installed' => true]);

                    if (($lockedState['license_mode'] ?? null) === 'PRE_ACTIVATION_2026') {
                        AuditLog::create([
                            'tenant_id' => $lockedState['tenant_id'],
                            'user_id' => $lockedState['admin_id'],
                            'action' => 'PRE_ACTIVATION_FINALIZED',
                            'entity_type' => 'installation',
                            'entity_id' => (string) $lockedState['installation_identity_id'],
                            'old_values' => ['state' => (string) ($state['state'] ?? 'PRE_ACTIVATION_READY')],
                            'new_values' => [
                                'state' => 'LOCKED',
                                'license_mode' => 'PRE_ACTIVATION_2026',
                                'schema_fingerprint' => $verification['schema_fingerprint'],
                                'pre_activation_issued_at' => $lockedState['pre_activation_issued_at'] ?? null,
                                'pre_activation_expires_at' => $lockedState['pre_activation_expires_at'] ?? null,
                                'migration_count' => $verification['migration_count'],
                            ],
                            'created_at' => now(),
                        ]);
                    }
                    return true;
                } catch (Throwable $exception) {
                    if (is_file($lockPath)) {
                        @unlink($lockPath);
                    }
                    // A failed audit write must not leave a half-finalized installation state.
                    try {
                        $this->stateStore->write($verifiedState);
                    } catch (Throwable) {
                        Log::critical('CBT installer state rollback failed after finalization error.');
                    }
                    if ($runtimeConfigExisted && $originalRuntimeConfig !== null) {
                        $this->writeAtomicFile($runtimeConfigPath, $originalRuntimeConfig, 0600);
                    } elseif (is_file($runtimeConfigPath)) {
                        @unlink($runtimeConfigPath);
                    }
                    try {
                        Artisan::call('config:clear');
                    } catch (Throwable) {
                        Log::warning('CBT installer config cache cleanup failed during finalization rollback.');
                    }
                    config([
                        'app.installed' => false,
                        'installer.bootstrap_token' => $originalBootstrapToken,
                    ]);
                    Log::error('CBT installer final lock failed; install remains unfinalized.', ['exception' => get_class($exception)]);
                    return false;
                }
            });
        } catch (Throwable $exception) {
            Log::error('CBT installer finalization lock failed.', ['exception' => get_class($exception)]);
            return false;
        }
    }

    public function verifyInstallationPostconditions(array $state): array
    {
        try {
            if (!hash_equals((string) ($state['db_fingerprint'] ?? ''), $this->databaseFingerprint($this->currentDatabaseParams()))) {
                return ['success' => false, 'message' => 'Database fingerprint mismatch.'];
            }

            $migrationCheck = $this->verifyMigrationLedger();
            if (!$migrationCheck['success']) {
                return ['success' => false, 'message' => 'Migration ledger incomplete.'];
            }

            if (empty(config('app.key'))) {
                return ['success' => false, 'message' => 'APP_KEY is not configured.'];
            }

            $tenant = Tenant::withoutGlobalScopes()->whereKey($state['tenant_id'] ?? null)->first();
            $admin = User::withoutGlobalScopes()->with('roles.permissions')->whereKey($state['admin_id'] ?? null)->first();
            if (!$tenant || !$admin || $admin->tenant_id !== $tenant->id || $tenant->code !== ($state['tenant_code'] ?? null) || $admin->username !== ($state['admin_username'] ?? null)) {
                return ['success' => false, 'message' => 'Tenant/admin postcondition failed.'];
            }

            $identity = \App\Models\LocalInstallationIdentity::withoutGlobalScopes()->where('tenant_id', $tenant->id)->first();
            if (!$identity || (string) $identity->tenant_id !== (string) $tenant->id) return ['success' => false, 'message' => 'Identitas instalasi lokal tidak valid.'];
            $isPreActivation = ($state['license_mode'] ?? null) === 'PRE_ACTIVATION_2026';
            if ($isPreActivation) {
                if (!config('license.pre_activation_2026')
                    || !in_array(($state['state'] ?? null), ['PRE_ACTIVATION_READY', 'INSTALL_VERIFIED'], true)) {
                    return ['success' => false, 'message' => 'Mode pra-aktivasi tidak diizinkan oleh konfigurasi server atau state installer.'];
                }
                if ((string) ($state['installation_identity_id'] ?? '') !== (string) $identity->getKey()) {
                    return ['success' => false, 'message' => 'ID identitas instalasi tidak cocok dengan state installer.'];
                }
                if (!empty($state['license_id']) || !empty($state['license_revision'])) {
                    return ['success' => false, 'message' => 'State pra-aktivasi tidak boleh memuat klaim lisensi aktif.'];
                }
                $issuedRaw = $state['pre_activation_issued_at'] ?? null;
                $expiresRaw = $state['pre_activation_expires_at'] ?? null;
                $deadlineRaw = config('license.pre_activation_issue_deadline');
                foreach ([$issuedRaw, $expiresRaw, $deadlineRaw] as $timestamp) {
                    if (!is_string($timestamp) || trim($timestamp) === '') {
                        return ['success' => false, 'message' => 'Tanggal penerbitan atau kedaluwarsa pilot tidak tersedia.'];
                    }
                }
                try {
                    $issuedAt = \Illuminate\Support\Carbon::parse($issuedRaw)->utc();
                    $expiresAt = \Illuminate\Support\Carbon::parse($expiresRaw)->utc();
                    $issueDeadline = \Illuminate\Support\Carbon::parse($deadlineRaw)->utc();
                } catch (Throwable) {
                    return ['success' => false, 'message' => 'Jadwal pra-aktivasi tidak valid.'];
                }
                $maxDays = max(1, (int) config('license.pre_activation_duration_days', 180));
                $now = \Illuminate\Support\Carbon::now('UTC');
                if ($issuedAt->greaterThan($issueDeadline)
                    || $issuedAt->greaterThan($now->copy()->addMinutes(5))
                    || $expiresAt->lessThanOrEqualTo($now)
                    || $expiresAt->lessThanOrEqualTo($issuedAt)
                    || $expiresAt->greaterThan($issuedAt->copy()->addDays($maxDays)->addMinutes(5))) {
                    return ['success' => false, 'message' => 'Masa berlaku pra-aktivasi tidak memenuhi batas pilot 2026.'];
                }
            } else {
                $licenseState = \App\Models\LocalLicenseState::query()->with('installation')->where('installation_identity_id', $identity->id)->first();
                if (!$licenseState || $licenseState->status !== 'ACTIVE' || (string) $licenseState->license_id !== (string) ($state['license_id'] ?? '') || $licenseState->lease_expires_at?->isPast()) return ['success' => false, 'message' => 'Lisensi Publisher belum aktif atau lease telah kedaluwarsa.'];
                app(\App\Services\Licensing\LicenseArtifactVerifier::class)->assertOperational($licenseState);
            }

            $roleNames = $admin->roles->pluck('name')->sort()->values()->all();
            if ($roleNames !== ['ADMIN_MADRASAH']) {
                return ['success' => false, 'message' => 'Admin role boundary failed.'];
            }

            if (!$admin->hasPermission('curriculum.view') || !$admin->hasPermission('curriculum.manage') || !$admin->hasPermission('academic.manage') || !$admin->hasPermission('user.manage')) {
                return ['success' => false, 'message' => 'Academic, curriculum, or user-management permission bootstrap is incomplete.'];
            }

            $kamadRole = Role::with('permissions')->where('name', 'KAMAD')->first();
            $guruRole = Role::with('permissions')->where('name', 'GURU')->first();
            if (!$kamadRole || !$guruRole
                || !$kamadRole->permissions->contains('name', 'curriculum.view')
                || !$guruRole->permissions->contains('name', 'curriculum.view')
                || $kamadRole->permissions->contains('name', 'curriculum.manage')
                || $guruRole->permissions->contains('name', 'curriculum.manage')) {
                return ['success' => false, 'message' => 'Curriculum role permission boundary failed.'];
            }

            return [
                'success' => true,
                'migration_count' => $migrationCheck['applied_count'],
                'schema_fingerprint' => $this->schemaFingerprint(),
            ];
        } catch (Throwable $exception) {
            Log::error('CBT installer postcondition verification failed.', ['exception' => get_class($exception)]);
            return ['success' => false, 'message' => 'Postcondition instalasi tidak dapat diverifikasi.'];
        }
    }

    private function verifyMigrationLedger(): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('migrations')) {
                return ['success' => false, 'applied_count' => 0, 'missing' => []];
            }

            $expected = array_map(
                static fn ($file) => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                File::allFiles(database_path('migrations'))
            );
            $applied = DB::table('migrations')->pluck('migration')->all();
            $missing = array_values(array_diff($expected, $applied));
            $unexpected = array_values(array_diff($applied, $expected));

            return [
                'success' => $expected !== [] && $missing === [] && $unexpected === [] && count($applied) === count($expected),
                'applied_count' => count($applied),
                'missing' => $missing,
                'unexpected' => $unexpected,
            ];
        } catch (Throwable $exception) {
            return ['success' => false, 'applied_count' => 0, 'missing' => ['LEDGER_UNREADABLE']];
        }
    }

    private function schemaFingerprint(): string
    {
        $connection = $this->currentDatabaseParams();
        $pdo = $this->connectPdo($connection);
        $objects = $pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM);
        $names = array_values(array_map(static fn (array $row) => (string) $row[0], $objects));
        sort($names, SORT_STRING);
        return hash('sha256', json_encode($names, JSON_THROW_ON_ERROR));
    }

    private function currentDatabaseParams(): array
    {
        $connection = config('database.connections.mysql', []);
        return $this->normalizeDatabaseParams([
            'host' => $connection['host'] ?? '',
            'port' => $connection['port'] ?? 3306,
            'database' => $connection['database'] ?? '',
            'username' => $connection['username'] ?? '',
            'password' => $connection['password'] ?? '',
        ]);
    }

    private function normalizeDatabaseParams(array $params): array
    {
        $values = [
            'host' => trim((string) ($params['host'] ?? '127.0.0.1')),
            'port' => (int) ($params['port'] ?? 3306),
            'database' => trim((string) ($params['database'] ?? '')),
            'username' => (string) ($params['username'] ?? ''),
            'password' => (string) ($params['password'] ?? ''),
        ];

        foreach ($values as $value) {
            if (preg_match('/[\r\n\0]/', $value)) {
                throw new RuntimeException('Database parameter contains an invalid control character.');
            }
        }

        if ($values['host'] === '' || $values['database'] === '' || $values['username'] === '' || $values['port'] < 1 || $values['port'] > 65535) {
            throw new RuntimeException('Database parameters are incomplete.');
        }
        if (!preg_match('/^[A-Za-z0-9._:\[\]-]{1,255}$/', $values['host'])) {
            throw new RuntimeException('Database host contains unsupported characters.');
        }
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $values['database'])) {
            throw new RuntimeException('Database name contains unsupported characters.');
        }
        if (strlen($values['username']) > 128 || strlen($values['password']) > 1024) {
            throw new RuntimeException('Database credentials exceed the supported length.');
        }

        return $values;
    }

    private function connectPdo(array $params): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $params['host'], $params['port'], $params['database']);
        return new PDO($dsn, $params['username'], $params['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private function databaseFingerprint(array $params): string
    {
        return hash('sha256', json_encode([
            'host' => $params['host'],
            'port' => $params['port'],
            'database' => $params['database'],
            'username' => $params['username'],
        ], JSON_THROW_ON_ERROR));
    }

    private function writeDatabaseEnvironment(array $params): void
    {
        $privateDirectory = storage_path('app/private');
        if (!is_dir($privateDirectory) && !mkdir($privateDirectory, 0700, true) && !is_dir($privateDirectory)) {
            throw new RuntimeException('Private installer directory cannot be created.');
        }

        $configPath = $privateDirectory . '/installer-database.php';
        $backupDirectory = $privateDirectory . '/installer-backups';
        if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0700, true) && !is_dir($backupDirectory)) {
            throw new RuntimeException('Private installer backup directory cannot be created.');
        }

        // Preserve a prior installer config before replacing it. Credentials never enter .env or installer state.
        if (is_file($configPath)) {
            $backupPath = $backupDirectory . '/database-config-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.bak';
            if (!copy($configPath, $backupPath)) {
                throw new RuntimeException('Existing private database configuration backup failed.');
            }
            @chmod($backupPath, 0600);
        }

        $privateConfig = [
            'default' => 'mysql',
            'mysql' => [
                'driver' => 'mysql',
                'url' => null,
                'host' => $params['host'],
                'port' => (string) $params['port'],
                'database' => $params['database'],
                'username' => $params['username'],
                'password' => $params['password'],
            ],
        ];
        $contents = "<?php\n\nreturn " . var_export($privateConfig, true) . ";\n";
        $this->writeAtomicFile($configPath, $contents, 0600);

        // A cached config would otherwise hide the newly persisted private override on the next request.
        $configCache = base_path('bootstrap/cache/config.php');
        if (is_file($configCache)) {
            $cacheBackup = $backupDirectory . '/config-cache-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.bak';
            if (!copy($configCache, $cacheBackup)) {
                throw new RuntimeException('Existing configuration cache backup failed.');
            }
            @chmod($cacheBackup, 0600);
            if (Artisan::call('config:clear') !== 0) {
                throw new RuntimeException('CONFIG_CACHE_CLEAR_FAILED');
            }
        }

        // The APP_KEY is provisioned before authorization; never rewrite the read-only application root.
        if (!is_string(config('app.key')) || strlen((string) config('app.key')) < 32) {
            throw new RuntimeException('APP_KEY must be configured before saving database settings.');
        }

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => array_replace(config('database.connections.mysql', []), $privateConfig['mysql']),
        ]);
    }

    private function writeAtomicFile(string $path, string $contents, int $mode = 0600): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Target directory cannot be created.');
        }
        $temporary = tempnam($directory, '.cbt-install-');
        if ($temporary === false) {
            throw new RuntimeException('Temporary file cannot be created.');
        }
        try {
            if (file_put_contents($temporary, $contents, LOCK_EX) === false) {
                throw new RuntimeException('Temporary file write failed.');
            }
            @chmod($temporary, $mode);
            if (!rename($temporary, $path)) {
                throw new RuntimeException('Atomic file replacement failed.');
            }
            @chmod($path, $mode);
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    private function safeReadState(): ?array
    {
        try {
            return $this->stateStore->read();
        } catch (Throwable $exception) {
            return null;
        }
    }
}
