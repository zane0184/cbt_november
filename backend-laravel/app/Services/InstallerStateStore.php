<?php

namespace App\Services;

use RuntimeException;

/**
 * File-backed, monotonic installer state for a single-server standalone install.
 * No database secret is ever written to this state file.
 */
class InstallerStateStore
{
    private const DEFAULT_STATE = [
        'state' => 'UNINITIALIZED',
        'updated_at' => null,
        'db_fingerprint' => null,
        'database' => null,
        'host' => null,
        'port' => null,
        'username' => null,
        'migration_count' => null,
        'schema_fingerprint' => null,
        'tenant_id' => null,
        'tenant_code' => null,
        'installation_identity_id' => null,
        'admin_id' => null,
        'last_error_code' => null,
        'last_error_at' => null,
    ];

    public function read(): array
    {
        $path = $this->statePath();
        if (!is_file($path)) {
            return self::DEFAULT_STATE;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Installer state cannot be read.');
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Installer state is invalid; recovery is required.', 0, $exception);
        }

        if (!is_array($decoded) || !isset($decoded['state']) || !is_string($decoded['state'])) {
            throw new RuntimeException('Installer state is incomplete; recovery is required.');
        }

        return array_merge(self::DEFAULT_STATE, $decoded);
    }

    /** Execute one installer step under an exclusive process lock. */
    public function synchronized(callable $callback): mixed
    {
        $directory = dirname($this->statePath());
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Installer state directory is not writable.');
        }

        $handle = fopen($this->lockPath(), 'c+');
        if ($handle === false) {
            throw new RuntimeException('Installer lock cannot be opened.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Installer is busy; try again later.');
            }

            return $callback($this->read());
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /** Atomic state replacement. Call from a synchronized installer step. */
    public function write(array $state): void
    {
        $directory = dirname($this->statePath());
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Installer state directory is not writable.');
        }

        $state = array_merge(self::DEFAULT_STATE, $state, [
            'updated_at' => date(DATE_ATOM),
        ]);
        $contents = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
        $temporary = tempnam($directory, '.installer-state-');
        if ($temporary === false) {
            throw new RuntimeException('Temporary installer state file cannot be created.');
        }

        try {
            if (file_put_contents($temporary, $contents, LOCK_EX) === false) {
                throw new RuntimeException('Installer state cannot be written.');
            }
            @chmod($temporary, 0600);
            if (!rename($temporary, $this->statePath())) {
                throw new RuntimeException('Installer state cannot be committed atomically.');
            }
            @chmod($this->statePath(), 0600);
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    public function statePath(): string
    {
        return storage_path('app/private/installer-state.json');
    }

    public function lockPath(): string
    {
        return storage_path('app/private/installer-state.lock');
    }

    public function resetForTesting(): void
    {
        foreach ([$this->statePath(), $this->lockPath()] as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
