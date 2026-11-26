<?php

namespace App\Services\Questions;

use Illuminate\Validation\ValidationException;
use ZipArchive;

final class DocxQuestionMediaExtractor
{
    private const MAX_IMAGE_BYTES = 2 * 1024 * 1024;
    private const MAX_TOTAL_IMAGE_BYTES = 8 * 1024 * 1024;
    private const MAX_IMAGES_PER_IMPORT = 20;
    private const MAX_IMAGE_PIXELS = 25000000;

    /**
     * Extract only relationship-bound raster images referenced by the parser.
     * No archive entry is extracted to the filesystem.
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function extractForQuestions(string $path, array $questions): array
    {
        if (!is_file($path) || filesize($path) < 1 || filesize($path) > 10 * 1024 * 1024) {
            throw ValidationException::withMessages(['docx' => 'File DOCX berubah atau tidak tersedia saat ekstraksi media.']);
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['docx' => 'Paket DOCX tidak dapat dibuka kembali untuk ekstraksi media.']);
        }

        $result = [];
        $totalBytes = 0;
        $totalImages = 0;
        // Shared stimulus images may appear in several question fragments.
        // Validate/count each source target once while retaining per-question references.
        $uniquePayloads = [];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        try {
            foreach ($questions as $question) {
                $sequence = (int) ($question['source_locator']['sequence'] ?? 0);
                if ($sequence < 1) {
                    continue;
                }

                $seenTargets = [];
                foreach (($question['source_fragments'] ?? []) as $fragment) {
                    if (($fragment['kind'] ?? null) !== 'IMAGE_REFERENCE') {
                        continue;
                    }

                    $target = str_replace('\\', '/', (string) ($fragment['target'] ?? ''));
                    if (empty($fragment['relationship_resolved'])
                        || !str_starts_with($target, 'word/media/')
                        || str_contains($target, "\0")
                        || preg_match('#(^|/)\.\.?(/|$)#', $target) === 1) {
                        throw ValidationException::withMessages([
                            'docx' => 'Relasi gambar DOCX tidak aman atau tidak dapat diverifikasi. Periksa dokumen sumber.',
                        ]);
                    }

                    if (isset($seenTargets[$target])) {
                        continue;
                    }
                    $seenTargets[$target] = true;

                    if (!isset($uniquePayloads[$target])) {
                        $bytes = $zip->getFromName($target);
                        if (!is_string($bytes) || $bytes === '' || strlen($bytes) > self::MAX_IMAGE_BYTES) {
                            throw ValidationException::withMessages([
                                'docx' => 'Gambar DOCX kosong atau melebihi batas 2 MB per gambar.',
                            ]);
                        }

                        $mime = $finfo->buffer($bytes);
                        $extension = match ($mime) {
                            'image/jpeg' => 'jpg',
                            'image/png' => 'png',
                            default => null,
                        };
                        $imageInfo = @getimagesizefromstring($bytes);
                        if ($extension === null
                            || !is_array($imageInfo)
                            || ($imageInfo['mime'] ?? null) !== $mime
                            || ((int) ($imageInfo[0] ?? 0) * (int) ($imageInfo[1] ?? 0)) > self::MAX_IMAGE_PIXELS) {
                            throw ValidationException::withMessages([
                                'docx' => 'Format gambar DOCX tidak didukung atau dimensi gambar terlalu besar. Hanya JPEG dan PNG yang diizinkan.',
                            ]);
                        }

                        $uniquePayloads[$target] = [
                            'bytes' => $bytes,
                            'mime_type' => $mime,
                            'extension' => $extension,
                            'file_size' => strlen($bytes),
                            'sha256' => hash('sha256', $bytes),
                            'source_target' => $target,
                        ];
                    }

                    $payload = $uniquePayloads[$target];
                    // The importer stores one tenant-owned asset per question reference.
                    // Count expanded attachments (including shared stimulus reuse) to cap disk writes.
                    $totalImages++;
                    $totalBytes += (int) $payload['file_size'];
                    if ($totalImages > self::MAX_IMAGES_PER_IMPORT || $totalBytes > self::MAX_TOTAL_IMAGE_BYTES) {
                        throw ValidationException::withMessages([
                            'docx' => 'Jumlah atau total ukuran lampiran gambar DOCX melebihi batas keamanan impor.',
                        ]);
                    }

                    $payload['relationship_id'] = (string) ($fragment['relationship_id'] ?? '');
                    $result[$sequence][] = $payload;
                }
            }
        } finally {
            $zip->close();
        }

        return $result;
    }
}
