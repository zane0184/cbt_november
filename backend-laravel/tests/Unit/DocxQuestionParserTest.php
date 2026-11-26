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
            $this->assertSame([null, null, null], array_column($result['questions'][2]['statements'], 'is_true'));
        } finally {
            @unlink($path);
        }
    }


    public function test_questions_without_answer_keys_are_ready_as_content_only_drafts(): void
    {
        $path = $this->makeDocx([
            'SOAL 1. [PG] Ibu kota Indonesia adalah ....',
            'A. Bandung',
            'B. Jakarta',
            'SOAL 2. [PGK] Pilih bilangan genap.',
            'A. 2',
            'B. 3',
            'C. 4',
            'SOAL 3. [B/S] Tentukan benar atau salah.',
            'a. 2 adalah bilangan genap.',
            'b. 5 lebih besar daripada 9.',
            'c. 10 habis dibagi 2.',
        ]);

        try {
            $result = app(DocxQuestionParser::class)->parse($path);
            $this->assertSame(3, $result['total']);
            $this->assertSame(3, $result['valid']);
            $this->assertSame('MULTIPLE_CHOICE', $result['questions'][0]['question_type']);
            $this->assertSame('MULTIPLE_RESPONSE', $result['questions'][1]['question_type']);
            $this->assertSame('TRUE_FALSE_GROUP', $result['questions'][2]['question_type']);
            $this->assertSame([false, false], array_column($result['questions'][0]['options'], 'is_correct'));
            $this->assertSame([false, false, false], array_column($result['questions'][1]['options'], 'is_correct'));
            $this->assertSame([null, null, null], array_column($result['questions'][2]['statements'], 'is_true'));
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
            $this->assertFalse($result['questions'][0]['options'][1]['is_correct']);
        } finally {
            @unlink($path);
        }
    }


    public function test_reads_checkbox_pgk_true_false_table_shared_stimulus_and_subject_sections(): void
    {
        $p = static function (string $text): string {
            $escaped = htmlspecialchars($text, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            return '<w:p><w:r><w:t xml:space="preserve">' . $escaped . '</w:t></w:r></w:p>';
        };
        $cell = static fn (string $text): string => '<w:tc>' . $p($text) . '</w:tc>';
        $row = static fn (array $cells): string => '<w:tr>' . implode('', array_map($cell, $cells)) . '</w:tr>';

        $body = $p('MTK Kelas 6')
            . $p('Bacalah teks berikut untuk menjawab soal 1 sampai 3.')
            . $p('Ali membaca buku di perpustakaan.')
            . $p('1. [PG] Apa yang dibaca Ali? A. Buku B. Majalah C. Koran D. Komik')
            . $p('2. [PGK] Pilih pernyataan yang benar.')
            . $p('☐ Ali membaca buku.')
            . $p('☐ Ali pergi ke pasar.')
            . $p('☐ Ali berada di perpustakaan.')
            . $p('3. [B/S] Tentukan benar atau salah.')
            . '<w:tbl>'
            . $row(['Pernyataan', 'Benar', 'Salah'])
            . $row(['Buku adalah bacaan.', '☐', '☐'])
            . $row(['2 adalah bilangan genap.', '☐', '☐'])
            . $row(['Matahari terbit dari barat.', '☐', '☐'])
            . '</w:tbl>'
            . $p('C. Kunci Jawaban Matematika')
            . $p('1. A')
            . $p('2. A dan C')
            . $p('3. B, S, B')
            . $p('B. Indo Kelas 6')
            . $p('1. [PG] Apa gagasan utama bacaan?')
            . $p('A. Membaca buku')
            . $p('B. Bermain bola');

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>' . $body . '<w:sectPr/></w:body></w:document>';
        $path = $this->makeDocxFromXml($xml);

        try {
            $result = app(DocxQuestionParser::class)->parse($path);
            $this->assertSame(4, $result['total']);
            $this->assertSame(4, $result['valid']);
            $this->assertTrue($result['requires_section_split']);
            $this->assertSame([
                ['title' => 'MATEMATIKA KELAS 6', 'count' => 3],
                ['title' => 'BAHASA INDONESIA KELAS 6', 'count' => 1],
            ], $result['sections']);
            $questions = $result['questions'];
            $this->assertSame('MULTIPLE_CHOICE', $questions[0]['question_type']);
            $this->assertSame(['Buku', 'Majalah', 'Koran', 'Komik'], array_column($questions[0]['options'], 'content'));
            $this->assertSame('MULTIPLE_RESPONSE', $questions[1]['question_type']);
            $this->assertCount(3, $questions[1]['options']);
            $this->assertSame([false, false, false], array_column($questions[1]['options'], 'is_correct'));
            $this->assertSame('TRUE_FALSE_GROUP', $questions[2]['question_type']);
            $this->assertSame(['Buku adalah bacaan.', '2 adalah bilangan genap.', 'Matahari terbit dari barat.'], array_column($questions[2]['statements'], 'text'));
            $this->assertSame([null, null, null], array_column($questions[2]['statements'], 'is_true'));
            $this->assertCount(4, $questions[2]['table_rows']);
            $this->assertSame($questions[0]['stimulus']['id'], $questions[1]['stimulus']['id']);
            $this->assertSame($questions[0]['stimulus']['id'], $questions[2]['stimulus']['id']);
            $this->assertStringContainsString('Ali membaca buku', $questions[0]['stimulus']['content']);
            $this->assertSame('BAHASA INDONESIA KELAS 6', $questions[3]['source_section']);
        } finally {
            @unlink($path);
        }
    }


    public function test_does_not_assume_every_pgk_table_is_a_list_of_options(): void
    {
        $p = static function (string $text): string {
            $escaped = htmlspecialchars($text, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            return '<w:p><w:r><w:t xml:space="preserve">' . $escaped . '</w:t></w:r></w:p>';
        };
        $cell = static fn (string $text): string => '<w:tc>' . $p($text) . '</w:tc>';
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            . $p('SOAL 1. [PGK] Perhatikan tabel jumlah siswa berikut.')
            . '<w:tbl><w:tr>' . $cell('Kelas') . $cell('Jumlah siswa') . '</w:tr>'
            . '<w:tr>' . $cell('VI-A') . $cell('20') . '</w:tr></w:tbl>'
            . '<w:sectPr/></w:body></w:document>';
        $path = $this->makeDocxFromXml($xml);

        try {
            $result = app(DocxQuestionParser::class)->parse($path);
            $question = $result['questions'][0];
            $this->assertSame('MULTIPLE_RESPONSE', $question['question_type']);
            $this->assertSame([], $question['options']);
            $this->assertSame('NEEDS_REVIEW', $question['status']);
            $this->assertCount(2, $question['table_rows']);
            $this->assertStringContainsString('Struktur tabel belum', implode(' ', $question['errors']));
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
            'SOAL 3. [LENGKAPI] Lengkapi kalimat berikut.',
            'SOAL 4. [URUT] Urutkan langkah berikut.',
            'SOAL 5. Pertanyaan tanpa penanda jenis.',
            'KUNCI: A, B',
        ]);

        try {
            $result = app(DocxQuestionParser::class)->parse($path);
            $this->assertSame('ESSAY', $result['questions'][0]['question_type']);
            $this->assertSame('READY', $result['questions'][0]['status']);
            $this->assertSame('MATCHING', $result['questions'][1]['question_type']);
            $this->assertSame('NEEDS_REVIEW', $result['questions'][1]['status']);
            $this->assertSame('UNSUPPORTED', $result['questions'][2]['question_type']);
            $this->assertSame('UNSUPPORTED', $result['questions'][3]['question_type']);
            $this->assertSame('UNSUPPORTED', $result['questions'][4]['question_type']);
            $this->assertSame('NEEDS_REVIEW', $result['questions'][4]['status']);
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


    public function test_rejects_external_relationships(): void
    {
        $externalRelationships = '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId9" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" Target="https://example.invalid/" TargetMode="External"/></Relationships>';
        $path = $this->makeDocx(['SOAL 1. [PG] Contoh soal.', 'A. Benar', 'B. Salah', 'KUNCI: A'], [
            'word/_rels/document.xml.rels' => $externalRelationships,
        ]);
        try {
            $this->expectException(ValidationException::class);
            app(DocxQuestionParser::class)->parse($path);
        } finally { @unlink($path); }
    }

    public function test_rejects_embedded_ole_objects(): void
    {
        $path = $this->makeDocx(['SOAL 1. [PG] Contoh soal.', 'A. Benar', 'B. Salah', 'KUNCI: A'], [
            'word/embeddings/oleObject1.bin' => 'embedded payload',
        ]);
        try {
            $this->expectException(ValidationException::class);
            app(DocxQuestionParser::class)->parse($path);
        } finally { @unlink($path); }
    }

    public function test_rejects_extreme_zip_compression_ratio(): void
    {
        $path = $this->makeDocx(['SOAL 1. [PG] Contoh soal.', 'A. Benar', 'B. Salah', 'KUNCI: A'], [
            'custom/repetitive.txt' => str_repeat('A', 300000),
        ]);
        try {
            $this->expectException(ValidationException::class);
            app(DocxQuestionParser::class)->parse($path);
        } finally { @unlink($path); }
    }

    public function test_invalid_inline_media_blocks_import_while_word_math_is_converted_for_review(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<w:body><w:p><w:r><w:t>SOAL 1. [PG] Hitung hasil berikut.</w:t></w:r></w:p>'
            . '<w:p><w:r><w:t>A. 2</w:t></w:r></w:p><w:p><w:r><w:t>B. 3</w:t></w:r></w:p>'
            . '<w:p><w:r><w:t>KUNCI: A</w:t></w:r></w:p>'
            . '<w:p><w:r><w:drawing><a:blip r:embed="rIdImage1"/></w:drawing></w:r></w:p>'
            . '<w:p><m:oMath><m:r><m:t>x+1</m:t></m:r></m:oMath></w:p><w:sectPr/></w:body></w:document>';
        $relationships = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rIdImage1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/image1.png"/>'
            . '</Relationships>';
        $path = $this->makeDocxFromXml($xml, [
            'word/_rels/document.xml.rels' => $relationships,
            'word/media/image1.png' => "fixture-image-bytes",
        ]);
        try {
            $result = app(DocxQuestionParser::class)->parse($path);
            $this->assertSame([1], $result['blocked_source_numbers']);
            $this->assertContains('IMAGE_OR_DIAGRAM', $result['questions'][0]['blocked_features']);
            $this->assertNotContains('WORD_MATH', $result['questions'][0]['blocked_features']);

            $imageFragment = collect($result['questions'][0]['source_fragments'])
                ->firstWhere('kind', 'IMAGE_REFERENCE');
            $mathFragment = collect($result['questions'][0]['source_fragments'])
                ->firstWhere('kind', 'WORD_MATH_OMML');

            $this->assertNotNull($imageFragment);
            $this->assertSame('rIdImage1', $imageFragment['relationship_id']);
            $this->assertTrue($imageFragment['relationship_resolved']);
            $this->assertFalse($imageFragment['media_valid']);
            $this->assertNotNull($mathFragment);
            $this->assertStringContainsString('<m:oMath', $mathFragment['xml']);
            $this->assertNotEmpty($mathFragment['xml_sha256']);
            $this->assertSame('x+1', $result['questions'][0]['mathml_fragments'][0]['fallback_text']);
            $this->assertTrue($result['questions'][0]['mathml_fragments'][0]['supported']);
        } finally { @unlink($path); }
    }

    public function test_preserves_arabic_text_and_harakat(): void
    {
        $arabic = 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ';
        $path = $this->makeDocx(['SOAL 1. [PG] ' . $arabic, 'A. Jawaban satu', 'B. Jawaban dua', 'KUNCI: A']);
        try {
            $result = app(DocxQuestionParser::class)->parse($path);
            $this->assertSame($arabic, $result['questions'][0]['stem']);
            $this->assertSame('READY', $result['questions'][0]['status']);
        } finally { @unlink($path); }
    }

    private function makeDocx(array $lines, array $extraEntries = []): string
    {
        $paragraphs = '';
        foreach ($lines as $line) {
            $escaped = htmlspecialchars($line, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $paragraphs .= '<w:p><w:r><w:t xml:space="preserve">' . $escaped . '</w:t></w:r></w:p>';
        }
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>' . $paragraphs . '<w:sectPr/></w:body></w:document>';
        return $this->makeDocxFromXml($xml, $extraEntries);
    }

    private function makeDocxFromXml(string $xml, array $extraEntries = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cbt-docx-parser-');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create DOCX test fixture.');
        }
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/document.xml', $xml);
        foreach ($extraEntries as $name => $content) { $zip->addFromString($name, $content); }
        $zip->close();
        return $path;
    }


}
