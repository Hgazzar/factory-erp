@extends('layouts.app')

@section('title', 'المستودعات - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">المستودعات</span>
@endsection

@push('styles')
<style>
    .wh-panel { background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem; }
    .wh-panel thead th { background-color: #f9fafb; color: #374151; font-weight: 600; border-bottom: 1px solid #e5e7eb; }
    .wh-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem; padding: 1rem; }
    .wh-card-title { font-size: .8rem; color: #6b7280; font-weight: 600; margin-bottom: .35rem; }
    .wh-card-value { font-size: 1.25rem; font-weight: 700; color: #111827; }
    .wh-star-icon { color: #eab308; }
</style>
@endpush

@section('content')
<div dir="rtl" class="content-wrap">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">المستودعات</h1>
            <p class="text-muted mb-0 small">إدارة المستودعات والرصيد الافتراضي</p>
        </div>
        <a href="{{ route('warehouses.create') }}" class="btn btn-primary">+ مستودع جديد</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="wh-card d-flex justify-content-between align-items-start">
                <div>
                    <div class="wh-card-title"><x-info field="inventory.wh_default" /> المستودع الافتراضي</div>
                    <div class="wh-card-value">{{ $defaultWarehouse?->name_ar ?? '—' }}</div>
                </div>
                <span class="wh-star-icon" title="المستودع الافتراضي">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16"><path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/></svg>
                </span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="wh-card d-flex justify-content-between align-items-start">
                <div>
                    <div class="wh-card-title"><x-info field="inventory.wh_active" /> المستودعات النشطة</div>
                    <div class="wh-card-value">{{ $activeCount ?? 0 }}</div>
                </div>
                <span class="text-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                </span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="wh-card d-flex justify-content-between align-items-start">
                <div>
                    <div class="wh-card-title"><x-info field="inventory.wh_total" /> إجمالي المستودعات</div>
                    <div class="wh-card-value">{{ $total ?? 0 }}</div>
                </div>
                <span class="text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113z"/><path d="M15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/></svg>
                </span>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('warehouses.index') }}" class="wh-panel p-3 mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label fw-semibold"><x-info field="inventory.wh_search" /> البحث في المستودعات</label>
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="البحث في المستودعات..." style="border:1px solid #e5e7eb;background:#f9fafb;border-radius:.5rem;height:2.5rem;">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary" style="height:2.5rem;">بحث</button>
                <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary" style="height:2.5rem;">الكل</a>
            </div>
        </div>
        <div class="mt-2 text-muted small">{{ $warehouses->count() }} الإجمالي</div>
    </form>

    <div class="wh-panel table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th><x-info field="inventory.wh_table_code" /> الرمز</th>
                    <th><x-info field="inventory.wh_table_name" /> الاسم</th>
                    <th><x-info field="inventory.wh_table_name_ar" /> الاسم بالعربية</th>
                    <th><x-info field="inventory.wh_table_city" /> المدينة</th>
                    <th><x-info field="inventory.wh_table_manager" /> المسؤول</th>
                    <th><x-info field="inventory.wh_table_phone" /> الهاتف</th>
                    <th><x-info field="inventory.wh_table_status" /> الحالة</th>
                    <th scope="col" class="text-center" style="width: 1%; white-space: nowrap;"><span class="d-inline-flex align-items-center justify-content-center gap-1"><x-info field="inventory.wh_table_actions" /> إجراءات</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($warehouses as $warehouse)
                <tr>
                    <td>
                        @if($defaultWarehouse && $warehouse->id === $defaultWarehouse->id)
                            <span class="wh-star-icon me-1"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/></svg></span>
                        @endif
                        <span class="badge bg-light text-dark border">{{ $warehouse->code }}</span>
                    </td>
                    <td class="fw-semibold">{{ $warehouse->name_en ?: $warehouse->name_ar }}</td>
                    <td>{{ $warehouse->name_ar }}</td>
                    <td class="text-muted">{{ $warehouse->city ?? '—' }}</td>
                    <td class="text-muted">{{ $warehouse->manager ?? '—' }}</td>
                    <td class="text-muted">{{ $warehouse->phone ?? '—' }}</td>
                    <td>
                        @if($warehouse->is_active)
                            <span class="badge rounded-pill bg-success">نشط</span>
                        @else
                            <span class="badge rounded-pill bg-secondary">غير نشط</span>
                        @endif
                    </td>
                    <td class="text-center align-middle">
                        @php $whMenuId = 'warehouse-actions-'.$warehouse->id; @endphp
                        <x-erp-actions-dropdown :menu-id="$whMenuId">
                            <a href="{{ route('warehouses.edit', $warehouse) }}"
                               class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 text-decoration-none transition hover:bg-gray-50"
                               role="menuitem">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                                </span>
                                <span class="flex-1 text-right font-medium leading-snug">تعديل المستودع</span>
                            </a>
                            <div class="mx-2 my-2 border-t border-gray-100"></div>
                            <form action="{{ route('warehouses.destroy', $warehouse) }}" method="POST" class="m-0" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستودع؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                        role="menuitem">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                    </span>
                                    <span class="flex-1 leading-snug">حذف المستودع</span>
                                </button>
                            </form>
                        </x-erp-actions-dropdown>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">لا توجد مستودعات مطابقة.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
