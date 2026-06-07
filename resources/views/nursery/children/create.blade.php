@extends('layouts.nursery')

@section('title', 'إضافة طفل')

@section('content')
<div class="w-full space-y-4" dir="rtl">
    <div>
        <h1 class="text-2xl font-extrabold text-orange-950">إضافة طفل</h1>
        <p class="text-sm text-orange-800/80 mt-1">نموذج بعرض الشاشة — املأ البيانات وارفع المستندات</p>
    </div>

    @include('nursery.partials.child-form', [
        'child' => null,
        'formAction' => route('nursery.children.store'),
        'formMethod' => 'POST',
        'submitLabel' => 'حفظ',
        'classrooms' => $classrooms,
        'genderOptions' => $genderOptions,
        'relationshipOptions' => $relationshipOptions,
    ])
</div>
@endsection
