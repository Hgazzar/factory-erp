@php
    $branding = $branding ?? [];
    $themePrimary = old('theme_primary_color', $branding['theme_primary'] ?? '#f97316');
    $themeSecondary = old('theme_secondary_color', $branding['theme_secondary'] ?? '#ffedd5');
    $hintPrefix = $hintPrefix ?? 'tenant';
    $entityLabel = $entityLabel ?? 'المنشأة';
    $portalUrl = $portalUrl ?? null;
    $submitRoute = $submitRoute ?? '#';
    $canManage = $canManage ?? true;
    $accent = $accent ?? 'orange';
    $isTeal = $accent === 'teal';
    $cardClass = $cardClass ?? ($isTeal ? 'clinic-card' : 'nursery-card');
    $btnPrimaryClass = $btnPrimaryClass ?? ($isTeal ? 'clinic-btn clinic-btn-primary' : 'nursery-btn nursery-btn-primary');
    $btnSoftClass = $btnSoftClass ?? ($isTeal ? 'clinic-btn clinic-btn-outline' : 'nursery-btn nursery-btn-soft');
    $previewEmoji = $previewEmoji ?? ($isTeal ? '🏥' : '🧸');
    $c = $isTeal ? [
        'border' => 'border-teal-100', 'border2' => 'border-teal-200', 'bgSoft' => 'bg-teal-50/60',
        'title' => 'text-teal-950', 'text' => 'text-teal-800/80', 'textSm' => 'text-teal-800/85',
        'muted' => 'text-teal-700/60', 'muted2' => 'text-teal-700/70', 'label' => 'text-teal-900',
        'checkbox' => 'border-teal-300', 'fileBtn' => 'file:bg-teal-600 hover:file:bg-teal-700',
    ] : [
        'border' => 'border-orange-100', 'border2' => 'border-orange-200', 'bgSoft' => 'bg-orange-50/60',
        'title' => 'text-orange-950', 'text' => 'text-orange-800/80', 'textSm' => 'text-orange-800/85',
        'muted' => 'text-orange-700/60', 'muted2' => 'text-orange-700/70', 'label' => 'text-orange-900',
        'checkbox' => 'border-orange-300', 'fileBtn' => 'file:bg-orange-500 hover:file:bg-orange-600',
    ];
