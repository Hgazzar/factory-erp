<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\PosSale;
use App\Services\Pos\PosSaleService;
use App\Services\Store\StoreOnlineOrderQuery;
use App\Services\Store\StorePaymentReceiptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class OrderManagementController extends Controller
{
    public function index(Request $request, StoreOnlineOrderQuery $ordersQuery): View
    {
        $tenantUserId = (int) auth()->id();
        $status = trim((string) $request->query('status', ''));

        $orders = $ordersQuery->paginatedList($tenantUserId, $status !== '' ? $status : null);

        $statusOptions = [
            'all' => 'كل الطلبات',
            PosSale::STATUS_PENDING => PosSale::onlineOrderStatusLabels()[PosSale::STATUS_PENDING],
            PosSale::STATUS_PENDING_VERIFICATION => PosSale::onlineOrderStatusLabels()[PosSale::STATUS_PENDING_VERIFICATION],
            PosSale::STATUS_DELIVERED => PosSale::onlineOrderStatusLabels()[PosSale::STATUS_DELIVERED],
            PosSale::STATUS_COLLECTED => PosSale::onlineOrderStatusLabels()[PosSale::STATUS_COLLECTED],
            PosSale::STATUS_COMPLETED => PosSale::onlineOrderStatusLabels()[PosSale::STATUS_COMPLETED],
            PosSale::STATUS_VOIDED => PosSale::onlineOrderStatusLabels()[PosSale::STATUS_VOIDED],
        ];

        return view('store.orders.index', compact('orders', 'status', 'statusOptions'));
    }

    public function updateStatus(Request $request, PosSale $posSale, PosSaleService $sales): RedirectResponse
    {
        abort_if((int) $posSale->user_id !== (int) auth()->id(), 403);
        abort_if($posSale->sale_channel !== PosSale::CHANNEL_ONLINE_STORE, 404);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:delivered,collected,cancelled'],
        ]);

        try {
            $sales->updateStatus(
                (int) auth()->id(),
                (int) $posSale->id,
                $validated['status'],
                (int) auth()->id(),
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        $messages = [
            'delivered' => 'تم تأكيد التسليم وترحيل قيد الذمم.',
            'collected' => 'تم تأكيد التحصيل/التحقق وترحيل القيد المحاسبي.',
            'cancelled' => 'تم إلغاء الطلب وإرجاع المخزون.',
        ];

        return back()->with('success', $messages[$validated['status']] ?? 'تم تحديث حالة الطلب.');
    }

    public function paymentReceipt(PosSale $posSale, StorePaymentReceiptService $receipts): Response
    {
        abort_if((int) $posSale->user_id !== (int) auth()->id(), 403);
        abort_if($posSale->sale_channel !== PosSale::CHANNEL_ONLINE_STORE, 404);

        $path = $posSale->payment_receipt_path;
        abort_if($path === null || trim($path) === '', 404);

        $url = $receipts->publicUrl($path);
        abort_if($url === null, 404);

        return redirect()->to($url);
    }
}
