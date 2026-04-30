@extends('layouts.pos')

@section('title', 'اختيار الجهاز - '.config('app.name'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1">اختر الجهاز</h1>
        <p class="text-muted mb-0 small">اختر جهاز نقطة البيع لهذا المتصفح أو أنشئ جهازاً جديداً.</p>
    </div>
    <button
        type="button"
        class="btn btn-primary rounded-lg"
        data-bs-toggle="modal"
        data-bs-target="#createPosDeviceModal"
    >
        إنشاء جهاز
    </button>
</div>

<div class="row g-4">
    @forelse($devices as $device)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-lg h-100">
                <div class="card-body d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h2 class="h6 fw-bold mb-1">{{ $device['name'] }}</h2>
                            <p class="text-muted small mb-0">{{ $device['warehouse_name'] }}</p>
                        </div>
                        <span class="badge rounded-pill bg-light text-dark">{{ $device['status'] }}</span>
                    </div>

                    <button
                        type="button"
                        class="btn btn-outline-primary rounded-lg w-100 js-choose-pos-device"
                        data-device-token="{{ $device['device_token'] }}"
                    >
                        اختيار الجهاز
                    </button>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-lg">
                <div class="card-body py-5 text-center">
                    <div class="display-6 text-muted mb-2">!</div>
                    <h2 class="h5 fw-bold mb-2">لا توجد أجهزة مضافة</h2>
                    <p class="text-muted mb-3">أنشئ جهازاً جديداً للمتابعة إلى شاشة الكاشير.</p>
                    <button
                        type="button"
                        class="btn btn-primary rounded-lg"
                        data-bs-toggle="modal"
                        data-bs-target="#createPosDeviceModal"
                    >
                        إنشاء جهاز
                    </button>
                </div>
            </div>
        </div>
    @endforelse
</div>

<div class="modal fade" id="createPosDeviceModal" tabindex="-1" aria-labelledby="createPosDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-lg border-0 shadow-lg">
            <form method="POST" action="{{ route('pos.devices.store') }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5 mb-0" id="createPosDeviceModalLabel">إنشاء جهاز نقطة بيع</h2>
                    <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="device_name" class="form-label fw-semibold">
                            اسم الجهاز
                            <x-info field="pos.device_name_label" />
                        </label>
                        <input
                            type="text"
                            class="form-control @error('name') is-invalid @enderror"
                            id="device_name"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="مثال: كاشير الفرع الرئيسي"
                            required
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="device_warehouse_id" class="form-label fw-semibold">
                            المخزن (منتج تام)
                            <x-info field="pos.device_warehouse_label" />
                        </label>
                        <select
                            id="device_warehouse_id"
                            name="warehouse_id"
                            class="form-select @error('warehouse_id') is-invalid @enderror"
                            required
                        >
                            <option value="">اختر المخزن</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse['id'] }}" @selected((string) old('warehouse_id') === (string) $warehouse['id'])>
                                    {{ $warehouse['name'] }} ({{ $warehouse['code'] }})
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if($warehouses->isEmpty())
                            <p class="text-danger small mt-2 mb-0">لا توجد مستودعات «منتج تام» مرتبطة بحسابك حالياً.</p>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-lg" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-lg" @disabled($warehouses->isEmpty())>حفظ الجهاز</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const storageKey = 'active_pos_device';
    const autoToken = @json($autoToken);

    if (autoToken) {
        localStorage.setItem(storageKey, autoToken);
        window.location.assign(@json(route('pos.dashboard')));
        return;
    }

    document.querySelectorAll('.js-choose-pos-device').forEach(function (button) {
        button.addEventListener('click', function () {
            const token = button.getAttribute('data-device-token');
            if (!token) return;
            localStorage.setItem(storageKey, token);
            window.location.assign(@json(route('pos.dashboard')));
        });
    });
});
</script>
@endpush
