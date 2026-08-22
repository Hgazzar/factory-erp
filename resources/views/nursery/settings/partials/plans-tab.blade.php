<section class="space-y-4" x-data="{ showAdd: {{ $errors->has('name') || $errors->has('amount') ? 'true' : 'false' }}, editPlan: null }">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-orange-950">خطط الاشتراك <x-info field="nursery.settings_plans_intro" /></h2>
        @if($canManage)
            <button type="button" @click="showAdd = true; editPlan = null" class="nursery-btn nursery-btn-primary text-sm">+ إضافة خطة اشتراك</button>
        @endif
    </div>

    <div class="nursery-card nursery-table-card">
        <div class="nursery-table-card__toolbar">
            <div>
                <h2>قائمة خطط الاشتراك</h2>
                <p>الأنواع والقيم والضريبة</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="nursery-table min-w-[640px]">
                <thead>
                    <tr>
                        <th>اسم الخطة <x-info field="nursery.settings_plan_name" /></th>
                        <th>النوع <x-info field="nursery.settings_plan_type" /></th>
                        <th>القيمة</th>
                        <th>الضريبة</th>
                        <th>بعد الضريبة <x-info field="nursery.settings_plan_after_tax" /></th>
                        <th>العملة <x-info field="nursery.settings_plan_currency" /></th>
                        @if($canManage)<th class="text-center w-28">إجراءات</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        @if(!$plan->is_active) @continue @endif
                        <tr>
                            <td>
                                <span class="nursery-table-name__title">{{ $plan->name }}</span>
                            </td>
                            <td>{{ collect($planTypeOptions)->firstWhere('value', $plan->plan_type ?? 'custom')['label'] ?? 'مخصص' }}</td>
                            <td class="tabular-nums font-semibold text-slate-700">{{ number_format((float) $plan->amount, 2) }}</td>
                            <td class="tabular-nums">{{ number_format((float) $plan->tax_rate, 0) }}%</td>
                            <td class="tabular-nums font-semibold text-orange-700">{{ number_format($plan->amountAfterTax(), 2) }}</td>
                            <td>{{ collect($currencyOptions)->firstWhere('value', $plan->currency_code ?? 'SAR')['label'] ?? 'SAR' }}</td>
                            @if($canManage)
                                <td class="text-center">
                                    <div class="flex gap-1 justify-center">
                                        <button type="button" @click="editPlan = {{ $plan->id }}; showAdd = true"
                                                class="nursery-btn nursery-btn-soft text-xs py-1 px-2">تعديل</button>
                                        <form method="POST" action="{{ route('nursery.settings.plans.destroy', $plan) }}" onsubmit="return confirm('إلغاء تفعيل هذه الخطة؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="nursery-btn nursery-btn-soft text-xs py-1 px-2 text-red-700">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 7 : 6 }}" class="!py-10 text-center text-orange-700/70">لا توجد خطط — أضف خطة اشتراك.</td>
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
