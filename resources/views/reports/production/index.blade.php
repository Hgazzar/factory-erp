@extends('layouts.app')

@section('title', 'تقارير الإنتاج - Factory ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h4 mb-1">تقارير الإنتاج</h1>
        <p class="text-muted mb-0 small">فلترة الإنتاج حسب التاريخ واسم المنتج مع عرض الكميات والهالك.</p>
    </div>
    <button type="button" class="btn btn-primary no-print" onclick="window.print()">
        طباعة / حفظ كـ PDF
    </button>
</div>

<form method="GET" action="{{ route('reports.production.index') }}" class="card shadow-sm mb-4 form-filter">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">من تاريخ</label>
                <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">إلى تاريخ</label>
                <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">الصنف</label>
                <select name="item_id" class="form-select">
                    <option value="">-- كل الأصناف --</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}" @selected($itemId == $item->id)>
                            {{ $item->code }} - {{ $item->name_ar }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    عرض
                </button>
            </div>
        </div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 150px;">التاريخ/الوقت</th>
                        <th>الصنف</th>
                        <th style="width: 140px;" class="text-end">الكمية المنتجة</th>
                        <th style="width: 140px;" class="text-end">كمية الهالك</th>
                        <th style="width: 180px;">الموظف</th>
                        <th>ملاحظات</th>
                        <th style="width: 90px;" class="no-print">عرض</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td>{{ $record->recorded_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                {{ $record->item?->code ?? '-' }} - {{ $record->item?->name_ar ?? '' }}
                            </td>
                            <td class="text-end">{{ number_format((float) $record->quantity, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $record->scrap_quantity, 2) }}</td>
                            <td>
                                {{ $record->employee?->name ?? '-' }}
                                <div class="small text-muted">{{ $record->employee?->linkedUser?->email }}</div>
                            </td>
                            <td>{{ $record->notes }}</td>
                            <td class="no-print">
                                <button type="button" class="btn btn-sm btn-outline-primary view-detail-btn" data-record-id="{{ $record->id }}">
                                    View
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                لا توجد سجلات إنتاج مطابقة للفلاتر المحددة.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($records->hasPages())
        <div class="card-footer">
            {{ $records->links() }}
        </div>
    @endif
</div>

{{-- Modal تفاصيل السجل --}}
<div class="modal fade" id="reportDetailModal" tabindex="-1" aria-labelledby="reportDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportDetailModalLabel">تفاصيل سجل الإنتاج</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body report-detail-body" id="reportDetailContent">
                <p class="text-muted text-center py-4">جاري التحميل...</p>
            </div>
            <div class="modal-footer no-print">
                <button type="button" class="btn btn-primary" onclick="window.print()">Print / Export PDF</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('reportDetailModal');
    var content = document.getElementById('reportDetailContent');
    if (!modal || !content) return;
    var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
    document.querySelectorAll('.view-detail-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-record-id');
            if (!id) return;
            content.innerHTML = '<p class="text-muted text-center py-4">جاري التحميل...</p>';
            bsModal.show();
            var url = '{{ route("reports.production.show", ["record" => "__ID__"]) }}'.replace('__ID__', id);
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    var scrapReason = d.scrap_reason || '—';
                    var downtime = (d.downtime_reason || '—') + (d.downtime_lost_hours != null ? ' (' + d.downtime_lost_hours + ' ساعة)' : '');
                    var html = '<div class="report-detail-print">';
                    html += '<table class="table table-bordered table-sm"><tbody>';
                    html += '<tr><th style="width:180px">التاريخ/الوقت</th><td>' + (d.recorded_at || '—') + '</td></tr>';
                    html += '<tr><th>الصنف</th><td>' + (d.item_code || '') + ' - ' + (d.item_name || '') + '</td></tr>';
                    html += '<tr><th>الكمية المنتجة</th><td>' + (d.quantity != null ? Number(d.quantity).toFixed(3) : '—') + '</td></tr>';
                    html += '<tr><th>كمية الهالك</th><td>' + (d.scrap_quantity != null ? Number(d.scrap_quantity).toFixed(3) : '—') + '</td></tr>';
                    html += '<tr><th>سبب الهالك</th><td>' + scrapReason + '</td></tr>';
                    html += '<tr><th>التوقف</th><td>' + downtime + '</td></tr>';
                    html += '<tr><th>الموظف</th><td>' + (d.employee || '—') + '</td></tr>';
                    html += '<tr><th>ملاحظات</th><td>' + (d.notes || '—') + '</td></tr>';
                    if (d.journal_reference) {
                        html += '<tr><th>قيد محاسبي</th><td>' + d.journal_reference + ' - ' + (d.journal_date || '') + ' - إجمالي: ' + (d.journal_total != null ? Number(d.journal_total).toFixed(3) : '') + '</td></tr>';
                    }
                    html += '</tbody></table>';
                    if (d.journal_items && d.journal_items.length) {
                        html += '<h6 class="mt-3">تفاصيل القيد</h6><table class="table table-bordered table-sm"><thead><tr><th>الحساب</th><th>البيان</th><th class="text-end">مدين</th><th class="text-end">دائن</th></tr></thead><tbody>';
                        d.journal_items.forEach(function(i) {
                            html += '<tr><td>' + (i.account || '') + '</td><td>' + (i.description || '') + '</td><td class="text-end">' + (i.debit > 0 ? Number(i.debit).toFixed(3) : '') + '</td><td class="text-end">' + (i.credit > 0 ? Number(i.credit).toFixed(3) : '') + '</td></tr>';
                        });
                        html += '</tbody></table>';
                    }
                    html += '</div>';
                    content.innerHTML = html;
                })
                .catch(function() {
                    content.innerHTML = '<p class="text-danger text-center py-4">فشل تحميل التفاصيل.</p>';
                });
        });
    });
});
</script>
@endpush
@endsection

