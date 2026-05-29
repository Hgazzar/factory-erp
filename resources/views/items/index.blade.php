@extends('layouts.app')

@section('title', 'الأصناف - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">المنتجات</span>
@endsection

@push('styles')
<style>
    .items-panel { background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem; }
    .items-panel thead th {
        background-color: #f9fafb;
        color: #374151;
        font-weight: 600;
        border-bottom: 1px solid #e5e7eb;
    }
    .items-toolbar { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; }
    .items-actions { display: inline-flex; gap: .5rem; flex-wrap: wrap; }
    .items-filter-grid { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 1rem; }
    @media (max-width: 992px) { .items-filter-grid { grid-template-columns: 1fr; } }
    .items-panel .form-control,
    .items-panel .form-select {
        border: 1px solid #e5e7eb;
        background-color: #f9fafb;
        color: #374151;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        height: 2.5rem;
        min-height: 2.5rem;
    }
    .items-panel .form-control::placeholder { color: #9ca3af; }
    .items-panel .form-control:focus,
    .items-panel .form-select:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 .2rem rgba(96, 165, 250, .2);
        background-color: #f9fafb;
        color: #374151;
    }
    .items-filter-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    .items-filter-buttons .btn {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        height: 2.5rem;
        min-height: 2.5rem;
        padding: 0 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .items-filter-buttons .btn-primary {
        border-color: #2563eb;
        background-color: #2563eb;
        color: #fff;
    }
    .items-filter-buttons .btn-outline-secondary {
        background-color: #ffffff;
        color: #374151;
    }
    .product-name-wrap { display: flex; align-items: center; gap: .65rem; }
    .product-thumb { width: 38px; height: 38px; border-radius: .5rem; object-fit: cover; border: 1px solid #e5e7eb; background: #f3f4f6; flex-shrink: 0; }
    .product-thumb-placeholder { width: 38px; height: 38px; border-radius: .5rem; display: inline-flex; align-items: center; justify-content: center; background: #eef2ff; color: #4f46e5; font-weight: 700; font-size: .72rem; border: 1px solid #e5e7eb; flex-shrink: 0; }
    .status-badge { border-radius: 999px; padding: .32rem .6rem; font-size: .75rem; font-weight: 600; white-space: nowrap; display: inline-flex; }
    .status-available { background: #dcfce7; color: #166534; }
    .status-low { background: #fef3c7; color: #92400e; }
    .status-out { background: #fee2e2; color: #991b1b; }
    .erp-actions-menu {
        overflow: visible !important;
        max-height: none !important;
        -webkit-overflow-scrolling: auto;
    }
</style>
@endpush

@section('content')
<div dir="rtl" class="content-wrap">
    @if (session('import_result'))
        <x-import-summary :result="session('import_result')" />
    @endif
    <div class="items-toolbar mb-4">
        <div>
            <h1 class="h4 mb-1">المنتجات</h1>
            <p class="text-muted mb-0 small">قائمة تفاعلية للأصناف مع المخزون والحالة التشغيلية</p>
        </div>
        <div class="items-actions">
            <a href="{{ route('items.create') }}" class="btn btn-primary">+ منتج جديد</a>
            <button type="button" data-import-modal="1" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#itemsImportModal">استيراد</button>
            <a href="{{ route('items.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-outline-secondary">تصدير</a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">طباعة باركود</button>
        </div>
    </div>

    <form method="GET" action="{{ route('items.index') }}" class="items-panel p-3 mb-4">
        <div class="items-filter-grid">
            <div>
                <label class="form-label fw-semibold">بحث سريع <x-info field="inventory.items_search" /></label>
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="ابحث بالاسم أو الكود">
            </div>
            <div>
                <label class="form-label fw-semibold">المستودع <x-info field="inventory.items_filter_warehouse" /></label>
                <select name="warehouse_id" class="form-select">
                    <option value="">الكل</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ (string) $warehouseId === (string) $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name_ar ?: ($warehouse->name_en ?: $warehouse->code) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label fw-semibold">التصنيف <x-info field="inventory.items_filter_category" /></label>
                <select name="category" class="form-select">
                    <option value="">الكل</option>
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}" {{ $category === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label fw-semibold">الحالة <x-info field="inventory.items_filter_status" /></label>
                <select name="status" class="form-select">
                    <option value="">الكل</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="items-filter-buttons">
            <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">إعادة ضبط</a>
            <button type="submit" class="btn btn-primary">تطبيق</button>
        </div>
    </form>

    <div class="items-panel table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>الرمز <x-info field="inventory.items_table_code" /></th>
                    <th>اسم المنتج <x-info field="inventory.items_table_name" /></th>
                    <th>التصنيف <x-info field="inventory.items_table_category" /></th>
                    <th>تكلفة الوحدة (WAC) <x-info field="inventory.items_table_cost" /></th>
                    <th>سعر البيع <x-info field="inventory.items_table_price" /></th>
                    <th>كمية المخزون <x-info field="inventory.items_table_stock" /></th>
                    <th>الحالة <x-info field="inventory.items_table_status" /></th>
                    <th scope="col" class="text-center" style="width: 1%; white-space: nowrap;"><span class="inline-flex items-center justify-center gap-1"><x-info field="inventory.items_table_actions" /> الإجراءات</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    @php
                        $totalStock = (float) ($item->total_stock ?? 0);
                        $minStock = (float) ($item->min_stock ?? 0);
                        $statusText = $totalStock <= 0 ? 'نفاد الكمية' : (($minStock > 0 && $totalStock <= $minStock) ? 'منخفض المخزون' : 'متوفر');
                        $statusClass = $totalStock <= 0 ? 'status-out' : (($minStock > 0 && $totalStock <= $minStock) ? 'status-low' : 'status-available');
                        $typeLabel = $categories[$item->type] ?? 'غير محدد';
                        $thumb = $item->catalogThumbUrl();
                    @endphp
                    <tr>
                        <td><span class="badge text-bg-light border">{{ $item->code }}</span></td>
                        <td>
                            <div class="product-name-wrap">
                                @if($thumb)
                                    <img src="{{ $thumb }}" alt="{{ $item->name_ar }}" class="product-thumb">
                                @else
                                    <span class="product-thumb-placeholder">{{ mb_substr($item->name_ar ?: $item->name_en ?: 'ص', 0, 1) }}</span>
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $item->name_ar }}</div>
                                    @if($item->name_en)
                                        <div class="small text-muted">{{ $item->name_en }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $typeLabel }}</td>
                        <td class="tabular-nums">SAR {{ number_format((float) ($item->cost ?? 0), 2) }}</td>
                        <td class="tabular-nums">SAR {{ number_format((float) ($item->selling_price ?? 0), 2) }}</td>
                        <td>{{ number_format($totalStock, 2) }}</td>
                        <td><span class="status-badge {{ $statusClass }}">{{ $statusText }}</span></td>
                        <td class="text-center align-middle">
                            <div class="relative inline-flex items-center justify-center">
                                <button type="button"
                                        class="erp-actions-trigger inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 transition shrink-0"
                                        data-actions-menu="item-actions-{{ $item->id }}"
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                        title="المزيد من الإجراءات"
                                        aria-label="المزيد من الإجراءات">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                    </svg>
                                </button>

                                <div id="item-actions-{{ $item->id }}"
                                     class="erp-actions-menu hidden min-w-[13.5rem] max-w-[min(18rem,calc(100vw-1.5rem))] rounded-xl border border-gray-200/90 bg-white py-2 shadow-2xl ring-1 ring-black/5"
                                     style="list-style: none;"
                                     role="menu"
                                     dir="rtl">
                                    <a href="{{ route('items.show', $item) }}"
                                       class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                       role="menuitem">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.12 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z"/></svg>
                                        </span>
                                        <span class="flex-1 text-right font-medium leading-snug">عرض</span>
                                    </a>
                                    <a href="{{ route('items.edit', $item) }}"
                                       class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                       role="menuitem">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                                        </span>
                                        <span class="flex-1 text-right font-medium leading-snug">تعديل</span>
                                    </a>
                                    <button type="button"
                                            class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-gray-50"
                                            data-bs-toggle="modal"
                                            data-bs-target="#itemMovementModal{{ $item->id }}"
                                            role="menuitem">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8.5 1.5a.5.5 0 0 0-1 0V6h-6a.5.5 0 0 0 0 1h6v4.5a.5.5 0 0 0 1 0V7h6a.5.5 0 0 0 0-1h-6V1.5z"/></svg>
                                        </span>
                                        <span class="flex-1 leading-snug">تفاصيل الحركة</span>
                                    </button>
                                    <div class="mx-2 my-2 border-t border-gray-100"></div>
                                    <form action="{{ route('items.destroy', $item) }}" method="POST" class="m-0" onsubmit="return confirm('حذف هذا الصنف؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                            </span>
                                            <span class="flex-1 leading-snug">حذف</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">لا توجد منتجات مطابقة لنتائج البحث أو الفلاتر.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @foreach($items as $item)
        <div class="modal fade" id="itemMovementModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تفاصيل الحركة - {{ $item->name_ar }}</h5>
                        <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="small text-muted mb-2">توزيع المخزون الحالي على المستودعات:</div>
                        <ul class="list-group">
                            @forelse($item->warehouses as $warehouse)
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>{{ $warehouse->name_ar ?: ($warehouse->name_en ?: $warehouse->code) }}</span>
                                    <span class="fw-semibold">{{ number_format((float) ($warehouse->pivot->quantity ?? 0), 2) }}</span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">لا توجد حركات أو أرصدة مخزنية مرتبطة بهذا الصنف.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إغلاق</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @if($items->hasPages())
        <div class="mt-3">
            {{ $items->links() }}
        </div>
    @endif

    {{-- مودال استيراد الأصناف --}}
    <div class="modal fade" id="itemsImportModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">استيراد الأصناف من ملف</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form method="POST" action="{{ route('items.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <p class="small text-muted mb-3">
                            استخدم ملف <strong>CSV أو Excel (XLSX / XLS)</strong> يحتوي على الأعمدة كما في النموذج الإرشادي. سيتم التحديث أو الإنشاء بناءً على العمود <code>code</code>.
                        </p>
                        <div class="rounded-3 bg-light border border-secondary-subtle p-3 mb-3 small text-muted">
                            <p class="fw-semibold mb-1">الأعمدة الإلزامية:</p>
                            <ul class="mb-2 ps-4">
                                <li><code>code</code> – كود الصنف (مفتاح التحديث).</li>
                                <li><code>name_ar</code> – اسم الصنف بالعربية.</li>
                                <li><code>type</code> – نوع الصنف: <code>raw_material</code> أو <code>finished_good</code> أو <code>service</code>.</li>
                            </ul>
                            <p class="fw-semibold mb-1">الأعمدة الاختيارية:</p>
                            <ul class="mb-0 ps-4">
                                <li><code>barcode</code>, <code>name_en</code>, <code>cost</code>, <code>selling_price</code>, <code>min_stock</code></li>
                                <li><code>supplier</code>, <code>material_type</code>, <code>is_active</code></li>
                            </ul>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">ملف البيانات <span class="text-danger">*</span></label>
                            <input type="file" name="file" accept=".csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required class="form-control">
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('items.import-template') }}" class="btn btn-link px-0">
                                تحميل النموذج الإرشادي
                            </a>
                            <span class="small text-muted">الصيغ المدعومة: CSV, XLSX, XLS</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">بدء الاستيراد</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
