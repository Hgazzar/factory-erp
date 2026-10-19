<div x-show="showAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @keydown.escape.window="showAdd = false">
    <div class="nursery-card w-full max-w-2xl max-h-[90vh] overflow-y-auto p-5 space-y-4" @click.outside="showAdd = false">
        <h3 class="text-lg font-bold text-teal-950" x-text="editPlan ? 'تعديل خطة اشتراك' : 'إضافة خطة اشتراك'"></h3>

        @foreach($plans as $plan)
            @php
                $planHourRates = $plan->relationLoaded('hourRates')
                    ? $plan->hourRates->map(fn ($r) => ['hours' => (int) $r->hours, 'amount' => (float) $r->amount])->values()->all()
                    : [];
                if ($planHourRates === []) {
                    $planHourRates = [['hours' => '', 'amount' => '']];
                }
            @endphp
            <template x-if="editPlan === {{ $plan->id }}">
                <form method="POST" action="{{ route('nursery.settings.plans.update', $plan) }}" class="space-y-4"
                      x-data="planCalc({
                          amount: {{ (float) $plan->amount }},
                          tax: {{ (float) $plan->tax_rate }},
                          hourRates: @js($planHourRates)
                      })">
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
                  x-data="planCalc({
                      amount: {{ (float) old('amount', 0) }},
                      tax: {{ (float) old('tax_rate', 15) }},
                      hourRates: @js(old('hour_rates', [['hours' => '', 'amount' => '']]))
                  })">
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
    var rows = Array.isArray(initial.hourRates) && initial.hourRates.length
        ? initial.hourRates.map(function (r) {
            return { hours: r.hours ?? '', amount: r.amount ?? '' };
        })
        : [{ hours: '', amount: '' }];

    return {
        amount: Number(initial.amount) || 0,
        tax: Number(initial.tax) || 0,
        hourRates: rows,
        afterTax() {
            const base = Number(this.amount) || 0;
            const rate = Number(this.tax) || 0;
            return (base * (1 + rate / 100)).toFixed(2);
        },
        rowAfterTax(row) {
            const raw = row && row.amount;
            if (raw === '' || raw === null || typeof raw === 'undefined') {
                return '—';
            }
            const base = Number(raw);
            if (!Number.isFinite(base)) {
                return '—';
            }
            const rate = Number(this.tax) || 0;
            return (base * (1 + rate / 100)).toFixed(2);
        },
        removeHourRow(index) {
            if (this.hourRates.length > 1) {
                this.hourRates.splice(index, 1);
                return;
            }
            this.hourRates[0].hours = '';
            this.hourRates[0].amount = '';
        },
    };
}
</script>
@endpush
