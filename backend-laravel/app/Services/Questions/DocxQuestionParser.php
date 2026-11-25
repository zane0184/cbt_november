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
                    if (trim($text) !== '') {
                        $blocks[] = ['text' => $text, 'locator' => ['sequence' => ++$sequence, 'kind' => 'paragraph']];
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
                            $parts = [];
                            foreach ($paragraphs as $paragraph) {
                                $part = trim($this->paragraphText($paragraph, $xpath));
                                if ($part !== '') {
                                    $parts[] = $part;
                                }
                            }
                            $text = trim(implode("\n", $parts));
                            if ($text !== '') {
                                $blocks[] = [
                                    'text' => $text,
                                    'locator' => [
                                        'sequence' => ++$sequence,
                                        'kind' => 'table-cell',
                                        'table' => $tableIndex,
                                        'row' => $rowIndex + 1,
                                        'cell' => $cellIndex + 1,
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
                if (preg_match('/\[(PGK|PG\s*KOMPLEKS|PILIHAN\s+GANDA\s+KOMPLEKS|PG|PILIHAN\s+GANDA|B\/S|BENAR\/SALAH|BS|ESSAY|URAIAN)\]/iu', $header, $typeMatch)) {
                    $hint = strtoupper(preg_replace('/\s+/', ' ', $typeMatch[1]));
                    $header = trim(str_replace($typeMatch[0], '', $header));
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
                && ($active['hint'] && (str_contains($active['hint'], 'B/S') || str_contains($active['hint'], 'BENAR/SALAH') || str_contains($active['hint'], 'BS')))) {
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
        if (str_contains($hint, 'B/S TUNGGAL') || str_contains($hint, 'BENAR/SALAH TUNGGAL')) {
            return 'TRUE_FALSE';
        }
        if (str_contains($hint, 'B/S') || str_contains($hint, 'BENAR/SALAH') || str_contains($hint, 'BS')) {
            return 'TRUE_FALSE_GROUP';
        }
        if (str_contains($hint, 'PGK') || str_contains($hint, 'KOMPLEKS')) {
            return 'MULTIPLE_RESPONSE';
        }
        if (str_contains($hint, 'PG') || str_contains($hint, 'PILIHAN GANDA')) {
            return 'MULTIPLE_CHOICE';
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
