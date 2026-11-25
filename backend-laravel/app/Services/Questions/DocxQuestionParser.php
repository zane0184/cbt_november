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
            if ($zip->numFiles < 1 || $zip->numFiles > 500) {
                throw ValidationException::withMessages(['docx' => 'Paket Word memiliki jumlah bagian yang tidak wajar.']);
            }
            $totalUncompressedBytes = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                $entrySize = (int) ($stat['size'] ?? 0);
                $totalUncompressedBytes += $entrySize;
                if ($entrySize > self::MAX_XML_BYTES || $totalUncompressedBytes > 30 * 1024 * 1024) {
                    throw ValidationException::withMessages(['docx' => 'Isi paket Word melebihi batas keamanan pemrosesan.']);
                }
            }

            $xml = $zip->getFromName('word/document.xml');
            if (!is_string($xml) || strlen($xml) > self::MAX_XML_BYTES) {
                throw ValidationException::withMessages(['docx' => 'Struktur dokumen Word tidak ditemukan atau terlalu besar.']);
            }

            $blocks = $this->readOrderedBlocks($xml);
        } finally {
            $zip->close();
        }

        return $this->buildQuestions($blocks, hash_file('sha256', $path));
    }

    private function readOrderedBlocks(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            if (!$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT)) {
                throw ValidationException::withMessages(['docx' => 'XML dokumen Word tidak dapat dibaca.']);
            }

            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/officeDocument/2006/math');
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
                    $rows = $xpath->query('./w:tr', $node);
                    foreach ($rows as $rowIndex => $row) {
                        $cells = $xpath->query('./w:tc', $row);
                        foreach ($cells as $cellIndex => $cell) {
                            $paragraphs = $xpath->query('.//w:p', $cell);
                            foreach ($paragraphs as $paragraphIndex => $paragraph) {
                                $text = $this->paragraphText($paragraph, $xpath);
                                $hasMedia = $xpath->query('.//w:drawing | .//w:pict', $paragraph)->length > 0;
                                $hasMath = $xpath->query('.//m:oMath | .//m:oMathPara', $paragraph)->length > 0;
                                if (trim($text) === '' && !$hasMedia && !$hasMath) {
                                    continue;
                                }
                                $blocks[] = [
                                    'text' => $text,
                                    'has_media' => $hasMedia,
                                    'has_math' => $hasMath,
                                    'locator' => [
                                        'sequence' => ++$sequence,
                                        'kind' => 'table-cell',
                                        'table' => $tableIndex,
                                        'row' => $rowIndex + 1,
                                        'cell' => $cellIndex + 1,
                                        'paragraph' => $paragraphIndex + 1,
                                    ],
                                ];
                            }
                        }
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
        $parts = [];
        foreach ($paragraph->childNodes as $node) {
            if ($node instanceof DOMElement && $node->localName === 'r') {
                foreach ($node->childNodes as $runNode) {
                    if (!$runNode instanceof DOMElement) {
                        continue;
                    }
                    if ($runNode->localName === 't') {
                        $parts[] = $runNode->textContent;
                    } elseif ($runNode->localName === 'tab') {
                        $parts[] = "\t";
                    } elseif ($runNode->localName === 'br' || $runNode->localName === 'cr') {
                        $parts[] = "\n";
                    }
                }
            } elseif ($node instanceof DOMElement && $node->localName === 'hyperlink') {
                $parts[] = $node->textContent;
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
        $answerKeys = [];
        $answerSection = false;
        $active = null;
        $stimulus = null;
        $excluded = [];
        $warnings = [];

        foreach ($blocks as $block) {
            $text = trim($block['text']);
            $blockUnsupported = [];
            if (!empty($block['has_media'])) {
                $blockUnsupported[] = 'Dokumen memuat gambar/diagram pada blok ini; import media belum diaktifkan dan item harus ditinjau manual.';
            }
            if (!empty($block['has_math'])) {
                $blockUnsupported[] = 'Dokumen memuat objek formula matematika Word pada blok ini; konversi formula belum diaktifkan.';
            }
            $isQuestionStart = preg_match('/^(?:SOAL\\s+)?\\d{1,3}\\s*[.):]/iu', $text) === 1;
            $isStimulusStart = preg_match('/^STIMULUS(?:\\s*\\[[A-Z0-9_-]+\\])?\\s*:/iu', $text) === 1;
            if (($block['locator']['kind'] ?? null) === 'table-cell') {
                if ($active !== null) {
                    $active['has_table_content'] = true;
                } elseif ($stimulus !== null) {
                    $stimulus['has_table_content'] = true;
                }
            }
            if ($blockUnsupported !== [] && !$isQuestionStart && !$isStimulusStart) {
                if ($active !== null) {
                    $active['unsupported_blocks'] = array_merge($active['unsupported_blocks'] ?? [], $blockUnsupported);
                } elseif ($stimulus !== null) {
                    $stimulus['unsupported_blocks'] = array_merge($stimulus['unsupported_blocks'] ?? [], $blockUnsupported);
                }
            }
            if ($text === '') {
                continue;
            }

            if (preg_match('/^(?:KUNCI\s*JAWABAN|DAFTAR\s*JAWABAN|ANSWER\s*KEY)\s*:?\s*$/iu', $text)) {
                $answerSection = true;
                $active = null;
                continue;
            }

            if (preg_match('/^(?:AKHIR\s+STIMULUS|END\s+STIMULUS)\s*$/iu', $text)) {
                $stimulus = null;
                continue;
            }

            if (preg_match('/^(?:STIMULUS)(?:\s*\[([A-Z0-9_-]+)\])?\s*:?\s*(.*)$/iu', $text, $match)) {
                $stimulus = [
                    'id' => strtoupper($match[1] ?? ('STIMULUS-' . ($block['locator']['sequence'] ?? count($rawQuestions) + 1))),
                    'content' => trim($match[2] ?? ''),
                    'source' => $block['locator'],
                    'unsupported_blocks' => $blockUnsupported,
                ];
                continue;
            }

            if ($answerSection && preg_match('/^(?:NO\.?\s*)?(\d{1,3})\s*[.):=-]\s*(.+)$/iu', $text, $match)) {
                $answerKeys[(int) $match[1]] = trim($match[2]);
                continue;
            }

            if (preg_match('/^(?:SOAL\s+)?(\d{1,3})\s*[.):]\s*(.*)$/iu', $text, $match)) {
                if ($active !== null) {
                    $rawQuestions[] = $active;
                }
                $header = trim($match[2]);
                $hint = null;
                if (preg_match('/\[([A-Z0-9_ \/-]{2,40})\]/iu', $header, $typeMatch)) {
                if (preg_match('/\[([A-Z0-9_ \/-]{2,40})\]/iu', $header, $typeMatch)) {
                if (preg_match('/\[([A-Z0-9_ \/-]{2,40})\]/iu', $header, $typeMatch)) {
                }
                $inlineKey = null;
                if (preg_match('/(?:KUNCI|JAWABAN)\s*[:=]\s*(.+)$/iu', $header, $keyMatch)) {
                    $inlineKey = trim($keyMatch[1]);
                    $header = trim(preg_replace('/(?:KUNCI|JAWABAN)\s*[:=]\s*.+$/iu', '', $header));
                }
                $active = [
                    'number' => (int) $match[1],
                    'stem_parts' => $header !== '' ? [$header] : [],
                    'options' => [],
                    'statements' => [],
                    'hint' => $hint,
                    'answer' => $inlineKey,
                    'locator' => $block['locator'],
                    'stimulus' => $stimulus,
                    'unsupported_blocks' => $blockUnsupported,
                    'has_table_content' => ($block['locator']['kind'] ?? null) === 'table-cell',
                ];
                $answerSection = false;
                continue;
            }

            if ($active === null) {
                if ($stimulus !== null && !preg_match('/^(?:SOAL\\s+)?\\d{1,3}\\s*[.):]/iu', $text)) {
                    $stimulus['content'] = trim($stimulus['content'] . "\n" . $text);
                    continue;
                }
                if (preg_match('/^(?:KISI[- ]KISI|IDENTITAS PENYUSUN|LEMBAR PENGESAHAN|CAPAIAN PEMBELAJARAN|TUJUAN PEMBELAJARAN)\b/iu', $text)) {
                    $excluded[] = ['text' => mb_substr($text, 0, 180), 'locator' => $block['locator'], 'reason' => 'Konten administrasi/kisi-kisi tidak diimpor sebagai soal.'];
                }
                continue;
            }

            if (preg_match('/^(?:KUNCI|JAWABAN)\s*[:=]\s*(.+)$/iu', $text, $keyMatch)) {
                $active['answer'] = trim($keyMatch[1]);
                continue;
            }

            if (preg_match('/^([A-D])\s*[.)=:]\s*(.+)$/iu', $text, $optionMatch)) {
                $active['options'][] = [
                    'option_key' => strtoupper($optionMatch[1]),
                    'content' => trim($optionMatch[2]),
                ];
                continue;
            }

            if (preg_match('/^([a-c])\s*[.)=:]\s*(.+)$/iu', $text, $statementMatch)
                && ($active['hint'] && !str_contains($active['hint'], 'TUNGGAL') && (str_contains($active['hint'], 'B/S') || str_contains($active['hint'], 'BENAR/SALAH') || str_contains($active['hint'], 'BS')))) {
                $active['statements'][] = [
                    'id' => 's' . strtolower($statementMatch[1]),
                    'text' => trim($statementMatch[2]),
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
            $answerText = $raw['answer'] ?? $answerKeys[$number] ?? null;
            $type = $this->resolveType($raw, $answerText);
            $stem = trim(implode("\n", $raw['stem_parts']));
            $errors = [];
            $warningsForQuestion = [];
            foreach (($raw['unsupported_blocks'] ?? []) as $unsupportedBlock) {
                $errors[] = $unsupportedBlock;
            }
            foreach (($raw['stimulus']['unsupported_blocks'] ?? []) as $unsupportedStimulusBlock) {
                $errors[] = 'Stimulus bersama belum dapat diimpor penuh: ' . $unsupportedStimulusBlock;
            }
            if (!empty($raw['has_table_content']) || !empty($raw['stimulus']['has_table_content']) || (($raw['stimulus']['source']['kind'] ?? null) === 'table-cell')) {
                $warningsForQuestion[] = 'Konten berasal dari tabel Word. Sistem membaca urutan teks per sel; periksa kembali susunan tabel pada preview.';
            }

            if ($stem === '') {
                $errors[] = 'Teks soal belum ditemukan.';
            }

            $answerTokens = $this->answerTokens((string) $answerText);

            if ($type === 'TRUE_FALSE') {
                $boolean = $this->booleanToken($answerTokens[0] ?? '');
                $raw['options'] = [
                    ['option_key' => 'A', 'content' => 'BENAR', 'is_correct' => $boolean === true],
                    ['option_key' => 'B', 'content' => 'SALAH', 'is_correct' => $boolean === false],
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
                if ($answerTokens === []) {
                    $errors[] = 'Kunci jawaban tidak ditemukan.';
                }
                $allowed = array_column($raw['options'], 'option_key');
                foreach ($answerTokens as $token) {
                    if (!in_array($token, $allowed, true)) {
                        $errors[] = "Kunci jawaban {$token} tidak cocok dengan opsi yang tersedia.";
                    }
                }
                if ($type === 'MULTIPLE_CHOICE' && count($answerTokens) !== 1) {
                    $errors[] = 'PG harus memiliki tepat satu kunci jawaban.';
                }
            } elseif ($type === 'TRUE_FALSE_GROUP') {
                if (count($raw['statements']) !== 3) {
                    $errors[] = 'Benar/Salah kelompok wajib memiliki tepat tiga pernyataan berlabel a, b, c.';
                }
                if (count($answerTokens) !== 3) {
                    $errors[] = 'Kunci Benar/Salah kelompok wajib memuat tiga nilai Benar/Salah.';
                }
                $normalized = [];
                foreach ($answerTokens as $token) {
                    $value = $this->booleanToken($token);
                    if ($value === null) {
                        $errors[] = 'Kunci Benar/Salah hanya menerima B/S, BENAR/SALAH, atau TRUE/FALSE.';
                    } else {
                        $normalized[] = $value;
                    }
                }
                if (count($normalized) === 3) {
                    foreach ($raw['statements'] as $index => &$statement) {
                        $statement['is_true'] = $normalized[$index];
                    }
                    unset($statement);
                }
            } elseif ($type === 'TRUE_FALSE') {
                $value = $this->booleanToken($answerTokens[0] ?? '');
                if ($value === null) {
                    $errors[] = 'Soal Benar/Salah tunggal memerlukan kunci BENAR atau SALAH.';
                }
            } elseif ($type === 'ESSAY') {
                if ($raw['options'] !== []) {
                    $errors[] = 'Soal uraian tidak boleh memiliki opsi pilihan ganda.';
                }
            } else {
                $errors[] = 'Jenis soal belum didukung untuk import otomatis. Pilih tipe PG, PGK, atau Benar/Salah.';
            }

            if ($type === 'MULTIPLE_RESPONSE' && $answerTokens === []) {
                $errors[] = 'PG Kompleks memerlukan satu atau lebih kunci jawaban.';
            }

            if (count($raw['options']) > 0 && $type === 'TRUE_FALSE_GROUP') {
                $warningsForQuestion[] = 'Opsi pilihan ganda diabaikan karena item terdeteksi sebagai kelompok Benar/Salah.';
            }

            $questions[] = [
                'source_number' => $number,
                'question_type' => $type,
                'stem' => $stem,
                'options' => array_map(function (array $option) use ($answerTokens): array {
                    return [
                        'option_key' => $option['option_key'],
                        'content' => $option['content'],
                        'is_correct' => array_key_exists('is_correct', $option) ? (bool) $option['is_correct'] : in_array($option['option_key'], $answerTokens, true),
                    ];
                }, $raw['options']),
                'statements' => $raw['statements'],
                'answer_tokens' => $answerTokens,
                'points' => 1,
                'difficulty' => 'MEDIUM',
                'stimulus' => $raw['stimulus'],
                'source_locator' => $raw['locator'],
                'errors' => array_values(array_unique($errors)),
                'warnings' => $warningsForQuestion,
                'status' => $errors === [] ? 'READY' : 'NEEDS_REVIEW',
            ];
        }

        return [
            'file_sha256' => $fileHash,
            'total' => count($questions),
            'valid' => count(array_filter($questions, fn (array $question) => $question['status'] === 'READY')),
            'invalid' => count(array_filter($questions, fn (array $question) => $question['status'] !== 'READY')),
            'questions' => $questions,
            'excluded_content' => $excluded,
            'warnings' => $warnings,
        ];
    }

    private function resolveType(array $raw, ?string $answer): string
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
            if (str_contains($hint, 'LENGKAPI')) {
                return 'FILL_BLANK';
            }
            if (str_contains($hint, 'URUT')) {
                return 'ORDERING';
            }

            return 'UNSUPPORTED';
        }

        $tokens = $this->answerTokens((string) $answer);
        if (count($tokens) > 1) {
            return 'MULTIPLE_RESPONSE';
        }

        return 'MULTIPLE_CHOICE';
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
