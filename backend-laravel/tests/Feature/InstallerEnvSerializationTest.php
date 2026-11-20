<?php

namespace Tests\Feature;

use App\Services\InstallerService;
use Tests\TestCase;

class InstallerEnvSerializationTest extends TestCase
{
    public function test_database_credentials_are_atomically_persisted_outside_read_only_application_root(): void
    {
        $service = app(InstallerService::class);
        $method = new \ReflectionMethod($service, 'writeDatabaseEnvironment');
        $method->setAccessible(true);

        $privatePath = storage_path('app/private/installer-database.php');
        $privateExisted = is_file($privatePath);
        $originalPrivate = $privateExisted ? file_get_contents($privatePath) : null;
        $envPath = base_path('.env');
        $envExisted = is_file($envPath);
        $originalEnvHash = $envExisted ? hash_file('sha256', $envPath) : null;
        $password = 'secret $1 # "quoted" \\ slash';

        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);

        try {
            $method->invoke($service, [
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'cbt_installer_test',
                'username' => 'cbt_installer_test',
                'password' => $password,
            ]);

            $this->assertFileExists($privatePath);
            $this->assertSame(0600, fileperms($privatePath) & 0777);

            $stored = require $privatePath;
            $this->assertSame('mysql', $stored['default']);
            $this->assertSame($password, $stored['mysql']['password']);

            $loadedConfig = require config_path('database.php');
            $this->assertSame('mysql', $loadedConfig['default']);
            $this->assertSame('cbt_installer_test', $loadedConfig['connections']['mysql']['database']);
            $this->assertSame($password, $loadedConfig['connections']['mysql']['password']);

            if ($envExisted) {
                $this->assertSame($originalEnvHash, hash_file('sha256', $envPath), '.env must remain unchanged and read-only.');
                $this->assertStringNotContainsString($password, (string) file_get_contents($envPath));
            }
        } finally {
            if ($privateExisted && $originalPrivate !== null) {
                file_put_contents($privatePath, $originalPrivate, LOCK_EX);
                @chmod($privatePath, 0600);
            } elseif (is_file($privatePath)) {
                @unlink($privatePath);
            }
        }
    }
    public function test_finalization_runtime_override_disables_bootstrap_token_without_env_write(): void
    {
        $runtimePath = storage_path('app/private/installer-runtime.php');
        $runtimeExisted = is_file($runtimePath);
        $originalRuntime = $runtimeExisted ? file_get_contents($runtimePath) : null;

        try {
            if (!is_dir(dirname($runtimePath))) {
                mkdir(dirname($runtimePath), 0700, true);
            }
            $runtime = ['bootstrap_token' => null];
            file_put_contents($runtimePath, "<?php\\n\\nreturn " . var_export($runtime, true) . ";\\n", LOCK_EX);
            @chmod($runtimePath, 0600);

            $loadedConfig = require config_path('installer.php');
            $this->assertArrayHasKey('bootstrap_token', $loadedConfig);
            $this->assertNull($loadedConfig['bootstrap_token']);
        } finally {
            if ($runtimeExisted && $originalRuntime !== null) {
                file_put_contents($runtimePath, $originalRuntime, LOCK_EX);
                @chmod($runtimePath, 0600);
            } elseif (is_file($runtimePath)) {
                @unlink($runtimePath);
            }
        }
    }

}
