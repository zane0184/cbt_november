<?php

namespace App\Services\Import;

use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use XMLReader;
use ZipArchive;

final class XlsxPackagePreflight
{
    public const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;
    public const MAX_ZIP_ENTRIES = 200;
    public const MAX_TOTAL_EXPANDED_BYTES = 25 * 1024 * 1024;
    public const MAX_ENTRY_EXPANDED_BYTES = 10 * 1024 * 1024;
    public const MAX_COMPRESSION_RATIO = 100;
    public const MAX_XML_BYTES = 10 * 1024 * 1024;
    public const MAX_SHARED_STRINGS_BYTES = 10 * 1024 * 1024;
    public const MAX_STYLES_BYTES = 2 * 1024 * 1024;
    public const MAX_ROWS = 5001;
    public const MAX_COLUMNS = 32;
    public const MAX_CELLS = 160000;
    public const MAX_CELL_BYTES = 4096;

    private const RELATIONSHIP_NAMESPACE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /**
     * Validate the OOXML package before PhpSpreadsheet is allowed to parse it.
     * Returns the only visible worksheet name accepted by the student importer.
     */
    public function inspect(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            $this->reject('File XLSX tidak dapat dibaca.');
        }

        $uploadSize = filesize($path);
        if (!is_int($uploadSize) || $uploadSize < 1 || $uploadSize > self::MAX_UPLOAD_BYTES) {
            $this->reject('Ukuran file XLSX harus lebih dari 0 dan maksimal 10 MiB.');
        }

        $zip = new ZipArchive();
        $opened = $zip->open($path, ZipArchive::RDONLY);
        if ($opened !== true) {
            $this->reject('Paket XLSX bukan ZIP yang valid atau rusak.');
        }

        try {
            if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_ZIP_ENTRIES) {
                $this->reject('Jumlah entry ZIP XLSX melewati batas keamanan.');
            }

            $names = [];
            $totalExpanded = 0;
            $xmlEntries = [];

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                if (!is_array($stat) || !isset($stat['name'], $stat['size'], $stat['comp_size'])) {
                    $this->reject('Struktur entry ZIP XLSX tidak valid.');
                }

                $name = (string) $stat['name'];
                $this->assertSafeEntryName($name);

                $normalizedName = strtolower($name);
                if (isset($names[$normalizedName])) {
                    $this->reject('Paket XLSX memiliki nama entry duplikat.');
                }
                $names[$normalizedName] = true;

                $lowerName = strtolower($name);
                if (
                    str_contains($lowerName, 'vbaproject.bin')
                    || str_contains($lowerName, 'externallinks/')
                    || str_contains($lowerName, 'embeddings/')
                    || str_contains($lowerName, 'oleobject')
                    || str_ends_with($lowerName, '/connections.xml')
                ) {
                    $this->reject('XLSX dengan macro, OLE, embedding, atau external link tidak diizinkan.');
                }

                $this->assertNotSymlink($zip, $index);

                $expanded = (int) $stat['size'];
                $compressed = (int) $stat['comp_size'];
                if ($expanded < 0 || $expanded > self::MAX_ENTRY_EXPANDED_BYTES) {
                    $this->reject('Ukuran hasil ekstraksi salah satu entry XLSX melewati batas.');
                }

                $totalExpanded += $expanded;
                if ($totalExpanded > self::MAX_TOTAL_EXPANDED_BYTES) {
                    $this->reject('Total ukuran hasil ekstraksi XLSX melewati batas keamanan.');
                }

                if ($expanded > 0) {
                    if ($compressed <= 0 || ($expanded / max(1, $compressed)) > self::MAX_COMPRESSION_RATIO) {
                        $this->reject('Compression ratio XLSX melewati batas keamanan.');
                    }
                }

