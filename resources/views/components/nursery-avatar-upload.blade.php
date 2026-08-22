@props([
    'name' => '',
    'src' => null,
    'inputName' => 'avatar',
    'infoField' => null,
    'removeName' => 'remove_avatar',
])

@php
    $hasPhoto = filled($src);
    $initial = trim((string) $name) !== '' ? mb_strtoupper(mb_substr(trim((string) $name), 0, 1)) : '؟';
@endphp

<div class="nursery-avatar-upload rounded-2xl border border-orange-100 bg-orange-50/30 p-4"
     x-data="{
        preview: @js($src),
        revoke() {
            if (this.preview && String(this.preview).startsWith('blob:')) {
                URL.revokeObjectURL(this.preview);
            }
        },
        onPick(event) {
            const file = event.target.files && event.target.files[0];
            this.revoke();
            this.preview = file ? URL.createObjectURL(file) : @js($src);
            const remove = this.$refs.removeBox;
            if (remove && file) remove.checked = false;
        },
        onRemoveToggle(event) {
            if (event.target.checked) {
                this.revoke();
                this.preview = null;
                if (this.$refs.fileInput) this.$refs.fileInput.value = '';
            } else {
                this.preview = @js($src);
            }
        }
     }">
    <div class="flex flex-wrap items-center gap-4">
        <div class="shrink-0">
            <img x-show="preview" x-cloak :src="preview" alt="معاينة الصورة"
                 class="h-20 w-20 rounded-full object-cover border border-orange-200 bg-white shadow-sm">
            <span x-show="!preview" class="nursery-person-avatar h-20 w-20 text-xl" aria-hidden="true">{{ $initial }}</span>
        </div>
        <div class="min-w-0 flex-1 space-y-2">
            <label class="block text-sm font-semibold text-orange-950">
                صورة الأفاتار
                @if($infoField)
                    <x-info :field="$infoField" />
                @endif
            </label>
            <p class="text-xs text-orange-800/70">JPG أو PNG أو WebP — تظهر في القوائم والجداول بجانب الاسم.</p>
            <input type="file"
                   name="{{ $inputName }}"
                   x-ref="fileInput"
                   accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                   class="block w-full max-w-md text-sm text-orange-900 file:me-3 file:rounded-lg file:border-0 file:bg-orange-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-orange-700"
                   @change="onPick($event)">
            @error($inputName)
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            @if($hasPhoto)
                <label class="inline-flex items-center gap-2 text-sm text-orange-900 cursor-pointer">
                    <input type="checkbox"
                           name="{{ $removeName }}"
                           x-ref="removeBox"
                           value="1"
                           class="rounded border-orange-300 text-orange-600"
                           @change="onRemoveToggle($event)">
                    <span>إزالة الصورة الحالية</span>
                </label>
            @endif
        </div>
    </div>
</div>
