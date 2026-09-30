<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تسجيل الدخول — نظام العيادة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if(file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css'])
    @endif
    @include('tenant.partials.theme-css-vars', [
        'tenantThemeVars' => $clinicThemeVars ?? null,
    ])
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            min-height: 100vh;
            color: var(--clinic-text);
            background: var(--clinic-bg, #F0FDFA);
        }
        .cl-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem 2rem;
        }
        .cl-brand {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .cl-brand__mark {
            width: 4.25rem;
            height: 4.25rem;
            margin: 0 auto .85rem;
            border-radius: 1.15rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--clinic-primary);
            color: var(--clinic-on-primary);
            box-shadow: 0 10px 28px var(--clinic-shadow);
        }
        .cl-brand__mark svg {
            width: 2.1rem;
            height: 2.1rem;
        }
        .cl-brand__rx {
            font-size: 2.15rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.04em;
            font-family: 'Times New Roman', 'Noto Serif', Georgia, serif;
        }
        .cl-brand__title {
            margin: 0;
            font-size: clamp(1.55rem, 4vw, 1.9rem);
            font-weight: 800;
            color: var(--clinic-text);
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .cl-brand__subtitle {
            margin: .4rem 0 0;
            font-size: .95rem;
            font-weight: 500;
            color: var(--clinic-text-muted);
        }
        .cl-card {
            width: 100%;
            max-width: 26rem;
            background: #fff;
            border: 1px solid var(--clinic-border);
            border-radius: 1.25rem;
            box-shadow: 0 14px 40px var(--clinic-shadow);
            padding: 1.5rem 1.35rem 1.35rem;
        }
        .cl-card__heading {
            margin: 0 0 .35rem;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--clinic-text);
        }
        .cl-card__lead {
            margin: 0 0 1.25rem;
            font-size: .875rem;
            line-height: 1.55;
            color: var(--clinic-text-muted);
        }
        .cl-label {
            display: block;
            font-size: .875rem;
            font-weight: 700;
            color: var(--clinic-text);
            margin-bottom: .4rem;
        }
        .cl-input {
            width: 100%;
            border: 1px solid var(--clinic-border);
            border-radius: .75rem;
            padding: .7rem .9rem;
            font-size: .95rem;
            font-family: inherit;
            color: var(--clinic-text);
            background: #fff;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .cl-input:focus {
            border-color: var(--clinic-primary);
            box-shadow: 0 0 0 3px var(--clinic-focus-ring);
        }
        .cl-field { margin-bottom: 1rem; }
        .cl-error {
            margin: .35rem 0 0;
            font-size: .75rem;
            color: #dc2626;
        }
        .cl-alert {
            margin-bottom: 1rem;
            border-radius: .85rem;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            padding: .75rem .9rem;
            font-size: .85rem;
        }
        .cl-alert ul { margin: .4rem 0 0; padding-inline-start: 1.1rem; }
        .cl-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            margin: .25rem 0 1.15rem;
        }
        .cl-remember {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            font-size: .85rem;
            color: var(--clinic-text-muted);
            cursor: pointer;
            user-select: none;
        }
        .cl-remember input {
            width: 1rem;
            height: 1rem;
            accent-color: var(--clinic-primary);
        }
        .cl-link {
            font-size: .85rem;
            font-weight: 600;
            color: var(--clinic-primary-dark);
            text-decoration: none;
        }
        .cl-link:hover { text-decoration: underline; }
        .cl-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            border: none;
            border-radius: .8rem;
            padding: .85rem 1rem;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            background: var(--clinic-primary);
            color: var(--clinic-on-primary);
            box-shadow: 0 8px 20px var(--clinic-shadow);
            transition: filter .15s, transform .15s;
        }
        .cl-btn:hover { filter: brightness(1.03); }
        .cl-btn:active { transform: translateY(1px); }
        .cl-status {
            margin-bottom: 1rem;
            border-radius: .85rem;
            border: 1px solid var(--clinic-border);
            background: var(--clinic-bg-mid);
            color: var(--clinic-text);
            padding: .75rem .9rem;
            font-size: .85rem;
        }
    </style>
</head>
<body>
    <div class="cl-shell">
        <div class="cl-brand">
            <div class="cl-brand__mark" aria-hidden="true">
                <span class="cl-brand__rx">℞</span>
            </div>
            <h1 class="cl-brand__title">نظام العيادة</h1>
            <p class="cl-brand__subtitle">دخول الطاقم والإدارة — Akwad Clinic</p>
        </div>

        <div class="cl-card">
            <h2 class="cl-card__heading">تسجيل الدخول</h2>
            <p class="cl-card__lead">ادخل بحساب العيادة الخاص بك لمتابعة الحجوزات، المرضى، والروشتات.</p>

            @if (session('status'))
                <div class="cl-status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="cl-alert" role="alert">
                    <strong>تعذّر تسجيل الدخول</strong>
                    <ul>
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('clinic.login.store') }}" data-html5-validate>
                @csrf

                <div class="cl-field">
                    <label class="cl-label" for="email">البريد الإلكتروني</label>
                    <input id="email" class="cl-input" type="email" name="email" value="{{ old('email') }}"
                           required autofocus autocomplete="username" dir="ltr" placeholder="name@clinic.test">
                    @error('email')<p class="cl-error">{{ $message }}</p>@enderror
                </div>

                <div class="cl-field">
                    <label class="cl-label" for="password">كلمة المرور</label>
                    <input id="password" class="cl-input" type="password" name="password"
                           required autocomplete="current-password" dir="ltr" placeholder="••••••••">
                    @error('password')<p class="cl-error">{{ $message }}</p>@enderror
                </div>

                <div class="cl-row">
                    <label class="cl-remember" for="remember_me">
                        <input id="remember_me" type="checkbox" name="remember">
                        <span>تذكّرني</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a class="cl-link" href="{{ route('password.request') }}">نسيت كلمة المرور؟</a>
                    @endif
                </div>

                <button type="submit" class="cl-btn">دخول إلى العيادة</button>
            </form>

        </div>
    </div>
</body>
</html>
