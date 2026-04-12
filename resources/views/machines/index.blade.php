@extends('layouts.app')

@section('title', 'الماكينات - Factory ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">الماكينات</h1>
        <p class="text-muted mb-0 small">تعريف وتجميع الماكينات داخل خطوط الإنتاج</p>
    </div>
    <a href="{{ route('machines.create') }}" class="btn btn-primary">
        إضافة ماكينة
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الكود</th>
                        <th>اسم الماكينة</th>
                        <th>خط الإنتاج</th>
                        <th>الحالة</th>
                        <th style="width: 150px;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($machines as $machine)
                        <tr>
                            <td>
                                <span class="badge bg-secondary">{{ $machine->code }}</span>
                            </td>
                            <td>{{ $machine->name_ar }}</td>
                            <td>
                                @if($machine->productionLine)
                                    {{ $machine->productionLine->name_ar }} ({{ $machine->productionLine->code }})
                                @else
                                    <span class="text-muted small">غير مرتبط بخط</span>
                                @endif
                            </td>
                            <td>
                                @if($machine->status === 'active')
                                    <span class="badge bg-success">نشطة</span>
                                @elseif($machine->status === 'maintenance')
                                    <span class="badge bg-warning text-dark">صيانة</span>
                                @else
                                    <span class="badge bg-secondary">متوقفة</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('machines.edit', $machine) }}" class="btn btn-sm btn-outline-primary">
                                        تعديل
                                    </a>
                                    <form action="{{ route('machines.destroy', $machine) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('حذف هذه الماكينة؟');">
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
                            <td colspan="5" class="text-center text-muted py-4">
                                لا توجد ماكينات مسجلة. <a href="{{ route('machines.create') }}">أضف ماكينة جديدة</a>
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
            <h5 class="mb-0">إدارة الماكينات</h5>
            <a href="{{ route('machines.create') }}" class="btn btn-primary btn-sm">إضافة ماكينة جديدة</a>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <table class="table table-hover text-center">
                <thead class="table-dark">
                    <tr>
                        <th>الكود</th>
                        <th>اسم الماكينة</th>
                        <th>خط الإنتاج</th> <th>الحالة</th>
                        <th>تاريخ الإضافة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($machines as $machine)
                    <tr>
                        <td><span class="badge bg-secondary">{{ $machine->code }}</span></td>
                        <td>{{ $machine->name_ar }}</td>
                        
                        <td class="fw-bold text-primary">
                            {{ $machine->productionLine?->name_ar ?? 'غير مرتبطة بخط' }}
                        </td>

                        <td>
                            @if($machine->status == 'active')
                                <span class="badge bg-success">تعمل</span>
                            @elseif($machine->status == 'maintenance')
                                <span class="badge bg-warning text-dark">صيانة</span>
                            @else
                                <span class="badge bg-danger">متوقفة</span>
                            @endif
                        </td>
                        <td>{{ $machine->created_at->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('machines.edit', $machine->id) }}" class="btn btn-sm btn-info text-white">تعديل</a>
                            <form action="{{ route('machines.destroy', $machine->id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف الماكينة؟')">حذف</button>
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