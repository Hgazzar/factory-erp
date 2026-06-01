@extends('layouts.app')

@section('title', 'سجل التدقيق - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-indigo-900 font-semibold">سجل التدقيق</span>
@endsection

@push('styles')
<style>
    .audit-shell { background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%); min-height: calc(100vh - 4rem); }
    .audit-card { background: #fff; border: 1px solid rgba(15,23,42,.06); border-radius: 1.25rem; box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 12px 40px -20px rgba(15,23,42,.12); }
    .audit-tab { border-radius: .75rem; padding: .55rem 1rem; font-size: .875rem; font-weight: 600; color: #64748b; background: transparent; border: none; transition: all .2s; }
    .audit-tab.active { background: #0f172a; color: #fff; box-shadow: 0 8px 24px -12px rgba(15,23,42,.45); }
    .audit-pill { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .65rem; border-radius: 9999px; font-size: .75rem; font-weight: 600; }
    .audit-pill-create { background: #ecfdf5; color: #047857; }
    .audit-pill-update { background: #eff6ff; color: #1d4ed8; }
    .audit-pill-delete { background: #fef2f2; color: #b91c1c; }
    .audit-pill-complete { background: #f5f3ff; color: #6d28d9; }
    .audit-pill-module { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
    .audit-table th { font-size: .75rem; text-transform: none; letter-spacing: 0; color: #64748b; font-weight: 600; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
    .audit-table td { vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: .875rem; }
    .audit-table tbody tr:hover { background: #fafbfc; }
</style>
@endpush

@section('content')
@php
    $userOptions = collect($users)->map(fn ($u) => ['value' => $u->id, 'label' => trim($u->name.' — '.$u->email)])->values()->all();
    $moduleOptions = ($source === 'control'
        ? collect($controlModules)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
        : collect($trailModules)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
    )->values()->all();
    $actionOptions = ($source === 'control'
        ? collect($controlActions)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
        : collect($trailActions)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
    )->values()->all();
@endphp
<div dir="rtl" class="audit-shell -mx-3 px-3 py-4 md:-mx-4 md:px-4">
    <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">سجل التدقيق</h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-500">
                تتبع تغييرات البيانات وأحداث التحكم المالي — مع فلاتر حسب المستخدم، الموديول، نوع العملية، والفترة.
            </p>
        </div>
        <a href="{{ route('admin.dashboard') }}"
           class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
            لوحة الأدمن
        </a>
    </header>

    <div class="audit-card mb-5 p-4 md:p-5">
        <div class="mb-4 flex flex-wrap gap-2">
            <a href="{{ route('system.audit.index', array_merge(request()->except('page'), ['source' => 'changes'])) }}"
               class="audit-tab {{ $source === 'changes' ? 'active' : '' }}">
                تغييرات البيانات
            </a>
            <a href="{{ route('system.audit.index', array_merge(request()->except('page'), ['source' => 'control'])) }}"
               class="audit-tab {{ $source === 'control' ? 'active' : '' }}">
                أحداث التحكم (audit_logs)
            </a>
        </div>

        <form method="GET" action="{{ route('system.audit.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
            <input type="hidden" name="source" value="{{ $source }}">
            <div>
                <label class="mb-1 flex items-center gap-1 text-xs font-medium text-slate-600">
                    <x-info field="audit.filter_user" /> المستخدم
                </label>
                <x-searchable-select name="user_id" :options="$userOptions" :value="$filters['user_id'] ?? ''" empty-label="جميع المستخدمين" placeholder="ابحث عن مستخدم..." />
            </div>
            <div>
                <label class="mb-1 flex items-center gap-1 text-xs font-medium text-slate-600">
                    <x-info field="audit.filter_module" /> الموديول
                </label>
                <x-searchable-select name="module" :options="$moduleOptions" :value="$filters['module'] ?? ''" empty-label="جميع الموديولات" placeholder="اختر الموديول..." />
            </div>
            <div>
                <label class="mb-1 flex items-center gap-1 text-xs font-medium text-slate-600">
                    <x-info field="audit.filter_action" /> نوع العملية
                </label>
                <x-searchable-select name="action" :options="$actionOptions" :value="$filters['action'] ?? ''" empty-label="جميع العمليات" placeholder="اختر العملية..." />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600"><x-info field="audit.filter_date_from" /> من تاريخ</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                       class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm focus:border-slate-400 focus:ring-0">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600"><x-info field="audit.filter_date_to" /> إلى تاريخ</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                       class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm focus:border-slate-400 focus:ring-0">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="h-10 flex-1 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">تطبيق</button>
                <a href="{{ route('system.audit.index', ['source' => $source]) }}"
                   class="inline-flex h-10 items-center rounded-xl border border-slate-200 px-3 text-sm text-slate-600 hover:bg-slate-50">مسح</a>
            </div>
        </form>
    </div>

    <div class="audit-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="audit-table w-full min-w-[920px]">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-right">التاريخ</th>
                        <th class="px-4 py-3 text-right">المستخدم</th>
                        <th class="px-4 py-3 text-right">الموديول</th>
                        <th class="px-4 py-3 text-right">العملية</th>
                        <th class="px-4 py-3 text-right">السجل</th>
                        <th class="px-4 py-3 text-right">التفاصيل</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @if($source === 'control')
                            @php
                                $moduleKey = \App\Support\AuditModuleCatalog::controlModuleForAction((string) $log->action);
                                $moduleLabel = \App\Support\AuditModuleCatalog::controlModuleLabel($moduleKey);
                                $actionLabel = \App\Support\AuditModuleCatalog::controlActionLabel((string) $log->action);
                            @endphp
                            <tr>
                                <td class="px-4 py-3 tabular-nums text-slate-700">{{ $log->logged_at?->format('Y-m-d H:i') ?? $log->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $log->actor?->name ?? '—' }}</div>
                                    <div class="text-xs text-slate-500">{{ $log->actor?->email }}</div>
                                </td>
                                <td class="px-4 py-3"><span class="audit-pill audit-pill-module">{{ $moduleLabel }}</span></td>
                                <td class="px-4 py-3"><span class="audit-pill audit-pill-update">{{ $actionLabel }}</span></td>
                                <td class="px-4 py-3 text-slate-600">
                                    @if($log->targetUser && (int) $log->target_user_id !== (int) $log->actor_id)
                                        مستهدف: {{ $log->targetUser->name }}
                                    @elseif($log->subject_id)
                                        #{{ $log->subject_id }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if(is_array($log->meta) && count($log->meta) > 0)
                                        <details class="text-xs">
                                            <summary class="cursor-pointer text-indigo-600">عرض البيانات</summary>
                                            <pre class="mt-2 max-w-xs overflow-auto rounded-lg bg-slate-50 p-2 text-[11px] text-slate-600" dir="ltr">{{ json_encode($log->meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                        </details>
                                    @elseif($log->old_role || $log->new_role)
                                        <span class="text-xs text-slate-600">{{ $log->old_role ?? '—' }} → {{ $log->new_role ?? '—' }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @else
                            @php
                                $actionClass = match($log->action) {
                                    'create' => 'audit-pill-create',
                                    'delete' => 'audit-pill-delete',
                                    'complete' => 'audit-pill-complete',
                                    default => 'audit-pill-update',
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-3 tabular-nums text-slate-700">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $log->user?->name ?? '—' }}</div>
                                    <div class="text-xs text-slate-500">{{ $log->user?->email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="audit-pill audit-pill-module">{{ \App\Support\AuditModuleCatalog::trailModuleLabel((string) $log->table_name) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="audit-pill {{ $actionClass }}">{{ \App\Support\AuditModuleCatalog::trailActionLabel((string) $log->action) }}</span>
                                </td>
                                <td class="px-4 py-3 tabular-nums text-slate-600">#{{ $log->record_id ?? '—' }}</td>
                                <td class="px-4 py-3"><x-audit-activity-details :trail="$log" /></td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-slate-500">لا توجد سجلات مطابقة للفلاتر المحددة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
