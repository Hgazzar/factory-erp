<?php

namespace App\Support;

final class AuditTrailPresenter
{
    /**
     * @return array{
     *   mode: 'diff'|'bom'|'kv',
     *   diff_rows?: list<array{label: string, old: string, new: string, trend: 'up'|'down'|'same'|'neutral'}>,
     *   bom?: array{added: list<array>, removed: list<array>, changed: list<array>},
     *   kv_old?: list<array{label: string, value: string}>,
     *   kv_new?: list<array{label: string, value: string}>
     * }
     */
    public static function present(?array $oldValues, ?array $newValues, string $action, string $tableName): array
    {
        $oldValues = $oldValues ?? [];
        $newValues = $newValues ?? [];

        if ($tableName === 'bom' && $action === 'update') {
            return self::presentBom($oldValues, $newValues);
        }

        if (in_array($action, ['update', 'complete'], true) && $oldValues !== [] && $newValues !== []) {
            return self::presentFieldDiff($oldValues, $newValues, $tableName);
        }

        return self::presentKeyValue($oldValues, $newValues, $action, $tableName);
    }

    /**
     * @param  array{lines?: list<array<string, mixed>>}  $oldValues
     * @param  array{lines?: list<array<string, mixed>>}  $newValues
     */
    private static function presentBom(array $oldValues, array $newValues): array
    {
        $oldLines = $oldValues['lines'] ?? [];
        $newLines = $newValues['lines'] ?? [];

        $byIdOld = self::indexBomLines($oldLines);
        $byIdNew = self::indexBomLines($newLines);

        $allIds = array_unique(array_merge(array_keys($byIdOld), array_keys($byIdNew)));
        sort($allIds);

        $added = [];
        $removed = [];
        $changed = [];

        foreach ($allIds as $id) {
            $o = $byIdOld[$id] ?? null;
            $n = $byIdNew[$id] ?? null;

            if ($o === null && $n !== null) {
                $added[] = [
                    'code' => $n['code'] ?? '—',
                    'name_hint' => $n['code'] ?? '',
                    'quantity_per_unit' => (string) ($n['quantity_per_unit'] ?? ''),
                ];

                continue;
            }

            if ($o !== null && $n === null) {
                $removed[] = [
                    'code' => $o['code'] ?? '—',
                    'quantity_per_unit' => (string) ($o['quantity_per_unit'] ?? ''),
                ];

                continue;
            }

            if ($o !== null && $n !== null) {
                $qo = (string) ($o['quantity_per_unit'] ?? '');
                $qn = (string) ($n['quantity_per_unit'] ?? '');
                if ($qo !== $qn) {
                    $changed[] = [
                        'code' => $n['code'] ?? $o['code'] ?? '—',
                        'old_qty' => $qo,
                        'new_qty' => $qn,
                        'trend' => self::numericTrend($qo, $qn),
                    ];
                }
            }
        }

        return [
            'mode' => 'bom',
            'bom' => [
                'added' => $added,
                'removed' => $removed,
                'changed' => $changed,
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array<string, array<string, mixed>>
     */
    private static function indexBomLines(array $lines): array
    {
        $out = [];
        foreach ($lines as $line) {
            $id = isset($line['component_item_id']) ? (string) $line['component_item_id'] : null;
            if ($id === null || $id === '') {
                continue;
            }
            $out[$id] = $line;
        }

        return $out;
    }

    private static function presentFieldDiff(array $oldValues, array $newValues, string $tableName): array
    {
        $flatOld = self::flattenForDiff($oldValues);
        $flatNew = self::flattenForDiff($newValues);
        $keys = array_unique(array_merge(array_keys($flatOld), array_keys($flatNew)));
        sort($keys);

        $rows = [];
        foreach ($keys as $key) {
            $o = $flatOld[$key] ?? null;
            $n = $flatNew[$key] ?? null;
            if (self::valuesEqual($o, $n)) {
                continue;
            }
            $label = self::labelForField($key, $tableName);
            $oldStr = self::formatScalarForDisplay($key, $o, $tableName);
            $newStr = self::formatScalarForDisplay($key, $n, $tableName);
            $rows[] = [
                'label' => $label,
                'old' => $oldStr,
                'new' => $newStr,
                'trend' => self::trendForPair($o, $n),
            ];
        }

        if ($rows === []) {
            return self::presentKeyValue($oldValues, $newValues, 'update', $tableName);
        }

        return [
            'mode' => 'diff',
            'diff_rows' => $rows,
        ];
    }

    private static function presentKeyValue(array $oldValues, array $newValues, string $action, string $tableName): array
    {
        $kvOld = [];
        $kvNew = [];

        foreach (self::flattenForDiff($oldValues) as $k => $v) {
            $kvOld[] = [
                'label' => self::labelForField($k, $tableName),
                'value' => self::formatScalarForDisplay($k, $v, $tableName),
            ];
        }

        foreach (self::flattenForDiff($newValues) as $k => $v) {
            $kvNew[] = [
                'label' => self::labelForField($k, $tableName),
                'value' => self::formatScalarForDisplay($k, $v, $tableName),
            ];
        }

        return [
            'mode' => 'kv',
            'kv_old' => $kvOld,
            'kv_new' => $kvNew,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function flattenForDiff(array $data, string $prefix = ''): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value) && ! array_is_list($value) && $value !== []) {
                $out = array_merge($out, self::flattenForDiff($value, $path));
            } elseif (is_array($value) && array_is_list($value)) {
                $out[$path] = $value;
            } else {
                $out[$path] = $value;
            }
        }

        return $out;
    }

    private static function valuesEqual(mixed $a, mixed $b): bool
    {
        if ($a === $b) {
            return true;
        }
        if (is_numeric($a) && is_numeric($b)) {
            return abs((float) $a - (float) $b) < 0.0000001;
        }

        return json_encode($a) === json_encode($b);
    }

    private static function labelForField(string $key, string $tableName): string
    {
        $base = [
            'status' => 'الحالة',
            'production_number' => 'رقم أمر الإنتاج',
            'journal_entry_id' => 'رقم القيد المحاسبي',
            'delivery_number' => 'رقم أمر التوريد',
            'customer_id' => 'معرف العميل',
            'customer_name' => 'اسم العميل',
            'order_date' => 'تاريخ الطلب',
            'total' => 'الإجمالي',
            'reference' => 'المرجع',
            'start_date' => 'تاريخ البداية',
            'lines' => 'البنود',
            'quantity' => 'الكمية',
            'quantity_per_unit' => 'الكمية لكل وحدة',
            'component_item_id' => 'الصنف (معرف)',
            'code' => 'الرمز',
            'unit_price' => 'سعر الوحدة',
            'price' => 'السعر',
            'paid_amount' => 'المبلغ المدفوع',
            'due_date' => 'تاريخ الاستحقاق',
            'payment_id' => 'سند الصرف',
            'payment_reference' => 'مرجع السند',
        ];

        if (isset($base[$key])) {
            return $base[$key];
        }

        $short = $key;
        if (str_contains($key, '.')) {
            $parts = explode('.', $key);
            $short = end($parts);
        }

        if ($tableName === 'service_orders') {
            $serviceLabels = [
                'reference_number' => 'رقم طلب الخدمة',
                'service_type' => 'نوع الخدمة',
                'assigned_technician_id' => 'الفني المسند',
                'is_paid_service' => 'خدمة مدفوعة',
                'outside_warranty' => 'خارج الضمان',
                'parts_added' => 'إضافة قطع غيار',
                'item_id' => 'الصنف',
                'sales_invoice_id' => 'فاتورة مرتبطة',
                'executed_at' => 'تاريخ التنفيذ',
                'by_technician_id' => 'المستخدم (فني)',
            ];
            if (isset($serviceLabels[$short])) {
                return $serviceLabels[$short];
            }
        }

        return $base[$short] ?? self::humanizeKey($short);
    }

    private static function humanizeKey(string $key): string
    {
        return str_replace('_', ' ', $key);
    }

    private static function formatScalarForDisplay(string $key, mixed $value, string $tableName): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_array($value)) {
            if ($value === []) {
                return '—';
            }

            return self::formatArrayListSummary($value);
        }

