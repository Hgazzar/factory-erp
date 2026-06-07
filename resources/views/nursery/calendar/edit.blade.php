@extends('layouts.nursery')

@section('title', 'تعديل التقويم')

@section('content')
@include('nursery.partials.calendar-form', [
    'entry' => $entry,
    'formAction' => route('nursery.calendar.update', $entry),
    'formMethod' => 'PUT',
    'submitLabel' => 'حفظ التعديلات',
    'entryType' => $entry->entry_type,
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
