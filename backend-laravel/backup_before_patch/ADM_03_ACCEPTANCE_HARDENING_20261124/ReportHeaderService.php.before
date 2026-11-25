<?php

namespace App\Services\Reporting;

use App\Models\Tenant;
use App\Services\TenantBrandingService;

class ReportHeaderService
{
    /**
     * Resolve the dedicated document logo (falling back to the app logo)
     * into a safe Data URI. Invalid/missing assets trigger text-only fallback.
     */
    public function getLogoDataUri(?Tenant $tenant): ?string
    {
        if (!$tenant) {
            return null;
        }

        $relativePath = $tenant->document_logo_path ?: $tenant->logo_path;
        $filePath = app(TenantBrandingService::class)->localPath($relativePath);
        if (!$filePath || !is_readable($filePath) || filesize($filePath) <= 0) {
            return null;
        }

        try {
            $content = file_get_contents($filePath);
            if ($content === false || $content === '') {
                return null;
            }

            $mime = mime_content_type($filePath);
            if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp', 'image/gif'], true)) {
                return null;
            }

            return 'data:' . $mime . ';base64,' . base64_encode($content);
        } catch (\Throwable) {
            return null;
        }
    }
}
