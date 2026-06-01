<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $storeName ?? config('app.name'))</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @if(file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    @endif
    <style>
        :root { --store-primary: #4f46e5; --store-primary-dark: #4338ca; --store-bg: #f8fafc; }
        body { font-family: 'Cairo', sans-serif; margin: 0; background: var(--store-bg); color: #0f172a; }
        [x-cloak] { display: none !important; }
        .store-container { max-width: 72rem; margin: 0 auto; padding: 0 1rem; }
        .store-header { position: sticky; top: 0; z-index: 40; background: #fff; border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 8px rgba(15,23,42,.06); }
        .store-header-inner { display: flex; align-items: center; gap: .75rem; padding: .75rem 0; flex-wrap: wrap; }
        .store-logo { font-weight: 800; font-size: 1.1rem; color: var(--store-primary); text-decoration: none; white-space: nowrap; }
        .store-search { flex: 1; min-width: 160px; border: 1px solid #cbd5e1; border-radius: 999px; padding: .55rem 1rem; font-size: .95rem; }
        .store-cart-btn { position: relative; background: var(--store-primary); color: #fff; border: none; border-radius: 999px; padding: .55rem 1rem; font-weight: 700; text-decoration: none; font-size: .9rem; }
        .store-cart-badge { position: absolute; top: -6px; left: -6px; background: #ef4444; color: #fff; font-size: .7rem; min-width: 1.25rem; height: 1.25rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; }
        .store-hero { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #fff; border-radius: 1rem; padding: 1.5rem 1.25rem; margin: 1rem 0; }
        .store-footer { background: #0f172a; color: #cbd5e1; padding: 2rem 0 2.5rem; margin-top: 2rem; }
        .store-footer a { color: #e2e8f0; text-decoration: none; }
        .store-footer a:hover { color: #fff; }
        .store-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
        @media (min-width: 640px) { .store-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; } }
        @media (min-width: 1024px) { .store-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        .store-product { background: #fff; border: 1px solid #e2e8f0; border-radius: .85rem; overflow: hidden; display: flex; flex-direction: column; }
        .store-product-thumb { aspect-ratio: 1; background: linear-gradient(145deg, #818cf8, #a78bfa); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; color: #fff; }
        .store-product-body { padding: .75rem; flex: 1; display: flex; flex-direction: column; }
        .store-btn { background: var(--store-primary); color: #fff; border: none; border-radius: .65rem; padding: .5rem .75rem; font-weight: 700; cursor: pointer; width: 100%; }
        .store-btn:disabled { opacity: .5; cursor: not-allowed; }
        .store-chip { border: 1px solid #cbd5e1; background: #fff; border-radius: 999px; padding: .35rem .85rem; font-size: .85rem; cursor: pointer; white-space: nowrap; }
        .store-chip.is-active { background: var(--store-primary); border-color: var(--store-primary); color: #fff; }
    </style>
    @stack('styles')
    <style>@media (min-width:768px){.store-footer-grid{grid-template-columns:1fr 1fr!important}}</style>
</head>
<body x-data="storeCartShell('{{ $tenantSlug }}')" x-init="init()">
    <header class="store-header">
        <div class="store-container store-header-inner">
            <a href="{{ route('store.portal.home', ['tenant_slug' => $tenantSlug]) }}" class="store-logo">
                @if(!empty($company?->logo_url))
                    <img src="{{ asset('storage/'.$company->logo_url) }}" alt="" style="height:32px;vertical-align:middle" class="me-1">
                @endif
                {{ $storeName }}
            </a>
            @hasSection('header_search')
                @yield('header_search')
            @endif
            <a href="{{ route('store.portal.cart', ['tenant_slug' => $tenantSlug]) }}" class="store-cart-btn ms-auto">
                السلة
                <span class="store-cart-badge" x-show="cartCount > 0" x-text="cartCount"></span>
            </a>
        </div>
    </header>

    <main class="store-container pb-4">
        @yield('content')
    </main>

    <footer class="store-footer">
        <div class="store-container">
            <div class="store-footer-grid" style="display:grid;grid-template-columns:1fr;gap:1.5rem">
                <div>
                    <div class="fw-bold text-white mb-2">{{ $storeName }}</div>
                    <p class="small mb-0 opacity-75">تسوق أونلاين — دفع عند الاستلام</p>
                    @php $social = $storeSettings->socialLinks(); @endphp
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @if($social['whatsapp'])<a href="https://wa.me/{{ preg_replace('/\D+/', '', $social['whatsapp']) }}" target="_blank" rel="noopener">واتساب</a>@endif
                        @if($social['instagram'])<a href="{{ $social['instagram'] }}" target="_blank" rel="noopener">إنستغرام</a>@endif
                        @if($social['facebook'])<a href="{{ $social['facebook'] }}" target="_blank" rel="noopener">فيسبوك</a>@endif
                        @if($social['twitter'])<a href="{{ $social['twitter'] }}" target="_blank" rel="noopener">X</a>@endif
                    </div>
                </div>
                <div>
                    <div class="fw-bold text-white mb-2">روابط سريعة</div>
                    <div class="d-flex flex-column gap-1 small">
                        <a href="{{ route('store.portal.about', ['tenant_slug' => $tenantSlug]) }}">من نحن</a>
                        <a href="{{ route('store.portal.contact', ['tenant_slug' => $tenantSlug]) }}">اتصل بنا</a>
                        <a href="{{ route('store.portal.faq', ['tenant_slug' => $tenantSlug]) }}">الأسئلة الشائعة</a>
                        <a href="{{ route('store.portal.shipping', ['tenant_slug' => $tenantSlug]) }}">سياسة الشحن</a>
                        <a href="{{ route('store.portal.privacy', ['tenant_slug' => $tenantSlug]) }}">سياسة الخصوصية</a>
                    </div>
                </div>
            </div>
            <div class="text-center small mt-4 pt-3 border-top border-secondary opacity-75">
                © {{ date('Y') }} {{ $storeName }}
            </div>
        </div>
    </footer>

    <script>
    function storeCartShell(slug) {
        return {
            slug,
            cartCount: 0,
            init() {
                this.refreshCount();
                window.addEventListener('store-cart-updated', () => this.refreshCount());
            },
            storageKey() { return 'akwad_store_cart_' + this.slug; },
            readCart() {
                try { return JSON.parse(localStorage.getItem(this.storageKey()) || '[]'); }
                catch { return []; }
            },
            refreshCount() {
                const cart = this.readCart();
                this.cartCount = cart.reduce((s, l) => s + Number(l.quantity || 0), 0);
            },
            addToCart(product, qty = 1) {
                const cart = this.readCart();
                const existing = cart.find(l => l.id === product.id);
                if (existing) {
                    existing.quantity = Number(existing.quantity) + qty;
                } else {
                    cart.push({
                        id: product.id,
                        name: product.name,
                        sale_price: product.sale_price,
                        vat_percent: product.vat_percent || 0,
                        quantity: qty,
                    });
                }
                localStorage.setItem(this.storageKey(), JSON.stringify(cart));
                window.dispatchEvent(new CustomEvent('store-cart-updated'));
            },
            formatMoney(amount) {
                return '{{ $currencyCode }} ' + Number(amount || 0).toFixed(2);
            },
        };
    }
    </script>
    @stack('scripts')
</body>
</html>
