@extends('layouts.pos')

@section('title', 'إيصال '.$posSale->receipt_number.' - '.config('app.name'))

@section('content')
<div class="mb-4">
    <a href="{{ route('pos.receipts.index') }}" class="text-muted small text-decoration-none">← الإيصالات</a>
</div>

<div class="card shadow-sm border-0 rounded-lg mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <h1 class="h5 mb-1">{{ $posSale->receipt_number }}</h1>
                <p class="text-muted mb-0 small">{{ $posSale->created_at?->format('Y-m-d H:i') }}</p>
            </div>
            <div class="text-start">
                <div class="fs-5 fw-bold tabular-nums">{{ $erpCurrencyCode }} {{ number_format((float) $posSale->total_price, 2) }}</div>
                <div class="small text-muted">طريقة الدفع: {{ $posSale->payment_method }}</div>
            </div>
        </div>
        <hr>
        <div class="row g-3 small">
            <div class="col-md-4">
                <div class="text-muted">جهاز نقطة البيع</div>
                <div>{{ $posSale->posDevice?->name ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted">المستودع</div>
                <div>{{ $posSale->posDevice?->warehouse?->name_ar ?? $posSale->posDevice?->warehouse?->name_en ?? '—' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted">الجلسة</div>
                <div>{{ $posSale->posSession ? '#'.$posSale->pos_session_id : '—' }}</div>
            </div>
            @if($posSale->journalEntry)
                <div class="col-md-4">
                    <div class="text-muted">القيد المحاسبي</div>
                    <div>
                        <a href="{{ route('finance.journals.show', $posSale->journalEntry) }}" class="text-decoration-none">
                            {{ $posSale->journalEntry->reference ?? 'عرض القيد' }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-lg">
    <div class="card-header bg-white border-0 py-3 fw-semibold">بنود الإيصال</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>الصنف</th>
                        <th class="text-end">الكمية</th>
                        <th class="text-end">سعر الوحدة</th>
                        <th class="text-end text-muted small">ت. الوحدة (تكلفة)</th>
                        <th class="text-end">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($posSale->lines as $line)
                        <tr>
                            <td>
                                {{ $line->item?->name_ar ?? $line->item?->name_en ?? $line->item?->code }}
                                <span class="text-muted small d-block">{{ $line->item?->code }}</span>
                            </td>
                            <td class="text-end tabular-nums">{{ rtrim(rtrim(number_format((float) $line->quantity, 4, '.', ''), '0'), '.') ?: '0' }}</td>
                            <td class="text-end tabular-nums">{{ $erpCurrencyCode }} {{ number_format((float) $line->unit_price, 2) }}</td>
                            <td class="text-end tabular-nums text-muted small">{{ $erpCurrencyCode }} {{ number_format((float) $line->unit_cost, 4) }}</td>
                            <td class="text-end tabular-nums fw-medium">{{ $erpCurrencyCode }} {{ number_format((float) $line->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
