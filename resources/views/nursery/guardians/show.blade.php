@extends('layouts.nursery')

@section('title', 'ملف ولي الأمر — '.$guardian->name)

@section('content')
<div class="w-full space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('nursery.guardians.index') }}" class="text-sm text-orange-600 font-semibold hover:underline">← أولياء الأمور</a>
            <h1 class="text-2xl font-extrabold text-orange-950 mt-1">{{ $guardian->name }}</h1>
            <p class="text-sm text-orange-800/80"><x-info field="nursery.guardians_profile_intro" /></p>
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
            <h2 class="text-base font-bold text-orange-950">بيانات التواصل</h2>
            <dl class="space-y-2 text-sm">
                <div>
                    <dt class="font-semibold text-orange-950">الجوال <x-info field="nursery.guardian_phone" /></dt>
                    <dd class="text-orange-800/90 tabular-nums" dir="ltr">{{ $guardian->phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-orange-950">البريد <x-info field="nursery.guardian_email" /></dt>
                    <dd class="text-orange-800/90" dir="ltr">{{ $guardian->email ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-orange-950">حالة البوابة <x-info field="nursery.guardians_portal_status" /></dt>
                    <dd>
                        @if($portalActive)
                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">مفعّل</span>
                        @else
                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-bold text-gray-600">غير مفعّل</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold text-orange-950">آخر دخول <x-info field="nursery.guardians_last_login" /></dt>
                    <dd class="text-orange-800/90">{{ $guardian->portal_last_login_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                </div>
                @if($guardian->portal_invited_at)
                    <div>
                        <dt class="font-semibold text-orange-950">تاريخ الدعوة</dt>
                        <dd class="text-orange-800/90">{{ $guardian->portal_invited_at->format('Y-m-d H:i') }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="nursery-card p-5 lg:col-span-2">
            <h2 class="text-base font-bold text-orange-950 mb-4">
                الأطفال المرتبطون
                <x-info field="nursery.guardians_children_count" />
                <span class="text-orange-600 tabular-nums">({{ $children->count() }})</span>
            </h2>
            @if($children->isEmpty())
                <p class="text-sm text-orange-800/70">لا يوجد أطفال مرتبطون بهذا ولي الأمر.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[480px]">
                        <thead>
                            <tr class="bg-orange-50/80 border-b border-orange-100">
                                <th class="px-3 py-2 text-right font-bold text-orange-950">الطفل</th>
                                <th class="px-3 py-2 text-right font-bold text-orange-950">الفصل</th>
                                <th class="px-3 py-2 text-right font-bold text-orange-950">الحالة</th>
                                <th class="px-3 py-2 w-24"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($children as $child)
                                <tr class="border-b border-orange-50 hover:bg-orange-50/40">
                                    <td class="px-3 py-2 font-semibold text-orange-950">{{ $child->name }}</td>
                                    <td class="px-3 py-2">{{ $child->activeEnrollment?->classroom?->name ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ $child->status === 'active' ? 'نشط' : 'مؤرشف' }}</td>
                                    <td class="px-3 py-2">
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
