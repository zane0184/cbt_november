<?php

namespace App\\Services\\Reporting;

use App\\Models\\Tenant;
use App\\Services\\TenantBrandingService;

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

        $branding = app(TenantBrandingService::class);

        foreach (array_unique([$tenant->document_logo_path, $tenant->logo_path]) as $relativePath) {
            $filePath = $branding->localPath($relativePath);
            if (!$filePath || !is_readable($filePath) || filesize($filePath) <= 0) {
                continue;
            }

            try {
                $content = file_get_contents($filePath);
                if ($content === false || $content === '') {
                    continue;
                }

                $mime = mime_content_type($filePath);
                if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
                    continue;
                }

                if (@getimagesize($filePath) === false) {
                    continue;
                }

                return 'data:' . $mime . ';base64,' . base64_encode($content);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}
