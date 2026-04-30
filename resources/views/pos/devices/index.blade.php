@extends('layouts.pos')

@section('title', 'اختيار الجهاز - '.config('app.name'))

@section('content')
<div class="max-w-full bg-gray-50 min-h-[calc(100vh-8rem)] -mx-4 sm:-mx-6 px-4 sm:px-6 py-6 space-y-6" dir="rtl">
    <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center justify-between gap-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 inline-flex items-center gap-2">
                أجهزة نقطة البيع
                <x-info field="pos.devices_intro" />
            </h1>
            <p class="text-sm text-gray-500 mt-1">اختر جهازاً لهذا المتصفح أو أنشئ جهازاً جديداً.</p>
        </div>
        <button
            type="button"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold shadow-sm hover:bg-blue-700 transition shrink-0"
            data-bs-toggle="modal"
            data-bs-target="#createPosDeviceModal"
        >
            إنشاء جهاز
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse($devices as $device)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col gap-4">
                <div class="flex justify-between items-start gap-3">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-gray-900 truncate">{{ $device['name'] }}</h2>
                        <p class="text-sm text-gray-500 mt-1">{{ $device['warehouse_name'] }}</p>
                    </div>
                    <span class="inline-flex shrink-0 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">{{ $device['status'] }}</span>
                </div>
                <button
                    type="button"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-blue-200 bg-blue-50 text-blue-800 text-sm font-semibold hover:bg-blue-100 transition js-choose-pos-device"
                    data-device-token="{{ $device['device_token'] }}"
                >
                    اختيار الجهاز
                </button>
            </div>
        @empty
            <div class="md:col-span-2 xl:col-span-3 bg-white rounded-xl border border-gray-200 shadow-sm p-10 text-center">
                <p class="text-gray-600 mb-6 max-w-md mx-auto">لا توجد أجهزة مضافة. أنشئ جهازاً جديداً للمتابعة إلى شاشة الكاشير.</p>
                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold shadow-sm hover:bg-blue-700 transition"
                    data-bs-toggle="modal"
                    data-bs-target="#createPosDeviceModal"
                >
                    إنشاء جهاز
                </button>
            </div>
        @endforelse
    </div>
</div>

<div class="modal fade" id="createPosDeviceModal" tabindex="-1" aria-labelledby="createPosDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-xl border-0 shadow-xl">
            <form method="POST" action="{{ route('pos.devices.store') }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h2 class="modal-title h5 mb-0 fw-bold" id="createPosDeviceModalLabel">إنشاء جهاز نقطة بيع</h2>
                    <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="mb-4">
                        <label for="device_name" class="form-label fw-semibold d-inline-flex align-items-center gap-1 flex-wrap">
                            اسم الجهاز
                            <x-info field="pos.device_name_label" />
                        </label>
                        <input
                            type="text"
                            class="form-control rounded-lg @error('name') is-invalid @enderror"
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
                        <label for="device_warehouse_id" class="form-label fw-semibold d-inline-flex align-items-center gap-1 flex-wrap">
                            المخزن (منتج تام)
                            <x-info field="pos.device_warehouse_label" />
                        </label>
                        <select
                            id="device_warehouse_id"
                            name="warehouse_id"
                            class="form-select rounded-lg @error('warehouse_id') is-invalid @enderror"
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
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-lg" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-lg px-4" @disabled($warehouses->isEmpty())>حفظ الجهاز</button>
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
