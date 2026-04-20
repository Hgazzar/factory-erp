@extends('layouts.app')

@section('title', 'التقرير الضريبي - Factory ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h4 mb-1">التقرير الضريبي</h1>
        <p class="text-muted mb-0 small">إجمالي ضريبة القيمة المضافة من المبيعات والمشتريات للفترة المحددة (المبالغ من الفواتير المسجّلة؛ النسبة المرجعية للنظام {{ erp_qty((float) $defaultVatPercent) }}%).</p>
    </div>
    <button type="button" class="btn btn-primary no-print" onclick="window.print()">
        طباعة / حفظ كـ PDF
    </button>
</div>

<form method="GET" action="{{ route('reports.tax.index') }}" class="card shadow-sm mb-4 form-filter">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold">من تاريخ</label>
                <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">إلى تاريخ</label>
                <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">عرض</button>
            </div>
        </div>
    </div>
</form>

@if($hasData)
    <div class="card shadow-sm">
        <div class="card-header">
            <strong>ملخص الضريبة</strong>
            @if($fromDate || $toDate)
                <span class="text-muted small">(من {{ $fromDate ?? '—' }} إلى {{ $toDate ?? '—' }})</span>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>البند</th>
                            <th class="text-end">عدد الفواتير</th>
                            <th class="text-end">الإجمالي</th>
                            <th class="text-end">ض.ق.م</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>المبيعات</td>
                            <td class="text-end">{{ $salesCount }}</td>
                            <td class="text-end">{{ erp_money($salesTotal) }}</td>
                            <td class="text-end">{{ erp_money($salesVat) }}</td>
                        </tr>
                        <tr>
                            <td>المشتريات</td>
                            <td class="text-end">{{ $purchasesCount }}</td>
                            <td class="text-end">{{ erp_money($purchasesTotal) }}</td>
                            <td class="text-end">{{ erp_money($purchasesVat) }}</td>
                        </tr>
                        <tr class="table-light">
                            <td><strong>صافي الضريبة (مبيعات - مشتريات)</strong></td>
                            <td class="text-end">—</td>
                            <td class="text-end">—</td>
                            <td class="text-end"><strong>{{ erp_money($salesVat - $purchasesVat) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@else
    @if(request()->hasAny(['from_date', 'to_date']))
        <div class="alert alert-warning mb-0">
            لا توجد بيانات للفترة المحددة
        </div>
    @endif
@endif
@endsection

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .table td.text-end,
        .table th.text-end {
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .table-responsive { overflow: visible !important; }
    }
</style>
@endpush
