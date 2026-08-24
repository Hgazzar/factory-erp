<div x-show="showAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @keydown.escape.window="showAdd = false">
    <div class="nursery-card w-full max-w-lg max-h-[90vh] overflow-y-auto p-5 space-y-4" @click.outside="showAdd = false">
        <h3 class="text-lg font-bold text-teal-950" x-text="editPlan ? 'تعديل خطة اشتراك' : 'إضافة خطة اشتراك'"></h3>

        @foreach($plans as $plan)
            <template x-if="editPlan === {{ $plan->id }}">
                <form method="POST" action="{{ route('nursery.settings.plans.update', $plan) }}" class="space-y-4"
                      x-data="planCalc({ amount: {{ (float) $plan->amount }}, tax: {{ (float) $plan->tax_rate }} })">
                    @csrf
                    @method('PUT')
                    @include('nursery.settings.partials.plan-fields', [
                        'plan' => $plan,
                        'planTypeOptions' => $planTypeOptions,
                        'currencyOptions' => $currencyOptions,
                    ])
                </form>
            </template>
        @endforeach

        <template x-if="!editPlan">
            <form method="POST" action="{{ route('nursery.settings.plans.store') }}" class="space-y-4"
                  x-data="planCalc({ amount: {{ (float) old('amount', 0) }}, tax: {{ (float) old('tax_rate', 15) }} })">
                @csrf
                @include('nursery.settings.partials.plan-fields', [
                    'plan' => null,
                    'planTypeOptions' => $planTypeOptions,
                    'currencyOptions' => $currencyOptions,
                ])
            </form>
        </template>
    </div>
</div>

@push('scripts')
<script>
function planCalc(initial) {
    return {
        amount: Number(initial.amount) || 0,
        tax: Number(initial.tax) || 0,
        afterTax() {
            const base = Number(this.amount) || 0;
            const rate = Number(this.tax) || 0;
            return (base * (1 + rate / 100)).toFixed(2);
        },
    };
}
</script>
@endpush
