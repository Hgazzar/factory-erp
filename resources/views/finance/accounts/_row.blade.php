@php
    /** @var \App\Models\Account $account */
    /** @var int $level */
    $pad = $level * 20;

    $typeLabel = match($account->type) {
        \App\Models\Account::TYPE_ASSET => 'أصل',
        \App\Models\Account::TYPE_LIABILITY => 'خصم',
        \App\Models\Account::TYPE_EQUITY => 'حقوق ملكية',
        \App\Models\Account::TYPE_EXPENSE => 'مصروف',
        \App\Models\Account::TYPE_REVENUE => 'إيراد',
        default => $account->type ?? '—',
    };

    $balance = (float) (data_get($balancesByAccount ?? [], $account->id, $account->current_balance ?? $account->opening_balance));
    $debit = $balance > 0 ? $balance : 0;
    $credit = $balance < 0 ? abs($balance) : 0;

    $journalLineSet = $journalLineSet ?? [];
    $hasJournalLines = isset($journalLineSet[$account->id]);
    $hasChildren = $account->relationLoaded('childrenRecursive')
        ? $account->childrenRecursive->isNotEmpty()
        : ($account->relationLoaded('children')
            ? $account->children->isNotEmpty()
            : $account->children()->exists());
    $canLedgerDeleteEmpty = \App\Support\ErpRoles::canDeleteExpenseDraft(auth()->user());
    $canSuperPurge = \App\Support\ErpRoles::isSuperAdmin(auth()->user());
    // حذف عادي: ورقة بلا قيود وبلا فروع — المسار الخادمي يرفض غير ذلك
    $showDeleteNormal = $canLedgerDeleteEmpty && ! $hasJournalLines && ! $hasChildren;
    // تطهير (سوبر أدمن): أي حالة لا يُقبل فيها الحذف العادي (قيود، فروع، أو أدمن ليس له حذف نهائي)
    $showPurgeAccount = $canSuperPurge && ! $showDeleteNormal;
@endphp

