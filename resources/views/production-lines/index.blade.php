@extends('layouts.app')

@section('title', 'خطوط الإنتاج - Factory ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">خطوط الإنتاج</h1>
        <p class="text-muted mb-0 small">تعريف الخطوط الرئيسية التي تعمل عليها الماكينات</p>
    </div>
    <a href="{{ route('production-lines.create') }}" class="btn btn-primary">
        إضافة خط إنتاج
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الكود</th>
                        <th>اسم الخط</th>
                        <th>الاسم بالإنجليزي</th>
                        <th style="width: 150px;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lines as $line)
                        <tr>
                            <td><span class="badge bg-secondary">{{ $line->code }}</span></td>
                            <td>{{ $line->name_ar }}</td>
                            <td class="text-muted">{{ $line->name_en ?? '-' }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('production-lines.edit', $line) }}" class="btn btn-sm btn-outline-primary">
                                        تعديل
                                    </a>
                                    <form action="{{ route('production-lines.destroy', $line) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('حذف هذا الخط؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                لا توجد خطوط إنتاج. <a href="{{ route('production-lines.create') }}">أضف أول خط</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">إدارة خطوط الإنتاج</h5>
            <a href="{{ route('production-lines.create') }}" class="btn btn-primary btn-sm">إضافة خط جديد</a>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <table class="table table-hover text-center">
                <thead class="table-dark">
                    <tr>
                        <th>الكود</th>
                        <th>الاسم (عربي)</th>
                        <th>الحالة</th>
                        <th>تاريخ الإنشاء</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lines as $line)
                    <tr>
                        <td><span class="badge bg-secondary">{{ $line->code }}</span></td>
                        <td>{{ $line->name_ar }}</td>
                        <td>
                            @if($line->is_active)
                                <span class="badge bg-success">نشط</span>
                            @else
                                <span class="badge bg-danger">متوقف</span>
                            @endif
                        </td>
                        <td>{{ $line->created_at->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('production-lines.edit', $line->id) }}" class="btn btn-sm btn-info text-white">تعديل</a>
                            <form action="{{ route('production-lines.destroy', $line->id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('حذف الخط؟')">حذف</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection