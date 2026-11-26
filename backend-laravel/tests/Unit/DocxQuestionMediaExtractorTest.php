<?php

namespace Tests\\Unit;

use App\\Services\\Questions\\DocxQuestionMediaExtractor;
use Illuminate\\Validation\\ValidationException;
use Tests\\TestCase;
use ZipArchive;

class DocxQuestionMediaExtractorTest extends TestCase
{
    private const ONE_PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p0sAAAAASUVORK5CYII=';

    public function test_extracts_only_resolved_png_referenced_by_question(): void
    {
        $path = $this->makeDocx(['word/media/image1.png' => base64_decode(self::ONE_PIXEL_PNG)]);

        try {
            $result = app(DocxQuestionMediaExtractor::class)->extractForQuestions($path, [[
                'source_locator' => ['sequence' => 7],
                'source_fragments' => [[
                    'kind' => 'IMAGE_REFERENCE',
                    'target' => 'word/media/image1.png',
                    'relationship_resolved' => true,
                    'relationship_id' => 'rIdImage1',
                ]],
            ]]);

            $this->assertSame('image/png', $result[7][0]['mime_type']);
            $this->assertSame('png', $result[7][0]['extension']);
            $this->assertSame('rIdImage1', $result[7][0]['relationship_id']);
            $this->assertSame(hash('sha256', base64_decode(self::ONE_PIXEL_PNG)), $result[7][0]['sha256']);
        } finally {
            @unlink($path);
        }
    }

    public function test_rejects_unresolved_or_traversal_image_relationship(): void
    {
        $path = $this->makeDocx([]);

        try {
            $this->expectException(ValidationException::class);
            app(DocxQuestionMediaExtractor::class)->extractForQuestions($path, [[
                'source_locator' => ['sequence' => 1],
                'source_fragments' => [[
                    'kind' => 'IMAGE_REFERENCE',
                    'target' => 'word/media/../secret.png',
                    'relationship_resolved' => true,
                    'relationship_id' => 'rIdUnsafe',
                ]],
            ]]);
        } finally {
            @unlink($path);
        }
    }

    public function test_rejects_unsupported_image_mime_type(): void
    {
        $path = $this->makeDocx(['word/media/image1.gif' => base64_decode('R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=')]);

        try {
            $this->expectException(ValidationException::class);
            app(DocxQuestionMediaExtractor::class)->extractForQuestions($path, [[
                'source_locator' => ['sequence' => 1],
                'source_fragments' => [[
                    'kind' => 'IMAGE_REFERENCE',
                    'target' => 'word/media/image1.gif',
                    'relationship_resolved' => true,
                    'relationship_id' => 'rIdGif',
                ]],
            ]]);
        } finally {
            @unlink($path);
        }
    }

    private function makeDocx(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cbt-docx-media-');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \\RuntimeException('Cannot create DOCX media fixture.');
        }

        foreach ($entries as $name => $bytes) {
            $zip->addFromString($name, $bytes);
        }
        $zip->close();

        return $path;
    }
}
