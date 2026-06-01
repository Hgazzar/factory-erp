@extends('layouts.app')

@section('title', 'قوائم الأسعار - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">قوائم الأسعار</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm4.5 0a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1h-3zM8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">قوائم الأسعار</h1>
                <p class="mt-1 text-sm text-gray-500">إدارة قوائم أسعار البيع والشراء.</p>
            </div>
        </div>
        <a href="{{ route('inventory.price-lists.create') }}" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            + قائمة أسعار جديدة
        </a>
    </header>

    <form method="GET" action="{{ route('inventory.price-lists.index') }}" class="flex flex-wrap items-center gap-3">
        <input type="text" name="search" value="{{ request('search') }}" class="h-10 w-full max-w-[220px] rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" placeholder="بحث بالرمز أو الاسم...">
        <select name="type" class="h-10 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            <option value="">— النوع —</option>
            @foreach($types as $k => $v)
                <option value="{{ $k }}" {{ request('type') === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
        <select name="status" class="h-10 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            <option value="">— الحالة —</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشط</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>غير نشط</option>
        </select>
        <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">بحث</button>
    </form>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-700">
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.pricelist_code" /> الرمز</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.pricelist_name" /> الاسم</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.pricelist_type" /> النوع</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.pricelist_currency" /> العملة</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.pricelist_items_count" /> عدد الأصناف</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.pricelist_validity" /> الصلاحية</th>
                        <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.pricelist_status" /> الحالة</th>
                        <th scope="col" class="w-[1%] whitespace-nowrap border-b border-gray-200 px-3 py-3 text-center font-semibold"><span class="inline-flex items-center justify-center gap-1"><x-info field="inventory.pricelist_actions" /> إجراءات</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($priceLists as $pl)
                    <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/60">
                        <td class="px-3 py-3 font-semibold text-gray-900">{{ $pl->code }}</td>
                        <td class="px-3 py-3 text-gray-800">{{ $pl->name }}</td>
                        <td class="px-3 py-3 text-gray-800">{{ $types[$pl->type] ?? $pl->type }}</td>
                        <td class="px-3 py-3 text-gray-800">{{ $pl->currency }}</td>
                        <td class="px-3 py-3 tabular-nums text-gray-800">{{ $pl->items_count }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-gray-700">
                            @if($pl->valid_from || $pl->valid_to)
                                {{ $pl->valid_from?->format('Y-m-d') ?? '—' }} / {{ $pl->valid_to?->format('Y-m-d') ?? '—' }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            @if($pl->is_active)
                                <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-800">نشط</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">غير نشط</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-center align-middle">
                            @php $plMenuId = 'pricelist-actions-'.$pl->id; @endphp
                            <x-erp-actions-dropdown :menu-id="$plMenuId">
                                <a href="{{ route('inventory.price-lists.edit', $pl) }}"
                                   class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                   role="menuitem">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                                    </span>
                                    <span class="flex-1 text-right font-medium leading-snug">تعديل القائمة</span>
                                </a>
                                <div class="mx-2 my-2 border-t border-gray-100"></div>
                                <form action="{{ route('inventory.price-lists.duplicate', $pl) }}" method="POST" class="m-0" onsubmit="return confirm('تكرار هذه القائمة؟');">
                                    @csrf
                                    <button type="submit"
                                            class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-gray-50"
                                            role="menuitem">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V2zm2-1a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H6zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1H2z"/></svg>
                                        </span>
                                        <span class="flex-1 leading-snug">تكرار القائمة</span>
                                    </button>
                                </form>
                            </x-erp-actions-dropdown>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-3 py-10 text-center text-gray-500">لا توجد قوائم أسعار</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($priceLists->hasPages())
    <div class="flex justify-center pt-2">
        {{ $priceLists->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
