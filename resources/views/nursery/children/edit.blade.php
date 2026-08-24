@extends('layouts.nursery')

@section('title', 'تعديل — '.$child->name)

@section('content')
<div class="w-full space-y-4" dir="rtl">
    <div>
        <h1 class="text-2xl font-extrabold text-teal-950">تعديل بيانات الطفل</h1>
        <p class="text-sm text-teal-800/80 mt-1">{{ $child->code }} — {{ $child->name }}</p>
    </div>

    @include('nursery.partials.child-form', [
        'child' => $child,
        'formAction' => route('nursery.children.update', $child),
        'formMethod' => 'PUT',
        'submitLabel' => 'حفظ التعديلات',
        'classrooms' => $classrooms,
        'genderOptions' => $genderOptions,
        'relationshipOptions' => $relationshipOptions,
    ])
</div>
@endsection
