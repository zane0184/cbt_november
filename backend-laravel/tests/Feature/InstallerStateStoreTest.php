<?php

namespace Tests\Feature;

use App\Services\InstallerStateStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class InstallerStateStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(InstallerStateStore::class)->resetForTesting();
        parent::tearDown();
    }

    public function test_installer_state_is_atomically_written_and_read_back(): void
    {
        $store = app(InstallerStateStore::class);
        $this->assertSame('UNINITIALIZED', $store->read()['state']);

        $store->synchronized(function () use ($store): void {
            $store->write([
                'state' => 'DB_CONFIG_VERIFIED',
                'database' => 'cbt_test',
                'db_fingerprint' => hash('sha256', 'test-fingerprint'),
            ]);
        });

        $state = $store->read();
        $this->assertSame('DB_CONFIG_VERIFIED', $state['state']);
        $this->assertSame('cbt_test', $state['database']);
        $this->assertNotNull($state['updated_at']);
        $this->assertFileExists($store->statePath());
    }

    public function test_corrupt_installer_state_throws_instead_of_resetting_to_uninitialized(): void
    {
        $store = app(InstallerStateStore::class);
        $directory = dirname($store->statePath());
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        file_put_contents($store->statePath(), '{invalid-json');

        $this->expectException(RuntimeException::class);
        $store->read();
    }
}
