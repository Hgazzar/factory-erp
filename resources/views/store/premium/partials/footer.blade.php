<footer class="ak-footer">
    <div class="ak-container">
        <div class="ak-footer-grid">
            <div>
                <div class="ak-logo" style="color:#fff;margin-bottom:var(--ak-4)">{{ $storeName }}</div>
                <p class="ak-caption" style="color:rgba(255,255,255,.55);max-width:20rem;line-height:1.7">
                    تجربة تسوق فاخرة بمعايير عالمية — مدعومة من منصة أكواد.
                </p>
                @php $social = $storeSettings->socialLinks(); @endphp
                <div style="display:flex;gap:var(--ak-6);margin-top:var(--ak-6);font-size:0.875rem">
                    @if($social['instagram'])<a href="{{ $social['instagram'] }}" target="_blank" rel="noopener">Instagram</a>@endif
                    @if($social['whatsapp'])<a href="https://wa.me/{{ preg_replace('/\D+/', '', $social['whatsapp']) }}" target="_blank" rel="noopener">WhatsApp</a>@endif
                    @if($social['facebook'])<a href="{{ $social['facebook'] }}" target="_blank" rel="noopener">Facebook</a>@endif
                </div>
            </div>
            <div>
                <p class="ak-eyebrow" style="margin-bottom:var(--ak-4)">استكشف</p>
                <div style="display:flex;flex-direction:column;gap:var(--ak-3);font-size:0.875rem">
                    <a href="{{ $routes['shop'] }}">المتجر</a>
                    <a href="{{ $routes['about'] }}">من نحن</a>
                    <a href="{{ $routes['contact'] }}">اتصل بنا</a>
                </div>
            </div>
            <div>
                <p class="ak-eyebrow" style="margin-bottom:var(--ak-4)">السياسات</p>
                <div style="display:flex;flex-direction:column;gap:var(--ak-3);font-size:0.875rem">
                    <a href="{{ $routes['shipping'] }}">الشحن</a>
                    <a href="{{ $routes['privacy'] }}">الخصوصية</a>
                    <a href="{{ $routes['faq'] }}">الأسئلة الشائعة</a>
                </div>
            </div>
        </div>
        <p style="text-align:center;margin-top:var(--ak-12);padding-top:var(--ak-8);border-top:1px solid rgba(255,255,255,.08);font-size:0.75rem;color:rgba(255,255,255,.4)">
            © {{ date('Y') }} {{ $storeName }}
        </p>
    </div>
</footer>
