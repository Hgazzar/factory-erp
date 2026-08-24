@extends('layouts.nursery')

@section('title', 'تعديل — '.$classroom->name)

@section('content')
<div class="w-full space-y-4" dir="rtl">
    <div>
        <h1 class="text-2xl font-extrabold text-teal-950">تعديل الفصل</h1>
        <p class="text-sm text-teal-800/80 mt-1">{{ $classroom->name }}</p>
    </div>

    @include('nursery.partials.classroom-form', [
        'classroom' => $classroom,
        'formAction' => route('nursery.classrooms.update', $classroom),
        'formMethod' => 'PUT',
        'submitLabel' => 'حفظ التعديلات',
        'ageGroupLabels' => $ageGroupLabels,
    ])
</div>
@endsection
