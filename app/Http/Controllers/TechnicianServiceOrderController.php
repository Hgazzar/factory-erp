<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\ServiceOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TechnicianServiceOrderController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = ServiceOrder::query()
            ->with(['customer', 'installedAsset.item', 'deliveryOrder'])
            ->where('assigned_technician_id', $user->id)
            ->whereNotIn('status', [ServiceOrder::STATUS_COMPLETED, ServiceOrder::STATUS_CANCELLED])
            ->orderByRaw("CASE WHEN priority = 'urgent' THEN 0 ELSE 1 END")
            ->orderByDesc('id');

        $orders = $query->paginate(20);

        return view('services.technician.index', compact('orders'));
    }

    public function update(Request $request, ServiceOrder $order): RedirectResponse
    {
        $user = $request->user();
        if ((int) $order->assigned_technician_id !== (int) $user->id && $user->role !== 'admin') {
            abort(403);
        }

        $data = $request->validate([
            'status' => ['required', 'in:assigned,in_progress'],
        ]);

        $old = ['status' => $order->status];
        $order->status = $data['status'];
        $order->save();

        AuditTrail::log('update', 'service_orders', $order->id, $old, [
            'status' => $order->status,
            'by_technician_id' => $user->id,
        ]);

        return redirect()
            ->route('services.technician.index')
            ->with('success', 'تم تحديث حالة الطلب.');
    }
}
