<section class="space-y-4" x-data="{ showAdd: {{ $errors->has('name') || $errors->has('amount') ? 'true' : 'false' }}, editPlan: null }">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-orange-950">خطط الاشتراك <x-info field="nursery.settings_plans_intro" /></h2>
        @if($canManage)
            <button type="button" @click="showAdd = true; editPlan = null" class="nursery-btn nursery-btn-primary text-sm">+ إضافة خطة اشتراك</button>
        @endif
    </div>

    <div class="nursery-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="bg-orange-50/80 border-b border-orange-100">
                        <th class="px-4 py-3 text-right font-bold text-orange-950">اسم الخطة <x-info field="nursery.settings_plan_name" /></th>
                        <th class="px-4 py-3 text-right font-bold text-orange-950">النوع <x-info field="nursery.settings_plan_type" /></th>
                        <th class="px-4 py-3 text-right font-bold text-orange-950">القيمة</th>
                        <th class="px-4 py-3 text-right font-bold text-orange-950">الضريبة</th>
                        <th class="px-4 py-3 text-right font-bold text-orange-950">بعد الضريبة <x-info field="nursery.settings_plan_after_tax" /></th>
                        <th class="px-4 py-3 text-right font-bold text-orange-950">العملة <x-info field="nursery.settings_plan_currency" /></th>
                        @if($canManage)<th class="px-4 py-3 w-28"></th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        @if(!$plan->is_active) @continue @endif
                        <tr class="border-b border-orange-50 hover:bg-orange-50/40">
                            <td class="px-4 py-3 font-semibold">{{ $plan->name }}</td>
                            <td class="px-4 py-3">{{ collect($planTypeOptions)->firstWhere('value', $plan->plan_type ?? 'custom')['label'] ?? 'مخصص' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ number_format((float) $plan->amount, 2) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ number_format((float) $plan->tax_rate, 0) }}%</td>
                            <td class="px-4 py-3 tabular-nums font-semibold text-orange-700">{{ number_format($plan->amountAfterTax(), 2) }}</td>
                            <td class="px-4 py-3">{{ collect($currencyOptions)->firstWhere('value', $plan->currency_code ?? 'SAR')['label'] ?? 'SAR' }}</td>
                            @if($canManage)
                                <td class="px-4 py-3">
                                    <div class="flex gap-1 justify-end">
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
                            <td colspan="{{ $canManage ? 7 : 6 }}" class="px-4 py-10 text-center text-orange-700/70">لا توجد خطط — أضف خطة اشتراك.</td>
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