<tr class="border-b border-gray-100 hover:bg-gray-50/80">
    <td class="px-3 py-3 text-right font-medium text-gray-900">{{ $account->code }}</td>
    <td class="px-3 py-3 text-right" dir="rtl">
        <div class="flex items-start gap-2">
            <button type="button"
                    class="account-copy-btn mt-0.5 shrink-0 rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-blue-600"
                    data-copy-text="{{ $account->name_ar ?: ($account->name_en ?: $account->code) }}"
                    title="نسخ اسم الحساب"
                    aria-label="نسخ اسم الحساب">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
            </button>
            <div class="min-w-0 flex-1 text-right">
                <p class="font-bold text-gray-900">{{ $account->name_ar ?? '—' }}</p>
                @if($account->name_en)
                    <p class="mt-0.5 text-xs font-normal text-gray-500 text-right" dir="ltr">{{ $account->name_en }}</p>
                @endif
            </div>
        </div>
    </td>
    <td class="px-3 py-3 text-right text-sm text-gray-700">{{ $typeLabel }}</td>
    <td class="px-3 py-3 text-left text-sm font-normal" style="color: #059669;">SAR {{ erp_money($debit) }}</td>
    <td class="px-3 py-3 text-left text-sm font-normal" style="color: #dc2626;">SAR {{ erp_money($credit) }}</td>
    <td class="px-3 py-3 text-left text-sm font-bold text-gray-700">SAR {{ erp_money($balance) }}</td>
    <td class="px-3 py-3">
        <form action="{{ route('finance.accounts.toggle-active', $account) }}" method="POST">
            @csrf
            @method('PATCH')
            <button type="submit" class="flex items-center justify-end gap-2 w-full" title="{{ $account->is_active ? 'إيقاف' : 'تفعيل' }}">
                <span class="text-xs {{ $account->is_active ? 'text-green-700 font-medium' : 'text-gray-400' }}">
                    {{ $account->is_active ? 'نشط' : 'غير نشط' }}
                </span>
                <span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors {{ $account->is_active ? 'bg-green-500' : 'bg-gray-300' }}">
                    <span class="absolute h-4 w-4 rounded-full bg-white shadow-sm transition-transform {{ $account->is_active ? 'right-[2px]' : 'left-[2px]' }}"></span>
                </span>
            </button>
        </form>
    </td>
    <td class="px-3 py-3 text-center align-middle">
        <div class="relative inline-flex items-center justify-center">
            <button type="button"
                    class="erp-actions-trigger inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm transition hover:bg-gray-50"
                    data-actions-menu="account-actions-{{ $account->id }}"
                    aria-haspopup="menu"
                    aria-expanded="false"
                    title="المزيد من الإجراءات"
                    aria-label="المزيد من الإجراءات">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                </svg>
            </button>
            <div id="account-actions-{{ $account->id }}"
                 class="erp-actions-menu hidden min-w-[13rem] rounded-xl border border-gray-200/90 bg-white py-2 shadow-2xl ring-1 ring-black/5"
                 style="list-style: none;"
                 role="menu"
                 dir="rtl">
                <button type="button"
                        class="erp-menu-item coa-action-edit flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm text-gray-800 transition hover:bg-gray-50"
                        role="menuitem"
                        data-coa-id="{{ $account->id }}"
                        data-coa-code="{{ e($account->code) }}"
                        data-coa-name="{{ e($account->name_ar ?? '') }}"
                        data-coa-current-balance="SAR {{ erp_money($balance) }}"
                        data-update-url="{{ route('finance.accounts.update', $account) }}"
                        onclick="if(window.__coaQuickEdit){window.__coaQuickEdit(this);} return false;">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                    </span>
                    <span class="flex-1 text-right font-medium leading-snug">تعديل الحساب</span>
                </button>
                @if($showDeleteNormal)
                    <div class="flex w-full min-w-0 items-stretch" role="menuitem">
                        <button type="button"
                                class="erp-menu-item coa-action-delete flex min-w-0 flex-1 items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                data-coa-id="{{ $account->id }}"
                                data-coa-code="{{ e($account->code) }}"
                                data-coa-name="{{ e($account->name_ar ?? '') }}"
                                data-delete-url="{{ route('finance.accounts.destroy', $account) }}"
                                onclick="if(window.__coaQuickDelete){window.__coaQuickDelete(this);} return false;">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                            </span>
                            <span class="min-w-0 flex-1 leading-snug">حذف الحساب</span>
                        </button>
                        <div class="flex shrink-0 items-center ps-1 pe-2">
                            <x-info field="finance_account_action_delete" />
                        </div>
                    </div>
                @endif
                @if($showPurgeAccount)
                    <div class="flex w-full min-w-0 items-stretch" role="menuitem">
                        <button type="button"
                                class="erp-menu-item coa-action-purge flex min-w-0 flex-1 items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-amber-900 transition hover:bg-amber-50"
                                data-coa-id="{{ $account->id }}"
                                data-coa-code="{{ e($account->code) }}"
                                data-coa-name="{{ e($account->name_ar ?? '') }}"
                                data-purge-url="{{ route('finance.accounts.purge', $account) }}"
                                onclick="if(window.__coaQuickPurge){window.__coaQuickPurge(this);} return false;">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-800">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg>
                            </span>
                            <span class="min-w-0 flex-1 leading-snug">تطهير الحساب</span>
                        </button>
                        <div class="flex shrink-0 items-center ps-1 pe-2">
                            <x-info field="finance_account_action_purge" />
                        </div>
                    </div>
                @endif
                <div class="mx-2 my-2 border-t border-gray-100"></div>
                <button type="button"
                        class="erp-menu-item account-copy-btn flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm text-gray-800 transition hover:bg-gray-50"
                        role="menuitem"
                        data-copy-text="{{ $account->code }}">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H6zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1H2z"/></svg>
                    </span>
                    <span class="flex-1 text-right font-medium leading-snug">نسخ الكود</span>
                </button>
                <button type="button"
                        class="erp-menu-item account-copy-btn flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm text-gray-800 transition hover:bg-gray-50"
                        role="menuitem"
                        data-copy-text="{{ $account->name_ar ?: ($account->name_en ?: $account->code) }}">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </span>
                    <span class="flex-1 text-right font-medium leading-snug">نسخ الاسم</span>
                </button>
            </div>
        </div>
    </td>
</tr>

@foreach($account->childrenRecursive as $child)
    @include('finance.accounts._row', ['account' => $child, 'level' => $level + 1, 'journalLineSet' => $journalLineSet])
@endforeach
