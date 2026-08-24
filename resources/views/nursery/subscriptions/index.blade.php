@extends('layouts.nursery')

@section('title', 'الاشتراكات')

@section('content')
<div class="w-full space-y-5" dir="rtl" x-data="{ showAdd: {{ $errors->any() ? 'true' : 'false' }} }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-orange-950">الاشتراكات</h1>
            <p class="text-sm text-orange-800/80 mt-1"><x-info field="nursery.nav_subscriptions" /> خطط الأطفال والمدفوعات</p>
        </div>
        @if($canManage)
            <button type="button" @click="showAdd = true" class="nursery-btn nursery-btn-primary">+ إضافة اشتراك</button>
        @endif
    </div>

    @if(session('success'))
        <div class="nursery-card px-4 py-3 text-sm text-emerald-800 bg-emerald-50">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="nursery-card px-4 py-3 text-sm text-red-800 bg-red-50">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap gap-2">
        @foreach(['all' => 'الكل', 'day' => '1ي', 'week' => '1أ', 'month' => '1ش', 'year' => '1س'] as $key => $label)
            <a href="{{ route('nursery.subscriptions.index', array_merge(request()->except('page'), ['period' => $key])) }}"
               class="nursery-btn text-sm py-1.5 {{ $period === $key ? 'nursery-btn-primary' : 'nursery-btn-soft' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="nursery-stats-row">
        <x-nursery-stat-card title="إجمالي الاشتراكات" :value="$stats['total']" info="nursery.sub_stat_total" tone="primary" hint="كل الفترات" spark="bars"
            :percent="$spark['total']['percent']" :trend="$spark['total']['trend']" />
        <x-nursery-stat-card title="المدفوعة" :value="$stats['paid']" info="nursery.sub_stat_paid" tone="success" hint="تم التحصيل" spark="ring"
            :percent="$spark['paid']['percent']" :trend="$spark['paid']['trend']" />
        <x-nursery-stat-card title="غير المدفوعة" :value="$stats['unpaid']" info="nursery.sub_stat_unpaid" tone="warning" hint="بانتظار الدفع" spark="bars"
            :percent="$spark['unpaid']['percent']" :trend="$spark['unpaid']['trend']" />
    </div>

    <div class="nursery-stats-row">
        <x-nursery-stat-card title="المنتهية" :value="$stats['expired'] ?? 0" info="nursery.sub_stat_expired" tone="danger" hint="انتهت مدتها" spark="line"
            :percent="$spark['expired']['percent']" :trend="$spark['expired']['trend']" />
        <x-nursery-stat-card title="الملغاة" :value="$stats['cancelled']" info="nursery.sub_stat_cancelled" tone="muted" hint="ملغاة" spark="none"
            :percent="$spark['cancelled']['percent']" :trend="$spark['cancelled']['trend']" />
        <x-nursery-stat-card title="إجمالي الاشتراكات" :value="$stats['total']" info="nursery.sub_stat_total" tone="primary" hint="كل الفترات" spark="bars"
            :percent="$spark['total']['percent']" :trend="$spark['total']['trend']" />
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <section class="nursery-card nursery-table-card xl:col-span-2">
            <div class="nursery-table-card__toolbar">
                <div>
                    <h2>قائمة الاشتراكات</h2>
                    <p>الطفل والخطة والحالة المالية</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="nursery-table min-w-[640px]">
                    <thead>
                        <tr>
                            <th>الطفل <x-info field="nursery.sub_child" /></th>
                            <th>الخطة <x-info field="nursery.sub_plan" /></th>
                            <th>الفترة</th>
                            <th>القيمة <x-info field="nursery.sub_amount" /></th>
                            <th class="text-center">الحالة <x-info field="nursery.sub_status" /></th>
                            @if($canManage)<th class="text-center w-14">إجراءات</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $sub)
                            @php
                                $hasSubActions = $sub->canBeMarkedPaid() || $sub->canBeRenewed() || $sub->isActive();
                            @endphp
                            <tr>
                                <td>
                                    <div class="nursery-table-name">
                                        <x-nursery-person-avatar :name="$sub->child?->name ?? '—'" :src="$sub->child?->firstImageUrl()" />
                                        <span class="nursery-table-name__text">
                                            <span class="nursery-table-name__title">{{ $sub->child?->name }}</span>
                                            @if($sub->renewed_from_id)
                                                <span class="nursery-table-name__sub">تجديد من {{ $sub->renewedFrom?->starts_on?->format('Y-m-d') }} → {{ $sub->renewedFrom?->ends_on?->format('Y-m-d') }}</span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td>{{ $sub->plan?->name }}</td>
                                <td class="text-xs tabular-nums text-slate-600">{{ $sub->starts_on?->format('Y-m-d') }} → {{ $sub->ends_on?->format('Y-m-d') }}</td>
                                <td class="tabular-nums font-semibold text-slate-800">{{ number_format($sub->finalAmount(), 2) }} ر.س</td>
                                <td class="text-center">
                                    @if($sub->status === 'cancelled')
                                        <span class="nursery-status-pill nursery-status-pill--muted">ملغى</span>
                                    @elseif($sub->status === 'expired')
                                        <span class="nursery-status-pill nursery-status-pill--warning">منتهٍ</span>
                                    @elseif($sub->is_paid || $sub->status === 'paid')
                                        <span class="nursery-status-pill nursery-status-pill--success">مدفوع</span>
                                    @else
                                        <span class="nursery-status-pill nursery-status-pill--warning">غير مدفوع</span>
                                    @endif
                                </td>
                                @if($canManage)
                                    <td class="text-center">
                                        @if($hasSubActions)
                                            <x-erp-actions-dropdown :menu-id="'nursery-sub-'.$sub->id">
                                                @if($sub->canBeMarkedPaid())
                                                    <form method="post" action="{{ route('nursery.subscriptions.mark-paid', $sub) }}" class="m-0">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="payment_method" value="cash">
                                                        <x-erp-actions-menu-item type="submit" icon="pay">
                                                            تسجيل الدفع · نقدي
                                                        </x-erp-actions-menu-item>
                                                    </form>
                                                    <form method="post" action="{{ route('nursery.subscriptions.mark-paid', $sub) }}" class="m-0">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="payment_method" value="transfer">
                                                        <x-erp-actions-menu-item type="submit" icon="pay">
                                                            تسجيل الدفع · تحويل
                                                        </x-erp-actions-menu-item>
                                                    </form>
                                                    <form method="post" action="{{ route('nursery.subscriptions.mark-paid', $sub) }}" class="m-0">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="payment_method" value="card">
                                                        <x-erp-actions-menu-item type="submit" icon="pay">
                                                            تسجيل الدفع · بطاقة
                                                        </x-erp-actions-menu-item>
                                                    </form>
                                                @endif
                                                @if($sub->canBeRenewed())
                                                    <form method="post" action="{{ route('nursery.subscriptions.renew', $sub) }}" class="m-0">
                                                        @csrf
                                                        <x-erp-actions-menu-item type="submit" icon="renew">
                                                            تجديد
                                                        </x-erp-actions-menu-item>
                                                    </form>
                                                @endif
                                                @if($sub->isActive())
                                                    <div class="mx-2 my-2 border-t border-gray-100"></div>
                                                    <form method="post" action="{{ route('nursery.subscriptions.cancel', $sub) }}" class="m-0">
                                                        @csrf @method('PATCH')
                                                        <x-erp-actions-menu-item type="submit" icon="cancel" :danger="true"
                                                            confirm="إلغاء هذا الاشتراك؟">
                                                            إلغاء الاشتراك
                                                        </x-erp-actions-menu-item>
                                                    </form>
                                                @endif
                                            </x-erp-actions-dropdown>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canManage ? 6 : 5 }}" class="!py-14 text-center text-orange-800/70">
                                    لا يوجد أي بيانات لعرضها!
                                    @if($canManage)
                                        <div class="mt-3"><button type="button" @click="showAdd = true" class="nursery-btn nursery-btn-primary">+ إضافة اشتراك</button></div>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($items->hasPages())
                <div class="px-4 py-3 border-t border-orange-100">{{ $items->links() }}</div>
            @endif
        </section>

        <aside class="nursery-card p-4 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-bold text-orange-950">التذكيرات <x-info field="nursery.sub_reminders" /></h2>
                @if($canManage && $reminderTab === 'payment')
                    <form method="post" action="{{ route('nursery.subscriptions.reminders.payment') }}">
                        @csrf
                        <button type="submit" class="nursery-btn nursery-btn-soft text-xs py-1.5">🔔 تذكير بالدفع للجميع</button>
                    </form>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('nursery.subscriptions.index', array_merge(request()->except('reminder'), ['reminder' => 'payment'])) }}"
                   class="nursery-btn text-xs py-1.5 {{ $reminderTab === 'payment' ? 'nursery-btn-primary' : 'nursery-btn-soft' }}">التذكير بالدفع</a>
                <a href="{{ route('nursery.subscriptions.index', array_merge(request()->except('reminder'), ['reminder' => 'renewal'])) }}"
                   class="nursery-btn text-xs py-1.5 {{ $reminderTab === 'renewal' ? 'nursery-btn-primary' : 'nursery-btn-soft' }}">التذكير بالتجديد</a>
            </div>
            <ul class="space-y-2 max-h-80 overflow-y-auto">
                @forelse($reminderTab === 'payment' ? $paymentReminders : $renewalReminders as $rem)
                    <li class="p-3 rounded-lg bg-orange-50 border border-orange-100 text-sm">
                        <p class="font-semibold text-orange-950">{{ $rem->child?->name }}</p>
                        <p class="text-xs text-orange-800/75">{{ $rem->plan?->name }} · ينتهي {{ $rem->ends_on?->format('Y-m-d') }}</p>
                        <p class="text-xs tabular-nums mt-1">{{ number_format($rem->finalAmount(), 2) }} ر.س @if(!$rem->is_paid)<span class="text-amber-700">· غير مدفوع</span>@endif</p>
                    </li>
                @empty
                    <li class="text-sm text-orange-800/70 text-center py-8">لا يوجد أي بيانات لعرضها!</li>
                @endforelse
            </ul>
        </aside>
    </div>

    {{-- modal إضافة اشتراك --}}
    <div x-show="showAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @keydown.escape.window="showAdd = false">
        <div class="nursery-card w-full max-w-lg p-5 space-y-4 max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-lg font-bold text-orange-950">إضافة اشتراك</h3>
                <button type="button" @click="showAdd = false" class="text-orange-800 text-xl leading-none">&times;</button>
            </div>
            @include('nursery.subscriptions.partials.form')
        </div>
    </div>
</div>
@endsection
