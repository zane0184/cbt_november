<?php

namespace App\Services\Questions;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class DocxQuestionParser
{
    private const MAX_FILE_BYTES = 10 * 1024 * 1024;
    private const MAX_XML_BYTES = 20 * 1024 * 1024;
    private const MAX_QUESTIONS = 500;

    public function parse(string $path): array
    {
        if (!is_file($path) || filesize($path) < 1 || filesize($path) > self::MAX_FILE_BYTES) {
            throw ValidationException::withMessages(['docx' => 'File DOCX kosong atau melebihi batas 10 MB.']);
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['docx' => 'File bukan paket DOCX yang valid atau rusak.']);
        }

        try {
            $xml = $this->validatePackage($zip);
            $blocks = $this->readOrderedBlocks($xml);
        } finally {
            $zip->close();
        }

        return $this->buildQuestions($blocks, hash_file('sha256', $path));
    }

    /**
     * Validate the OOXML package before parsing any document part.
     * No package entry is extracted to the filesystem.
     */
    private function validatePackage(ZipArchive $zip): string
    {
        if ($zip->numFiles < 3 || $zip->numFiles > 500) {
            throw ValidationException::withMessages(['docx' => 'Paket Word memiliki jumlah bagian yang tidak wajar.']);
        }

        $requiredEntries = ['[Content_Types].xml', '_rels/.rels', 'word/document.xml'];
        $foundEntries = [];
        $totalUncompressedBytes = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            if (!is_array($stat)) {
                throw ValidationException::withMessages(['docx' => 'Struktur ZIP DOCX tidak dapat diverifikasi.']);
            }

            $name = (string) ($stat['name'] ?? '');
            $normalizedName = str_replace('\\', '/', $name);
            if ($name === ''
                || str_contains($name, "\0")
                || str_starts_with($normalizedName, '/')
                || preg_match('#(^|/)\.\.?(/|$)#', $normalizedName) === 1) {
                throw ValidationException::withMessages(['docx' => 'Paket Word mengandung nama bagian yang tidak aman.']);
            }

            $entrySize = (int) ($stat['size'] ?? 0);
            $compressedSize = (int) ($stat['comp_size'] ?? 0);
            $totalUncompressedBytes += $entrySize;
            if ($entrySize > self::MAX_XML_BYTES || $totalUncompressedBytes > 30 * 1024 * 1024) {
                throw ValidationException::withMessages(['docx' => 'Isi paket Word melebihi batas keamanan pemrosesan.']);
            }
            if ($entrySize > 0 && ($compressedSize < 1 || ($entrySize / $compressedSize) > 100)) {
                throw ValidationException::withMessages(['docx' => 'Rasio kompresi DOCX melebihi batas keamanan.']);
            }

            $lowerName = strtolower($normalizedName);
            if (str_starts_with($lowerName, 'word/embeddings/')
                || str_starts_with($lowerName, 'word/activex/')
                || str_contains($lowerName, 'vbaproject')
                || preg_match('~oleobject[^/]*\.bin$~i', $lowerName) === 1) {
                throw ValidationException::withMessages(['docx' => 'DOCX dengan embedded OLE, ActiveX, atau macro tidak diizinkan.']);
            }

            $foundEntries[$normalizedName] = true;
            if (str_ends_with(strtolower($normalizedName), '.rels')) {
                $this->validateRelationships($zip, $index);
            }
        }

        foreach ($requiredEntries as $requiredEntry) {
            if (!isset($foundEntries[$requiredEntry])) {
                throw ValidationException::withMessages(['docx' => 'Paket Word tidak memiliki struktur OOXML wajib.']);
            }
        }

        $contentTypes = $zip->getFromName('[Content_Types].xml');
        $documentXml = $zip->getFromName('word/document.xml');
        if (!is_string($contentTypes) || !is_string($documentXml)
            || strlen($contentTypes) > self::MAX_XML_BYTES
            || strlen($documentXml) > self::MAX_XML_BYTES) {
            throw ValidationException::withMessages(['docx' => 'Struktur dokumen Word tidak ditemukan atau terlalu besar.']);
        }

        $this->assertSafeXml($contentTypes);
        $this->assertSafeXml($documentXml);
        if (preg_match('/<(?:[A-Za-z0-9_]+:)?(?:altChunk|object|OLEObject|control)\b/i', $documentXml) === 1) {
            throw ValidationException::withMessages(['docx' => 'DOCX dengan konten aktif atau embedded object tidak diizinkan.']);
        }

        return $documentXml;
    }

    private function validateRelationships(ZipArchive $zip, int $index): void
    {
        $xml = $zip->getFromIndex($index);
        if (!is_string($xml) || strlen($xml) > 1024 * 1024) {
            throw ValidationException::withMessages(['docx' => 'Bagian relasi DOCX tidak valid atau terlalu besar.']);
        }
        $this->assertSafeXml($xml);
        $document = new DOMDocument();
        if (!$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT)) {
            throw ValidationException::withMessages(['docx' => 'Bagian relasi DOCX tidak dapat dibaca.']);
        }
        $xpath = new \DOMXPath($document);
        foreach ($xpath->query('//*[local-name()="Relationship"]') as $relationship) {
            if (strcasecmp($relationship->getAttribute('TargetMode'), 'External') === 0) {
                throw ValidationException::withMessages(['docx' => 'Relasi eksternal dalam DOCX tidak diizinkan.']);
            }
        }
    }

    private function assertSafeXml(string $xml): void
    {
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $xml) === 1) {
            throw ValidationException::withMessages(['docx' => 'Deklarasi DTD atau ENTITY dalam DOCX tidak diizinkan.']);
        }
    }

    private function readOrderedBlocks(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $this->assertSafeXml($xml);
            $document = new DOMDocument();
            if (!$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT)) {
                throw ValidationException::withMessages(['docx' => 'XML dokumen Word tidak dapat dibaca.']);
            }
            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/officeDocument/2006/math');
            $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
            $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $body = $xpath->query('/w:document/w:body')->item(0);
            if (!$body) {
                throw ValidationException::withMessages(['docx' => 'Bagian isi dokumen Word tidak ditemukan.']);
            }
            $blocks = [];
            $sequence = 0;
            $tableSequence = 0;
            foreach ($body->childNodes as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }
                if ($node->localName === 'p') {
                    $text = $this->paragraphText($node, $xpath);
                    $hasMedia = $xpath->query('.//w:drawing | .//w:pict', $node)->length > 0;
                    $hasMath = $xpath->query('.//m:oMath | .//m:oMathPara', $node)->length > 0;
                    if (trim($text) !== '' || $hasMedia || $hasMath) {
                        $blocks[] = [
                            'text' => $text,
                            'has_media' => $hasMedia,
                            'has_math' => $hasMath,
                            'locator' => ['sequence' => ++$sequence, 'kind' => 'paragraph'],
                        ];
                    }
                    continue;
                }
                if ($node->localName === 'tbl') {
                    $tableIndex = ++$tableSequence;
                    foreach ($xpath->query('./w:tr', $node) as $rowIndex => $row) {
                        $cells = [];
                        $rowHasMedia = false;
                        $rowHasMath = false;
                        foreach ($xpath->query('./w:tc', $row) as $cell) {
                            $paragraphTexts = [];
                            foreach ($xpath->query('.//w:p', $cell) as $paragraph) {
                                $cellText = $this->paragraphText($paragraph, $xpath);
                                if (trim($cellText) !== '') {
                                    $paragraphTexts[] = $cellText;
                                }
                                $rowHasMedia = $rowHasMedia || $xpath->query('.//w:drawing | .//w:pict', $paragraph)->length > 0;
                                $rowHasMath = $rowHasMath || $xpath->query('.//m:oMath | .//m:oMathPara', $paragraph)->length > 0;
                            }
                            $cells[] = trim(implode("\n", $paragraphTexts));
                        }
                        $rowText = implode(' | ', $cells);
                        if (trim($rowText) === '' && !$rowHasMedia && !$rowHasMath) {
                            continue;
                        }
                        $blocks[] = [
                            'text' => $rowText,
                            'cells' => $cells,
                            'has_media' => $rowHasMedia,
                            'has_math' => $rowHasMath,
                            'has_nested_table' => $xpath->query('.//w:tbl', $row)->length > 0,
                            'locator' => [
                                'sequence' => ++$sequence,
                                'kind' => 'table-row',
                                'table' => $tableIndex,
                                'row' => $rowIndex + 1,
                                'cell_count' => count($cells),
                            ],
                        ];
                    }
                }
            }
            return $blocks;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function paragraphText(DOMNode $paragraph, \DOMXPath $xpath): string
    {
        $nodes = $xpath->query(
            './/w:t[not(ancestor::w:del)] | .//w:tab[not(ancestor::w:del)] | .//w:br[not(ancestor::w:del)] | .//w:cr[not(ancestor::w:del)] | .//w:sym[not(ancestor::w:del)]',
            $paragraph
        );
        $parts = [];
        foreach ($nodes as $node) {
            if ($node->localName === 't') {
                $parts[] = $node->textContent;
            } elseif ($node->localName === 'tab') {
                $parts[] = "\t";
            } elseif ($node->localName === 'br' || $node->localName === 'cr') {
                $parts[] = "\n";
            } elseif ($node->localName === 'sym') {
                $char = strtoupper($node->getAttributeNS(
                    'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
                    'char'
                ));
                $parts[] = in_array($char, ['F0A8', 'F0A3', 'F0A7', 'F0FE'], true) ? '☐' : '□';
            }
        }
        return $this->normalizeText(implode('', $parts));
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\u{00A0}", "\r"], [' ', "\n"], $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    private function buildQuestions(array $blocks, string $fileHash): array
    {
        $rawQuestions = [];
        $answerSection = false;
        $active = null;
        $stimulus = null;
        $excluded = [];
        $warnings = [];
        $currentSection = null;

        foreach ($blocks as $block) {
            $text = trim((string) ($block['text'] ?? ''));
            $locator = $block['locator'] ?? [];
            $isTableRow = ($locator['kind'] ?? null) === 'table-row';
            $cells = array_values($block['cells'] ?? []);
            $primaryText = $isTableRow ? trim((string) ($cells[0] ?? '')) : $text;

            $blockUnsupported = [];
            $blockUnsupportedFeatures = [];
            if (!empty($block['has_media'])) {
                $blockUnsupported[] = 'Dokumen memuat gambar/diagram pada blok ini; import media belum diaktifkan dan item harus ditinjau manual.';
                $blockUnsupportedFeatures[] = 'IMAGE_OR_DIAGRAM';
            }
            if (!empty($block['has_math'])) {
                $blockUnsupported[] = 'Dokumen memuat objek formula matematika Word pada blok ini; konversi formula belum diaktifkan.';
                $blockUnsupportedFeatures[] = 'WORD_MATH';
            }

            $questionPattern = '/^(?:(?:SOAL|NOMOR)\s+)?(?:\((\d{1,3})\)|(\d{1,3})\s*[.):=-])\s*(.*)$/iu';
            $questionMatch = [];
            $isQuestionStart = $isTableRow
                ? ($active === null && preg_match('/^(?:(?:SOAL|NOMOR)\s+)?(?:\(\d{1,3}\)|\d{1,3}\s*[.):=-]).*\[(?:PGK?|B\/S|BENAR\/SALAH|ESSAY|URAIAN|MATCH(?:ING)?|MENJODOHKAN|ISIAN|SHORT\s*ANSWER|LENGKAPI|URUT|SEBAB(?:-AKIBAT)?)/iu', $primaryText) === 1)
                : preg_match($questionPattern, $primaryText, $questionMatch) === 1;

            $sectionTitle = $this->detectSubjectSection($primaryText);
            if ($sectionTitle !== null) {
                if ($active !== null) {
                    $rawQuestions[] = $active;
                    $active = null;
                }
                $currentSection = $sectionTitle;
                $stimulus = null;
                $answerSection = false;
                continue;
            }

            if ($isTableRow && $isQuestionStart) {
                preg_match($questionPattern, $primaryText, $questionMatch);
            }

            $isExplicitStimulusStart = preg_match('/^STIMULUS(?:\s*\[[A-Z0-9_-]+\])?\s*:/iu', $primaryText) === 1;
            if ($isTableRow) {
                if ($active !== null) {
                    $active['has_table_content'] = true;
                } elseif ($stimulus !== null) {
                    $stimulus['has_table_content'] = true;
                }
            }

            if ($blockUnsupported !== [] && !$isQuestionStart && !$isExplicitStimulusStart) {
                if ($active !== null) {
                    $active['unsupported_blocks'] = array_merge($active['unsupported_blocks'] ?? [], $blockUnsupported);
                    $active['unsupported_features'] = array_merge($active['unsupported_features'] ?? [], $blockUnsupportedFeatures);
                } elseif ($stimulus !== null) {
                    $stimulus['unsupported_blocks'] = array_merge($stimulus['unsupported_blocks'] ?? [], $blockUnsupported);
                    $stimulus['unsupported_features'] = array_merge($stimulus['unsupported_features'] ?? [], $blockUnsupportedFeatures);
                }
            }

            if ($primaryText === '') {
                continue;
            }

            $isAnswerKeyHeading = preg_match('/^(?:[A-Z0-9][.)]\s*)?(?:KUNCI\s+JAWABAN|DAFTAR\s+JAWABAN|JAWABAN\s+SOAL|ANSWER\s+KEY)\b.*$/iu', $primaryText) === 1
                || preg_match('/^(?:[A-Z0-9][.)]\s*)?KUNCI\s*:?$/iu', $primaryText) === 1;
            if ($isAnswerKeyHeading) {
                if ($active !== null) {
                    $rawQuestions[] = $active;
                    $active = null;
                }
                $answerSection = true;
                $stimulus = null;
                continue;
            }

            if (preg_match('/^(?:AKHIR\s+STIMULUS|END\s+STIMULUS)\s*$/iu', $primaryText)) {
                $stimulus = null;
                continue;
            }

            if (preg_match('/^(?:STIMULUS)(?:\s*\[([A-Z0-9_-]+)\])?\s*:?\s*(.*)$/iu', $primaryText, $stimulusMatch)) {
                if ($active !== null) {
                    $rawQuestions[] = $active;
                    $active = null;
                }
                $stimulus = [
                    'id' => strtoupper($stimulusMatch[1] ?? ('STIMULUS-' . ($locator['sequence'] ?? count($rawQuestions) + 1))),
                    'content' => trim($stimulusMatch[2] ?? ''),
                    'source' => $locator,
                    'unsupported_blocks' => $blockUnsupported,
                    'unsupported_features' => $blockUnsupportedFeatures,
                    'table_rows' => [],
                ];
                continue;
            }

            if ($answerSection) {
                if (preg_match('/^(?:SOAL|NOMOR)\s+\d{1,3}\s*[.):]/iu', $primaryText) === 1) {
                    $answerSection = false;
                } else {
                    continue;
                }
            }

            if ($active === null && preg_match('/^(?:BACA(?:LAH)?|CERMATI|SIMAK|PERHATIKAN)\b/iu', $primaryText) === 1) {
                $stimulus = [
                    'id' => 'STIMULUS-' . ($locator['sequence'] ?? (count($rawQuestions) + 1)),
                    'content' => $primaryText,
                    'source' => $locator,
                    'unsupported_blocks' => $blockUnsupported,
                    'unsupported_features' => $blockUnsupportedFeatures,
                    'table_rows' => [],
                ];
                continue;
            }

            if ($isQuestionStart) {
                if ($active !== null) {
                    $rawQuestions[] = $active;
                }
                $number = (int) (($questionMatch[1] ?? '') !== '' ? $questionMatch[1] : ($questionMatch[2] ?? 0));
                $header = trim($questionMatch[3] ?? '');
                $hint = null;
                if (preg_match('/\[([A-Z0-9_ \/-]{2,40})\]/iu', $header, $typeMatch)) {
                    $hint = strtoupper(preg_replace('/\s+/', ' ', $typeMatch[1]));
                    $header = trim(str_replace($typeMatch[0], '', $header));
                }
                $inline = $this->extractInlineOptions($header);
                $initialOptions = [];
                if ($inline !== null) {
                    $header = $inline['stem'];
                    $initialOptions = $inline['options'];
                }
                $header = trim(preg_replace('/(?:KUNCI|JAWABAN)\s*[:=]\s*.+$/iu', '', $header));

                $active = [
                    'number' => $number,
                    'stem_parts' => $header !== '' ? [$header] : [],
                    'options' => $initialOptions,
                    'statements' => [],
                    'hint' => $hint,
                    'locator' => $locator,
                    'stimulus' => $stimulus,
                    'source_section' => $currentSection,
                    'table_rows' => [],
                    'table_unclassified' => false,
                    'inferred_type' => false,
                    'unsupported_blocks' => $blockUnsupported,
                    'unsupported_features' => $blockUnsupportedFeatures,
                    'has_table_content' => $isTableRow,
                ];
                if ($isTableRow) {
                    $active['table_rows'][] = $cells;
                    $tableOptions = $this->extractTableOptions(array_slice($cells, 1), $hint);
                    if ($tableOptions !== []) {
                        $active['options'] = array_merge($active['options'], $tableOptions);
                    } elseif (count(array_filter(array_slice($cells, 1), fn ($cell) => trim((string) $cell) !== '')) > 0) {
                        $active['table_unclassified'] = true;
                        $active['stem_parts'][] = implode(' | ', array_slice($cells, 1));
                    }
                }
                $answerSection = false;
                continue;
            }

            if ($active === null) {
                if ($isTableRow) {
                    if ($stimulus !== null) {
                        $stimulus['has_table_content'] = true;
                        $stimulus['table_rows'][] = $cells;
                        $stimulus['content'] = trim(($stimulus['content'] ?? '') . "\n" . $text);
                    } else {
                        $excluded[] = [
                            'text' => mb_substr($text, 0, 500),
                            'locator' => $locator,
                            'reason' => 'Tabel sebelum soal tidak dikenali sebagai stimulus atau tabel soal; ditampilkan untuk pemeriksaan.',
                        ];
                    }
                    continue;
                }
                if ($stimulus !== null) {
                    $stimulus['content'] = trim(($stimulus['content'] ?? '') . "\n" . $text);
                    continue;
                }
                if (preg_match('/^(?:KISI[- ]KISI|IDENTITAS PENYUSUN|LEMBAR PENGESAHAN|CAPAIAN PEMBELAJARAN|TUJUAN PEMBELAJARAN)\b/iu', $primaryText)) {
                    $excluded[] = ['text' => mb_substr($primaryText, 0, 180), 'locator' => $locator, 'reason' => 'Konten administrasi/kisi-kisi tidak diimpor sebagai soal.'];
                }
                continue;
            }

            if ($isTableRow) {
                $active['table_rows'][] = $cells;
                if ($this->isTrueFalseHeaderRow($cells)) {
                    if (empty($active['hint'])) {
                        $active['hint'] = 'B/S';
                        $active['inferred_type'] = true;
                    }
                    continue;
                }
                if ($this->isTrueFalseGroupHint($active['hint'])) {
                    $statementText = trim((string) ($cells[0] ?? ''));
                    if ($statementText !== '') {
                        $statementText = trim(preg_replace('/^(?:[a-c]|\d{1,2})\s*[.)=:]\s*/iu', '', $statementText));
                        $statementId = 's' . chr(97 + count($active['statements']));
                        $active['statements'][] = ['id' => $statementId, 'text' => $statementText, 'is_true' => null];
                        continue;
                    }
                }
                $tableOptions = $this->extractTableOptions($cells, $active['hint']);
                if ($tableOptions !== []) {
                    $active['options'] = array_merge($active['options'], $tableOptions);
                    continue;
                }
                $active['table_unclassified'] = true;
                $active['stem_parts'][] = $text;
                continue;
            }

            if (preg_match('/^(?:KUNCI|JAWABAN)\s*[:=]\s*.+$/iu', $text)) {
                continue;
            }
            if (preg_match('/^([a-c])\s*[.)=:]\s*(.+)$/iu', $text, $statementMatch)
                && $this->isTrueFalseGroupHint($active['hint'])) {
                $active['statements'][] = [
                    'id' => 's' . strtolower($statementMatch[1]),
                    'text' => trim($statementMatch[2]),
                    'is_true' => null,
                ];
                continue;
            }
            if (preg_match('/^[☐□☑✓]\s*(.+)$/u', $text, $checkboxMatch)
                && ($this->isMultipleResponseHint($active['hint']) || empty($active['hint']))) {
                if (empty($active['hint'])) {
                    $active['hint'] = 'PGK';
                    $active['inferred_type'] = true;
                }
                $content = trim(preg_replace('/^[A-D]\s*[.)=:]\s*/iu', '', $checkboxMatch[1]));
                if ($content !== '') {
                    $active['options'][] = ['option_key' => chr(65 + count($active['options'])), 'content' => $content];
                }
                continue;
            }
            $inline = $this->extractInlineOptions($text);
            if ($inline !== null) {
                if ($inline['stem'] !== '') {
                    $active['stem_parts'][] = $inline['stem'];
                }
                $active['options'] = array_merge($active['options'], $inline['options']);
                continue;
            }
            if (preg_match('/^([A-D])\s*[.)=:]\s*(.+)$/iu', $text, $optionMatch)) {
                $active['options'][] = [
                    'option_key' => strtoupper($optionMatch[1]),
                    'content' => trim($optionMatch[2]),
                ];
                continue;
            }
            $active['stem_parts'][] = $text;
        }

        if ($active !== null) {
            $rawQuestions[] = $active;
        }
        if (count($rawQuestions) > self::MAX_QUESTIONS) {
            throw ValidationException::withMessages(['docx' => 'Maksimal 500 soal dalam satu dokumen DOCX.']);
        }

        $questions = [];
        foreach ($rawQuestions as $raw) {
            $number = $raw['number'];
            $type = $this->resolveType($raw);
            $stem = trim(implode("\n", $raw['stem_parts']));
            $errors = [];
            $warningsForQuestion = [];
            foreach (($raw['unsupported_blocks'] ?? []) as $unsupportedBlock) {
                $errors[] = $unsupportedBlock;
            }
            foreach (($raw['stimulus']['unsupported_blocks'] ?? []) as $unsupportedStimulusBlock) {
                $errors[] = 'Stimulus bersama belum dapat diimpor penuh: ' . $unsupportedStimulusBlock;
            }
            if (!empty($raw['has_table_content']) || !empty($raw['stimulus']['has_table_content'])) {
                $warningsForQuestion[] = 'Konten berasal dari tabel Word. Sistem membaca urutan baris dan sel; periksa kembali susunan tabel pada preview.';
            }
            if (!empty($raw['table_unclassified'])) {
                $errors[] = 'Struktur tabel belum dapat diklasifikasikan secara otomatis. Periksa dan koreksi isi tabel pada preview.';
            }
            if (!empty($raw['inferred_type'])) {
                $errors[] = 'Jenis soal dikenali dari pola isi tanpa penanda eksplisit. Konfirmasi jenis soal pada preview.';
            }
            if ($stem === '') {
                $errors[] = 'Teks soal belum ditemukan.';
            }
            if (trim((string) ($raw['hint'] ?? '')) === '' && in_array($type, ['MULTIPLE_CHOICE', 'MULTIPLE_RESPONSE'], true)) {
                $errors[] = 'Jenis PG atau PGK belum dapat dipastikan dari struktur dokumen. Periksa jenis soal pada preview.';
            }
            if ($type === 'TRUE_FALSE') {
                $raw['options'] = [
                    ['option_key' => 'A', 'content' => 'BENAR', 'is_correct' => false],
                    ['option_key' => 'B', 'content' => 'SALAH', 'is_correct' => false],
                ];
            }
            if ($type === 'MULTIPLE_CHOICE' || $type === 'MULTIPLE_RESPONSE') {
                if (count($raw['options']) < 2 || count($raw['options']) > 4) {
                    $errors[] = 'Pilihan ganda harus memiliki 2 sampai 4 opsi A-D.';
                }
                $keys = array_column($raw['options'], 'option_key');
                if (count($keys) !== count(array_unique($keys))) {
                    $errors[] = 'Label opsi duplikat.';
                }
            } elseif ($type === 'TRUE_FALSE_GROUP') {
                if (count($raw['statements']) !== 3) {
                    $errors[] = 'Benar/Salah kelompok wajib memiliki tepat tiga pernyataan.';
                }
                foreach ($raw['statements'] as &$statement) {
                    $statement['is_true'] = null;
                }
                unset($statement);
            } elseif ($type === 'TRUE_FALSE') {
                // Answer keys are intentionally not extracted by the parser.
            } elseif ($type === 'ESSAY') {
                if ($raw['options'] !== []) {
                    $errors[] = 'Soal uraian tidak boleh memiliki opsi pilihan ganda.';
                }
            } else {
                $errors[] = 'Jenis soal belum didukung untuk import otomatis. Konten dipertahankan untuk pemeriksaan Guru.';
            }

            $blockedFeatures = array_values(array_unique(array_merge(
                $raw['unsupported_features'] ?? [],
                $raw['stimulus']['unsupported_features'] ?? []
            )));
            $questions[] = [
                'source_number' => $number,
                'source_section' => $raw['source_section'] ?? null,
                'table_rows' => array_values($raw['table_rows'] ?? []),
                'blocked_features' => $blockedFeatures,
                'question_type' => $type,
                'stem' => $stem,
                'options' => array_map(fn (array $option): array => [
                    'option_key' => $option['option_key'],
                    'content' => $option['content'],
                    'is_correct' => false,
                ], $raw['options']),
                'statements' => $raw['statements'],
                'difficulty' => 'MEDIUM',
                'stimulus' => $raw['stimulus'],
                'source_locator' => $raw['locator'],
                'errors' => array_values(array_unique($errors)),
                'warnings' => $warningsForQuestion,
                'status' => $errors === [] ? 'READY' : 'NEEDS_REVIEW',
            ];
        }

        $sectionCounts = [];
        foreach ($questions as $question) {
            $section = $question['source_section'] ?? null;
            if ($section !== null && $section !== '') {
                $sectionCounts[$section] = ($sectionCounts[$section] ?? 0) + 1;
            }
        }
        $sections = [];
        foreach ($sectionCounts as $title => $count) {
            $sections[] = ['title' => $title, 'count' => $count];
        }
        $requiresSectionSplit = count($sections) > 1;
        if ($requiresSectionSplit) {
            $warnings[] = 'Dokumen memuat beberapa bagian mata pelajaran. Pisahkan per bagian agar soal tidak tercampur ke satu penugasan.';
        }

        return [
            'file_sha256' => $fileHash,
            'total' => count($questions),
            'valid' => count(array_filter($questions, fn (array $question) => $question['status'] === 'READY')),
            'invalid' => count(array_filter($questions, fn (array $question) => $question['status'] !== 'READY')),
            'questions' => $questions,
            'sections' => $sections,
            'requires_section_split' => $requiresSectionSplit,
            'blocked_source_numbers' => array_values(array_unique(array_map(
                fn (array $question): int => (int) $question['source_number'],
                array_filter($questions, fn (array $question): bool => ($question['blocked_features'] ?? []) !== [])
            ))),
            'excluded_content' => $excluded,
            'warnings' => $warnings,
        ];
    }

    private function detectSubjectSection(string $text): ?string
    {
        $value = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $text) ?? $text));
        $value = preg_replace('/^[A-Z0-9][.)]\s*/u', '', $value) ?? $value;
        if (preg_match('/^MATA PELAJARAN\s*[:=]\s*(.+)$/u', $value, $match) === 1) {
            return trim($match[1]);
        }
        $subjects = [
            'MATEMATIKA', 'BAHASA INDONESIA', 'BAHASA ARAB', 'IPAS', 'IPA', 'IPS',
            'PENDIDIKAN AGAMA ISLAM', 'AL-QURAN HADIS', 'QURAN HADIS', 'AKIDAH AKHLAK',
            'FIKIH', 'FIQIH', 'SKI', 'PJOK', 'PENDIDIKAN JASMANI', 'PENDIDIKAN PANCASILA',
            'PPKN', 'SENI BUDAYA',
        ];
        foreach ($subjects as $subject) {
            if ($value === $subject || preg_match('/^' . preg_quote($subject, '/') . '\s+KELAS\s+[1-6]$/u', $value) === 1) {
                return $value;
            }
        }
        return null;
    }

    private function isTrueFalseHeaderRow(array $cells): bool
    {
        return count($cells) >= 3
            && str_contains(mb_strtolower((string) ($cells[0] ?? '')), 'pernyataan')
            && str_contains(mb_strtolower((string) ($cells[1] ?? '')), 'benar')
            && str_contains(mb_strtolower((string) ($cells[2] ?? '')), 'salah');
    }

    private function isTrueFalseGroupHint(?string $hint): bool
    {
        $hint = mb_strtoupper((string) $hint);
        return str_contains($hint, 'B/S') || str_contains($hint, 'BENAR/SALAH') || preg_match('/\bBS\b/u', $hint) === 1;
    }

    private function isMultipleResponseHint(?string $hint): bool
    {
        $hint = mb_strtoupper((string) $hint);
        return str_contains($hint, 'PGK') || str_contains($hint, 'MULTIPLE RESPONSE');
    }

    private function extractTableOptions(array $cells, ?string $hint): array
    {
        $normalizedHint = mb_strtoupper((string) $hint);
        $isChoiceType = $normalizedHint === 'PG'
            || $this->isMultipleResponseHint($normalizedHint)
            || str_contains($normalizedHint, 'PILIHAN GANDA');
        if (!$isChoiceType) {
            return [];
        }

        $hasExplicitOptionMarkers = false;
        foreach ($cells as $cell) {
            $value = trim((string) $cell);
            if (preg_match('/^(?:[☐□☑✓]\s*\S|[A-D]\s*[.)=:]\s*\S)/iu', $value) === 1) {
                $hasExplicitOptionMarkers = true;
                break;
            }
        }
        if (!$hasExplicitOptionMarkers) {
            return [];
        }

        $options = [];
        foreach ($cells as $cell) {
            $value = trim((string) $cell);
            if ($value === '') {
                continue;
            }
            if (preg_match('/^[☐□☑✓]\s*(.+)$/u', $value, $checkboxMatch) === 1) {
                $content = trim($checkboxMatch[1]);
                if (preg_match('/^([A-D])\s*[.)=:]\s*(.+)$/iu', $content, $labelMatch) === 1) {
                    $options[] = ['option_key' => strtoupper($labelMatch[1]), 'content' => trim($labelMatch[2])];
                } elseif ($content !== '') {
                    $options[] = ['option_key' => chr(65 + count($options)), 'content' => $content];
                }
                continue;
            }
            if (preg_match('/^([A-D])\s*[.)=:]\s*(.+)$/iu', $value, $optionMatch) === 1) {
                $options[] = [
                    'option_key' => strtoupper($optionMatch[1]),
                    'content' => trim($optionMatch[2]),
                ];
            }
        }

        return $options;
    }

    private function extractInlineOptions(string $text): ?array
    {
        $count = preg_match_all('/(?<!\S)([A-D])\s*[.)=:]\s*/iu', $text, $matches, PREG_OFFSET_CAPTURE);
        if ($count < 2) {
            return null;
        }
        $firstOffset = $matches[0][0][1];
        $stem = trim(substr($text, 0, $firstOffset));
        $options = [];
        for ($index = 0; $index < $count; $index++) {
            $start = $matches[0][$index][1] + strlen($matches[0][$index][0]);
            $end = $index + 1 < $count ? $matches[0][$index + 1][1] : strlen($text);
            $content = trim(substr($text, $start, $end - $start));
            if ($content !== '') {
                $options[] = ['option_key' => strtoupper($matches[1][$index][0]), 'content' => $content];
            }
        }
        return $options === [] ? null : ['stem' => $stem, 'options' => $options];
    }

    private function resolveType(array $raw): string
    {
        $hint = strtoupper((string) ($raw['hint'] ?? ''));
        if ($hint !== '') {
            if (str_contains($hint, 'B/S TUNGGAL') || str_contains($hint, 'BENAR/SALAH TUNGGAL')) {
                return 'TRUE_FALSE';
            }
            if (str_contains($hint, 'B/S') || str_contains($hint, 'BENAR/SALAH') || $hint === 'BS') {
                return 'TRUE_FALSE_GROUP';
            }
            if (str_contains($hint, 'PGK') || str_contains($hint, 'KOMPLEKS')) {
                return 'MULTIPLE_RESPONSE';
            }
            if ($hint === 'PG' || str_contains($hint, 'PILIHAN GANDA')) {
                return 'MULTIPLE_CHOICE';
            }
            if (str_contains($hint, 'MATCH') || str_contains($hint, 'JODOHKAN')) {
                return 'MATCHING';
            }
            if (str_contains($hint, 'ESSAY') || str_contains($hint, 'URAIAN')) {
                return 'ESSAY';
            }
            if (str_contains($hint, 'ISIAN') || str_contains($hint, 'SHORT ANSWER')) {
                return 'SHORT_ANSWER';
            }
            if (str_contains($hint, 'LENGKAPI') || str_contains($hint, 'URUT')) {
                return 'UNSUPPORTED';
            }

            return 'UNSUPPORTED';
        }

        // Without an explicit marker, do not guess PG, PGK, or another question type.
        return 'UNSUPPORTED';
    }

    private function answerTokens(string $answer): array
    {
        $answer = trim($answer);
        if ($answer === '') {
            return [];
        }

        preg_match_all('/\b(?:BENAR|SALAH|TRUE|FALSE|[A-D]|B|S)\b/iu', $answer, $matches);
        return array_map(
            fn (string $token): string => strtoupper($token),
            $matches[0] ?? []
        );
    }

    private function booleanToken(string $token): ?bool
    {
        return match (strtoupper(trim($token))) {
            'B', 'BENAR', 'TRUE' => true,
            'S', 'SALAH', 'FALSE' => false,
            default => null,
        };
    }
}
