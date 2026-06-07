@extends('layouts.nursery')

@section('title', 'إضافة فصل')

@section('content')
<div class="w-full space-y-4" dir="rtl">
    <div>
        <h1 class="text-2xl font-extrabold text-orange-950">إضافة فصل</h1>
        <p class="text-sm text-orange-800/80 mt-1">بيانات الفصل — الاسم، السعة، والفئة العمرية</p>
    </div>

    @include('nursery.partials.classroom-form', [
        'classroom' => null,
        'formAction' => route('nursery.classrooms.store'),
        'formMethod' => 'POST',
        'submitLabel' => 'حفظ',
        'ageGroupLabels' => $ageGroupLabels,
    ])
</div>
@endsection
