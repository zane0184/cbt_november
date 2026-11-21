<?php

namespace App\Services\Licensing;

use RuntimeException;

class TrustedKeyStore
{
    private function path(): string { return storage_path('app/private/publisher-license-trust.json'); }

    public function store(string $keyId, string $publicKey): void
    {
        $directory = dirname($this->path());
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) throw new RuntimeException('TRUST_STORE_DIRECTORY_FAILED');
        $path = $this->path();
        if (is_file($path)) {
            $existing = $this->read();
            if (($existing['key_id'] ?? null) !== $keyId || ($existing['public_key'] ?? null) !== $publicKey) throw new RuntimeException('TRUST_ANCHOR_CHANGE_REQUIRES_ROTATION');
            return;
        }
        $content = json_encode(['purpose' => 'LICENSE', 'algorithm' => 'Ed25519', 'key_id' => $keyId, 'public_key' => $publicKey, 'pinned_at' => now()->toIso8601String()], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
        $temporary = tempnam($directory, '.license-trust-');
        if ($temporary === false || file_put_contents($temporary, $content, LOCK_EX) === false) throw new RuntimeException('TRUST_STORE_WRITE_FAILED');
        @chmod($temporary, 0600);
        if (! rename($temporary, $path)) { @unlink($temporary); throw new RuntimeException('TRUST_STORE_COMMIT_FAILED'); }
        @chmod($path, 0600);
    }

    public function read(): array
    {
        $path = $this->path();
        if (! is_file($path) || is_link($path)) throw new RuntimeException('TRUST_ANCHOR_MISSING');
        $data = json_decode((string) file_get_contents($path), true, 16, JSON_THROW_ON_ERROR);
        if (! is_array($data) || ($data['purpose'] ?? null) !== 'LICENSE' || ($data['algorithm'] ?? null) !== 'Ed25519' || ! is_string($data['key_id'] ?? null) || ! is_string($data['public_key'] ?? null)) throw new RuntimeException('TRUST_ANCHOR_INVALID');
        return $data;
    }
}
