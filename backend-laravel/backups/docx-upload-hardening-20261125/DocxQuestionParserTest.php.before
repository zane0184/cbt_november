<?php

namespace Tests\Unit;

use App\Services\Questions\DocxQuestionParser;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use ZipArchive;

class DocxQuestionParserTest extends TestCase
{
    public function test_parses_multiple_choice_complex_and_three_statement_true_false_in_document_order(): void
    {
        $path = $this->makeDocx([
            'SOAL 1. [PG] Hasil 4 + 5 adalah ....',
            'A. 7',
            'B. 8',
            'C. 9',
            'D. 10',
            'KUNCI: C',
            'SOAL 2. [PGK] Pilih bilangan genap.',
            'A. 2',
            'B. 3',
            'C. 4',
            'D. 5',
            'KUNCI: A, C',
            'SOAL 3. [B/S] Tentukan benar atau salah.',
            'a. 2 adalah bilangan genap.',
            'b. 5 lebih besar daripada 9.',
            'c. 10 habis dibagi 2.',
            'KUNCI: B, S, B',
        ]);

        try {
            $result = app(DocxQuestionParser::class)->parse($path);
            $this->assertSame(3, $result['total']);
            $this->assertSame(3, $result['valid']);
            $this->assertSame('MULTIPLE_CHOICE', $result['questions'][0]['question_type']);
            $this->assertSame('MULTIPLE_RESPONSE', $result['questions'][1]['question_type']);
            $this->assertSame('TRUE_FALSE_GROUP', $result['questions'][2]['question_type']);
            $this->assertSame([true, false, true], array_column($result['questions'][2]['statements'], 'is_true'));
        } finally {
            @unlink($path);
        }
    }

    public function test_preserves_last_question_when_answer_key_is_at_end_of_document(): void
    {
        $path = $this->makeDocx([
            'SOAL 1. [PG] Ibu kota Indonesia adalah ....',
            'A. Bandung',
            'B. Jakarta',
            'KUNCI JAWABAN',
            '1. B',
        ]);

        try {
            $result = app(DocxQuestionParser::class)->parse($path);
            $this->assertSame(1, $result['total']);
            $this->assertSame(1, $result['valid']);
            $this->assertTrue($result['questions'][0]['options'][1]['is_correct']);
        } finally {
            @unlink($path);
        }
    }

    public function test_keeps_shared_stimulus_group_identity_across_questions(): void
    {
        $path = $this->makeDocx([
            'STIMULUS [BACAAN-A]: Hujan turun sejak pagi.',
            'Paragraf kedua bacaan.',
            'SOAL 1. [PG] Apa keadaan cuaca pada bacaan?',
            'A. Hujan',
            'B. Cerah',
            'KUNCI: A',
            'SOAL 2. [PG] Sejak kapan hujan turun?',
            'A. Siang',
            'B. Pagi',
            'KUNCI: B',
            'AKHIR STIMULUS',
        ]);

        try {
            $result = app(DocxQuestionParser::class)->parse($path);
            $this->assertSame(2, $result['total']);
            $this->assertSame('BACAAN-A', $result['questions'][0]['stimulus']['id']);
            $this->assertSame(
                $result['questions'][0]['stimulus']['id'],
                $result['questions'][1]['stimulus']['id']
            );
            $this->assertStringContainsString('Paragraf kedua bacaan.', $result['questions'][0]['stimulus']['content']);
        } finally {
            @unlink($path);
        }
    }

    public function test_essay_is_recognized_as_manual_review_and_unsupported_types_fail_closed(): void
    {
        $path = $this->makeDocx([
            'SOAL 1. [ESSAY] Jelaskan proses terjadinya hujan.',
            'SOAL 2. [MATCHING] Pasangkan istilah dengan artinya.',
            'A. Istilah satu',
            'B. Istilah dua',
            'KUNCI: A, B',
        ]);

        try {
            $result = app(DocxQuestionParser::class)->parse($path);
            $this->assertSame('ESSAY', $result['questions'][0]['question_type']);
            $this->assertSame('READY', $result['questions'][0]['status']);
            $this->assertSame('MATCHING', $result['questions'][1]['question_type']);
            $this->assertSame('NEEDS_REVIEW', $result['questions'][1]['status']);
        } finally {
            @unlink($path);
        }
    }

    public function test_downloaded_word_template_is_a_valid_docx_and_parses_end_to_end(): void
    {
        $response = app(\App\Services\Questions\DocxQuestionTemplate::class)->download();
        $path = $response->getFile()->getPathname();

        try {
            $zip = new ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $this->assertNotFalse($zip->locateName('word/document.xml'));
            $zip->close();

            $result = app(DocxQuestionParser::class)->parse($path);
            $this->assertSame(5, $result['total']);
            $this->assertSame(5, $result['valid']);
            $this->assertSame('ESSAY', $result['questions'][3]['question_type']);
            $this->assertSame('BACAAN-A', $result['questions'][4]['stimulus']['id']);
        } finally {
            @unlink($path);
        }
    }

    public function test_rejects_non_docx_or_corrupt_archive(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'cbt-invalid-docx-');
        file_put_contents($path, 'not a Word file');

        try {
            $this->expectException(ValidationException::class);
            app(DocxQuestionParser::class)->parse($path);
        } finally {
            @unlink($path);
        }
    }

    private function makeDocx(array $lines): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cbt-docx-parser-');
        $paragraphs = '';
        foreach ($lines as $line) {
            $escaped = htmlspecialchars($line, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $paragraphs .= '<w:p><w:r><w:t xml:space="preserve">' . $escaped . '</w:t></w:r></w:p>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>' . $paragraphs . '<w:sectPr/></w:body></w:document>';
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create DOCX test fixture.');
        }
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return $path;
    }
}
