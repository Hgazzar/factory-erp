@extends('layouts.app')

@section('title', 'تسجيل إنتاج - Factory ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">تسجيل الإنتاج اللحظي</h1>
    <a href="{{ route('operations.shifts.index') }}" class="btn btn-outline-secondary">العودة لإدارة الورديات</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-body">
                <form class="row g-3 align-items-end" method="GET" action="{{ route('operations.production-entry.create') }}">
                    <div class="col-md-6">
                        <label class="form-label">تاريخ الوردية</label>
                        <input type="date" name="date" class="form-control" value="{{ $date }}">
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-outline-primary mt-3 mt-md-0">تحديث</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                نموذج إدخال الإنتاج
            </div>
            <div class="card-body">
                <form action="{{ route('operations.production-entry.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">وردية الإنتاج <span class="text-danger">*</span></label>
                        <select name="production_shift_id" class="form-select @error('production_shift_id') is-invalid @enderror" required>
                            <option value="">-- اختر الوردية --</option>
                            @foreach($productionShifts as $ps)
                                <option value="{{ $ps->id }}"
                                    {{ old('production_shift_id', $selectedProductionShiftId) == $ps->id ? 'selected' : '' }}>
                                    {{ $ps->date->format('Y-m-d') }} -
                                    {{ $ps->shift?->name_ar ?? 'غير معرّفة' }}
                                    @if($ps->productionLine)
                                        - خط {{ $ps->productionLine->code }}
                                    @endif
                                    @if($ps->machine)
                                        - م {{ $ps->machine->code }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('production_shift_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الصنف <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" id="item-barcode" class="form-control" placeholder="امسح الباركود أو اكتبه"
                                   autocomplete="off" title="امسح الباركود لاختيار الصنف تلقائياً">
                            <span class="input-group-text small text-muted">باركود</span>
                        </div>
                        <select name="item_id" id="item_id" class="form-select mt-2 @error('item_id') is-invalid @enderror" required>
                            <option value="">-- اختر الصنف --</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" data-barcode="{{ $item->barcode ?? '' }}" {{ old('item_id') == $item->id ? 'selected' : '' }}>
                                    {{ $item->code }} - {{ $item->name_ar }}
                                </option>
                            @endforeach
                        </select>
                        @error('item_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">الكمية المنتجة <span class="text-danger">*</span></label>
                            <input type="number" inputmode="decimal" name="quantity"
                                   class="form-control @error('quantity') is-invalid @enderror"
                                   value="{{ old('quantity') }}" min="0" step="any" required>
                            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الكمية المرفوضة / الهالك (Scrap Quantity)</label>
                            <input type="number" inputmode="decimal" name="rejected_quantity"
                                   class="form-control @error('rejected_quantity') is-invalid @enderror"
                                   value="{{ old('rejected_quantity', 0) }}" min="0" step="any">
                            @error('rejected_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">سبب الهالك (Scrap Reason)</label>
                        <select name="scrap_reason" class="form-select @error('scrap_reason') is-invalid @enderror">
                            <option value="">-- لا يوجد --</option>
                            <option value="quality_defect" {{ old('scrap_reason') === 'quality_defect' ? 'selected' : '' }}>عيب جودة</option>
                            <option value="machine_error" {{ old('scrap_reason') === 'machine_error' ? 'selected' : '' }}>خطأ ماكينة</option>
                            <option value="material_defect" {{ old('scrap_reason') === 'material_defect' ? 'selected' : '' }}>عيب مادة خام</option>
                            <option value="operator_error" {{ old('scrap_reason') === 'operator_error' ? 'selected' : '' }}>خطأ تشغيل</option>
                            <option value="other" {{ old('scrap_reason') === 'other' ? 'selected' : '' }}>أخرى</option>
                        </select>
                        @error('scrap_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mt-3 row g-2">
                        <div class="col-md-6">
                            <label class="form-label">سبب التوقف (Downtime Reason)</label>
                            <select name="downtime_reason" id="downtime_reason" class="form-select @error('downtime_reason') is-invalid @enderror">
                                <option value="">-- لا يوجد --</option>
                                <option value="electricity" {{ old('downtime_reason') === 'electricity' ? 'selected' : '' }}>انقطاع كهرباء</option>
                                <option value="machine_failure" {{ old('downtime_reason') === 'machine_failure' ? 'selected' : '' }}>عطل ماكينة</option>
                                <option value="maintenance" {{ old('downtime_reason') === 'maintenance' ? 'selected' : '' }}>صيانة</option>
                                <option value="other" {{ old('downtime_reason') === 'other' ? 'selected' : '' }}>أخرى</option>
                            </select>
                            @error('downtime_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الساعات الضائعة (Lost Hours)</label>
                            <input type="number" inputmode="decimal" name="downtime_lost_hours" class="form-control @error('downtime_lost_hours') is-invalid @enderror"
                                   value="{{ old('downtime_lost_hours') }}" min="0" max="24" step="any" placeholder="0">
                            @error('downtime_lost_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">وقت التسجيل</label>
                        <input type="datetime-local" name="logged_at"
                               class="form-control @error('logged_at') is-invalid @enderror"
                               value="{{ old('logged_at') }}">
                        @error('logged_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mt-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2"
                                  class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mt-3 d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">تسجيل</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>آخر 10 سجلات إنتاج لتاريخ {{ $date }}</span>
                <a href="{{ route('operations.dashboard.index', ['from_date' => $date, 'to_date' => $date]) }}" class="btn btn-sm btn-outline-primary">
                    عرض في لوحة التحكم
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>الوقت</th>
                                <th>الوردية</th>
                                <th>الصنف</th>
                                <th>منتج</th>
                                <th>هالك</th>
                                <th>ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLogs as $log)
                                <tr>
                                    <td>{{ $log->logged_at?->format('H:i') ?? '-' }}</td>
                                    <td>
                                        {{ $log->productionShift?->shift?->name_ar ?? '-' }}
                                        <div class="small text-muted">
                                            {{ $log->productionShift?->date?->format('Y-m-d') }}
                                        </div>
                                    </td>
                                    <td>
                                        {{ $log->item?->code ?? '-' }} - {{ $log->item?->name_ar ?? '' }}
                                    </td>
                                    <td>{{ number_format($log->quantity, 2) }}</td>
                                    <td>{{ number_format($log->rejected_quantity, 2) }}</td>
                                    <td>{{ $log->notes }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        لا توجد سجلات إنتاج لهذا التاريخ.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var barcodeInput = document.getElementById('item-barcode');
    var itemSelect = document.getElementById('item_id');
    if (!barcodeInput || !itemSelect) return;

    function selectItemById(id) {
        itemSelect.value = id;
        barcodeInput.value = '';
    }

    function searchByBarcode() {
        var barcode = barcodeInput.value.trim();
        if (!barcode) return;
        var url = '{{ route("operations.production-entry.item-by-barcode") }}?barcode=' + encodeURIComponent(barcode);
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.found && data.id) {
                    selectItemById(data.id);
                } else {
                    var opt = Array.from(itemSelect.options).find(function(o) { return o.getAttribute('data-barcode') === barcode; });
                    if (opt) selectItemById(opt.value);
                }
            })
            .catch(function() {});
    }

    barcodeInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); searchByBarcode(); }
    });
    barcodeInput.addEventListener('blur', searchByBarcode);
});
</script>
@endpush
@endsection

