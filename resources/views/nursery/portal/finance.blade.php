@extends('layouts.nursery-portal')

@section('title', 'المالية')

@section('content')
<div class="space-y-4">
    <div class="np-card">
        <h2 class="text-lg font-extrabold text-teal-950">السجل المالي</h2>
        <p class="text-sm text-teal-800/75 mt-1">
            اشتراكات أطفالك — للاطلاع فقط
            <x-info field="nursery.portal_finance_intro" />
        </p>
    </div>

    @if($subscriptions->isEmpty())
        <div class="np-card text-sm text-teal-700/80">لا توجد اشتراكات مسجّلة لأطفالك.</div>
    @else
        <div class="space-y-3">
            @foreach($subscriptions as $subscription)
                <div class="np-child-card">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <div class="font-bold text-teal-950">{{ $subscription->child?->name ?? '—' }}</div>
                            <div class="text-sm text-teal-800/75">{{ $subscription->plan?->name ?? 'خطة' }}</div>
                        </div>
                        <span @class([
                            'text-xs font-bold px-2 py-1 rounded-full',
                            'bg-emerald-100 text-emerald-800' => $subscription->is_paid,
                            'bg-amber-100 text-amber-800' => ! $subscription->is_paid,
                        ])>
                            {{ $subscription->is_paid ? 'مدفوع' : 'غير مدفوع' }}
                        </span>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div><dt class="text-teal-800/70">من</dt><dd class="font-medium">{{ $subscription->starts_on?->format('Y-m-d') }}</dd></div>
                        <div><dt class="text-teal-800/70">إلى</dt><dd class="font-medium">{{ $subscription->ends_on?->format('Y-m-d') ?? '—' }}</dd></div>
                        <div class="col-span-2"><dt class="text-teal-800/70">المبلغ</dt><dd class="font-bold text-teal-950">{{ number_format($subscription->finalAmount(), 2) }} ر.س</dd></div>
                    </dl>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@section('bottom_nav')
    @include('nursery.partials.portal-nav')
@endsection
