<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ExcelImportService
{
    /**
     * استيراد بسيط لملف CSV أو Excel مع معالجة كل سطر عبر callback.
     *
     * @param  array<string>|array<int, list<string>>  $requiredColumns  أسماء أعمدة مطلوبة؛ إما قائمة أسماء (كلّها إلزامية) أو قائمة مجموعات [ ['A','A2'], ['B'] ] حيث يكفي وجود عمود واحد من كل مجموعة
     * @param  callable  $rowHandler  fn(array $row, int $lineNumber): 'created'|'updated'|void
     * @param  array{
     *   min_header_row_index?:int,
     *   header_keywords?:list<string>,
     *   header_scan_limit?:int
     * }  $options
     *        When header_keywords is non-empty, the first row (from index 0) within header_scan_limit rows
     *        that contains any of those labels (case-insensitive, normalized) is used as the header row.
     *        Otherwise the best match against requiredColumns is used (legacy), optionally starting at min_header_row_index.
     * @return array{
     *   created:int,
     *   updated:int,
     *   failed:int,
     *   errors:array<int,array{line:int,reason:string}>
     * }
     */
    public function importSimple(UploadedFile $file, array $requiredColumns, callable $rowHandler, array $options = []): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->importFromCsv($file, $requiredColumns, $rowHandler, $options);
        }

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            return $this->importFromExcel($file, $requiredColumns, $rowHandler, $options);
        }

        throw new \RuntimeException('صيغة الملف غير مدعومة. الرجاء استخدام CSV أو Excel (XLSX / XLS).');
    }

    /**
     * استيراد من CSV باستخدام fgetcsv.
     */
    private function importFromCsv(UploadedFile $file, array $requiredColumns, callable $rowHandler, array $options = []): array
    {
        $path = $file->getRealPath();
        if (! $path || ! is_readable($path)) {
            throw new \RuntimeException('تعذر قراءة الملف المرفوع. يرجى المحاولة مرة أخرى.');
        }

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        $rows = $this->readCsvFileIntoRows($path);

        if ($rows === []) {
            throw new \RuntimeException('الملف فارغ أو لا يحتوي على بيانات.');
        }

        $headerRowIndex = $this->resolveHeaderRowIndex($rows, $requiredColumns, $options);
        $header = $rows[$headerRowIndex] ?? [];

        $normalizedHeader = $this->buildNormalizedHeaderMap($header);

        $this->assertRequiredColumnsExist($requiredColumns, $normalizedHeader);

        $lineNumber = $headerRowIndex + 1; // 1-based header line
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $lineNumber++;
            $row = $rows[$i];
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $assoc = [];
            foreach ($normalizedHeader as $index => $name) {
                $assoc[$name] = array_key_exists($index, $row) ? trim((string) $row[$index]) : null;
            }

            try {
                $result = $rowHandler($assoc, $lineNumber);

                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'updated') {
                    $updated++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'line' => $lineNumber,
                    'reason' => $e->getMessage() !== '' ? $e->getMessage() : 'خطأ غير معروف أثناء معالجة السطر.',
                ];
            }
        }

        return ['created' => $created, 'updated' => $updated, 'failed' => $failed, 'errors' => $errors];
    }

    /**
     * قراءة CSV مع اكتشاف فاصل الحقول (فاصلة / فاصلة منقوطة / تاب) ودعم BOM.
     *
     * @return array<int, array<int, string|null>>
     */
    private function readCsvFileIntoRows(string $path): array
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException('تعذر قراءة الملف المرفوع.');
        }
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }

        $lines = preg_split('/\r\n|\n|\r/', $raw) ?: [];
        $delimiter = $this->detectCsvDelimiter($lines);
        $rows = [];
        foreach ($lines as $line) {
            $rows[] = str_getcsv($line, $delimiter);
        }

        return $rows;
    }

    /**
     * @param  list<string>  $lines
     */
    private function detectCsvDelimiter(array $lines): string
    {
        foreach ($lines as $line) {
            $t = trim((string) $line);
            if ($t === '') {
                continue;
            }
            $comma = count(str_getcsv($t, ','));
            $semi = count(str_getcsv($t, ';'));
            $tab = count(str_getcsv($t, "\t"));
            $best = max($comma, $semi, $tab);
            if ($best <= 1) {
                continue;
            }
            if ($semi >= $comma && $semi >= $tab && $semi === $best) {
                return ';';
            }
            if ($tab >= $comma && $tab >= $semi && $tab === $best) {
                return "\t";
            }

            return ',';
        }

        return ',';
    }

    /**
     * استيراد من ملف Excel باستخدام Laravel-Excel.
     */
    private function importFromExcel(UploadedFile $file, array $requiredColumns, callable $rowHandler, array $options = []): array
    {
        $sheets = Excel::toArray(null, $file);
        if (empty($sheets) || empty($sheets[0])) {
            throw new \RuntimeException('الملف فارغ أو لا يحتوي على بيانات.');
        }

        $allRows = $sheets[0];
        $headerRowIndex = $this->resolveHeaderRowIndex($allRows, $requiredColumns, $options);
        $headerRow = $allRows[$headerRowIndex] ?? null;
        if (! is_array($headerRow)) {
            throw new \RuntimeException('تعذر قراءة الترويسة من الملف.');
        }

        $normalizedHeader = $this->buildNormalizedHeaderMap($headerRow);

        $this->assertRequiredColumnsExist($requiredColumns, $normalizedHeader);

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];
        $lineNumber = $headerRowIndex + 1; // 1-based header line

        for ($i = $headerRowIndex + 1; $i < count($allRows); $i++) {
            $lineNumber++;
            $row = $allRows[$i];

            if (! is_array($row) || $this->isEmptyRow($row)) {
                continue;
            }

            $assoc = [];
            foreach ($normalizedHeader as $index => $name) {
                $assoc[$name] = array_key_exists($index, $row) ? trim((string) $row[$index]) : null;
            }

            try {
                $result = $rowHandler($assoc, $lineNumber);

                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'updated') {
                    $updated++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'line' => $lineNumber,
                    'reason' => $e->getMessage() !== '' ? $e->getMessage() : 'خطأ غير معروف أثناء معالجة السطر.',
                ];
            }
        }

        return ['created' => $created, 'updated' => $updated, 'failed' => $failed, 'errors' => $errors];
    }

    /**
     * التحقق هل السطر فارغ بالكامل.
     *
     * @param  array<int,string|null>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, array<int|string, mixed>>  $rows
     * @param  array<string>  $requiredColumns
     * @param  array{min_header_row_index?:int,header_keywords?:list<string>,header_scan_limit?:int}  $options
     */
    private function resolveHeaderRowIndex(array $rows, array $requiredColumns, array $options): int
    {
        $maxScan = (int) ($options['header_scan_limit'] ?? 50);
        if ($maxScan < 1) {
            $maxScan = 50;
        }

        $keywords = $options['header_keywords'] ?? [];
        if (is_array($keywords) && $keywords !== []) {
            $idx = $this->detectHeaderRowIndexByKeywords($rows, $keywords, $maxScan);
            if ($idx === null) {
                throw ValidationException::withMessages([
                    'file' => ['لم يتم العثور على عناوين الأعمدة المطلوبة في الملف'],
                ]);
            }

            return $idx;
        }

        return $this->detectHeaderRowIndex(
            $rows,
            $requiredColumns,
            (int) ($options['min_header_row_index'] ?? 0),
            $maxScan
        );
    }

    /**
     * First row index (0-based) within the scan window where any cell matches any keyword (normalized equality).
     *
     * @param  array<int, array<int|string, mixed>>  $rows
     * @param  list<string>  $keywords
     */
    private function detectHeaderRowIndexByKeywords(array $rows, array $keywords, int $maxScanRows): ?int
    {
        $normalizedKeywordSet = [];
        foreach ($keywords as $k) {
            $n = $this->normalizeHeaderName((string) $k);
            if ($n !== '') {
                $normalizedKeywordSet[$n] = true;
            }
        }

        if ($normalizedKeywordSet === []) {
            return null;
        }

        $limit = min($maxScanRows, count($rows));
        for ($i = 0; $i < $limit; $i++) {
            $row = $rows[$i] ?? null;
            if (! is_array($row)) {
                continue;
            }
            foreach ($row as $cell) {
                $cellNorm = $this->normalizeHeaderName((string) $cell);
                if ($cellNorm !== '' && isset($normalizedKeywordSet[$cellNorm])) {
                    return $i;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int|string,mixed>  $headerRow
     * @return array<int|string,string>
     */
    private function buildNormalizedHeaderMap(array $headerRow): array
    {
        $normalizedHeader = [];
        foreach ($headerRow as $index => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            if ($index === 0 || $index === '0') {
                $name = preg_replace('/^\xEF\xBB\xBF/', '', $name) ?? $name;
            }
            $normalizedHeader[$index] = $name;
        }

        return $normalizedHeader;
    }

    /**
     * @return list<list<string>>
     */
    private function normalizeRequiredColumnGroups(array $requiredColumns): array
    {
        if ($requiredColumns === []) {
            return [];
        }

        if (is_array($requiredColumns[0] ?? null)) {
            /** @var list<list<string>> $requiredColumns */
            return array_values(array_map(
                fn (array $g) => array_values(array_map(static fn ($c) => (string) $c, $g)),
                $requiredColumns
            ));
        }

        return array_map(static fn ($c) => [(string) $c], $requiredColumns);
    }

    /**
     * Detects header row: prefers the first row that matches every required column group (junk rows skipped).
     * Falls back to best partial match only if no full match exists in the scan window.
     *
     * @param  array<int, array<int|string, mixed>>  $rows
     * @param  array<string>|array<int, list<string>>  $requiredColumns
     */
    private function detectHeaderRowIndex(array $rows, array $requiredColumns, int $minHeaderRowIndex = 0, int $maxScanRows = 50): int
    {
        $scanLimit = min($maxScanRows, count($rows));
        $groups = $this->normalizeRequiredColumnGroups($requiredColumns);
        $needed = count($groups);
        $start = max(0, $minHeaderRowIndex);

        if ($needed === 0) {
            return $start;
        }

        $scoreForRow = function (int $i) use ($rows, $groups): int {
            $row = $rows[$i] ?? null;
            if (! is_array($row)) {
                return 0;
            }
            $normalizedCells = [];
            foreach ($row as $cell) {
                $normalized = $this->normalizeHeaderName((string) $cell);
                if ($normalized !== '') {
                    $normalizedCells[$normalized] = true;
                }
            }
            $score = 0;
            foreach ($groups as $group) {
                foreach ($group as $alt) {
                    if (isset($normalizedCells[$this->normalizeHeaderName($alt)])) {
                        $score++;
                        break;
                    }
                }
            }

            return $score;
        };

        for ($i = $start; $i < $scanLimit; $i++) {
            if ($needed > 0 && $scoreForRow($i) === $needed) {
                return $i;
            }
        }

        $bestIndex = $start;
        $bestScore = -1;
        for ($i = $start; $i < $scanLimit; $i++) {
            $s = $scoreForRow($i);
            if ($s > $bestScore) {
                $bestScore = $s;
                $bestIndex = $i;
            }
        }

        if ($needed > 0 && $bestScore < $needed) {
            throw ValidationException::withMessages([
                'file' => ['لم يتم العثور على صف ترويسة يحتوي كل الأعمدة المطلوبة ضمن أول صفوف الملف.'],
            ]);
        }

        return $bestIndex;
    }

    /**
     * @param  array<string>|array<int, list<string>>  $requiredColumns
     * @param  array<int|string,string>  $header
     */
    private function assertRequiredColumnsExist(array $requiredColumns, array $header): void
    {
        $normalizedHeaderMap = [];
        foreach ($header as $h) {
            $normalizedHeaderMap[$this->normalizeHeaderName($h)] = true;
        }

        $groups = $this->normalizeRequiredColumnGroups($requiredColumns);
        $available = implode(', ', array_values($header));

        foreach ($groups as $group) {
            $found = false;
            foreach ($group as $col) {
                if (isset($normalizedHeaderMap[$this->normalizeHeaderName((string) $col)])) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $label = implode('، ', $group);
                throw new \RuntimeException("يلزم وجود أحد الأعمدة التالية في الترويسة: {$label}. الأعمدة المقروءة: {$available}");
            }
        }
    }

    private function normalizeHeaderName(string $name): string
    {
        $name = preg_replace('/^\xEF\xBB\xBF/', '', $name) ?? $name;
        $name = mb_strtolower(trim($name));
        $name = str_replace("\xC2\xA0", ' ', $name); // NBSP
        $name = preg_replace('/\p{Cf}+/u', '', $name) ?? $name; // zero-width chars
        $name = preg_replace('/[^\p{L}\p{N}]+/u', '', $name) ?? $name; // keep only letters/digits

        return $name;
    }
}
