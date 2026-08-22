<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تسجيل الدخول — نظام الحضانة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if(file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css'])
    @endif
    @include('nursery.partials.theme-css-vars')
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            min-height: 100vh;
            color: var(--nursery-text);
            background:
                radial-gradient(1200px 600px at 100% -10%, color-mix(in srgb, var(--nursery-primary) 22%, transparent), transparent 55%),
                radial-gradient(900px 500px at -10% 110%, color-mix(in srgb, var(--nursery-secondary) 80%, transparent), transparent 50%),
                linear-gradient(160deg, var(--nursery-bg) 0%, var(--nursery-bg-mid) 45%, var(--nursery-secondary) 100%);
        }
        .nl-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem 2rem;
        }
        .nl-brand {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .nl-brand__mark {
            width: 4.25rem;
            height: 4.25rem;
            margin: 0 auto .85rem;
            border-radius: 1.15rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            background: linear-gradient(135deg, var(--nursery-primary), var(--nursery-primary-dark));
            color: var(--nursery-on-primary);
            box-shadow: 0 10px 28px var(--nursery-shadow);
        }
        .nl-brand__title {
            margin: 0;
            font-size: clamp(1.55rem, 4vw, 1.9rem);
            font-weight: 800;
            color: var(--nursery-text);
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .nl-brand__subtitle {
            margin: .4rem 0 0;
            font-size: .95rem;
            font-weight: 500;
            color: var(--nursery-text-muted);
        }
        .nl-card {
            width: 100%;
            max-width: 26rem;
            background: #fff;
            border: 1px solid var(--nursery-border);
            border-radius: 1.25rem;
            box-shadow: 0 14px 40px var(--nursery-shadow);
            padding: 1.5rem 1.35rem 1.35rem;
        }
        .nl-card__heading {
            margin: 0 0 .35rem;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--nursery-text);
        }
        .nl-card__lead {
            margin: 0 0 1.25rem;
            font-size: .875rem;
            line-height: 1.55;
            color: var(--nursery-text-muted);
        }
        .nl-label {
            display: block;
            font-size: .875rem;
            font-weight: 700;
            color: var(--nursery-text);
            margin-bottom: .4rem;
        }
        .nl-input {
            width: 100%;
            border: 1px solid var(--nursery-border);
            border-radius: .75rem;
            padding: .7rem .9rem;
            font-size: .95rem;
            font-family: inherit;
            color: var(--nursery-text);
            background: #fff;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .nl-input:focus {
            border-color: var(--nursery-primary);
            box-shadow: 0 0 0 3px var(--nursery-focus-ring);
        }
        .nl-field { margin-bottom: 1rem; }
        .nl-error {
            margin: .35rem 0 0;
            font-size: .75rem;
            color: #dc2626;
        }
        .nl-alert {
            margin-bottom: 1rem;
            border-radius: .85rem;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            padding: .75rem .9rem;
            font-size: .85rem;
        }
        .nl-alert ul { margin: .4rem 0 0; padding-inline-start: 1.1rem; }
        .nl-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            margin: .25rem 0 1.15rem;
        }
        .nl-remember {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            font-size: .85rem;
            color: var(--nursery-text-muted);
            cursor: pointer;
            user-select: none;
        }
        .nl-remember input {
            width: 1rem;
            height: 1rem;
            accent-color: var(--nursery-primary);
        }
        .nl-link {
            font-size: .85rem;
            font-weight: 600;
            color: var(--nursery-primary-dark);
            text-decoration: none;
        }
        .nl-link:hover { text-decoration: underline; }
        .nl-btn {
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
            background: linear-gradient(135deg, var(--nursery-primary), var(--nursery-primary-dark));
            color: var(--nursery-on-primary);
            box-shadow: 0 8px 20px var(--nursery-shadow);
            transition: filter .15s, transform .15s;
        }
        .nl-btn:hover { filter: brightness(1.03); }
        .nl-btn:active { transform: translateY(1px); }
        .nl-status {
            margin-bottom: 1rem;
            border-radius: .85rem;
            border: 1px solid var(--nursery-border);
            background: var(--nursery-bg-mid);
            color: var(--nursery-text);
            padding: .75rem .9rem;
            font-size: .85rem;
        }
    </style>
</head>
<body>
    <div class="nl-shell">
        <div class="nl-brand">
            <div class="nl-brand__mark" aria-hidden="true">🧸</div>
            <h1 class="nl-brand__title">نظام الحضانة</h1>
            <p class="nl-brand__subtitle">دخول الطاقم والإدارة — Akwad Nursery</p>
        </div>

        <div class="nl-card">
            <h2 class="nl-card__heading">تسجيل الدخول</h2>
            <p class="nl-card__lead">ادخل بحساب الحضانة الخاص بك لمتابعة الحضور، الفصول، والاشتراكات.</p>

            @if (session('status'))
                <div class="nl-status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="nl-alert" role="alert">
                    <strong>تعذّر تسجيل الدخول</strong>
                    <ul>
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('nursery.login.store') }}" data-html5-validate>
                @csrf

                <div class="nl-field">
                    <label class="nl-label" for="email">البريد الإلكتروني</label>
                    <input id="email" class="nl-input" type="email" name="email" value="{{ old('email') }}"
                           required autofocus autocomplete="username" dir="ltr" placeholder="name@nursery.test">
                    @error('email')<p class="nl-error">{{ $message }}</p>@enderror
                </div>

                <div class="nl-field">
                    <label class="nl-label" for="password">كلمة المرور</label>
                    <input id="password" class="nl-input" type="password" name="password"
                           required autocomplete="current-password" dir="ltr" placeholder="••••••••">
                    @error('password')<p class="nl-error">{{ $message }}</p>@enderror
                </div>

                <div class="nl-row">
                    <label class="nl-remember" for="remember_me">
                        <input id="remember_me" type="checkbox" name="remember">
                        <span>تذكّرني</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a class="nl-link" href="{{ route('password.request') }}">نسيت كلمة المرور؟</a>
                    @endif
                </div>

                <button type="submit" class="nl-btn">دخول إلى الحضانة</button>
            </form>

        </div>
    </div>
</body>
</html>
