<?php

namespace App\Services\Reporting;

use App\Models\Tenant;

class ReportHeaderService
{
    /**
     * Resolve tenant logo into base64 Data URI if a valid image file exists,
     * otherwise return null to trigger clean text-based kop fallback.
     */
    public function getLogoDataUri(?Tenant $tenant): ?string
    {
        if (!$tenant || empty($tenant->logo_path)) {
            return null;
        }

        $logoPath = trim($tenant->logo_path);

        $candidates = [];

        if (file_exists($logoPath)) {
            $candidates[] = $logoPath;
        }

        $candidates[] = storage_path('app/public/' . ltrim($logoPath, '/'));
        $candidates[] = storage_path('app/' . ltrim($logoPath, '/'));
        $candidates[] = public_path(ltrim($logoPath, '/'));
        $candidates[] = public_path('storage/' . ltrim($logoPath, '/'));

        foreach ($candidates as $filePath) {
            if (file_exists($filePath) && is_file($filePath) && is_readable($filePath) && filesize($filePath) > 0) {
                try {
                    $content = @file_get_contents($filePath);
                    if ($content === false || strlen($content) === 0) {
                        continue;
                    }

                    $mime = @mime_content_type($filePath);
                    if (!$mime || !str_starts_with($mime, 'image/')) {
                        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                        $mime = match ($ext) {
                            'png' => 'image/png',
                            'jpg', 'jpeg' => 'image/jpeg',
                            'gif' => 'image/gif',
                            'svg' => 'image/svg+xml',
                            'webp' => 'image/webp',
                            default => null,
                        };
                    }

                    if ($mime) {
                        return 'data:' . $mime . ';base64,' . base64_encode($content);
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        return null;
    }
}