@endphp
<section class="{{ $cardClass }} p-5 space-y-6" x-data="{ primary: @js($themePrimary), secondary: @js($themeSecondary) }">
    <div class="border-b {{ $c['border'] }} pb-3">
        <h2 class="text-lg font-bold {{ $c['title'] }}">الهوية البصرية ل{{ $entityLabel }}</h2>
        <p class="text-sm {{ $c['text'] }} mt-1">
            <x-info :field="$hintPrefix.'.settings_branding_intro'" />
            الشعار والألوان والاسم يظهران في البوابات ولوحة التحكم.
        </p>
    </div>

    <div class="flex flex-wrap items-start gap-6 p-4 rounded-xl {{ $c['bgSoft'] }} border {{ $c['border'] }}">
        <div class="shrink-0 text-center">
            <p class="text-xs font-semibold {{ $c['title'] }} mb-2">معاينة الشعار</p>
            <div class="w-24 h-24 rounded-2xl border-2 {{ $c['border2'] }} bg-white shadow-sm flex items-center justify-center overflow-hidden p-2 mx-auto">
                @if(!empty($branding['logo_url']))
                    <img src="{{ $branding['logo_url'] }}" alt="" class="max-w-full max-h-full object-contain">
                @else
                    <span class="text-4xl" aria-hidden="true">{{ $previewEmoji }}</span>
                @endif
            </div>
            <p class="text-xs {{ $c['muted2'] }} mt-2 max-w-[9rem]">{{ $branding['display_name'] ?? $entityLabel }}</p>
        </div>
        <div class="flex-1 min-w-[200px] text-sm {{ $c['textSm'] }} space-y-2">
            <p><strong>أين يظهر؟</strong></p>
            <ul class="list-disc list-inside space-y-1 text-xs">
                @if($portalPathHint ?? null)
                    <li>بوابة العملاء (<code dir="ltr">{{ $portalPathHint }}</code>)</li>
                @endif
                <li>القائمة الجانبية في لوحة {{ $entityLabel }}</li>
            </ul>
            @if(empty($branding['logo_url']))
                <p class="text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-xs mt-2">
                    لم يُرفع شعار بعد — اختر صورة ثم احفظ.
                </p>
            @endif
        </div>
    </div>

    @if($canManage)
        <form method="POST" action="{{ $submitRoute }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-semibold {{ $c['title'] }} mb-1">
                    الاسم الظاهر في البوابة
                    <x-info :field="$hintPrefix.'.settings_display_name'" />
                </label>
                <input type="text" name="display_name" value="{{ old('display_name', $displayNameValue ?? '') }}"
                       placeholder="{{ $displayNamePlaceholder ?? '' }}"
                       class="w-full max-w-md rounded-lg border {{ $c['border2'] }} px-3 py-2 text-sm">
                @if($displayNameHelp ?? null)
                    <p class="text-xs {{ $c['muted'] }} mt-1">{{ $displayNameHelp }}</p>
                @endif
                @error('display_name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold {{ $c['title'] }} mb-2">
                    الشعار
                    <x-info :field="$hintPrefix.'.settings_logo'" />
                </label>
                @if(!empty($branding['logo_url']))
                    <label class="inline-flex items-center gap-2 text-sm {{ $c['label'] }} mb-3">
                        <input type="checkbox" name="remove_logo" value="1" class="rounded {{ $c['checkbox'] }}">
                        حذف الشعار الحالي
                    </label>
                @endif
                <input type="file" name="logo_file" accept="image/png,image/jpeg,image/webp,image/gif"
                       class="w-full max-w-lg text-sm file:mr-3 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-white file:font-bold {{ $c['fileBtn'] }}">
                <p class="text-xs {{ $c['muted'] }} mt-2">PNG أو JPG أو WebP — حتى 2 ميجا.</p>
                @error('logo_file')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="border-t {{ $c['border'] }} pt-5">
                <h3 class="text-sm font-bold {{ $c['title'] }} mb-3">
                    تخصيص الألوان
                    <x-info :field="$hintPrefix.'.settings_theme_colors'" />
                </h3>
                <div class="grid gap-4 sm:grid-cols-2 max-w-xl">
                    <div>
                        <label class="block text-sm font-semibold {{ $c['title'] }} mb-2">اللون الأساسي</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="theme_primary_color" x-model="primary" class="h-11 w-14 rounded-lg border {{ $c['border2'] }} cursor-pointer p-0.5 bg-white">
                            <input type="text" x-model="primary" maxlength="7" dir="ltr" class="flex-1 rounded-lg border {{ $c['border2'] }} px-3 py-2 text-sm font-mono">
                        </div>
                        @error('theme_primary_color')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold {{ $c['title'] }} mb-2">اللون الثانوي</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="theme_secondary_color" x-model="secondary" class="h-11 w-14 rounded-lg border {{ $c['border2'] }} cursor-pointer p-0.5 bg-white">
                            <input type="text" x-model="secondary" maxlength="7" dir="ltr" class="flex-1 rounded-lg border {{ $c['border2'] }} px-3 py-2 text-sm font-mono">
                        </div>
                        @error('theme_secondary_color')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <label class="inline-flex items-center gap-2 text-sm {{ $c['label'] }} mt-3">
                    <input type="checkbox" name="reset_theme_colors" value="1" class="rounded {{ $c['checkbox'] }}">
                    إعادة الألوان الافتراضية للنيش
                </label>
                <div class="mt-4 p-4 rounded-xl border {{ $c['border'] }}" :style="`background: linear-gradient(160deg, ${secondary} 0%, #fff 100%)`">
                    <p class="text-xs font-semibold {{ $c['title'] }} mb-3">معاينة سريعة</p>
                    <div class="flex flex-wrap gap-2 items-center">
                        <button type="button" class="px-4 py-2 rounded-lg text-sm font-bold shadow-sm"
                                :style="`background:${primary}; color: ${parseInt(primary.slice(1,3),16)*0.299 + parseInt(primary.slice(3,5),16)*0.587 + parseInt(primary.slice(5,7),16)*0.114 > 140 ? '#1c1917' : '#fff'}`">زر أساسي</button>
                        <span class="px-3 py-1.5 rounded-lg text-sm font-semibold border" :style="`background:${secondary}; border-color:${primary}; color:${primary}`">عنصر ثانوي</span>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="{{ $btnPrimaryClass }}">حفظ الهوية البصرية</button>
                @if($portalUrl)
                    <a href="{{ $portalUrl }}" target="_blank" rel="noopener" class="{{ $btnSoftClass }} text-sm">معاينة البوابة</a>
                @endif
            </div>
        </form>
    @else
        <p class="text-sm {{ $c['text'] }}">ليس لديك صلاحية تعديل الهوية البصرية.</p>
    @endif
</section>
