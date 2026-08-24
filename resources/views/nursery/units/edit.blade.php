@extends('layouts.nursery')

@section('title', 'تعديل وحدة')

@section('content')
<div class="w-full space-y-4" dir="rtl">
    <div>
        <h1 class="text-2xl font-extrabold text-teal-950">تعديل وحدة</h1>
        <p class="text-sm text-teal-800/80 mt-1">{{ $unit->name }}</p>
    </div>

    @include('nursery.partials.unit-form', [
        'unit' => $unit,
        'formAction' => route('nursery.units.update', $unit),
        'formMethod' => 'PUT',
        'submitLabel' => 'حفظ التعديلات',
        'ageGroupLabels' => $ageGroupLabels,
    ])
</div>
@endsection
