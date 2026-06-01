@extends('layouts.store-portal')

@section('title', $pageTitle.' — '.$storeName)

@section('content')
<article class="bg-white rounded-3 border p-3 p-md-4 mt-3">
    <h1 class="h4 fw-bold mb-3">{{ $pageTitle }}</h1>
    @if(filled($pageBody))
        <div class="store-page-body" style="white-space:pre-wrap;line-height:1.8">{!! nl2br(e($pageBody)) !!}</div>
    @else
        <p class="text-muted mb-0">لم يُضف محتوى لهذه الصفحة بعد من إعدادات المتجر.</p>
    @endif
</article>
@endsection
