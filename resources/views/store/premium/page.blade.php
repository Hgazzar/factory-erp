@extends('layouts.store-premium')

@section('title', ($pageTitle ?? '').' — '.$storeName)

@section('content')
<div class="ak-container ak-section">
    <div style="max-width:40rem">
        <p class="ak-eyebrow">أكواد</p>
        <h1 class="ak-section-title" style="margin:var(--ak-3) 0 var(--ak-8)">{{ $pageTitle }}</h1>
        <div class="ak-body-lg" style="white-space:pre-wrap;line-height:1.85">
            {!! nl2br(e($pageBody ?: 'المحتوى قيد التحديث.')) !!}
        </div>
    </div>
</div>
@endsection
