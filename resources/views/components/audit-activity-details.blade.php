@props(['trail'])

@php
    $p = \App\Support\AuditTrailPresenter::present(
        $trail->old_values,
        $trail->new_values,
        (string) $trail->action,
        (string) ($trail->table_name ?? '')
    );
@endphp

@if(! $trail->old_values && ! $trail->new_values)
    <span class="text-muted">—</span>
@else
    <details class="audit-activity-details rounded-lg border border-gray-200 bg-gray-50 text-end">
        <summary class="px-3 py-2 cursor-pointer text-primary small fw-medium user-select-none" style="list-style: none;">
            <span class="d-inline-flex align-items-center gap-1">عرض التفاصيل <x-info field="dashboard.audit_details_toggle" /></span>
        </summary>
        <div class="px-3 pb-3 pt-2 border-top border-gray-200 bg-white rounded-bottom">
            @if($p['mode'] === 'diff' && ! empty($p['diff_rows']))
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 align-middle text-end">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="small">الحقل</th>
                                <th scope="col" class="small">القيمة القديمة</th>
                                <th scope="col" class="small">القيمة الجديدة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($p['diff_rows'] as $r)
                                <tr>
                                    <td class="small text-nowrap">{{ $r['label'] }}</td>
                                    <td class="small text-muted">{{ $r['old'] }}</td>
                                    <td class="small @if($r['trend'] === 'up') text-success fw-medium @elseif($r['trend'] === 'down') text-danger fw-medium @elseif($r['trend'] === 'same') text-secondary @endif">{{ $r['new'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mb-0 mt-2">تُعرض الحقول المتغيّرة فقط؛ التلوين يخص القيمة الجديدة عند المقارنة الرقمية.</p>
            @elseif($p['mode'] === 'bom' && isset($p['bom']))
                @php $bom = $p['bom']; @endphp
                <div class="space-y-3 small">
                    @if(! empty($bom['added']))
                        <div>
                            <div class="fw-semibold text-success mb-1">مكوّنات أُضيفت</div>
                            <ul class="list-unstyled mb-0 ps-0">
                                @foreach($bom['added'] as $line)
                                    <li class="border-bottom border-light py-1">
                                        <span class="fw-medium">{{ $line['code'] }}</span>
                                        — الكمية لكل وحدة منتج: <span class="text-success fw-medium">{{ $line['quantity_per_unit'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if(! empty($bom['removed']))
                        <div>
                            <div class="fw-semibold text-danger mb-1">مكوّنات أُزيلت</div>
                            <ul class="list-unstyled mb-0 ps-0">
                                @foreach($bom['removed'] as $line)
                                    <li class="border-bottom border-light py-1">
                                        <span class="fw-medium">{{ $line['code'] }}</span>
                                        — كانت {{ $line['quantity_per_unit'] }} لكل وحدة منتج
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if(! empty($bom['changed']))
                        <div>
                            <div class="fw-semibold text-dark mb-1">تعديل كميات</div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="small">الصنف</th>
                                            <th class="small">قبل</th>
                                            <th class="small">بعد</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($bom['changed'] as $c)
                                            <tr>
                                                <td class="small">{{ $c['code'] }}</td>
                                                <td class="small text-muted">{{ $c['old_qty'] }}</td>
                                                <td class="small @if(($c['trend'] ?? '') === 'up') text-success fw-medium @elseif(($c['trend'] ?? '') === 'down') text-danger fw-medium @endif">{{ $c['new_qty'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                    @if(empty($bom['added']) && empty($bom['removed']) && empty($bom['changed']))
                        <p class="text-muted mb-0">لا توجد اختلافات في بنود BOM.</p>
                    @endif
                </div>
            @else
                @if(empty($p['kv_old']) && empty($p['kv_new']))
                    <p class="text-muted mb-0 small">لا توجد بيانات لعرضها.</p>
                @else
                    <div class="row g-3 small">
                        @if(! empty($p['kv_old']))
                            <div class="col-12 @if(! empty($p['kv_new'])) col-md-6 @endif">
                                <div class="fw-semibold text-muted mb-1">قيم سابقة</div>
                                <ul class="list-unstyled mb-0 border rounded p-2 bg-light">
                                    @foreach($p['kv_old'] as $kv)
                                        <li class="py-1 border-bottom border-white"><span class="text-muted">{{ $kv['label'] }}:</span> {{ $kv['value'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if(! empty($p['kv_new']))
                            <div class="col-12 @if(! empty($p['kv_old'])) col-md-6 @endif">
                                <div class="fw-semibold text-muted mb-1">قيم مسجّلة</div>
                                <ul class="list-unstyled mb-0 border rounded p-2 bg-light">
                                    @foreach($p['kv_new'] as $kv)
                                        <li class="py-1 border-bottom border-white"><span class="text-muted">{{ $kv['label'] }}:</span> {{ $kv['value'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    </details>
@endif
