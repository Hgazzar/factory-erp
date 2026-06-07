@extends('layouts.nursery')

@section('title', $child->name)

@section('content')
<div class="w-full space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-orange-950">{{ $child->name }}</h1>
            <p class="text-sm text-orange-800/80">كود: {{ $child->code }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($canEdit)
                <a href="{{ route('nursery.children.edit', $child) }}" class="nursery-btn nursery-btn-primary">تعديل البيانات</a>
                @if($portalInviteUrl)
                    <form method="post" action="{{ route('nursery.children.portal-invite', $child) }}" class="inline">
                        @csrf
                        <button type="submit" class="nursery-btn nursery-btn-soft">إرسال دعوة البوابة</button>
                    </form>
                @endif
            @endif
            <a href="{{ route('nursery.children.index') }}" class="nursery-btn nursery-btn-soft">← قائمة الأطفال</a>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="nursery-card p-4">
            <h2 class="font-bold text-orange-950 mb-3">المعلومات الأساسية</h2>
            <dl class="grid gap-2 sm:grid-cols-2 text-sm">
                <div><dt class="text-orange-800/70">الجنس</dt><dd class="font-medium">{{ $child->gender === 'male' ? 'ذكر' : ($child->gender === 'female' ? 'أنثى' : '—') }}</dd></div>
                <div><dt class="text-orange-800/70">تاريخ الميلاد</dt><dd class="font-medium">{{ $child->date_of_birth?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt class="text-orange-800/70">الفصل</dt><dd class="font-medium">{{ $child->activeEnrollment?->classroom?->name ?? 'غير معين لفصل' }}</dd></div>
                <div><dt class="text-orange-800/70">حالة الحساب</dt><dd class="font-medium">{{ $child->status === 'active' ? 'نشط' : 'مؤرشف' }}</dd></div>
            </dl>
        </section>

        <section class="nursery-card p-4">
            <h2 class="font-bold text-orange-950 mb-3">المعلومات الصحية</h2>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-orange-800/70">الحساسية</dt><dd class="font-medium">{{ $child->allergies ?: '—' }}</dd></div>
                <div><dt class="text-orange-800/70">الأمراض</dt><dd class="font-medium">{{ $child->diseases ?: '—' }}</dd></div>
                <div><dt class="text-orange-800/70">ملاحظات</dt><dd class="font-medium">{{ $child->health_notes ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="nursery-card p-4 lg:col-span-2">
            <h2 class="font-bold text-orange-950 mb-3">
                الأدوية
                <x-info field="nursery.child_medications_intro" />
            </h2>
            @include('nursery.partials.medications-readonly-table', ['medications' => $child->medications])
        </section>

        <section class="nursery-card p-4 lg:col-span-2">
            <h2 class="font-bold text-orange-950 mb-3">معلومات ولي الأمر</h2>
            <dl class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 text-sm">
                <div><dt class="text-orange-800/70">الاسم</dt><dd class="font-medium">{{ $child->guardian?->name }}</dd></div>
                <div><dt class="text-orange-800/70">العلاقة</dt><dd class="font-medium">{{ $relationshipLabels[$child->guardian_relationship] ?? '—' }}</dd></div>
                <div><dt class="text-orange-800/70">رقم الجوال</dt><dd class="font-medium" dir="ltr">{{ $child->guardian?->phone }}</dd></div>
                <div><dt class="text-orange-800/70">البريد</dt><dd class="font-medium" dir="ltr">{{ $child->guardian?->email ?? '—' }}</dd></div>
                <div><dt class="text-orange-800/70">رقم الهوية</dt><dd class="font-medium">{{ $child->guardian?->national_id ?? '—' }}</dd></div>
                <div><dt class="text-orange-800/70">المنطقة</dt><dd class="font-medium">{{ \App\Support\SaudiRegions::regionLabel($child->guardian?->region) ?? '—' }}</dd></div>
                <div><dt class="text-orange-800/70">المدينة</dt><dd class="font-medium">{{ $child->guardian?->city ?? '—' }}</dd></div>
                <div class="sm:col-span-2 lg:col-span-3"><dt class="text-orange-800/70">العنوان</dt><dd class="font-medium">{{ $child->guardian?->address ?? '—' }}</dd></div>
            </dl>
        </section>
    </div>

    <section class="nursery-card p-4">
        <h2 class="font-bold text-orange-950 mb-3">
            مستندات الطفل
            <x-info field="nursery.child_attachments" />
        </h2>
        <x-attachment-handler
            theme="tailwind"
            hint-field="nursery.child_attachments"
            title="المرفقات"
            :existing="$child->attachments"
            :uploadable="false"
            :allow-delete="$canEdit"
            help-text="معاينة وتحميل الملفات. الحذف والإضافة من شاشة التعديل."
        />
        @if($canEdit)
            <p class="text-sm text-orange-800/80 mt-3">
                <a href="{{ route('nursery.children.edit', $child) }}" class="text-orange-600 font-semibold underline">تعديل الطفل</a>
                لرفع مستندات جديدة.
            </p>
        @endif
    </section>

    <div class="flex flex-wrap gap-2">
        <form method="post" action="{{ route('nursery.attendance.check-in') }}">
            @csrf
            <input type="hidden" name="child_id" value="{{ $child->id }}">
            <button type="submit" class="nursery-btn nursery-btn-primary">تسجيل حضور</button>
        </form>
        <form method="post" action="{{ route('nursery.attendance.check-out') }}">
            @csrf
            <input type="hidden" name="child_id" value="{{ $child->id }}">
            <button type="submit" class="nursery-btn nursery-btn-soft">تسجيل انصراف</button>
        </form>
    </div>

    <section class="nursery-card p-4">
        <h2 class="font-bold text-orange-950 mb-3">سجل الحضور (آخر 14 يوم)</h2>
        <ul class="space-y-2 text-sm">
            @forelse($recentAttendance as $log)
                <li class="flex justify-between border-b border-orange-50 py-1">
                    <span>تاريخ: {{ $log->attendance_date->format('Y-m-d') }}</span>
                    <span>
                        @if($log->checked_in_at) حضور {{ $log->checked_in_at->format('H:i') }} @endif
                        @if($log->checked_out_at) · انصراف {{ $log->checked_out_at->format('H:i') }} @endif
                    </span>
                </li>
            @empty
                <li class="text-orange-700/60">لا يوجد سجل حضور.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
