@extends('layouts.nursery')

@section('title', 'ملف ولي الأمر — '.$guardian->name)

@section('content')
<div class="w-full space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('nursery.guardians.index') }}" class="text-sm text-teal-600 font-semibold hover:underline">← أولياء الأمور</a>
            <h1 class="text-2xl font-extrabold text-teal-950 mt-1">{{ $guardian->name }}</h1>
            <p class="text-sm text-teal-800/80"><x-info field="nursery.guardians_profile_intro" /></p>
        </div>
        @if($canManage && $portalEnabled)
            <div class="flex flex-wrap gap-2">
                <form method="post" action="{{ route('nursery.guardians.portal-invite', $guardian) }}">
                    @csrf
                    <button type="submit" class="nursery-btn nursery-btn-primary text-sm">إعادة إرسال دعوة البوابة</button>
                </form>
                @if($portalActive)
                    <form method="post"
                          action="{{ route('nursery.guardians.revoke-portal', $guardian) }}"
                          onsubmit="return confirm('إلغاء وصول هذا ولي الأمر للبوابة؟');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="nursery-btn nursery-btn-soft text-sm text-red-700 border-red-200">إلغاء الوصول</button>
                    </form>
                @endif
            </div>
        @endif
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="nursery-card p-5 lg:col-span-1 space-y-3">
            <h2 class="text-base font-bold text-teal-950">بيانات التواصل</h2>
            <dl class="space-y-2 text-sm">
                <div>
                    <dt class="font-semibold text-teal-950">الجوال <x-info field="nursery.guardian_phone" /></dt>
                    <dd class="text-teal-800/90 tabular-nums" dir="ltr">{{ $guardian->phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-teal-950">البريد <x-info field="nursery.guardian_email" /></dt>
                    <dd class="text-teal-800/90" dir="ltr">{{ $guardian->email ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-teal-950">حالة البوابة <x-info field="nursery.guardians_portal_status" /></dt>
                    <dd>
                        @if($portalActive)
                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">مفعّل</span>
                        @else
                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-bold text-gray-600">غير مفعّل</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold text-teal-950">آخر دخول <x-info field="nursery.guardians_last_login" /></dt>
                    <dd class="text-teal-800/90">{{ $guardian->portal_last_login_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                </div>
                @if($guardian->portal_invited_at)
                    <div>
                        <dt class="font-semibold text-teal-950">تاريخ الدعوة</dt>
                        <dd class="text-teal-800/90">{{ $guardian->portal_invited_at->format('Y-m-d H:i') }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="nursery-card nursery-table-card lg:col-span-2">
            <div class="nursery-table-card__toolbar">
                <div>
                    <h2>
                        الأطفال المرتبطون
                        <x-info field="nursery.guardians_children_count" />
                    </h2>
                    <p>{{ $children->count() }} طفل مرتبط بهذا ولي الأمر</p>
                </div>
            </div>
            @if($children->isEmpty())
                <p class="text-sm text-teal-800/70 px-4 py-8 text-center">لا يوجد أطفال مرتبطون بهذا ولي الأمر.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="nursery-table min-w-[480px]">
                        <thead>
                            <tr>
                                <th>الطفل</th>
                                <th>الفصل</th>
                                <th class="text-center">الحالة</th>
                                <th class="text-center w-24">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($children as $child)
                                <tr>
                                    <td>
                                        <a href="{{ route('nursery.children.show', $child) }}" class="nursery-table-name no-underline text-inherit hover:opacity-90">
                                            <x-nursery-person-avatar :name="$child->name" :src="$child->firstImageUrl()" />
                                            <span class="nursery-table-name__text">
                                                <span class="nursery-table-name__title">{{ $child->name }}</span>
                                            </span>
                                        </a>
                                    </td>
                                    <td>{{ $child->activeEnrollment?->classroom?->name ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($child->status === 'active')
                                            <span class="nursery-status-pill nursery-status-pill--success">نشط</span>
                                        @else
                                            <span class="nursery-status-pill nursery-status-pill--muted">مؤرشف</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('nursery.children.show', $child) }}" class="nursery-btn nursery-btn-soft text-xs py-1">الملف</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
