@extends('layouts.clinic-portal')

@section('title', 'إدارة الموعد — '.$clinicName)

@section('content')
<div dir="rtl">
    <p class="text-center text-muted small mb-3">إدارة الموعد (إلغاء أو إعادة جدولة)</p>

    <div class="portal-card">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <h2 class="h5 fw-bold mb-3">تفاصيل الموعد</h2>
        <div class="mb-3 text-sm">
            <div><strong>رقم الحجز:</strong> {{ $appointment->appointment_number }}</div>
            <div><strong>المريض:</strong> {{ $appointment->patient?->name }}</div>
            <div><strong>الطبيب:</strong> {{ $appointment->doctor?->name ?? '—' }}</div>
            <div><strong>التاريخ:</strong> {{ $appointment->appointment_date?->format('Y-m-d') }}</div>
            <div><strong>الوقت:</strong> {{ substr((string) $appointment->start_time, 0, 5) }}</div>
            <div><strong>الحالة:</strong> {{ \App\Models\Clinic\Appointment::statusLabels()[$appointment->status] ?? $appointment->status }}</div>
        </div>

        @if(! $canManage)
            <div class="alert alert-warning mb-0">تعديل أو إلغاء الموعد غير متاح حالياً (مدفوع/ملغى أو داخل فترة المنع).</div>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                <form method="POST" action="{{ route('clinic.portal.manage.cancel', ['tenant_slug' => $tenantSlug, 'token' => $token]) }}" class="border rounded-lg p-3 bg-white">
                    @csrf
                    <h3 class="font-semibold mb-2 text-red-700">إلغاء الموعد</h3>
                    <p class="text-sm text-gray-600 mb-3">سيتم إلغاء الموعد الحالي فوراً.</p>
                    <button type="submit" class="btn btn-danger">إلغاء الموعد</button>
                </form>

                <form method="POST" action="{{ route('clinic.portal.manage.reschedule', ['tenant_slug' => $tenantSlug, 'token' => $token]) }}" class="border rounded-lg p-3 bg-white">
                    @csrf
                    <h3 class="font-semibold mb-2 text-teal-800">إعادة الجدولة</h3>
                    <div class="mb-2">
                        <label class="form-label">تاريخ جديد</label>
                        <input type="date" name="appointment_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وقت جديد</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-portal-primary">حفظ الموعد الجديد</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
