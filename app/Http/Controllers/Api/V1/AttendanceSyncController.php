<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Services\AttendanceLogIngestService;
use App\Services\AttendanceRollupService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttendanceSyncController extends Controller
{
    public function store(
        Request $request,
        AttendanceLogIngestService $ingest,
        AttendanceRollupService $rollup,
    ): JsonResponse {
        $records = $this->normalizeIncomingRecords($request->all());

        if ($records === []) {
            return response()->json([
                'success' => false,
                'message' => 'لم يُرسل أي سجلات (records أو مصفوفة من الكائنات).',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $validator = Validator::make(
            ['records' => $records],
            [
                'records' => 'required|array|max:2000',
                'records.*.employee_code' => 'nullable|string|max:30',
                'records.*.employee_device_id' => 'nullable|string|max:128',
                'records.*.timestamp' => 'required|date',
                'records.*.direction' => 'required|in:in,out',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة.',
                'errors' => $validator->errors(),
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $validated = $validator->validated();
        $userId = (int) $request->attributes->get('attendance_api_user_id');
        if ($userId < 1) {
            return response()->json(['success' => false, 'message' => 'غير مصرّح.'], 401, [], JSON_UNESCAPED_UNICODE);
        }

        $imported = 0;
        $failed = [];
        $pairs = [];

        foreach ($validated['records'] as $idx => $rec) {
            $key = trim((string) ($rec['employee_code'] ?? ''));
            if ($key === '') {
                $key = trim((string) ($rec['employee_device_id'] ?? ''));
            }
            if ($key === '') {
                $failed[] = [
                    'index' => $idx,
                    'reason' => 'empty_employee_key',
                ];

                continue;
            }

            $loggedAt = Carbon::parse($rec['timestamp'], config('app.timezone'));
            $result = $ingest->ingest(
                $userId,
                $key,
                $loggedAt,
                (string) $rec['direction'],
                AttendanceLog::SOURCE_API_SYNC,
                ['batch_index' => $idx],
                deferRollup: true,
            );

            if (! ($result['ok'] ?? false)) {
                $failed[] = [
                    'index' => $idx,
                    'employee_code' => $rec['employee_code'] ?? null,
                    'employee_device_id' => $rec['employee_device_id'] ?? null,
                    'lookup_key' => $key,
                    'reason' => $result['reason'] ?? 'unknown',
                ];

                continue;
            }

            $imported++;
            $pairs[] = [
                'employee_id' => (int) $result['employee_id'],
                'work_date' => (string) $result['work_date'],
            ];
        }

        $rollup->rollupPairs($userId, $pairs);

        return response()->json([
            'success' => true,
            'imported' => $imported,
            'failed' => $failed,
            'failed_count' => count($failed),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function normalizeIncomingRecords(array $payload): array
    {
        if (isset($payload['records']) && is_array($payload['records'])) {
            return array_values($payload['records']);
        }

        if (isset($payload['employee_device_id']) || isset($payload['employee_code'])) {
            return [$payload];
        }

        if ($payload !== [] && array_is_list($payload) && isset($payload[0]) && is_array($payload[0])) {
            return $payload;
        }

        return [];
    }
}
