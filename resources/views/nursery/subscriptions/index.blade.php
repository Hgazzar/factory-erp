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

    <div class="grid gap-3 grid-cols-2 lg:grid-cols-4">
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-semibold text-orange-950">إجمالي الاشتراكات <x-info field="nursery.sub_stat_total" /></p>
            <p class="text-2xl font-extrabold text-orange-600 tabular-nums">{{ $stats['total'] }}</p>
        </div>
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-semibold text-orange-950">المدفوعة <x-info field="nursery.sub_stat_paid" /></p>
            <p class="text-2xl font-extrabold text-emerald-600 tabular-nums">{{ $stats['paid'] }}</p>
        </div>
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-semibold text-orange-950">غير المدفوعة <x-info field="nursery.sub_stat_unpaid" /></p>
            <p class="text-2xl font-extrabold text-amber-600 tabular-nums">{{ $stats['unpaid'] }}</p>
        </div>
        <div class="nursery-card p-4 text-center">
            <p class="text-sm font-semibold text-orange-950">الملغاة <x-info field="nursery.sub_stat_cancelled" /></p>
            <p class="text-2xl font-extrabold text-gray-500 tabular-nums">{{ $stats['cancelled'] }}</p>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <section class="nursery-card overflow-hidden xl:col-span-2">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead>
                        <tr class="bg-orange-50/80 border-b border-orange-100">
                            <th class="px-4 py-3 text-right font-bold text-orange-950">الطفل <x-info field="nursery.sub_child" /></th>
                            <th class="px-4 py-3 text-right font-bold text-orange-950">الخطة <x-info field="nursery.sub_plan" /></th>
                            <th class="px-4 py-3 text-right font-bold text-orange-950">الفترة</th>
                            <th class="px-4 py-3 text-right font-bold text-orange-950">القيمة <x-info field="nursery.sub_amount" /></th>
                            <th class="px-4 py-3 text-right font-bold text-orange-950">الحالة</th>
                            @if($canManage)<th class="px-4 py-3 w-24"></th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $sub)
                            <tr class="border-b border-orange-50 hover:bg-orange-50/40">
                                <td class="px-4 py-3 font-semibold">{{ $sub->child?->name }}</td>
                                <td class="px-4 py-3">{{ $sub->plan?->name }}</td>
                                <td class="px-4 py-3 text-xs tabular-nums">{{ $sub->starts_on?->format('Y-m-d') }} → {{ $sub->ends_on?->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ number_format($sub->finalAmount(), 2) }} ر.س</td>
                                <td class="px-4 py-3">
                                    @if($sub->status === 'cancelled')
                                        <span class="text-gray-500 font-medium">ملغى</span>
                                    @elseif($sub->is_paid)
                                        <span class="text-emerald-700 font-medium">مدفوع</span>
                                    @else
                                        <span class="text-amber-700 font-medium">غير مدفوع</span>
                                    @endif
                                </td>
                                @if($canManage)
                                    <td class="px-4 py-3">
                                        @if($sub->isActive())
                                            <form method="post" action="{{ route('nursery.subscriptions.cancel', $sub) }}" onsubmit="return confirm('إلغاء هذا الاشتراك؟')">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="text-xs text-red-700 hover:underline">إلغاء</button>
                                            </form>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canManage ? 6 : 5 }}" class="px-4 py-14 text-center text-orange-800/70">
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
