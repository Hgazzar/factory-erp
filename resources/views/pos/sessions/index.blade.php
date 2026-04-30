@extends('layouts.pos')

@section('title', 'جلسات نقاط البيع - '.config('app.name'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1">جلسات نقاط البيع</h1>
        <p class="text-muted mb-0 small">فتح جلسة جديدة ومتابعة الجلسات المفتوحة على أجهزتك.</p>
    </div>
    <a href="{{ route('pos.dashboard') }}" class="btn btn-sm btn-outline-secondary rounded-lg">لوحة التحكم</a>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm rounded-lg h-100">
            <div class="card-header bg-white border-0 py-3">
                <span class="fw-semibold">فتح جلسة جديدة</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('pos.sessions.store') }}" class="d-flex flex-column gap-3">
                    @csrf
                    <div>
                        <label class="form-label fw-semibold" for="pos_device_id">
                            الجهاز
                            <x-info field="pos.col_device" />
                        </label>
                        <select id="pos_device_id" name="pos_device_id" class="form-select @error('pos_device_id') is-invalid @enderror" required>
                            <option value="">اختر الجهاز</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}" @selected((string) old('pos_device_id') === (string) $device->id)>{{ $device->name }}</option>
                            @endforeach
                        </select>
                        @error('pos_device_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="employee_id">
                            الكاشير
                            <x-info field="pos.session_employee_label" />
                        </label>
                        <select id="employee_id" name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                            <option value="">اختر الموظف</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" @selected((string) old('employee_id') === (string) $employee->id)>
                                    {{ $employee->name }}{{ $employee->code ? ' ('.$employee->code.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label fw-semibold" for="opening_balance">
                            الرصيد الافتتاحي
                            <x-info field="pos.session_opening_balance_label" />
                        </label>
                        <input type="number" step="0.01" min="0" id="opening_balance" name="opening_balance" value="{{ old('opening_balance', '0') }}" class="form-control @error('opening_balance') is-invalid @enderror">
                        @error('opening_balance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary rounded-lg" @disabled($devices->isEmpty() || $employees->isEmpty())>
                        فتح الجلسة
                    </button>
                </form>

                @if($devices->isEmpty() || $employees->isEmpty())
                    <p class="text-danger small mt-3 mb-0">يتطلب فتح الجلسة وجود جهاز نشط وموظف فعّال تابعين لحسابك.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm rounded-lg h-100">
            <div class="card-header bg-white border-0 py-3">
                <span class="fw-semibold">الجلسات المفتوحة</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 text-end">
                        <thead class="table-light">
                            <tr>
                                <th><x-info field="pos.col_device" /></th>
                                <th>الكاشير</th>
                                <th>الرصيد الافتتاحي</th>
                                <th><x-info field="pos.col_datetime" /></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($openSessions as $session)
                                <tr>
                                    <td>{{ $session->posDevice?->name ?? '—' }}</td>
                                    <td>{{ $session->employee?->name ?? '—' }}</td>
                                    <td class="tabular-nums">{{ $erpCurrencyCode }} {{ number_format((float) $session->opening_balance, 2) }}</td>
                                    <td class="small text-muted">{{ $session->opened_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">لا توجد جلسات مفتوحة حالياً.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
