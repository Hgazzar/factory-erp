@extends('layouts.print')

@php
    $emp = $slip->employee;
    $deptName = $emp?->department?->name
        ?? (trim((string) ($emp?->department ?? '')) !== '' ? $emp->department : '—');
    $earn = $slip->items->where('item_kind', \App\Models\PaySlip::ITEM_KIND_EARNING);
    $ded = $slip->items->where('item_kind', \App\Models\PaySlip::ITEM_KIND_DEDUCTION);
@endphp
@section('title', 'قسيمة راتب — ' . ($emp?->name ?? 'موظف') . ' - ' . config('app.name', 'MIRADA ERP'))

@push('print_styles')
<style>
    .payslip-toia { font-size: 1.35rem; font-weight: 800; color: #312e81; letter-spacing: 0.04em; margin: 0; line-height: 1.2; }
    .payslip-subhead { font-size: 0.95rem; color: #64748b; margin: 0.15rem 0 0; }
    .payslip-h2 { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 1rem 0 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.25rem; }
    .payslip-net-block {
        margin-top: 1.25rem;
        padding: 1rem 1.25rem;
        border: 2px solid #1e3a5f;
        border-radius: 0.5rem;
        background: #f8fafc;
        text-align: center;
    }
    .payslip-net-label { font-size: 0.9rem; color: #475569; margin-bottom: 0.25rem; }
    .payslip-net-value { font-size: 1.75rem; font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; direction: ltr; unicode-bidi: embed; }
    .payslip-sig-grid { display: flex; flex-wrap: wrap; gap: 1.5rem; margin-top: 2.5rem; justify-content: space-between; }
    .payslip-sig-box { flex: 1 1 40%; min-height: 5rem; border-top: 1px solid #94a3b8; padding-top: 0.5rem; text-align: center; font-size: 0.88rem; color: #475569; }
    @media print {
        .payslip-net-block { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
@endpush

@section('company_logo')
    <div class="print-logo-wrap" style="align-items: flex-start;">
        <div>
            <p class="payslip-toia">Toia</p>
        </div>
        @if($logoDataUri)
            <img src="{{ $logoDataUri }}" alt="" class="print-logo-img" style="margin-right:0.5rem" />
        @endif
    </div>
@endsection

@section('company_name', $company?->name ?? 'اسم المنشأة')

@section('company_tax')
    @if(filled($company?->tax_number))
        الرقم الضريبي: {{ $company->tax_number }}
    @else
        &nbsp;
    @endif
@endsection

@section('doc_type')
    <div>
        <div style="font-size:1.1rem;font-weight:700">قسيمة راتب</div>
        <div class="payslip-subhead">الفترة: {{ $payroll->periodLabelAr() }} — {{ $payroll->year }}</div>
    </div>
@endsection

@section('document_meta')
    <div class="print-meta" style="grid-template-columns: 1fr 1fr;">
        <div class="print-meta-item">
            <span class="print-meta-label">الموظف</span>
            <span class="print-meta-value">{{ $emp?->name ?? '—' }}</span>
        </div>
        <div class="print-meta-item">
            <span class="print-meta-label">الكود</span>
            <span class="print-meta-value font-mono" dir="ltr">{{ $emp?->code ? $emp->code : '—' }}</span>
        </div>
        <div class="print-meta-item">
            <span class="print-meta-label">القسم</span>
            <span class="print-meta-value">{{ $deptName }}</span>
        </div>
        <div class="print-meta-item">
            <span class="print-meta-label">دورة / قسيمة</span>
            <span class="print-meta-value">#{{ $payroll->id }} — قسيمة #{{ $slip->id }}</span>
        </div>
    </div>
@endsection

@section('document_table')
    <h2 class="payslip-h2">الاستحقاقات</h2>
    <div class="print-table-wrap">
        <table class="print-table">
            <thead>
                <tr>
                    <th>البند</th>
                    <th class="num" style="width:30%">المبلغ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($earn as $row)
                    <tr>
                        <td>{{ $row->label ?? $row->item_code }}</td>
                        <td class="num">{{ number_format((float) $row->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">—</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="payslip-h2">الاستقطاعات</h2>
    <div class="print-table-wrap">
        <table class="print-table">
            <thead>
                <tr>
                    <th>البند</th>
                    <th class="num" style="width:30%">المبلغ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ded as $row)
                    <tr>
                        <td>{{ $row->label ?? $row->item_code }}</td>
                        <td class="num">{{ number_format((float) $row->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">—</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@section('document_footer')
    <div class="payslip-net-block">
        <div class="payslip-net-label">صافي المرتب ({{ $payroll->periodLabelAr() }})</div>
        <div class="payslip-net-value">{{ number_format((float) $slip->net_salary, 2) }}</div>
    </div>

    <div class="payslip-sig-grid">
        <div class="payslip-sig-box">توقيع الموظف</div>
        <div class="payslip-sig-box">توقيع المدير المالي</div>
    </div>
@endsection

@push('print_scripts')
@if(request()->boolean('autoprint'))
<script>
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 400);
    });
</script>
@endif
@endpush
