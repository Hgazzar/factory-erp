@extends('layouts.nursery')

@section('title', 'إضافة إلى التقويم')

@section('content')
@include('nursery.partials.calendar-form', [
    'entry' => null,
    'formAction' => route('nursery.calendar.store'),
    'formMethod' => 'POST',
    'submitLabel' => 'إضافة',
    'entryType' => $entryType,
    'typeLabels' => $typeLabels,
    'unitOptions' => $unitOptions,
    'lessonOptions' => $lessonOptions,
    'classrooms' => $classrooms,
    'children' => $children,
    'selectedClassroomIds' => $selectedClassroomIds,
    'selectedChildIds' => $selectedChildIds,
    'mediaLinks' => $mediaLinks,
])
@endsection
