<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Nursery\Child;
use App\Models\Nursery\Subscription;
use App\Models\Nursery\SubscriptionPlan;
use App\Services\Nursery\NurserySubscriptionService;
use App\Support\NurseryAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class NurserySubscriptionWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(Request $request, NurserySubscriptionService $subscriptions): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $subscriptions->ensureDefaultPlans($tenantUserId);

        $period = $request->query('period', 'all');
        [$from, $to] = $subscriptions->resolvePeriod($period === 'all' ? null : $period);
        $reminderTab = $request->query('reminder', 'payment') === 'renewal' ? 'renewal' : 'payment';

        $stats = $subscriptions->stats($tenantUserId, $from, $to);

        $listQuery = Subscription::query()
            ->with(['child:id,name', 'plan:id,name'])
            ->where('user_id', $tenantUserId)
            ->orderByDesc('created_at');

        if ($from !== null) {
            $listQuery->where('created_at', '>=', $from->copy()->startOfDay());
        }
        if ($to !== null) {
            $listQuery->where('created_at', '<=', $to->copy()->endOfDay());
        }

        $items = $listQuery->paginate(20)->withQueryString();

        $childOptions = Child::query()
            ->where('user_id', $tenantUserId)
            ->where('status', Child::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Child $c) => ['value' => (string) $c->id, 'label' => $c->name])
            ->all();

        $planOptions = SubscriptionPlan::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (SubscriptionPlan $p) => [
                'value' => (string) $p->id,
                'label' => $p->name,
                'amount_after_tax' => $p->amountAfterTax(),
            ])
            ->all();

        $canManage = app(NurseryAccess::class)->allows(NurseryAccess::CAP_MANAGE_SUBSCRIPTIONS);

        return view('nursery.subscriptions.index', [
            'items' => $items,
            'stats' => $stats,
            'period' => $period,
            'reminderTab' => $reminderTab,
            'paymentReminders' => $subscriptions->paymentReminders($tenantUserId),
            'renewalReminders' => $subscriptions->renewalReminders($tenantUserId),
            'childOptions' => $childOptions,
            'planOptions' => $planOptions,
            'canManage' => $canManage,
        ]);
    }

    public function store(Request $request, NurserySubscriptionService $subscriptions): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $request->validate([
            'child_id' => ['required', 'integer'],
            'plan_id' => ['required', 'integer'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'amount_after_tax' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_paid' => ['nullable'],
        ]);

        try {
            $result = $subscriptions->create($tenantUserId, $data, (int) auth()->id());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $message = 'تم إضافة الاشتراك.';
        if ($result['finance_posted']) {
            $message .= ' وتم ترحيل القيد المالي.';
        }
        if ($result['whatsapp_sent']) {
            $message .= ' وتم إرسال تأكيد واتساب لولي الأمر.';
        }

        return redirect()->route('nursery.subscriptions.index')->with('success', $message);
    }

    public function cancel(Subscription $subscription, NurserySubscriptionService $subscriptions): RedirectResponse
    {
        $subscriptions->cancel($subscription, $this->resolveOperationsTenantUserId());

        return back()->with('success', 'تم إلغاء الاشتراك.');
    }

    public function sendPaymentReminders(NurserySubscriptionService $subscriptions): RedirectResponse
    {
        $result = $subscriptions->sendPaymentReminders($this->resolveOperationsTenantUserId());

        return back()->with('success', $result['sent'] > 0
            ? "تم إرسال تذكير بالدفع لـ {$result['sent']} اشتراك."
            : ($result['skipped'] > 0
                ? 'لم يُرسل أي تذكير (تحقق من تفعيل واتساب الحضانة أو أرقام أولياء الأمور).'
                : 'لا توجد اشتراكات غير مدفوعة تحتاج تذكيراً.'));
    }

    public function planAmount(Request $request): JsonResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $planId = (int) $request->query('plan_id');

        $plan = SubscriptionPlan::query()
            ->where('user_id', $tenantUserId)
            ->whereKey($planId)
            ->first();

        if ($plan === null) {
            return response()->json(['amount_after_tax' => null]);
        }

        return response()->json(['amount_after_tax' => $plan->amountAfterTax()]);
    }
}
