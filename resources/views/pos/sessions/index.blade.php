@extends('layouts.pos')

@section('title', 'جلسات نقاط البيع - '.config('app.name'))

@section('content')
<div class="max-w-full bg-gray-50 min-h-[calc(100vh-8rem)] -mx-4 sm:-mx-6 px-4 sm:px-6 py-6 space-y-6" dir="rtl">
    <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center justify-between gap-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">جلسات نقاط البيع</h1>
            <p class="text-sm text-gray-500 mt-1">فتح جلسة جديدة ومتابعة الجلسات المفتوحة على أجهزتك.</p>
        </div>
        <a href="{{ route('pos.dashboard') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-gray-200 bg-white text-gray-800 text-sm font-semibold shadow-sm hover:bg-gray-50 transition shrink-0">
            لوحة نقاط البيع
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-900">فتح جلسة جديدة</h2>
            </div>
            <div class="p-5">
                <form method="POST" action="{{ route('pos.sessions.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2" for="pos_device_id">
                            <span class="inline-flex items-center gap-1">الجهاز <x-info field="pos.col_device" /></span>
                        </label>
                        <select id="pos_device_id" name="pos_device_id" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 @error('pos_device_id') border-red-400 @enderror" required>
                            <option value="">اختر الجهاز</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}" @selected((string) old('pos_device_id') === (string) $device->id)>{{ $device->name }}</option>
                            @endforeach
                        </select>
                        @error('pos_device_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2" for="employee_id">
                            <span class="inline-flex items-center gap-1">الكاشير <x-info field="pos.session_employee_label" /></span>
                        </label>
                        <select id="employee_id" name="employee_id" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 @error('employee_id') border-red-400 @enderror" required>
                            <option value="">اختر الموظف</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" @selected((string) old('employee_id') === (string) $employee->id)>
                                    {{ $employee->name }}{{ $employee->code ? ' ('.$employee->code.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2" for="opening_balance">
                            <span class="inline-flex items-center gap-1">الرصيد الافتتاحي <x-info field="pos.session_opening_balance_label" /></span>
                        </label>
                        <input type="number" step="0.01" min="0" id="opening_balance" name="opening_balance" value="{{ old('opening_balance', '0') }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 tabular-nums @error('opening_balance') border-red-400 @enderror">
                        @error('opening_balance')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold shadow-sm hover:bg-blue-700 transition disabled:opacity-50 disabled:pointer-events-none" @disabled($devices->isEmpty() || $employees->isEmpty())>
                        فتح الجلسة
                    </button>
                </form>

                @if($devices->isEmpty() || $employees->isEmpty())
                    <p class="text-sm text-red-600 mt-4 mb-0">يتطلب فتح الجلسة وجود جهاز نشط وموظف فعّال تابعين لحسابك.</p>
                @endif
            </div>
        </div>

        <div class="lg:col-span-3 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-900">الجلسات المفتوحة</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right min-w-[320px]">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-4 font-semibold whitespace-nowrap"><span class="inline-flex items-center gap-1">الجهاز <x-info field="pos.col_device" /></span></th>
                            <th class="py-3 px-4 font-semibold whitespace-nowrap"><span class="inline-flex items-center gap-1">الكاشير <x-info field="pos.session_employee_label" /></span></th>
                            <th class="py-3 px-4 font-semibold whitespace-nowrap"><span class="inline-flex items-center gap-1">الرصيد الافتتاحي <x-info field="pos.session_opening_balance_label" /></span></th>
                            <th class="py-3 px-4 font-semibold whitespace-nowrap"><span class="inline-flex items-center gap-1">الوقت <x-info field="pos.col_datetime" /></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($openSessions as $session)
                            <tr class="border-b border-gray-100 hover:bg-gray-50/80">
                                <td class="py-3 px-4 font-medium text-gray-900">{{ $session->posDevice?->name ?? '—' }}</td>
                                <td class="py-3 px-4 text-gray-700">{{ $session->employee?->name ?? '—' }}</td>
                                <td class="py-3 px-4 tabular-nums">{{ $erpCurrencyCode }} {{ number_format((float) $session->opening_balance, 2) }}</td>
                                <td class="py-3 px-4 text-gray-500 text-xs whitespace-nowrap">{{ $session->opened_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 px-4 text-center text-gray-500">لا توجد جلسات مفتوحة حالياً.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
