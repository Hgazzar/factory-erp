@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" dir="rtl" style="text-align: right;">
    <h2 class="mb-4 fw-bold text-dark">لوحة تحكم الإنتاج والجودة 📊</h2>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <h6 class="opacity-75">إجمالي المخطط</h6>
                    <h2 class="fw-bold mb-0">{{ number_format($summary['total_planned']) }}</h2>
                    <small>قطعة مستهدفة</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body">
                    <h6 class="opacity-75">إجمالي الفعلي</h6>
                    <h2 class="fw-bold mb-0">{{ number_format($summary['total_actual']) }}</h2>
                    <small>إنتاج تام</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger text-white">
                <div class="card-body">
                    <h6 class="opacity-75">إجمالي الهالك</h6>
                    <h2 class="fw-bold mb-0">{{ number_format($summary['total_rejected']) }}</h2>
                    <small>فاقد إنتاج</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning text-dark">
                <div class="card-body">
                    <h6 class="opacity-75">متوسط الجودة (Yield)</h6>
                    <h2 class="fw-bold mb-0">{{ $summary['avg_yield'] }}%</h2>
                    <div class="progress mt-2" style="height: 4px; background: rgba(0,0,0,0.1)">
                        <div class="progress-bar bg-dark" style="width: {{ $summary['avg_yield'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('operations.dashboard.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">من تاريخ</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">إلى تاريخ</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-dark w-100">تحديث البيانات 🔄</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">تفاصيل ورديات الإنتاج</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>الوردية</th>
                            <th>الماكينة</th>
                            <th>المخطط</th>
                            <th>الفعلي</th>
                            <th>الإنجاز %</th>
                            <th>الهالك</th>
                            <th>الجودة %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productionShifts as $pShift)
                        <tr>
                            <td>{{ $pShift->date }}</td>
                            <td><span class="badge bg-secondary px-3">{{ $pShift->shift->name_ar ?? 'وردية' }}</span></td>
                            <td><span class="fw-bold text-dark">{{ $pShift->machine->name_ar ?? 'ماكينة' }}</span></td>
                            <td class="text-muted">{{ $pShift->planned_quantity }}</td>
                            <td class="fw-bold text-success">{{ $pShift->actual_quantity }}</td>
                            <td style="min-width: 150px;">
                                <div class="progress" style="height: 12px;">
                                    <div class="progress-bar bg-info" style="width: {{ min($pShift->achievement_rate, 100) }}%">
                                        <small>{{ $pShift->achievement_rate }}%</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-danger fw-bold">{{ $pShift->rejected_quantity }}</td>
                            <td>
                                @php
                                    $color = $pShift->yield_rate >= 95 ? 'bg-success' : ($pShift->yield_rate >= 85 ? 'bg-warning text-dark' : 'bg-danger');
                                @endphp
                                <span class="badge rounded-pill {{ $color }} px-3">
                                    {{ $pShift->yield_rate }}%
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-5 text-muted">لا توجد بيانات لهذه الفترة</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection