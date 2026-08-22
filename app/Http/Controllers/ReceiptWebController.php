<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Receipt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Support\DefaultLedgerAccounts;
use Illuminate\View\View;

class ReceiptWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(): View
    {
        $receipts = Receipt::with(['customer', 'creator'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('finance.receipts.index', compact('receipts'));
    }

    public function create(): View
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('finance.receipts.create', compact('customers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = $this->resolveOperationsTenantUserId();
        $data = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('user_id', $uid)],
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $user = $request->user();
        $tenantUserId = $uid;

        DB::transaction(function () use ($data, $user, $tenantUserId) {
            $amount = (float) $data['amount'];

            $cashAccount = DefaultLedgerAccounts::cashOnHand();
            $customersAccount = DefaultLedgerAccounts::accountsReceivable();

            $entry = JournalEntry::create([
                'user_id' => $tenantUserId,
                'date' => $data['date'],
                'reference' => 'RCPT',
                'description' => 'سند قبض من العميل #' . $data['customer_id'],
                'total' => $amount,
            ]);

            // مدين: الخزينة
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $cashAccount->id,
                'description' => 'تحصيل نقدي',
                'debit' => $amount,
                'credit' => 0,
            ]);

            // دائن: العميل
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $customersAccount->id,
                'description' => 'تسوية رصيد العميل',
                'debit' => 0,
                'credit' => $amount,
            ]);

            $receipt = Receipt::create([
                'user_id' => $tenantUserId,
                'customer_id' => $data['customer_id'],
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'amount' => $amount,
                'journal_entry_id' => $entry->id,
                'created_by' => $user->id,
            ]);

            // Audit Log
            AuditLog::create([
                'actor_id' => $user->id,
                'target_user_id' => null,
                'action' => 'receipt_created',
                'old_role' => null,
                'new_role' => null,
                'logged_at' => now(),
            ]);
        });

        return redirect()
            ->route('finance.receipts.index')
            ->with('success', 'تم حفظ سند القبض وإنشاء القيد المحاسبي بنجاح.');
    }
}

