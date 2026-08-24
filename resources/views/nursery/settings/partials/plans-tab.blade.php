<section class="space-y-4" x-data="{ showAdd: {{ $errors->has('name') || $errors->has('amount') ? 'true' : 'false' }}, editPlan: null }">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-teal-950">خطط الاشتراك <x-info field="nursery.settings_plans_intro" /></h2>
        @if($canManage)
            <button type="button" @click="showAdd = true; editPlan = null" class="nursery-btn nursery-btn-primary text-sm">+ إضافة خطة اشتراك</button>
        @endif
    </div>

    <div class="nursery-card nursery-table-card min-w-0">
        <div class="nursery-table-card__toolbar">
            <div>
                <h2>قائمة خطط الاشتراك</h2>
                <p>الأنواع والقيم والضريبة</p>
            </div>
        </div>
        <div class="min-w-0 overflow-x-auto">
            <table class="nursery-table nursery-table--plans w-full table-fixed">
                <colgroup>
                    <col style="width: {{ $canManage ? '28%' : '32%' }}">
                    <col style="width: 16%">
                    <col style="width: 12%">
                    <col style="width: 18%">
                    <col style="width: 14%">
                    @if($canManage)<col style="width: 12%">@endif
                </colgroup>
                <thead>
                    <tr>
                        <th>اسم الخطة <x-info field="nursery.settings_plan_name" /></th>
                        <th>القيمة</th>
                        <th>الضريبة</th>
                        <th>بعد الضريبة <x-info field="nursery.settings_plan_after_tax" /></th>
                        <th>العملة <x-info field="nursery.settings_plan_currency" /></th>
                        @if($canManage)<th class="text-center">إجراءات</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        @if(!$plan->is_active) @continue @endif
                        @php
                            $typeLabel = collect($planTypeOptions)->firstWhere('value', $plan->plan_type ?? 'custom')['label'] ?? 'مخصص';
                            $currencyCode = (string) ($plan->currency_code ?? 'SAR');
                        @endphp
                        <tr>
                            <td>
                                <div class="nursery-table-name__text">
                                    <span class="nursery-table-name__title">{{ $plan->name }}</span>
                                    <span class="nursery-table-name__sub">{{ $typeLabel }}</span>
                                </div>
                            </td>
                            <td class="tabular-nums font-semibold text-slate-700 whitespace-nowrap">{{ number_format((float) $plan->amount, 2) }}</td>
                            <td class="tabular-nums whitespace-nowrap">{{ number_format((float) $plan->tax_rate, 0) }}%</td>
                            <td class="tabular-nums font-semibold text-teal-700 whitespace-nowrap">{{ number_format($plan->amountAfterTax(), 2) }}</td>
                            <td class="whitespace-nowrap">
                                <span class="font-semibold text-slate-700">{{ $currencyCode }}</span>
                            </td>
                            @if($canManage)
                                <td class="text-center">
                                    <x-erp-actions-dropdown :menu-id="'nursery-plan-'.$plan->id">
                                        <x-erp-actions-menu-item type="button" icon="edit"
                                            @click="editPlan = {{ $plan->id }}; showAdd = true">
                                            تعديل
                                        </x-erp-actions-menu-item>
                                        <form method="POST" action="{{ route('nursery.settings.plans.destroy', $plan) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-erp-actions-menu-item type="submit" icon="delete" :danger="true"
                                                confirm="إلغاء تفعيل هذه الخطة؟">
                                                حذف
                                            </x-erp-actions-menu-item>
                                        </form>
                                    </x-erp-actions-dropdown>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 6 : 5 }}" class="!py-10 text-center text-teal-700/70">لا توجد خطط — أضف خطة اشتراك.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canManage)
        @include('nursery.settings.partials.plan-modal')
    @endif
</section>
