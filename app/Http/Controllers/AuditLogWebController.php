<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogWebController extends Controller
{
    public function index(): View
    {
        $logs = AuditLog::with(['actor', 'targetUser'])
            ->when((int) auth()->id() !== 1, fn ($q) => $q->where('actor_id', auth()->id()))
            ->orderByDesc('logged_at')
            ->orderByDesc('id')
            ->paginate(30);

        return view('system.audit.index', compact('logs'));
    }
}

