<footer class="bg-dark-900 text-white mt-16">
    <div class="max-w-7xl mx-auto px-4 py-12">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-8">
            <div class="col-span-2 md:col-span-1">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-store-gradient-br flex items-center justify-center">
                        <i class="fas fa-store text-white"></i>
                    </div>
                    <h3 class="text-lg font-black">{{ $storeName }}</h3>
                </div>
                <p class="text-gray-400 text-sm leading-relaxed">وجهتك الأولى للتسوق الإلكتروني بأفضل الأسعار وأعلى جودة</p>
            </div>
            <div>
                <h4 class="font-bold mb-4">روابط سريعة</h4>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="{{ $routes['home'] }}" class="hover:text-white transition-colors">الرئيسية</a></li>
                    <li><a href="{{ $routes['shop'] }}" class="hover:text-white transition-colors">المنتجات</a></li>
                    <li><a href="{{ $routes['offers'] }}" class="hover:text-white transition-colors">العروض</a></li>
                    <li><a href="{{ $routes['contact'] }}" class="hover:text-white transition-colors">تواصل معنا</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold mb-4">خدمة العملاء</h4>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="{{ $routes['returns'] }}" class="hover:text-white transition-colors">سياسة الإرجاع</a></li>
                    <li><a href="{{ $routes['shipping'] }}" class="hover:text-white transition-colors">الشحن والتوصيل</a></li>
                    <li><a href="{{ $routes['faq'] }}" class="hover:text-white transition-colors">الأسئلة الشائعة</a></li>
                    <li><a href="{{ $routes['track'] }}" class="hover:text-white transition-colors">تتبع الطلب</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold mb-4">تواصل معنا</h4>
                @if(!empty($storeSettings->contact_us))
                    <p class="text-gray-400 text-sm whitespace-pre-line">{{ \Illuminate\Support\Str::limit(strip_tags($storeSettings->contact_us), 120) }}</p>
                @endif
            </div>
        </div>
        <hr class="border-gray-800 mb-6">
        <div class="text-center text-gray-500 text-sm">
            <p>© {{ date('Y') }} {{ $storeName }}. جميع الحقوق محفوظة</p>
        </div>
    </div>
</footer>
