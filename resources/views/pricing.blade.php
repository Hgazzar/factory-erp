@extends('layouts.pricing')

@section('title', 'الباقات والأسعار — '.config('app.name'))

@section('content')
<header class="pr-nav" :class="{ 'is-scrolled': scrolled }">
    <div class="pr-container pr-nav__inner">
        <a href="{{ url('/') }}" class="pr-logo">{{ config('app.name') }}</a>
        <nav class="pr-nav__links" aria-label="تنقل">
            <a href="{{ url('/') }}">الرئيسية</a>
            <a href="#plans">الباقات</a>
            <a href="#compare">المقارنة</a>
        </nav>
        @if(Route::has('login'))
            <a href="{{ route('login') }}" class="pr-btn pr-btn--ghost" style="width:auto;padding:0.5rem 1.25rem;font-size:0.8125rem">تسجيل الدخول</a>
        @endif
    </div>
</header>

<main x-data="prBilling()">
    <section class="pr-hero pr-container">
        <p style="font-size:0.6875rem;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;color:var(--pr-gold);margin:0 0 1rem">أكواد · خطط مرنة</p>
        <h1 class="pr-hero__title">اختر الباقة المناسبة لحجم أعمالك</h1>
        <p class="pr-hero__sub">
            منصة واحدة تجمع المحاسبة والمخزون والتجارة الإلكترونية — بتجربة فاخرة ووحدات تتوسع مع نموك.
        </p>

        <div class="pr-toggle" role="group" aria-label="دورة الفوترة">
            <button type="button" class="pr-toggle__label" :class="{ 'is-active': cycle === 'monthly' }" @click="cycle = 'monthly'">
                شهري
            </button>
            <button type="button" class="pr-toggle__label" :class="{ 'is-active': cycle === 'yearly' }" @click="cycle = 'yearly'">
                سنوي
                <span class="pr-toggle__save">وفّر 17%</span>
            </button>
        </div>
    </section>

    <section id="plans" class="pr-container">
        <div class="pr-cards">
            @foreach($plans as $plan)
                <article class="pr-card @if($plan['popular']) pr-card--popular @endif">
                    @if($plan['popular'])
                        <span class="pr-card__badge">الأكثر شيوعاً</span>
                    @endif

                    <p class="pr-card__tier" dir="ltr">{{ $plan['name'] }}</p>
                    <h2 class="pr-card__name">{{ $plan['name_ar'] }}</h2>
                    <p class="pr-card__tagline">{{ $plan['tagline'] }}</p>

                    <div class="pr-card__price">
                        <span class="pr-card__amount" x-text="price(@js($plan)).toLocaleString('en-US')"></span>
                        <span class="pr-card__currency">ر.س</span>
                    </div>
                    <p class="pr-card__period">
                        <span x-text="periodLabel()"></span>
                        <template x-if="isYearly() && monthlyEquivalent(@js($plan))">
                            <span> · يعادل <span x-text="monthlyEquivalent(@js($plan)).toLocaleString('en-US')"></span> ر.س/شهر</span>
                        </template>
                    </p>

                    <ul class="pr-card__features">
                        @foreach($plan['modules'] as $feature)
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-3.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    @php
                        $ctaClass = $plan['popular'] ? 'pr-btn--gold' : 'pr-btn--primary';
                        $ctaUrl = Route::has('register') ? route('register') : (Route::has('login') ? route('login') : url('/'));
                    @endphp
                    <a href="{{ $ctaUrl }}" class="pr-btn {{ $ctaClass }}">
                        @if(!empty($plan['custom']))
                            تواصل مع المبيعات
                        @else
                            ابدأ تجربتك المجانية
                        @endif
                    </a>
                </article>
            @endforeach
        </div>
    </section>

    <section id="compare" class="pr-compare">
        <div class="pr-container">
            <h2 class="pr-compare__title">مقارنة تفصيلية</h2>
            <p class="pr-compare__sub">كل ما تحتاجه لاتخاذ القرار — بدون تعقيد</p>

            <div class="pr-table-wrap">
                <table class="pr-table">
                    <thead>
                        <tr>
                            <th scope="col">الميزة</th>
                            <th scope="col">Basic</th>
                            <th scope="col" class="pr-col--popular">Premium Plus</th>
                            <th scope="col">Enterprise</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comparison as $group)
                            <tr class="pr-table__cat">
                                <td colspan="4">{{ $group['category'] }}</td>
                            </tr>
                            @foreach($group['rows'] as $row)
                                <tr>
                                    <td>{{ $row['feature'] }}</td>
                                    @foreach(['basic', 'plus', 'enterprise'] as $tier)
                                        <td @if($tier === 'plus') class="pr-col--popular" @endif>
                                            @if(is_bool($row[$tier]))
                                                @if($row[$tier])
                                                    <span class="pr-cell--yes" aria-label="متضمن">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20" style="display:inline"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-3.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                                    </span>
                                                @else
                                                    <span class="pr-cell--no" aria-label="غير متضمن">—</span>
                                                @endif
                                            @else
                                                <span style="font-size:0.8125rem;color:var(--pr-ink-muted)">{{ $row[$tier] }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="pr-footer-cta pr-container">
        <h2>جاهز لتجربة أكواد؟</h2>
        <p>14 يوماً تجريبية — بدون بطاقة ائتمان. فريقنا يساعدك على الإعداد.</p>
        <div style="display:flex;flex-wrap:wrap;gap:1rem;justify-content:center">
            <a href="{{ Route::has('register') ? route('register') : url('/') }}" class="pr-btn pr-btn--gold" style="width:auto;min-width:12rem">ابدأ تجربتك المجانية</a>
            <a href="mailto:sales@{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'akwad.app' }}" class="pr-btn pr-btn--ghost" style="width:auto;min-width:12rem">تحدث مع المبيعات</a>
        </div>
    </section>
</main>

<footer class="pr-site-footer">
    <div class="pr-container">
        © {{ date('Y') }} {{ config('app.name') }} — منصة إدارة أعمال بمعايير عالمية
    </div>
</footer>
@endsection
