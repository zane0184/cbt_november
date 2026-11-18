<?php

namespace App\Contexts;

class TenantContext
{
    private ?string $tenantId = null;
    private static ?TenantContext $instance = null;

    public function __construct()
    {
        static::$instance = $this;
    }

    public static function getInstance(): ?TenantContext
    {
        return static::$instance;
    }

    public static function resetInstance(): void
    {
        static::$instance = null;
    }

    public function setTenantId(?string $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }
}
