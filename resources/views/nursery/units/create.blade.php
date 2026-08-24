@extends('layouts.nursery')

@section('title', 'إضافة وحدة')

@section('content')
<div class="w-full space-y-4" dir="rtl">
    <div>
        <h1 class="text-2xl font-extrabold text-teal-950">إضافة وحدة</h1>
        <p class="text-sm text-teal-800/80 mt-1">اسم الوحدة، الفئات العمرية، والأهداف التعليمية</p>
    </div>

    @include('nursery.partials.unit-form', [
        'unit' => null,
        'formAction' => route('nursery.units.store'),
        'formMethod' => 'POST',
        'submitLabel' => 'حفظ',
        'ageGroupLabels' => $ageGroupLabels,
    ])
</div>
@endsection
