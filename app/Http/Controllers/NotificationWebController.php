<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationWebController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $notifications = $user->notifications()->orderByDesc('created_at')->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return back()->with('success', 'تم تمييز كل التنبيهات كمقروءة.');
    }
}

