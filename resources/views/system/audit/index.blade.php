@extends('layouts.app')

@section('title', 'سجل العمليات (Audit Log) - Factory ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">سجل العمليات (تغييرات الأدوار)</h1>
        <p class="text-muted mb-0 small">يعرض من قام بتغيير صلاحيات المستخدمين، ومتى، وما هو الدور السابق والجديد.</p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 180px;">التاريخ/الوقت</th>
                        <th>المستخدم (Actor)</th>
                        <th>المستهدف</th>
                        <th style="width: 140px;">الدور السابق</th>
                        <th style="width: 140px;">الدور الجديد</th>
                        <th>بيانات إضافية</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->logged_at?->format('Y-m-d H:i') ?? $log->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                {{ $log->actor?->name ?? '-' }}
                                <div class="small text-muted">{{ $log->actor?->email }}</div>
                            </td>
                            <td>
                                {{ $log->targetUser?->name ?? '-' }}
                                <div class="small text-muted">{{ $log->targetUser?->email }}</div>
                            </td>
                            <td>{{ $log->old_role ?? '-' }}</td>
                            <td>{{ $log->new_role ?? '-' }}</td>
                            <td class="small text-break" style="max-width: 280px;">
                                <span class="text-muted">{{ $log->action }}</span>
                                @if(is_array($log->meta) && count($log->meta) > 0)
                                    <div class="mt-1 font-monospace" dir="ltr">{{ json_encode($log->meta, JSON_UNESCAPED_UNICODE) }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                لا توجد عمليات مسجلة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
        <div class="card-footer">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection

