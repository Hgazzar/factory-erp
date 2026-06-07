@extends('layouts.nursery-portal')

@section('title', 'أطفالي')

@section('content')
<div class="space-y-4">
    <div class="np-card">
        <p class="text-sm text-orange-800/80 mb-1">مرحباً،</p>
        <h2 class="text-xl font-extrabold text-orange-950">{{ $guardian->name }}</h2>
    </div>

    <div>
        <h3 class="font-bold text-orange-950 mb-2 px-1">أطفالي</h3>
        @if($children->isEmpty())
            <div class="np-card text-sm text-orange-700/80">
                لا يوجد أطفال نشطون مرتبطون بحسابك حالياً.
            </div>
        @else
            <div class="space-y-3">
                @foreach($children as $child)
                    <a href="{{ route('nursery.portal.children.show', ['tenant_slug' => $tenantSlug, 'childId' => $child->id]) }}"
                       class="np-child-card block hover:ring-2 hover:ring-orange-200 transition">
                        <div class="font-bold text-orange-950">{{ $child->name }}</div>
                        <div class="text-sm text-orange-800/75 mt-1">
                            {{ $child->activeEnrollment?->classroom?->name ?? 'غير معين لفصل' }}
                        </div>
                        @if($child->code)
                            <div class="text-xs text-orange-700/60 mt-1" dir="ltr">كود: {{ $child->code }}</div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('nursery.portal.logout', ['tenant_slug' => $tenantSlug]) }}">
        @csrf
        <button type="submit" class="np-btn np-btn-soft">تسجيل الخروج</button>
    </form>
</div>
@endsection

@section('bottom_nav')
    @include('nursery.partials.portal-nav')
@endsection
