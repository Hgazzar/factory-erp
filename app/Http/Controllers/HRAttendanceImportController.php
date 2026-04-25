<?php

namespace App\Http\Controllers;

use App\Models\AttendanceImportMapping;
use App\Services\AttendanceExcelImportService;
use App\Services\AttendanceSpreadsheetReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HRAttendanceImportController extends Controller
{
    /**
     * نموذج Excel ثابت العناوين لإدخال الحضور يدوياً (نفس ترتيب الأعمدة يُبنى منه الـ header_signature).
     */
    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'Employee Code');
            $sheet->setCellValue('B1', 'Check Time');

            $dummyRows = [
                ['10001', '2026-01-15 08:05:00'],
                ['10002', '2026-01-15 17:30:00'],
                ['10003', '2026-01-16 09:00:00'],
            ];
            $row = 2;
            foreach ($dummyRows as [$code, $checkTime]) {
                $sheet->setCellValueExplicit("A{$row}", (string) $code, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("B{$row}", $checkTime, DataType::TYPE_STRING);
                $row++;
            }

            $sheet->getStyle('A1:B1')->getFont()->setBold(true);
            $sheet->getStyle('B1:B4')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            $sheet->getStyle('A2:A4')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            $sheet->getColumnDimension('A')->setWidth(16);
            $sheet->getColumnDimension('B')->setWidth(22);

            (new Xlsx($spreadsheet))->save('php://output');
        }, 'attendance-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function preview(Request $request): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return redirect()
                ->route('hr.attendance', ['open_import' => 1])
                ->with('error', 'استخدم زر «استيراد من Excel» في شاشة الحضور لفتح النافذة.');
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:12288',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first('file') ?? 'ملف غير صالح.',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $userId = (int) auth()->id();
        $path = $request->file('file')->store('attendance-imports', 'local');
        $absolute = Storage::disk('local')->path($path);

        try {
            $rows = AttendanceSpreadsheetReader::readAllRows($absolute);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);

            return response()->json([
                'message' => 'تعذّر قراءة الملف: '.$e->getMessage(),
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        if (count($rows) < 2) {
            Storage::disk('local')->delete($path);

            return response()->json([
                'message' => 'الملف لا يحتوي على صف بيانات بعد صف العناوين.',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $headers = array_map(fn ($h) => (string) $h, $rows[0]);
        $preview = array_slice($rows, 1, 5);
        $signature = AttendanceImportMapping::headerSignatureFromHeaders($headers);

        $savedMapping = AttendanceImportMapping::query()
            ->where('header_signature', $signature)
            ->first();

        $token = Str::random(48);
        Cache::put('attendance_import:'.$token, [
            'user_id' => $userId,
            'path' => $path,
            'header_signature' => $signature,
            'headers' => $headers,
        ], now()->addMinutes(45));

        $previewJson = array_map(function ($row) {
            if (! is_array($row)) {
                return [];
            }

            return array_map(fn ($cell) => $cell === null || $cell === '' ? null : (is_scalar($cell) ? $cell : json_encode($cell)), $row);
        }, $preview);

        $savedPayload = $savedMapping ? [
            'device_column_index' => $savedMapping->device_column_index,
            'datetime_column_index' => $savedMapping->datetime_column_index,
            'name' => $savedMapping->name,
        ] : null;

        $canAutoImport = $savedMapping !== null
            && (int) $savedMapping->device_column_index !== (int) $savedMapping->datetime_column_index;

        return response()->json([
            'token' => $token,
            'headers' => $headers,
            'preview' => $previewJson,
            'saved_mapping' => $savedPayload,
            'known_header_signature' => $canAutoImport,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function execute(Request $request, AttendanceExcelImportService $excelImport): JsonResponse|RedirectResponse
    {
        $expectsJson = $request->expectsJson();

        $validator = Validator::make($request->all(), [
            'token' => 'required|string|size:48',
            'device_column_index' => 'required|integer|min:0',
            'datetime_column_index' => 'required|integer|min:0',
            'mapping_name' => 'nullable|string|max:120',
        ]);

        if ($validator->fails()) {
            return $this->executeResponse($expectsJson, false, $validator->errors()->first() ?? 'بيانات غير صالحة.', 422);
        }

        if ($request->integer('device_column_index') === $request->integer('datetime_column_index')) {
            return $this->executeResponse($expectsJson, false, 'يجب اختيار عمودين مختلفين لرقم البصمة وللتاريخ/الوقت.', 422);
        }

        $cacheKey = 'attendance_import:'.$request->string('token');
        $cached = Cache::get($cacheKey);
        if (! is_array($cached) || (int) ($cached['user_id'] ?? 0) !== (int) auth()->id()) {
            return $this->executeResponse($expectsJson, false, 'انتهت صلاحية الجلسة أو الملف غير موجود. أعد رفع الملف.', 410);
        }

        $path = (string) ($cached['path'] ?? '');
        $signature = (string) ($cached['header_signature'] ?? '');
        $headers = (array) ($cached['headers'] ?? []);

        if ($path === '' || ! Storage::disk('local')->exists($path)) {
            Cache::forget($cacheKey);

            return $this->executeResponse($expectsJson, false, 'ملف الرفع غير متوفر.', 410);
        }

        $maxIndex = max(count($headers) - 1, 0);
        if (
            $request->integer('device_column_index') > $maxIndex
            || $request->integer('datetime_column_index') > $maxIndex
        ) {
            return $this->executeResponse($expectsJson, false, 'رقم عمود غير صالح.', 422);
        }

        $absolute = Storage::disk('local')->path($path);

        try {
            $rows = AttendanceSpreadsheetReader::readAllRows($absolute);
        } catch (\Throwable $e) {
            return $this->executeResponse($expectsJson, false, 'تعذّر قراءة الملف: '.$e->getMessage(), 422);
        }

        $userId = (int) auth()->id();

        AttendanceImportMapping::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'header_signature' => $signature,
            ],
            [
                'device_column_index' => $request->integer('device_column_index'),
                'datetime_column_index' => $request->integer('datetime_column_index'),
                'headers_snapshot' => $headers,
                'name' => $request->input('mapping_name'),
            ]
        );

        $report = $excelImport->importMappedRows(
            $userId,
            $rows,
            $request->integer('device_column_index'),
            $request->integer('datetime_column_index'),
        );

        Storage::disk('local')->delete($path);
        Cache::forget($cacheKey);

        $message = sprintf(
            'تم الاستيراد: %d سطراً ناجحاً، %d فاشلاً.',
            $report['success'],
            count($report['failed'])
        );

        if ($expectsJson) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'report' => $report,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        return redirect()
            ->route('hr.attendance')
            ->with([
                'success' => $message,
                'attendance_import_report' => $report,
            ]);
    }

    private function executeResponse(bool $expectsJson, bool $ok, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($expectsJson) {
            return response()->json([
                'success' => $ok,
                'message' => $message,
            ], $status, [], JSON_UNESCAPED_UNICODE);
        }

        return redirect()
            ->route('hr.attendance')
            ->with($ok ? 'success' : 'error', $message);
    }
}
