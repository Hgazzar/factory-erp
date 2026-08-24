@props([
    'name' => '',
    'src' => null,
    'inputName' => 'avatar',
    'infoField' => null,
    'removeName' => 'remove_avatar',
])

@php
    $initial = trim((string) $name) !== '' ? mb_strtoupper(mb_substr(trim((string) $name), 0, 1)) : '؟';
    $originalSrc = $src;
@endphp

<div class="nursery-avatar-upload rounded-2xl border border-teal-100 bg-teal-50/30 p-4"
     x-data="{
        original: @js($originalSrc),
        preview: @js($originalSrc),
        pendingFile: false,
        revoke() {
            if (this.preview && String(this.preview).startsWith('blob:')) {
                URL.revokeObjectURL(this.preview);
            }
        },
        onPick(event) {
            const file = event.target.files && event.target.files[0];
            this.revoke();
            if (file) {
                this.preview = URL.createObjectURL(file);
                this.pendingFile = true;
                if (this.$refs.removeBox) this.$refs.removeBox.checked = false;
            } else {
                this.preview = this.original;
                this.pendingFile = false;
            }
        },
        clearPreview() {
            this.revoke();
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';

            // إلغاء اختيار جديد → رجوع للصورة الأصلية (إن وُجدت)
            if (this.pendingFile) {
                this.pendingFile = false;
                this.preview = this.original;
                if (this.$refs.removeBox) this.$refs.removeBox.checked = false;
                return;
            }

            // حذف صورة محفوظة
            this.preview = null;
            this.pendingFile = false;
            if (this.$refs.removeBox) this.$refs.removeBox.checked = !!this.original;
        },
        onRemoveToggle(event) {
            if (event.target.checked) {
                this.revoke();
                this.preview = null;
                this.pendingFile = false;
                if (this.$refs.fileInput) this.$refs.fileInput.value = '';
            } else {
                this.preview = this.original;
                this.pendingFile = false;
            }
        }
     }">
    <div class="flex flex-wrap items-center gap-4">
        <div class="shrink-0 relative">
            <img x-show="preview" x-cloak :src="preview" alt="معاينة الصورة"
                 class="h-20 w-20 rounded-full object-cover border border-teal-200 bg-white shadow-sm">
            <span x-show="!preview" class="nursery-person-avatar h-20 w-20 text-xl" aria-hidden="true">{{ $initial }}</span>
            <button type="button"
                    x-show="preview"
                    x-cloak
                    @click="clearPreview()"
                    class="absolute -top-1 -start-1 inline-flex h-7 w-7 items-center justify-center rounded-full border border-teal-200 bg-white text-teal-800 shadow-sm hover:bg-red-50 hover:text-red-700 hover:border-red-200 focus:outline-none focus:ring-2 focus:ring-teal-400"
                    title="إلغاء الصورة"
                    aria-label="إلغاء الصورة">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            {{-- يُرسل مع الحفظ عند إلغاء صورة محفوظة --}}
            <input type="checkbox"
                   name="{{ $removeName }}"
                   x-ref="removeBox"
                   value="1"
                   class="sr-only"
                   tabindex="-1"
                   aria-hidden="true">
        </div>
        <div class="min-w-0 flex-1 space-y-2">
            <label class="block text-sm font-semibold text-teal-950">
                صورة الأفاتار
                @if($infoField)
                    <x-info :field="$infoField" />
                @endif
            </label>
            <p class="text-xs text-teal-800/70">JPG أو PNG أو WebP — تظهر في القوائم والجداول بجانب الاسم. اضغط ✕ على الصورة لإلغائها.</p>
            <input type="file"
                   name="{{ $inputName }}"
                   x-ref="fileInput"
                   accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                   class="block w-full max-w-md text-sm text-teal-900 file:me-3 file:rounded-lg file:border-0 file:bg-teal-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-teal-700"
                   @change="onPick($event)">
            @error($inputName)
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="text-xs text-teal-700/80" x-show="preview && pendingFile" x-cloak>تم اختيار صورة جديدة — احفظ النموذج لتأكيدها، أو اضغط ✕ للإلغاء.</p>
            <p class="text-xs text-amber-800" x-show="!preview && original" x-cloak>سيتم حذف الصورة الحالية عند الحفظ.</p>
        </div>
    </div>
</div>