        $shortKey = $key;
        if (str_contains($key, '.')) {
            $parts = explode('.', $key);
            $shortKey = end($parts);
        }

        if ($shortKey === 'status' || str_ends_with($key, '.status')) {
            return self::translateStatus((string) $value, $tableName);
        }

        if (is_numeric($value) && (str_contains($shortKey, 'amount') || str_contains($shortKey, 'total') || str_contains($shortKey, 'price') || $shortKey === 'quantity_per_unit')) {
            return is_float($value + 0) || str_contains((string) $value, '.')
                ? rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.') ?: '0'
                : (string) $value;
        }

        return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private static function translateStatus(string $value, string $tableName): string
    {
        if ($tableName === 'service_orders') {
            $so = [
                'open' => 'مفتوح',
                'assigned' => 'مسند لفني',
                'in_progress' => 'قيد التنفيذ',
                'completed' => 'مكتمل',
                'cancelled' => 'ملغى',
            ];

            return $so[$value] ?? $value;
        }

        if ($tableName === 'purchase_invoices') {
            $pi = [
                'draft' => 'مسودة',
                'unpaid' => 'غير مدفوعة',
                'partial' => 'مدفوعة جزئياً',
                'paid' => 'مدفوعة',
                'overdue' => 'متأخرة',
            ];

            return $pi[$value] ?? $value;
        }

        $map = [
            'pending' => 'قيد الانتظار',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغى',
            'delivered' => 'تم التسليم',
            'معلق' => 'معلق',
            'مكتمل' => 'مكتمل',
            'ملغي' => 'ملغي',
        ];

        return $map[$value] ?? $value;
    }

    /**
     * @param  list<array<string, mixed>>|array<string, mixed>  $value
     */
    private static function formatArrayListSummary(array $value): string
    {
        if (array_is_list($value)) {
            $n = count($value);

            return 'قائمة ('.$n.' عنصر)';
        }

        $parts = [];
        foreach ($value as $k => $v) {
            $parts[] = $k.': '.(is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE));
        }

        return implode('، ', $parts);
    }

    private static function trendForPair(mixed $old, mixed $new): string
    {
        if (is_numeric($old) && is_numeric($new)) {
            return self::numericTrend((string) $old, (string) $new);
        }

        return 'neutral';
    }

    private static function numericTrend(string $old, string $new): string
    {
        if (! is_numeric($old) || ! is_numeric($new)) {
            return 'neutral';
        }

        $o = (float) $old;
        $n = (float) $new;
        if (abs($o - $n) < 0.0000001) {
            return 'same';
        }

        return $n > $o ? 'up' : 'down';
    }
}
