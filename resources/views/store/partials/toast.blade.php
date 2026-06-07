<div class="fixed top-24 left-1/2 -translate-x-1/2 z-[60]" x-show="toastOpen" x-cloak x-transition>
    <div class="toast bg-gray-800 text-white px-6 py-3 rounded-xl shadow-2xl flex items-center gap-3">
        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
            <i class="fas fa-check text-sm"></i>
        </div>
        <span class="font-bold text-sm" x-text="toastMessage"></span>
    </div>
</div>
