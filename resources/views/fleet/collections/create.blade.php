@extends('layouts.fleet')

@section('title', 'تحصيل ميداني — '.niche_module_label('fleet'))

@section('content')
<div class="space-y-6 max-w-4xl" dir="rtl">
    <h1 class="text-2xl font-extrabold text-violet-950">تسجيل تحصيل ميداني</h1>

    @if($errors->has('collection'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $errors->first('collection') }}</div>
    @endif

    @if($products->isEmpty())
        <div class="fleet-card p-6 text-sm text-violet-800">
            أضف أصنافاً في <a href="{{ route('fleet.products.index') }}" class="font-semibold underline">الكتalog الخفيف</a> أولاً.
        </div>
    @else
        <form method="POST" action="{{ route('fleet.collections.store') }}" class="fleet-card p-6 space-y-4">
            @csrf
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-1"><x-info field="fleet.collection_agent" /> المندوب</label>
                    <x-searchable-select
                        name="agent_id"
                        :options="$agentOptions"
                        :selected="$selectedAgentId"
                        empty-label="اختر المندوب"
                    />
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1"><x-info field="fleet.collection_collected_on" /> تاريخ التحصيل</label>
                    <input type="date" name="collected_on" value="{{ old('collected_on', now()->toDateString()) }}" required class="w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1"><x-info field="fleet.collection_customer" /> العميل</label>
                    <x-searchable-select
                        name="customer_id"
                        :options="$customerOptions"
                        :selected="$selectedCustomerId"
                        empty-label="اختياري"
                        empty-option
                    />
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1"><x-info field="fleet.collection_payment_method" /> طريقة التحصيل</label>
                    <x-searchable-select
                        name="payment_method"
                        :options="$paymentOptions"
                        :selected="old('payment_method', 'cod')"
                        empty-label="اختر الطريقة"
                        :searchable="false"
                    />
                </div>
            </div>

            @if($selectedRouteStopId !== '')
                <input type="hidden" name="route_stop_id" value="{{ $selectedRouteStopId }}">
                <input type="hidden" name="route_id" value="{{ $selectedRouteId }}">
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    <x-info field="fleet.collection_route_stop" /> مرتبط بمحطة خط السير #{{ $selectedRouteStopId }}
                </div>
            @elseif(count($routeOptions) > 0)
                <div>
                    <label class="block text-sm font-semibold mb-1"><x-info field="fleet.collection_route" /> خط السير (اختياري)</label>
                    <x-searchable-select
                        name="route_id"
                        :options="$routeOptions"
                        :selected="$selectedRouteId"
                        empty-label="بدون ربط"
                        empty-option
                    />
                </div>
            @endif

            @if($agentBalanceLines->isNotEmpty())
                <div class="rounded-lg border border-violet-100 bg-violet-50/50 p-4 text-sm">
                    <p class="font-semibold text-violet-900 mb-2"><x-info field="fleet.custody_balance" /> رصيد عهدة المندوب</p>
                    <ul class="space-y-1 text-violet-800">
                        @foreach($agentBalanceLines as $line)
                            <li>{{ $line->product_name }} — {{ number_format($line->quantity, 2) }} · {{ erp_money($line->unit_price) }}</li>
                        @endforeach
                    </ul>
                </div>
            @elseif($selectedAgentId !== '')
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">لا يوجد رصيد عهدة لهذا المندوب — صرف عهدة أولاً.</div>
            @endif

            <div>
                <label class="block text-sm font-semibold mb-2"><x-info field="fleet.collection_lines" /> بنود البيع من العهدة</label>
                <div class="space-y-3">
                    @for($i = 0; $i < 8; $i++)
                        <div class="grid sm:grid-cols-3 gap-3 items-end">
                            <div>
                                <x-searchable-select
                                    name="lines[{{ $i }}][product_id]"
                                    :options="$productOptions"
                                    :selected="old('lines.'.$i.'.product_id', '')"
                                    empty-label="اختر الصنف"
                                    empty-option
                                    label=""
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-violet-700 mb-1"><x-info field="fleet.custody_quantity" /> الكمية</label>
                                <input type="number" name="lines[{{ $i }}][quantity]" value="{{ old('lines.'.$i.'.quantity') }}" step="0.0001" min="0" class="w-full rounded-lg border-gray-300" placeholder="0">
                            </div>
                            <div>
                                <label class="block text-xs text-violet-700 mb-1"><x-info field="fleet.collection_unit_price" /> سعر الوحدة</label>
                                <input type="number" name="lines[{{ $i }}][unit_price]" value="{{ old('lines.'.$i.'.unit_price') }}" step="0.01" min="0" class="w-full rounded-lg border-gray-300" placeholder="افتراضي">
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1"><x-info field="fleet.collection_notes" /> ملاحظات</label>
                <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="fleet-btn fleet-btn-primary">حفظ مسودة</button>
                <a href="{{ route('fleet.collections.index') }}" class="fleet-btn fleet-btn-soft">إلغاء</a>
            </div>
        </form>
    @endif
</div>
@endsection
