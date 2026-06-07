<div id="cartOverlay" class="fixed inset-0 bg-black/50 z-50 overlay" x-show="cartOpen" x-cloak @click="closeCart()"></div>
<div id="cartSidebar" class="fixed top-0 left-0 h-full w-full sm:w-[420px] bg-white z-50 transition-transform duration-300 shadow-2xl flex flex-col"
     :class="cartOpen ? 'translate-x-0' : '-translate-x-full'">
    <div class="p-6 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-xl font-black text-gray-800 flex items-center gap-2">
            <i class="fas fa-shopping-bag text-store-primary"></i>
            سلة التسوق
            <span class="text-sm font-bold text-gray-400">(<span x-text="cartCount"></span>)</span>
        </h2>
        <button type="button" @click="closeCart()" class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center hover:bg-red-50 hover:text-red-500 transition-all">
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto p-6 space-y-4">
        <template x-if="!cartLines.length">
            <div class="text-center py-16">
                <i class="fas fa-shopping-bag text-6xl text-gray-200 mb-4"></i>
                <p class="text-gray-400 font-bold">السلة فارغة</p>
                <p class="text-gray-300 text-sm mt-1">أضف منتجات للبدء بالتسوق</p>
            </div>
        </template>
        <template x-for="line in cartLines" :key="line.id">
            <div class="flex gap-4 bg-gray-50 rounded-xl p-3 fade-in">
                <img :src="line.image_url" :alt="line.name" class="w-20 h-20 object-cover rounded-xl flex-shrink-0">
                <div class="flex-1 min-w-0">
                    <h4 class="font-bold text-sm text-gray-800 truncate" x-text="line.name"></h4>
                    <p class="text-store-primary font-black text-sm mt-1" x-text="formatMoney(line.sale_price)"></p>
                    <div class="flex items-center justify-between mt-2">
                        <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden bg-white">
                            <button type="button" @click="updateQty(line.id, line.quantity - 1)" class="px-2.5 py-1 hover:bg-gray-100 text-xs font-bold text-gray-500 qty-btn">-</button>
                            <span class="px-3 py-1 text-xs font-bold text-gray-800" x-text="line.quantity"></span>
                            <button type="button" @click="updateQty(line.id, line.quantity + 1)" class="px-2.5 py-1 hover:bg-gray-100 text-xs font-bold text-gray-500 qty-btn">+</button>
                        </div>
                        <button type="button" @click="removeLine(line.id)" class="text-gray-400 hover:text-red-500 transition-colors p-1">
                            <i class="fas fa-trash-alt text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div class="border-t border-gray-100 p-6 space-y-4" x-show="cartLines.length" x-cloak>
        <div class="flex justify-between text-sm text-gray-500">
            <span>المجموع الفرعي</span>
            <span x-text="quote ? formatMoney(quote.subtotal) : '—'"></span>
        </div>
        <div class="flex justify-between text-sm text-gray-500">
            <span>الشحن</span>
            <span class="text-green-500 font-bold">مجاني</span>
        </div>
        <div class="flex justify-between text-xl font-black text-gray-800">
            <span>الإجمالي</span>
            <span class="gradient-text" x-text="quote ? formatMoney(quote.total) : '—'"></span>
        </div>
        <a :href="routes.checkout" class="block w-full py-4 bg-store-gradient text-white rounded-xl font-bold text-lg text-center hover-shadow-store transition-all hover:-translate-y-0.5 active:translate-y-0">
            <i class="fas fa-lock ml-2"></i>إتمام الشراء
        </a>
        <button type="button" @click="closeCart()" class="w-full py-3 bg-gray-100 text-gray-600 rounded-xl font-bold hover:bg-gray-200 transition-all text-sm">
            <i class="fas fa-arrow-right ml-1"></i>متابعة التسوق
        </button>
    </div>
</div>
