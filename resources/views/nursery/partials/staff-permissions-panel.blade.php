@php
    /** @var array<string, array{label: string, permissions: array<string, string>}> $permissionGroups */
    /** @var list<string> $selectedPermissions */
    /** @var list<string> $grantableKeys */
    $selectedPermissions = $selectedPermissions ?? [];
    $grantableKeys = $grantableKeys ?? [];
    $canGrantAll = $canGrantAll ?? true;
@endphp

<section class="nursery-card p-0 overflow-hidden h-fit lg:sticky lg:top-4">
    <div class="px-5 py-4 border-b border-teal-100 bg-teal-50/80">
        <h2 class="text-lg font-bold text-teal-950">
            الصلاحيات
            <x-info field="nursery.staff_permissions" />
        </h2>
        <p class="text-xs text-teal-800/75 mt-1">يمكنك منح ما تملكه فقط من صلاحيات.</p>
        @unless($canGrantAll)
            <p class="text-xs text-amber-800 mt-1 font-medium">بعض الخيارات مقفلة — خارج صلاحيات حسابك.</p>
        @endunless
    </div>

    <div class="max-h-[min(70vh,42rem)] overflow-y-auto p-3 space-y-4">
        @foreach($permissionGroups as $groupKey => $group)
            <div class="rounded-lg border border-teal-200 overflow-hidden">
                <div class="px-3 py-2 bg-teal-100/70 border-b border-teal-200">
                    <span class="text-sm font-bold text-teal-950">{{ $group['label'] }}</span>
                </div>
                <div>
                    @foreach($group['permissions'] as $permKey => $permLabel)
                        @php
                            $checked = in_array($permKey, $selectedPermissions, true);
                            $canGrant = in_array($permKey, $grantableKeys, true);
                        @endphp
                        <label class="nursery-perm-row flex items-center justify-between gap-3 {{ $loop->iteration % 2 === 0 ? 'nursery-perm-row--stripe' : '' }} {{ $canGrant ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}">
                            <span class="text-sm text-teal-950 flex-1">{{ $permLabel }}</span>
                            <span class="nursery-switch shrink-0">
                                <input type="checkbox"
                                       name="permissions[]"
                                       value="{{ $permKey }}"
                                       class="nursery-switch-input"
                                       @checked($checked)
                                       @disabled(! $canGrant)>
                                <span class="nursery-switch-track" aria-hidden="true"></span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>
