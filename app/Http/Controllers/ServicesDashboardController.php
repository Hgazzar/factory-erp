<?php

namespace App\Http\Controllers;

use App\Models\InstalledAsset;
use App\Models\ServiceOrder;
use Illuminate\View\View;

class ServicesDashboardController extends Controller
{
    public function index(): View
    {
        $openCount = ServiceOrder::query()
            ->whereIn('status', [
                ServiceOrder::STATUS_OPEN,
                ServiceOrder::STATUS_ASSIGNED,
                ServiceOrder::STATUS_IN_PROGRESS,
            ])
            ->count();

        $urgentOpen = ServiceOrder::query()
            ->where('priority', ServiceOrder::PRIORITY_URGENT)
            ->whereIn('status', [
                ServiceOrder::STATUS_OPEN,
                ServiceOrder::STATUS_ASSIGNED,
                ServiceOrder::STATUS_IN_PROGRESS,
            ])
            ->count();

        $warrantyExpiringSoon = InstalledAsset::query()
            ->whereNotNull('warranty_end')
            ->whereDate('warranty_end', '>=', now()->toDateString())
            ->whereDate('warranty_end', '<=', now()->addDays(30)->toDateString())
            ->with(['item', 'deliveryOrder'])
            ->orderBy('warranty_end')
            ->limit(15)
            ->get();

        $recentOrders = ServiceOrder::query()
            ->with(['customer', 'assignedTechnician'])
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('services.dashboard', compact(
            'openCount',
            'urgentOpen',
            'warrantyExpiringSoon',
            'recentOrders'
        ));
    }
}
