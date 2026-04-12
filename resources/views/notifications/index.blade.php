@extends('layouts.app')

@section('title', 'التنبيهات - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">التنبيهات</span>
@endsection

@section('content')
<div class="max-w-full" dir="rtl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">التنبيهات</h1>
        @if(auth()->user()->unreadNotifications()->count() > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}" class="no-print">
                @csrf
                <button type="submit" class="px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                    تمييز الكل كمقروء
                </button>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="divide-y divide-gray-100">
            @forelse($notifications as $notification)
                @php $data = $notification->data; @endphp
                <div class="flex items-start gap-3 px-4 py-3 {{ $notification->read_at ? 'bg-white' : 'bg-indigo-50/40' }}">
                    <div class="mt-1">
                        @php $category = $data['category'] ?? null; @endphp
                        @if($category === 'commissions')
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-50 text-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0a8 8 0 1 0 8 8A8.009 8.009 0 0 0 8 0zm3.5 8.5a.5.5 0 0 1 0 1H8.707l1.147 1.146a.5.5 0 0 1-.708.708l-2-2a.5.5 0 0 1 0-.708l2-2a.5.5 0 1 1 .708.708L8.707 8.5z"/></svg>
                            </span>
                        @elseif($category === 'contracts')
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-50 text-amber-600">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M3 2a1 1 0 0 0-1 1v10.5a.5.5 0 0 0 .757.429L6 12.101l3.243 1.828A.5.5 0 0 0 10 13.5V3a1 1 0 0 0-1-1H3z"/></svg>
                            </span>
                        @elseif($category === 'installments')
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-sky-50 text-sky-600">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/></svg>
                            </span>
                        @elseif($category === 'einvoice')
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-50 text-red-600">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .064.016l6.857 3.94c.059.034.077.074.077.104v7.88a.25.25 0 0 1-.25.25H1.25A.25.25 0 0 1 1 13.94v-7.88c0-.03.018-.07.077-.104z"/><path d="M7 7h2v4H7V7zm0 5h2v2H7v-2z" fill="#fff"/></svg>
                            </span>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1z"/></svg>
                            </span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $data['title'] ?? 'تنبيه' }}</p>
                            <span class="text-xs text-gray-400 whitespace-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-gray-700 mt-0.5">{{ $data['body'] ?? '' }}</p>
                        @if(!empty($data['url']))
                            <a href="{{ $data['url'] }}" class="inline-flex items-center text-xs text-indigo-600 hover:text-indigo-700 mt-1">
                                فتح الشاشة المرتبطة
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-4 py-10 text-center text-sm text-gray-500">
                    لا توجد تنبيهات حالياً.
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

