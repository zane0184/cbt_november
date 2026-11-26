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
            $relationships = $this->readDocumentRelationshipMap($zip);
            $blocks = $this->readOrderedBlocks($xml, $relationships, $zip);
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


    /**
     * Resolve internal document relationships without extracting package files.
     * External relationships are rejected by validateRelationships before this method.
     */
    private function readDocumentRelationshipMap(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('word/_rels/document.xml.rels');
        if (!is_string($xml)) {
            return [];
        }

        $this->assertSafeXml($xml);
        $document = new DOMDocument();
        if (!$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT)) {
            throw ValidationException::withMessages(['docx' => 'Relasi dokumen Word tidak dapat dibaca.']);
        }

        $xpath = new \DOMXPath($document);
        $relationships = [];
        foreach ($xpath->query('//*[local-name()="Relationship"]') as $relationship) {
            $id = trim($relationship->getAttribute('Id'));
            $target = trim($relationship->getAttribute('Target'));
            $type = trim($relationship->getAttribute('Type'));
            if ($id === '' || $target === '' || strcasecmp($relationship->getAttribute('TargetMode'), 'External') === 0) {
                continue;
            }

            $resolvedTarget = $this->resolveDocumentRelationshipTarget($target);
            $relationships[$id] = [
                'target' => $resolvedTarget,
                'type' => $type,
                'is_image' => str_ends_with(strtolower($type), '/image'),
                'resolved' => $resolvedTarget !== null && $zip->locateName($resolvedTarget) !== false,
            ];
        }

        return $relationships;
    }

    private function resolveDocumentRelationshipTarget(string $target): ?string
    {
        $target = str_replace('\\', '/', trim($target));
        if ($target === '' || str_contains($target, "\0")
            || preg_match('/^[a-z][a-z0-9+.-]*:/i', $target) === 1) {
            return null;
        }

        $segments = str_starts_with($target, '/')
            ? explode('/', ltrim($target, '/'))
            : array_merge(['word'], explode('/', $target));
        $normalized = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if ($normalized === []) {
                    return null;
                }
                array_pop($normalized);
                continue;
            }
            $normalized[] = $segment;
        }

        $path = implode('/', $normalized);
        return $path !== '' ? $path : null;
    }

    private function assertSafeXml(string $xml): void
    {
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $xml) === 1) {
            throw ValidationException::withMessages(['docx' => 'Deklarasi DTD atau ENTITY dalam DOCX tidak diizinkan.']);
        }
    }

    private function readOrderedBlocks(string $xml, array $relationships, ZipArchive $zip): array
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
            $xpath->registerNamespace('v', 'urn:schemas-microsoft-com:vml');
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
                    $sourceFragments = $this->extractSourceFragments($node, $xpath, $relationships, $zip);
                    if (trim($text) !== '' || $hasMedia || $hasMath) {
                        $blocks[] = [
                            'text' => $text,
                            'has_media' => $hasMedia,
                            'has_math' => $hasMath,
                            'text_direction' => $this->detectTextDirection($node, $xpath, $text),
                            'source_fragments' => $sourceFragments,
                            'locator' => ['sequence' => ++$sequence, 'kind' => 'paragraph'],
                        ];
                    }
                    continue;
                }
                if ($node->localName === 'tbl') {
                    $tableIndex = ++$tableSequence;
                    $tableHasNestedTables = $xpath->query('.//w:tbl', $node)->length > 0;
                    foreach ($xpath->query('./w:tr', $node) as $rowIndex => $row) {
                        $cells = [];
                        $rowHasMedia = false;
                        $rowHasMath = false;
                        $rowSourceFragments = [];
                        foreach ($xpath->query('./w:tc', $row) as $cell) {
                            $paragraphTexts = [];
                            foreach ($xpath->query('.//w:p', $cell) as $paragraph) {
                                $cellText = $this->paragraphText($paragraph, $xpath);
                                if (trim($cellText) !== '') {
                                    $paragraphTexts[] = $cellText;
                                }
                                $rowHasMedia = $rowHasMedia || $xpath->query('.//w:drawing | .//w:pict', $paragraph)->length > 0;
                                $rowHasMath = $rowHasMath || $xpath->query('.//m:oMath | .//m:oMathPara', $paragraph)->length > 0;
                                $rowSourceFragments = array_merge(
                                    $rowSourceFragments,
                                    $this->extractSourceFragments($paragraph, $xpath, $relationships, $zip)
                                );
                            }
                            $cells[] = trim(implode("\n", $paragraphTexts));
                        }
                        $rowText = implode(' | ', $cells);
                        $rowXml = $row->ownerDocument?->saveXML($row) ?: '';
                        if ($rowXml !== '') {
                            $rowFragment = [
                                'kind' => 'WORD_TABLE_ROW_OOXML',
                                'xml' => $rowXml,
                                'xml_sha256' => hash('sha256', $rowXml),
                                'has_nested_table' => $tableHasNestedTables,
                            ];
                            if ($tableHasNestedTables) {
                                $rowFragment['table_index'] = $tableIndex;
                                $rowFragment['row_index'] = $rowIndex + 1;
                                $rowFragment['structure'] = $this->extractNestedTableRowStructure($row, $xpath, 0);
                            }
                            $rowSourceFragments[] = $rowFragment;
                        }
                        if (trim($rowText) === '' && !$rowHasMedia && !$rowHasMath) {
                            continue;
                        }
                        $blocks[] = [
                            'text' => $rowText,
                            'cells' => $cells,
                            'has_media' => $rowHasMedia,
                            'has_math' => $rowHasMath,
                            'has_nested_table' => $tableHasNestedTables,
                            'text_direction' => $this->detectTextDirection($row, $xpath, $rowText),
                            'source_fragments' => $rowSourceFragments,
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


    private function extractNestedTableRowStructure(DOMElement $row, \DOMXPath $xpath, int $depth): array
    {
        $cells = [];
        foreach ($xpath->query('./w:tc', $row) as $cell) {
            $content = [];
            foreach ($cell->childNodes as $child) {
                if (!$child instanceof DOMElement) {
                    continue;
                }
                if ($child->localName === 'p') {
                    $text = $this->paragraphText($child, $xpath);
                    if ($text !== '') {
                        $content[] = [
                            'type' => 'paragraph',
                            'text' => $text,
                            'direction' => $this->detectTextDirection($child, $xpath, $text),
                        ];
                    }
                } elseif ($child->localName === 'tbl') {
                    $content[] = [
                        'type' => 'table',
                        'rows' => $this->extractNestedTableRows($child, $xpath, $depth + 1),
                    ];
                } else {
                    $content = array_merge($content, $this->extractNestedCellContent($child, $xpath, $depth));
                }
            }
            $cells[] = ['content' => $content];
        }

        return ['cells' => $cells];
    }

    private function extractNestedTableRows(DOMElement $table, \DOMXPath $xpath, int $depth): array
    {
        if ($depth > 4) {
            return [[
                'cells' => [[
                    'content' => [[
                        'type' => 'paragraph',
                        'text' => '[Kedalaman tabel bersarang melebihi batas preview]',
                        'direction' => 'auto',
                    ]],
                ]],
            ]];
        }

        $rows = [];
        foreach ($xpath->query('./w:tr', $table) as $row) {
            $rows[] = $this->extractNestedTableRowStructure($row, $xpath, $depth);
        }

        return $rows;
    }

    private function extractNestedCellContent(DOMNode $node, \DOMXPath $xpath, int $depth): array
    {
        $content = [];
        foreach ($node->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }
            if ($child->localName === 'p') {
                $text = $this->paragraphText($child, $xpath);
                if ($text !== '') {
                    $content[] = [
                        'type' => 'paragraph',
                        'text' => $text,
                        'direction' => $this->detectTextDirection($child, $xpath, $text),
                    ];
                }
            } elseif ($child->localName === 'tbl') {
                $content[] = [
                    'type' => 'table',
                    'rows' => $this->extractNestedTableRows($child, $xpath, $depth + 1),
                ];
            } else {
                $content = array_merge($content, $this->extractNestedCellContent($child, $xpath, $depth));
            }
        }

        return $content;
    }

    private function extractSourceFragments(DOMElement $node, \DOMXPath $xpath, array $relationships, ZipArchive $zip): array
    {
        $fragments = [];
        $paragraphXml = $node->ownerDocument?->saveXML($node) ?: '';
        if ($paragraphXml !== '') {
            $fragments[] = [
                'kind' => 'WORD_PARAGRAPH_OOXML',
                'xml' => $paragraphXml,
                'xml_sha256' => hash('sha256', $paragraphXml),
            ];
        }

        foreach ($xpath->query('.//w:drawing | .//w:pict', $node) as $mediaNode) {
            $relationshipId = null;
            $relationshipAttributes = $xpath->query(
                './/a:blip/@r:embed | .//a:blip/@r:link | .//v:imagedata/@r:id',
                $mediaNode
            );
            if ($relationshipAttributes->length > 0) {
                $relationshipId = trim((string) $relationshipAttributes->item(0)->nodeValue);
            }

            $relationship = $relationshipId !== null ? ($relationships[$relationshipId] ?? null) : null;
            $target = (string) ($relationship['target'] ?? '');
            $relationshipResolved = (bool) (($relationship['is_image'] ?? false) && ($relationship['resolved'] ?? false));
            $imageBytes = $relationshipResolved && $target !== '' ? $zip->getFromName($target) : false;
            $imageMime = is_string($imageBytes) ? (new \finfo(FILEINFO_MIME_TYPE))->buffer($imageBytes) : null;
            $imageInfo = is_string($imageBytes) ? @getimagesizefromstring($imageBytes) : false;
            $imageExtension = strtolower(pathinfo($target, PATHINFO_EXTENSION));
            $mediaValid = is_string($imageBytes)
                && $imageBytes !== ''
                && strlen($imageBytes) <= 2 * 1024 * 1024
                && in_array($imageExtension, ['jpg', 'jpeg', 'png'], true)
                && in_array($imageMime, ['image/jpeg', 'image/png'], true)
                && is_array($imageInfo)
                && ($imageInfo['mime'] ?? null) === $imageMime
                && ((int) ($imageInfo[0] ?? 0) * (int) ($imageInfo[1] ?? 0)) <= 25000000;
            $fragmentXml = $mediaNode->ownerDocument?->saveXML($mediaNode) ?: '';
            $fragments[] = [
                'kind' => 'IMAGE_REFERENCE',
                'relationship_id' => $relationshipId,
                'relationship_resolved' => $relationshipResolved,
                'media_valid' => $mediaValid,
                'mime_type' => $imageMime,
                'target' => $target !== '' ? $target : null,
                'xml' => $fragmentXml,
                'xml_sha256' => hash('sha256', $fragmentXml),
            ];
        }

        foreach ($xpath->query('.//m:oMath | .//m:oMathPara', $node) as $mathNode) {
            $fragmentXml = $mathNode->ownerDocument?->saveXML($mathNode) ?: '';
            $fragments[] = [
                'kind' => 'WORD_MATH_OMML',
                'xml' => $fragmentXml,
                'xml_sha256' => hash('sha256', $fragmentXml),
            ];
        }

        return $fragments;
    }

    private function paragraphText(DOMNode $paragraph, \DOMXPath $xpath): string
    {
        $nodes = $xpath->query(
            './/w:t[not(ancestor::w:del)] | .//m:t | .//w:tab[not(ancestor::w:del)] | .//w:br[not(ancestor::w:del)] | .//w:cr[not(ancestor::w:del)] | .//w:sym[not(ancestor::w:del)]',
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

    private function detectTextDirection(DOMNode $node, \DOMXPath $xpath, string $text): string
    {
        if ($xpath->query('.//w:bidi | .//w:rtl', $node)->length > 0
            || preg_match('/[\\x{0590}-\\x{08FF}]/u', $text) === 1) {
            return 'rtl';
        }

        return 'auto';
    }

    private function extractNestedTableStructures(array $fragments): array
    {
        $rows = array_values(array_filter(
            $fragments,
            fn (array $fragment): bool => ($fragment['kind'] ?? null) === 'WORD_TABLE_ROW_OOXML'
                && isset($fragment['structure'])
        ));

        return array_map(fn (array $fragment): array => [
            'table_index' => (int) ($fragment['table_index'] ?? 0),
            'row_index' => (int) ($fragment['row_index'] ?? 0),
            'row' => $fragment['structure'],
        ], $rows);
    }

    private function countNestedTables(array $fragments): int
    {
        $indexes = array_map(
            fn (array $fragment): int => (int) ($fragment['table_index'] ?? 0),
            array_filter(
                $fragments,
                fn (array $fragment): bool => ($fragment['kind'] ?? null) === 'WORD_TABLE_ROW_OOXML'
                    && !empty($fragment['has_nested_table'])
            )
        );

        return count(array_unique(array_filter($indexes)));
    }

    private function buildMathMlFragments(array $fragments): array
    {
        $mathFragments = array_values(array_filter(
            $fragments,
            fn (array $fragment): bool => ($fragment['kind'] ?? null) === 'WORD_MATH_OMML'
        ));
        $converter = app(OmmlToMathMlConverter::class);

        return array_map(function (array $fragment) use ($converter): array {
            $converted = $converter->convert((string) ($fragment['xml'] ?? ''));

            return [
                'mathml' => $converted['mathml'],
                'fallback_text' => $converted['fallback_text'],
                'supported' => $converted['supported'],
                'unsupported_nodes' => $converted['unsupported_nodes'],
                'source_sha256' => (string) ($fragment['xml_sha256'] ?? ''),
            ];
        }, $mathFragments);
    }

    private function extractRichParagraphs(array $fragments): array
    {
        $paragraphs = [];
        $wordNamespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

        foreach ($fragments as $fragment) {
            if (($fragment['kind'] ?? null) !== 'WORD_PARAGRAPH_OOXML' || empty($fragment['xml'])) {
                continue;
            }

            $xml = (string) $fragment['xml'];
            $this->assertSafeXml($xml);
            $document = new DOMDocument();
            if (!$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT)) {
                continue;
            }

            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('w', $wordNamespace);
            $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/officeDocument/2006/math');
            $paragraphNode = $xpath->query('//*[local-name()="p"]')->item(0);
            if (!$paragraphNode) {
                continue;
            }

            $runs = [];
            foreach ($xpath->query('.//w:r', $paragraphNode) as $run) {
                $parts = [];
                foreach ($xpath->query('.//w:t | .//m:t | .//w:tab | .//w:br | .//w:cr', $run) as $textNode) {
                    if ($textNode->localName === 'tab') {
                        $parts[] = "\t";
                    } elseif ($textNode->localName === 'br' || $textNode->localName === 'cr') {
                        $parts[] = "\n";
                    } else {
                        $parts[] = $textNode->textContent;
                    }
                }

                $runText = implode('', $parts);
                if ($runText === '') {
                    continue;
                }

                $boldNode = $xpath->query('./w:rPr/w:b | ./w:rPr/w:bCs', $run)->item(0);
                $italicNode = $xpath->query('./w:rPr/w:i | ./w:rPr/w:iCs', $run)->item(0);
                $underlineNode = $xpath->query('./w:rPr/w:u', $run)->item(0);
                $runs[] = [
                    'text' => $runText,
                    'bold' => $this->wordToggleEnabled($boldNode, $wordNamespace),
                    'italic' => $this->wordToggleEnabled($italicNode, $wordNamespace),
                    'underline' => $underlineNode !== null
                        && !in_array(strtolower($underlineNode->getAttributeNS($wordNamespace, 'val')), ['0', 'false', 'off', 'none'], true),
                ];
            }

            if ($runs !== []) {
                $paragraphText = implode('', array_column($runs, 'text'));
                $paragraphs[] = [
                    'direction' => $this->detectTextDirection($paragraphNode, $xpath, $paragraphText),
                    'runs' => $runs,
                ];
            }
        }

        return $paragraphs;
    }

    private function wordToggleEnabled(?DOMElement $node, string $wordNamespace): bool
    {
        if (!$node) {
            return false;
        }

        return !in_array(strtolower($node->getAttributeNS($wordNamespace, 'val')), ['0', 'false', 'off', 'none'], true);
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
            $blockWarnings = [];
            $blockSourceFragments = array_values($block['source_fragments'] ?? []);
            if (!empty($block['has_media'])) {
                $imageFragments = array_values(array_filter(
                    $blockSourceFragments,
                    fn (array $fragment): bool => ($fragment['kind'] ?? null) === 'IMAGE_REFERENCE'
                ));
                $supportedImages = $imageFragments !== [];
                foreach ($imageFragments as $imageFragment) {
                    $target = (string) ($imageFragment['target'] ?? '');
                    $extension = strtolower(pathinfo($target, PATHINFO_EXTENSION));
                    if (empty($imageFragment['relationship_resolved'])
                        || empty($imageFragment['media_valid'])
                        || !str_starts_with($target, 'word/media/')
                        || !in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
                        $supportedImages = false;
                        break;
                    }
                }
                if (!$supportedImages) {
                    $blockUnsupported[] = 'Gambar DOCX tidak memiliki relasi internal yang valid atau formatnya tidak didukung (hanya JPEG/PNG).';
                    $blockUnsupportedFeatures[] = 'IMAGE_OR_DIAGRAM';
                } else {
                    $blockWarnings[] = 'Gambar terdeteksi dan akan diekstrak sebagai media privat tenant.';
                }
            }
            if (!empty($block['has_math'])) {
                $blockUnsupported[] = 'Rumus Word dipertahankan sebagai sumber OMML dan teks fallback. Soal ditandai PERLU DITINJAU agar Guru memeriksa bentuk rumus sebelum menerbitkan.';
                $blockWarnings[] = 'Periksa kembali susunan pecahan, akar, pangkat, indeks, dan simbol rumus terhadap dokumen Word asli.';
            }
            if (!empty($block['has_nested_table'])) {
                $blockUnsupported[] = 'Tabel bersarang disimpan sebagai sumber OOXML dan teks sel ringkas. Periksa kembali tata letak tabel sebelum menerbitkan.';
                $blockWarnings[] = 'Preview meratakan teks sel; struktur tabel bersarang asli tetap tersimpan pada metadata versi soal.';
            }

            $questionPattern = '/^(?:(?:SOAL|NOMOR)\s+)?(?:\((\d{1,3})\)|(\d{1,3})\s*[.):=-])\s*(.*)$/iu';
            $questionMatch = [];
            $isQuestionStart = $isTableRow
                ? ($active === null && preg_match('/^(?:(?:SOAL|NOMOR)\s+)?(?:\(\d{1,3}\)|\d{1,3}\s*[.):=-]).*\[(?:PG|PGK|B\/S|BENAR\/SALAH|ESSAY|URAIAN)/iu', $primaryText) === 1)
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

            if (($blockUnsupported !== [] || $blockWarnings !== []) && !$isQuestionStart && !$isExplicitStimulusStart) {
                if ($active !== null) {
                    $active['unsupported_blocks'] = array_merge($active['unsupported_blocks'] ?? [], $blockUnsupported);
                    $active['unsupported_features'] = array_merge($active['unsupported_features'] ?? [], $blockUnsupportedFeatures);
                    $active['source_fragments'] = array_merge($active['source_fragments'] ?? [], $blockSourceFragments);
                    $active['source_warnings'] = array_merge($active['source_warnings'] ?? [], $blockWarnings);
                } elseif ($stimulus !== null) {
                    $stimulus['unsupported_blocks'] = array_merge($stimulus['unsupported_blocks'] ?? [], $blockUnsupported);
                    $stimulus['unsupported_features'] = array_merge($stimulus['unsupported_features'] ?? [], $blockUnsupportedFeatures);
                    $stimulus['source_fragments'] = array_merge($stimulus['source_fragments'] ?? [], $blockSourceFragments);
                    $stimulus['source_warnings'] = array_merge($stimulus['source_warnings'] ?? [], $blockWarnings);
                } elseif ($rawQuestions !== []) {
                    // Word commonly places floating media/formulas after the answer-key paragraph.
                    // Attach a contiguous trailing unsupported block to the last completed question.
                    $lastIndex = array_key_last($rawQuestions);
                    $rawQuestions[$lastIndex]['unsupported_blocks'] = array_merge(
                        $rawQuestions[$lastIndex]['unsupported_blocks'] ?? [],
                        $blockUnsupported
                    );
                    $rawQuestions[$lastIndex]['unsupported_features'] = array_merge(
                        $rawQuestions[$lastIndex]['unsupported_features'] ?? [],
                        $blockUnsupportedFeatures
                    );
                    $rawQuestions[$lastIndex]['source_fragments'] = array_merge(
                        $rawQuestions[$lastIndex]['source_fragments'] ?? [],
                        $blockSourceFragments
                    );
                    $rawQuestions[$lastIndex]['source_warnings'] = array_merge(
                        $rawQuestions[$lastIndex]['source_warnings'] ?? [],
                        $blockWarnings
                    );
                }
            }

            if ($blockUnsupported === [] && $blockWarnings === [] && !$isQuestionStart && !$isExplicitStimulusStart) {
                if ($active !== null) {
                    $active['source_fragments'] = array_merge($active['source_fragments'] ?? [], $blockSourceFragments);
                } elseif ($stimulus !== null && !$answerSection) {
                    $stimulus['source_fragments'] = array_merge($stimulus['source_fragments'] ?? [], $blockSourceFragments);
                }
            }

            if ($primaryText === '') {
                continue;
            }

            if (preg_match('/^(?:[A-Z0-9][.)]\s*)?(?:KUNCI(?:\s+JAWABAN)?|DAFTAR\s+JAWABAN|JAWABAN\s+SOAL|ANSWER\s+KEY)\b.*$/iu', $primaryText)) {
                if ($active !== null) {
                    $rawQuestions[] = $active;
                    $active = null;
                }
                $answerSection = true;
                // An answer-key section closes question parsing, not the shared reading stimulus.
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
                    'source_fragments' => $blockSourceFragments,
                    'source_warnings' => $blockWarnings,
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
                    'source_fragments' => $blockSourceFragments,
                    'source_warnings' => $blockWarnings,
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
                    'source_fragments' => $blockSourceFragments,
                    'source_warnings' => $blockWarnings,
                    'has_table_content' => $isTableRow,
                ];
                if ($isTableRow) {
                    $active['table_rows'][] = $cells;
                    $tableOptions = $this->extractTableOptions(array_slice($cells, 1), $hint);
                    if ($tableOptions !== []) {
                        $active['options'] = array_merge($active['options'], $tableOptions);
                    } elseif (count(array_filter(array_slice($cells, 1), fn ($cell) => trim((string) $cell) !== '')) > 0) {
                        $active['table_unclassified'] = true;
                        if (empty($block['has_nested_table'])) {
                            $active['stem_parts'][] = implode(' | ', array_slice($cells, 1));
                        }
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
                        if (empty($block['has_nested_table'])) {
                            $stimulus['content'] = trim(($stimulus['content'] ?? '') . "\n" . $text);
                        }
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
                if (empty($block['has_nested_table'])) {
                    $active['stem_parts'][] = $text;
                }
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
            $warningsForQuestion = array_values(array_unique(array_merge(
                $raw['source_warnings'] ?? [],
                $raw['stimulus']['source_warnings'] ?? []
            )));
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
                'source_fragments' => array_values(array_merge(
                    $raw['source_fragments'] ?? [],
                    $raw['stimulus']['source_fragments'] ?? []
                )),
                'rich_paragraphs' => $this->extractRichParagraphs(array_merge(
                    $raw['source_fragments'] ?? [],
                    $raw['stimulus']['source_fragments'] ?? []
                )),
                'mathml_fragments' => $this->buildMathMlFragments(array_merge(
                    $raw['source_fragments'] ?? [],
                    $raw['stimulus']['source_fragments'] ?? []
                )),
                'media_count' => count(array_filter(
                    array_merge($raw['source_fragments'] ?? [], $raw['stimulus']['source_fragments'] ?? []),
                    fn (array $fragment): bool => ($fragment['kind'] ?? null) === 'IMAGE_REFERENCE'
                )),
                'math_count' => count(array_filter(
                    array_merge($raw['source_fragments'] ?? [], $raw['stimulus']['source_fragments'] ?? []),
                    fn (array $fragment): bool => ($fragment['kind'] ?? null) === 'WORD_MATH_OMML'
                )),
                'table_structure' => (
                    !empty($raw['table_unclassified'])
                    || !empty($raw['stimulus']['has_table_content'])
                    || $this->countNestedTables(array_merge(
                        $raw['source_fragments'] ?? [],
                        $raw['stimulus']['source_fragments'] ?? []
                    )) > 0
                ) ? $this->extractNestedTableStructures(array_merge(
                    $raw['source_fragments'] ?? [],
                    $raw['stimulus']['source_fragments'] ?? []
                )) : [],
                'nested_table_count' => $this->countNestedTables(array_merge(
                    $raw['source_fragments'] ?? [],
                    $raw['stimulus']['source_fragments'] ?? []
                )),
                'text_direction' => preg_match('/[\\x{0590}-\\x{08FF}]/u', $stem) === 1 ? 'rtl' : 'auto',
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
        $aliases = [
            'MTK' => 'MATEMATIKA',
            'INDO' => 'BAHASA INDONESIA',
            'B INDONESIA' => 'BAHASA INDONESIA',
            'B INDONESIA' => 'BAHASA INDONESIA',
        ];
        $subjects = [
            'MATEMATIKA', 'BAHASA INDONESIA', 'BAHASA ARAB', 'IPAS', 'IPA', 'IPS',
            'PENDIDIKAN AGAMA ISLAM', 'AL-QURAN HADIS', 'QURAN HADIS', 'AKIDAH AKHLAK',
            'FIKIH', 'FIQIH', 'SKI', 'PJOK', 'PENDIDIKAN JASMANI', 'PENDIDIKAN PANCASILA',
            'PPKN', 'SENI BUDAYA',
        ];

        if (preg_match('/^(.+?)\s+KELAS\s+([1-6])$/u', $value, $classMatch) === 1) {
            $candidate = $aliases[$classMatch[1]] ?? $classMatch[1];
            if (in_array($candidate, $subjects, true)) {
                return $candidate . ' KELAS ' . $classMatch[2];
            }
        }

        $canonical = $aliases[$value] ?? $value;
        if (in_array($canonical, $subjects, true)) {
            return $canonical;
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
        $options = [];
        foreach ($cells as $cell) {
            $value = trim((string) $cell);
            if ($value === '') {
                continue;
            }
            $hasCheckbox = preg_match('/^[☐□☑✓]\s*(.+)$/u', $value, $checkboxMatch) === 1;
            if ($hasCheckbox) {
                $value = trim($checkboxMatch[1]);
            }
            if (preg_match('/^([A-D])\s*[.)=:]\s*(.+)$/iu', $value, $optionMatch) === 1) {
                $key = strtoupper($optionMatch[1]);
                $content = trim($optionMatch[2]);
            } elseif ($hasCheckbox && $this->isMultipleResponseHint($hint)) {
                $key = chr(65 + count($options));
                $content = $value;
            } else {
                continue;
            }
            if ($content !== '') {
                $options[] = ['option_key' => $key, 'content' => $content];
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

        // Without an explicit marker, PG and PGK cannot safely be distinguished.
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