                if (preg_match('/\.(xml|rels)$/i', $name) === 1) {
                    $xmlLimit = self::MAX_XML_BYTES;
                    if (strtolower($name) === 'xl/sharedstrings.xml') {
                        $xmlLimit = self::MAX_SHARED_STRINGS_BYTES;
                    } elseif (strtolower($name) === 'xl/styles.xml') {
                        $xmlLimit = self::MAX_STYLES_BYTES;
                    }

                    if ($expanded > $xmlLimit) {
                        $this->reject('Ukuran XML, shared strings, atau styles XLSX melewati batas.');
                    }

                    $xml = $this->readEntry($zip, $index, $expanded);
                    $this->assertSafeXml($xml, $name);
                    if (str_ends_with(strtolower($name), '.rels')) {
                        $this->assertNoExternalRelationships($xml, $name);
                    }

                    $xmlEntries[$name] = true;
                }
            }

            if (!isset($names['[content_types].xml'], $names['xl/workbook.xml'], $names['xl/_rels/workbook.xml.rels'])) {
                $this->reject('Paket XLSX tidak memiliki bagian workbook OOXML yang wajib.');
            }

            $workbookXml = $this->readNamedEntry($zip, 'xl/workbook.xml', self::MAX_XML_BYTES);
            $relationshipsXml = $this->readNamedEntry($zip, 'xl/_rels/workbook.xml.rels', self::MAX_XML_BYTES);
            [$sheetName, $relationshipId] = $this->readSingleWorksheet($workbookXml);
            $targets = $this->readWorkbookRelationships($relationshipsXml);

            $target = $targets[$relationshipId] ?? null;
            if (!is_string($target) || $target === '') {
                $this->reject('Relationship worksheet XLSX tidak ditemukan.');
            }

            $worksheetPath = $this->resolveWorksheetPath($target);
            $worksheetXml = $this->readNamedEntry($zip, $worksheetPath, self::MAX_XML_BYTES);
            $this->assertWorksheetBounds($worksheetXml, $worksheetPath);

            if (isset($names['xl/sharedstrings.xml'])) {
                $sharedStrings = $this->readNamedEntry($zip, 'xl/sharedStrings.xml', self::MAX_SHARED_STRINGS_BYTES);
                $this->assertSharedStrings($sharedStrings);
            }

            if (isset($names['xl/styles.xml'])) {
                $styles = $this->readNamedEntry($zip, 'xl/styles.xml', self::MAX_STYLES_BYTES);
                $this->assertStyles($styles);
            }

            return $sheetName;
        } finally {
            $zip->close();
        }
    }

    private function assertSafeEntryName(string $name): void
    {
        if (
            $name === ''
            || str_starts_with($name, '/')
            || str_contains($name, '\\')
            || str_contains($name, "\0")
            || preg_match('~(^|/)\.\.?(/|$)~', $name) === 1
            || preg_match('/^[A-Za-z]:/', $name) === 1
            || preg_match('/[\x00-\x1F]/', $name) === 1
        ) {
            $this->reject('Paket XLSX mengandung path entry yang tidak aman.');
        }
    }

    private function assertNotSymlink(ZipArchive $zip, int $index): void
    {
        if (!method_exists($zip, 'getExternalAttributesIndex')) {
            return;
        }

        $opsys = 0;
        $attributes = 0;
        if (!$zip->getExternalAttributesIndex($index, $opsys, $attributes)) {
            return;
        }

        if ($opsys === ZipArchive::OPSYS_UNIX) {
            $fileType = ($attributes >> 16) & 0170000;
            if ($fileType === 0120000) {
                $this->reject('Paket XLSX tidak boleh mengandung symbolic link.');
            }
        }
    }

    private function readNamedEntry(ZipArchive $zip, string $name, int $limit): string
    {
        $index = $zip->locateName($name);
        if (!is_int($index)) {
            $this->reject('Bagian wajib XLSX tidak ditemukan.');
        }

        $stat = $zip->statIndex($index);
        if (!is_array($stat) || !isset($stat['size'])) {
            $this->reject('Metadata entry XLSX tidak valid.');
        }

        $size = (int) $stat['size'];
        if ($size < 0 || $size > $limit) {
            $this->reject('Ukuran bagian XLSX melewati batas keamanan.');
        }

        return $this->readEntry($zip, $index, $size);
    }

    private function readEntry(ZipArchive $zip, int $index, int $expectedSize): string
    {
        $content = $zip->getFromIndex($index);
        if (!is_string($content) || strlen($content) !== $expectedSize) {
            $this->reject('Entry ZIP XLSX rusak atau hasil ekstraksinya tidak konsisten.');
        }

        return $content;
    }

    private function assertSafeXml(string $xml, string $entryName): void
    {
        if (preg_match('/<!\s*(DOCTYPE|ENTITY)\b/i', $xml) === 1) {
            $this->reject('XML XLSX tidak boleh memakai DOCTYPE atau ENTITY.');
        }

        $this->consumeXml($xml, $entryName, static function (XMLReader $reader): void {
            if ($reader->nodeType === XMLReader::DOC_TYPE) {
                throw ValidationException::withMessages(['file' => 'XML XLSX mengandung deklarasi DTD yang tidak diizinkan.']);
            }
        });
    }

    private function assertNoExternalRelationships(string $xml, string $entryName): void
    {
        if (preg_match('/\bTargetMode\s*=\s*["\']External["\']/i', $xml) === 1) {
            $this->reject('XLSX tidak boleh memuat external relationship.');
        }
    }

    private function readSingleWorksheet(string $xml): array
    {
        $sheets = [];
        $this->consumeXml($xml, 'xl/workbook.xml', static function (XMLReader $reader) use (&$sheets): void {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'sheet') {
                return;
            }

            $sheets[] = [
                'name' => $reader->getAttribute('name'),
                'state' => $reader->getAttribute('state'),
                'relationship_id' => $reader->getAttributeNs('id', self::RELATIONSHIP_NAMESPACE) ?: $reader->getAttribute('r:id'),
            ];
        });

        if (count($sheets) !== 1) {
            $this->reject('Template Import Siswa harus memiliki tepat satu worksheet.');
        }

        $sheet = $sheets[0];
        if (
            !is_string($sheet['name']) || trim($sheet['name']) === ''
            || !is_string($sheet['relationship_id']) || trim($sheet['relationship_id']) === ''
            || in_array(strtolower((string) $sheet['state']), ['hidden', 'veryhidden'], true)
        ) {
            $this->reject('Worksheet aktif XLSX tidak valid atau tersembunyi.');
        }

        return [$sheet['name'], $sheet['relationship_id']];
    }

    private function readWorkbookRelationships(string $xml): array
    {
        $relationships = [];
        $this->consumeXml($xml, 'xl/_rels/workbook.xml.rels', static function (XMLReader $reader) use (&$relationships): void {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'Relationship') {
                return;
            }

            $id = $reader->getAttribute('Id');
            $target = $reader->getAttribute('Target');
            $mode = $reader->getAttribute('TargetMode');

            if (strtolower((string) $mode) === 'external') {
                throw ValidationException::withMessages(['file' => 'XLSX tidak boleh memuat external relationship.']);
            }

            if (is_string($id) && $id !== '' && is_string($target) && $target !== '') {
                $relationships[$id] = $target;
            }
        });

        return $relationships;
    }

    private function resolveWorksheetPath(string $target): string
    {
        if (
            str_starts_with($target, '/')
            || str_contains($target, '\\')
            || preg_match('~(^|/)\.\.?(/|$)~', $target) === 1
            || preg_match('/^[A-Za-z][A-Za-z0-9+.-]*:/', $target) === 1
        ) {
            $this->reject('Target worksheet XLSX tidak aman.');
        }

        $path = str_starts_with($target, 'xl/') ? $target : 'xl/' . ltrim($target, '/');
        if (!str_starts_with($path, 'xl/worksheets/') || !str_ends_with(strtolower($path), '.xml')) {
            $this->reject('Relationship workbook tidak menunjuk ke worksheet XLSX yang valid.');
        }

        return $path;
    }

    private function assertWorksheetBounds(string $xml, string $entryName): void
    {
        $cellCount = 0;
        $this->consumeXml($xml, $entryName, function (XMLReader $reader) use (&$cellCount): void {
            if ($reader->nodeType !== XMLReader::ELEMENT) {
                return;
            }

            if ($reader->localName === 'dimension') {
                $reference = $reader->getAttribute('ref');
                if (is_string($reference) && $reference !== '') {
                    $this->assertDimensionReference($reference);
                }
            }

            if ($reader->localName === 'f') {
                $this->reject('Formula tidak diizinkan pada file Import Siswa; gunakan nilai tetap.');
            }

            if ($reader->localName === 'c') {
                $reference = $reader->getAttribute('r');
                if (!is_string($reference) || $reference === '') {
                    $this->reject('Worksheet XLSX memiliki cell tanpa alamat yang valid.');
                }

                [$column, $row] = $this->parseCellReference($reference);
                if ($row > self::MAX_ROWS + 1 || $column > self::MAX_COLUMNS) {
                    $this->reject('Worksheet XLSX melewati batas 5.000 baris data atau 32 kolom.');
                }

                $cellCount++;
                if ($cellCount > self::MAX_CELLS) {
                    $this->reject('Jumlah cell XLSX melewati batas keamanan.');
                }
            }

            if ($reader->localName === 't') {
                $text = $reader->readString();
                if (strlen($text) > self::MAX_CELL_BYTES) {
                    $this->reject('Isi satu cell XLSX melewati batas 4 KiB.');
                }
            }
        });
    }

    private function assertDimensionReference(string $reference): void
    {
        $parts = explode(':', strtoupper($reference));
        if (count($parts) > 2) {
            $this->reject('Worksheet dimension XLSX tidak valid.');
        }

        $end = $parts[count($parts) - 1];
        [$column, $row] = $this->parseCellReference($end);
        if ($row > self::MAX_ROWS + 1 || $column > self::MAX_COLUMNS) {
            $this->reject('Worksheet dimension XLSX melewati batas sebelum workbook dimuat.');
        }
    }

    private function parseCellReference(string $reference): array
    {
        if (preg_match('/^\$?([A-Z]{1,3})\$?([1-9][0-9]*)$/i', $reference, $matches) !== 1) {
            $this->reject('Alamat cell XLSX tidak valid.');
        }

        $column = Coordinate::columnIndexFromString(strtoupper($matches[1]));
        $row = (int) $matches[2];

        return [$column, $row];
    }

    private function assertSharedStrings(string $xml): void
    {
        $count = 0;
        $this->consumeXml($xml, 'xl/sharedStrings.xml', function (XMLReader $reader) use (&$count): void {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'si') {
                $count++;
                if ($count > self::MAX_CELLS) {
                    $this->reject('Jumlah shared strings XLSX melewati batas keamanan.');
                }

                if (strlen($reader->readString()) > self::MAX_CELL_BYTES) {
                    $this->reject('Shared string XLSX melewati batas 4 KiB.');
                }
            }
        });
    }

    private function assertStyles(string $xml): void
    {
        $styleCount = 0;
        $this->consumeXml($xml, 'xl/styles.xml', static function (XMLReader $reader) use (&$styleCount): void {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'xf') {
                $styleCount++;
            }
        });

        if ($styleCount > 10000) {
            $this->reject('Jumlah style XLSX melewati batas keamanan.');
        }
    }

    private function consumeXml(string $xml, string $entryName, callable $visitor): void
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $reader = new XMLReader();
            if (!$reader->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT)) {
                $this->reject('XML XLSX rusak: ' . $entryName . '.');
            }

            $reader->setParserProperty(XMLReader::LOADDTD, false);
            $reader->setParserProperty(XMLReader::SUBST_ENTITIES, false);
            $reader->setParserProperty(XMLReader::VALIDATE, false);

            while ($reader->read()) {
                $visitor($reader);
            }

            $reader->close();
            if (libxml_get_errors() !== []) {
                $this->reject('XML XLSX tidak valid: ' . $entryName . '.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
