<?php

namespace App\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

final class AttendanceSpreadsheetReader
{
    /**
     * @return list<list<mixed>>
     */
    public static function readAllRows(string $absolutePath): array
    {
        $reader = IOFactory::createReaderForFile($absolutePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($absolutePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $spreadsheet->disconnectWorksheets();

        /** @var list<list<mixed>> $rows */
        return $rows;
    }

    public static function parseCellToCarbon(mixed $cell): ?Carbon
    {
        if ($cell === null || $cell === '') {
            return null;
        }

        if ($cell instanceof \DateTimeInterface) {
            return Carbon::instance(\DateTimeImmutable::createFromInterface($cell))->timezone(config('app.timezone'));
        }

        if (is_numeric($cell)) {
            $n = (float) $cell;
            if ($n > 200 && $n < 600000) {
                try {
                    $dt = ExcelDate::excelToDateTimeObject($n);

                    return Carbon::instance($dt)->timezone(config('app.timezone'));
                } catch (\Throwable) {
                    // fall through
                }
            }
        }

        if (is_string($cell)) {
            $s = trim($cell);
            if ($s === '') {
                return null;
            }
            try {
                return Carbon::parse($s, config('app.timezone'));
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
