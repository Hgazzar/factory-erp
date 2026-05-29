@extends('layouts.app')

@section('title', 'صنف: ' . $item->name_ar . ' - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <a href="{{ route('items.index') }}" class="text-gray-500 hover:text-indigo-600">المنتجات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">{{ $item->code }}</span>
@endsection

@php
    $typeLabels = ['raw_material' => 'مادة خام', 'finished_good' => 'منتج تام', 'service' => 'خدمة'];
@endphp

@section('content')
<div dir="rtl" class="content-wrap">
    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mb-3">{{ session('error') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0">الصنف: {{ $item->name_ar }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">القائمة</a>
            <a href="{{ route('items.edit', $item) }}" class="btn btn-primary">تعديل البيانات</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header fw-semibold">بيانات الصنف</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><span class="text-muted">الرمز</span><div class="fw-semibold">{{ $item->code }}</div></div>
                <div class="col-md-4"><span class="text-muted">النوع</span><div>{{ $typeLabels[$item->type] ?? $item->type }}</div></div>
                <div class="col-md-4"><span class="text-muted">وحدة القياس</span><div>{{ $item->unit?->name_ar ?? '—' }}</div></div>
                <div class="col-md-6"><span class="text-muted">الاسم بالإنجليزي</span><div>{{ $item->name_en ?? '—' }}</div></div>
                <div class="col-md-6"><span class="text-muted">الحالة</span><div>{{ $item->is_active ? 'نشط' : 'غير نشط' }}</div></div>
                <div class="col-md-4"><span class="text-muted">تكلفة الوحدة (WAC) <x-info field="inventory.item_cost_price" /></span><div class="fw-semibold tabular-nums">SAR {{ number_format((float) ($item->cost ?? 0), 4) }}</div></div>
                <div class="col-md-4"><span class="text-muted">سعر البيع</span><div class="tabular-nums">SAR {{ number_format((float) ($item->selling_price ?? 0), 2) }}</div></div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header fw-semibold d-inline-flex align-items-center gap-1">
            مرفقات الصنف
            <x-info field="inventory.item_attachments" />
        </div>
        <div class="card-body">
            <x-attachment-handler
                theme="bootstrap"
                hint-field="inventory.item_attachments"
                title="المرفقات"
                :existing="$item->attachments"
                :uploadable="false"
                :allow-delete="true"
                help-text="معاينة وحذف آمن. لإضافة ملفات استخدم «تعديل البيانات» (التخزين: items/{{ $item->id }})."
            />
        </div>
    </div>

    @if($item->type === \App\Models\Item::TYPE_FINISHED_GOOD)
        <div class="card mb-4 border-primary border-opacity-25">
            <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-semibold inline-flex align-items-center gap-1">مكونات التصنيع (BOM) <x-info field="inventory.item_bom_section" /></span>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#bomEditModal">
                    تعديل المكونات
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th><span class="inline-flex align-items-center gap-1">المادة الخام <x-info field="inventory.item_bom_component" /></span></th>
                                <th><span class="inline-flex align-items-center gap-1">الكمية لكل وحدة منتج <x-info field="inventory.item_bom_qty_per_unit" /></span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($item->bomComponents as $line)
                                <tr>
                                    <td>{{ $line->componentItem?->code }} — {{ $line->componentItem?->name_ar }}</td>
                                    <td>{{ rtrim(rtrim(number_format((float) $line->quantity_per_unit, 4, '.', ''), '0'), '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-muted text-center py-4">لا توجد مكونات مسجّلة. اضغط «تعديل المكونات» لإضافة الخامات لكل قطعة منتج.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Modal BOM --}}
        <div class="modal fade" id="bomEditModal" tabindex="-1" aria-labelledby="bomEditModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content" x-data="bomEditor(@js($bomInitialRows), @js($rawMaterialOptions))">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bomEditModalLabel">تعديل مكونات التصنيع — {{ $item->name_ar }}</h5>
                        <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <form x-ref="bomForm" method="POST" action="{{ route('items.bom.update', $item) }}" @submit.prevent="submitBom">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <p class="small text-muted mb-3">حدد المواد الخام والكمية المطلوبة لإنتاج <strong>واحدة</strong> من هذا المنتج التام. تُستخدم هذه البيانات في «اقتراح الخامات» عند إنشاء أمر إنتاج.</p>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="min-width: 220px;">المادة الخام</th>
                                            <th style="width: 160px;">الكمية / وحدة منتج</th>
                                            <th style="width: 72px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(row, index) in rows" :key="index">
                                            <tr>
                                                <td>
                                                    <select class="form-select form-select-sm" x-model="row.component_item_id">
                                                        <option value="">— اختر —</option>
                                                        <template x-for="opt in rawOptions" :key="opt.id">
                                                            <option :value="String(opt.id)" x-text="opt.label"></option>
                                                        </template>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" inputmode="decimal" class="form-control form-control-sm" min="0.0001" step="any" placeholder="0" x-model="row.quantity_per_unit">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-danger" @click="removeRow(index)" title="حذف السطر">حذف</button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="addRow">+ إضافة سطر</button>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">حفظ BOM</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('bomEditor', (initialRows, rawOptions) => ({
                    rows: Array.isArray(initialRows) && initialRows.length
                        ? initialRows.map((r) => ({
                            component_item_id: r.component_item_id != null ? String(r.component_item_id) : '',
                            quantity_per_unit: r.quantity_per_unit != null ? String(r.quantity_per_unit) : '',
                        }))
                        : [],
                    rawOptions: rawOptions || [],
                    init() {
                        if (this.rows.length === 0) {
                            this.rows.push({ component_item_id: '', quantity_per_unit: '' });
                        }
                    },
                    addRow() {
                        this.rows.push({ component_item_id: '', quantity_per_unit: '' });
                    },
                    removeRow(index) {
                        if (this.rows.length <= 1) {
                            this.rows = [{ component_item_id: '', quantity_per_unit: '' }];
                            return;
                        }
                        this.rows.splice(index, 1);
                    },
                    submitBom() {
                        const form = this.$refs.bomForm;
                        form.querySelectorAll('[data-bom-dynamic]').forEach((el) => el.remove());
                        const filtered = this.rows.filter((r) => {
                            const id = String(r.component_item_id || '').trim();
                            const q = parseFloat(String(r.quantity_per_unit || '').replace(',', '.'));
                            return id !== '' && !isNaN(q) && q > 0;
                        });
                        filtered.forEach((r, i) => {
                            const h1 = document.createElement('input');
                            h1.type = 'hidden';
                            h1.name = 'components[' + i + '][component_item_id]';
                            h1.value = r.component_item_id;
                            h1.setAttribute('data-bom-dynamic', '1');
                            form.appendChild(h1);
                            const h2 = document.createElement('input');
                            h2.type = 'hidden';
                            h2.name = 'components[' + i + '][quantity_per_unit]';
                            h2.value = String(r.quantity_per_unit).replace(',', '.');
                            h2.setAttribute('data-bom-dynamic', '1');
                            form.appendChild(h2);
                        });
                        form.submit();
                    },
                }));
            });
        </script>
        @endpush
    @else
        <div class="alert alert-info mb-0">إدارة BOM متاحة فقط للأصناف من نوع «منتج تام».</div>
    @endif
</div>
@endsection
