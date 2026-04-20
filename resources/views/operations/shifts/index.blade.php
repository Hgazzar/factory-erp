@extends('layouts.app')

@section('title', 'إدارة الورديات - Factory ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">إدارة ورديات الإنتاج</h1>
    <a href="{{ route('operations.production-entry.create') }}" class="btn btn-outline-light bg-primary text-white">
        تسجيل إنتاج
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <form class="row g-3 align-items-end" method="GET" action="{{ route('operations.shifts.index') }}">
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الوردية</label>
                        <input type="date" name="date" class="form-control" value="{{ $date }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-outline-primary mt-3 mt-md-0">تحديث</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>وردية اليوم {{ $date }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>الوردية</th>
                                <th>الخط / الماكينة</th>
                                <th>مخطط</th>
                                <th>فعلي</th>
                                <th>حالة</th>
                                <th scope="col" class="text-center" style="width: 1%; white-space: nowrap;">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productionShifts as $ps)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $ps->shift?->name_ar ?? '-' }}</div>
                                        <div class="small text-muted">{{ $ps->shift?->code }}</div>
                                        <div class="small text-muted">
                                            {{ $ps->planned_start_at?->format('H:i') ?? '--:--' }}
                                            -
                                            {{ $ps->planned_end_at?->format('H:i') ?? '--:--' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($ps->productionLine)
                                            خط: {{ $ps->productionLine->name_ar }} ({{ $ps->productionLine->code }})<br>
                                        @endif
                                        @if($ps->machine)
                                            ماكينة: {{ $ps->machine->name_ar }} ({{ $ps->machine->code }})
                                        @endif
                                        @if(!$ps->productionLine && !$ps->machine)
                                            <span class="text-muted">غير محدد</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>الكمية: {{ number_format($ps->planned_quantity, 2) }}</div>
                                    </td>
                                    <td>
                                        <div>منتج: {{ number_format($ps->actual_quantity, 2) }}</div>
                                        <div>هالك: {{ number_format($ps->rejected_quantity, 2) }}</div>
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = match($ps->status) {
                                                \App\Models\ProductionShift::STATUS_IN_PROGRESS => 'bg-success',
                                                \App\Models\ProductionShift::STATUS_COMPLETED => 'bg-secondary',
                                                \App\Models\ProductionShift::STATUS_CANCELLED => 'bg-danger',
                                                default => 'bg-warning text-dark',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            @if($ps->status === \App\Models\ProductionShift::STATUS_IN_PROGRESS)
                                                جارية
                                            @elseif($ps->status === \App\Models\ProductionShift::STATUS_COMPLETED)
                                                مكتملة
                                            @elseif($ps->status === \App\Models\ProductionShift::STATUS_CANCELLED)
                                                ملغاة
                                            @else
                                                مخططة
                                            @endif
                                        </span>
                                        <div class="small text-muted mt-1">
                                            {{ $ps->actual_start_at?->format('H:i') ?? '--:--' }}
                                            -
                                            {{ $ps->actual_end_at?->format('H:i') ?? '--:--' }}
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        @php $shiftMenuId = 'production-shift-actions-'.$ps->id; @endphp
                                        <x-erp-actions-dropdown :menu-id="$shiftMenuId">
                                            @if($ps->status !== \App\Models\ProductionShift::STATUS_COMPLETED)
                                                @if($ps->status !== \App\Models\ProductionShift::STATUS_IN_PROGRESS)
                                                    <form action="{{ route('operations.shifts.start', $ps) }}" method="POST" class="m-0">
                                                        @csrf
                                                        <button type="submit"
                                                                class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-emerald-800 transition hover:bg-emerald-50"
                                                                role="menuitem">
                                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z"/></svg>
                                                            </span>
                                                            <span class="flex-1 leading-snug">بدء الوردية</span>
                                                        </button>
                                                    </form>
                                                    <div class="mx-2 my-2 border-t border-gray-100"></div>
                                                @endif
                                                <form action="{{ route('operations.shifts.complete', $ps) }}" method="POST" class="m-0"
                                                      onsubmit="return confirm('تأكيد إنهاء الوردية؟');">
                                                    @csrf
                                                    <button type="submit"
                                                            class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-gray-50"
                                                            role="menuitem">
                                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M11.354 4.646a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708l6-6a.5.5 0 0 1 .708 0z"/></svg>
                                                        </span>
                                                        <span class="flex-1 leading-snug">إنهاء الوردية</span>
                                                    </button>
                                                </form>
                                            @else
                                                <p class="m-0 px-3 py-2.5 text-right text-xs text-gray-500" role="presentation">منتهية</p>
                                            @endif
                                        </x-erp-actions-dropdown>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        لا توجد ورديات لهذا التاريخ.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                إنشاء وردية إنتاج جديدة
            </div>
            <div class="card-body">
                <form action="{{ route('operations.shifts.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">التاريخ</label>
                        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                               value="{{ old('date', $date) }}" required>
                        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوردية <span class="text-danger">*</span></label>
                        <select name="shift_id" class="form-select @error('shift_id') is-invalid @enderror" required>
                            <option value="">-- اختر الوردية --</option>
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}" {{ old('shift_id') == $shift->id ? 'selected' : '' }}>
                                    {{ $shift->name_ar }} ({{ $shift->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('shift_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">خط الإنتاج</label>
                        <select name="production_line_id" class="form-select @error('production_line_id') is-invalid @enderror">
                            <option value="">-- بدون تحديد --</option>
                            @foreach($productionLines as $line)
                                <option value="{{ $line->id }}" {{ old('production_line_id') == $line->id ? 'selected' : '' }}>
                                    {{ $line->name_ar }} ({{ $line->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('production_line_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الماكينة</label>
                        <select name="machine_id" class="form-select @error('machine_id') is-invalid @enderror">
                            <option value="">-- بدون تحديد --</option>
                            @foreach($machines as $machine)
                                <option value="{{ $machine->id }}" {{ old('machine_id') == $machine->id ? 'selected' : '' }}>
                                    {{ $machine->name_ar }} ({{ $machine->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('machine_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">بداية مخططة</label>
                            <input type="datetime-local" name="planned_start_at"
                                   class="form-control @error('planned_start_at') is-invalid @enderror"
                                   value="{{ old('planned_start_at') }}">
                            @error('planned_start_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">نهاية مخططة</label>
                            <input type="datetime-local" name="planned_end_at"
                                   class="form-control @error('planned_end_at') is-invalid @enderror"
                                   value="{{ old('planned_end_at') }}">
                            @error('planned_end_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">الكمية المخططة</label>
                        <input type="number" inputmode="decimal" name="planned_quantity"
                               class="form-control @error('planned_quantity') is-invalid @enderror"
                               value="{{ old('planned_quantity', 0) }}" min="0" step="any">
                        @error('planned_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mt-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2"
                                  class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mt-3 d-grid">
                        <button type="submit" class="btn btn-primary">حفظ الوردية</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

