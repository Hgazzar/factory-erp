@extends('layouts.pos')

@section('title', 'إيصالات نقاط البيع - '.config('app.name'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">إيصالات نقاط البيع</h1>
        <p class="text-muted mb-0 small">قائمة عمليات البيع المكتملة.</p>
    </div>
    <a href="{{ route('pos.dashboard') }}" class="btn btn-sm btn-outline-secondary rounded-lg">لوحة التحكم</a>
</div>

<div class="card shadow-sm border-0 rounded-lg">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الإيصال <x-info field="pos.col_receipt" /></th>
                        <th>المبلغ <x-info field="pos.col_amount_sar" /></th>
                        <th>طريقة الدفع</th>
                        <th>الجهاز <x-info field="pos.col_device" /></th>
                        <th>الوقت <x-info field="pos.col_datetime" /></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        <tr>
                            <td>
                                <a href="{{ route('pos.sales.show', $sale) }}" class="fw-semibold text-decoration-none">{{ $sale->receipt_number }}</a>
                            </td>
                            <td class="tabular-nums">{{ $erpCurrencyCode }} {{ number_format((float) $sale->total_price, 2) }}</td>
                            <td>{{ $sale->payment_method }}</td>
                            <td>{{ $sale->posDevice?->name ?? '—' }}</td>
                            <td class="small text-muted">{{ $sale->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">لا توجد إيصالات مسجلة بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($sales->hasPages())
        <div class="card-footer bg-white">{{ $sales->links() }}</div>
    @endif
</div>
@endsection
