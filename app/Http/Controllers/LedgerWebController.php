<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Account;
use App\Models\JournalItem;
use App\Services\ChartOfAccountsProvisioner;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LedgerWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(Request $request): View
    {
        $userId = $this->resolveOperationsTenantUserId();
        ChartOfAccountsProvisioner::ensureForUser($userId);

        $accounts = Account::orderBy('code')->get();
        $accountsById = $accounts->keyBy('id');

        $selectedAccount = null;
        $items = collect();
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if ($request->filled('account_id')) {
            $selectedAccount = Account::findOrFail($request->input('account_id'));

            // ترتيب النتائج حسب تاريخ القيد عبر join
            $items = JournalItem::query()
                ->select('journal_items.*')
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
                ->where('journal_items.account_id', $selectedAccount->id)
                ->when($fromDate, fn ($q) => $q->whereDate('journal_entries.date', '>=', $fromDate))
                ->when($toDate, fn ($q) => $q->whereDate('journal_entries.date', '<=', $toDate))
                ->orderBy('journal_entries.date')
                ->orderBy('journal_items.id')
                ->with('journalEntry')
                ->get();
        }

        return view('finance.ledger.index', compact(
            'accounts',
            'accountsById',
            'selectedAccount',
            'items',
            'fromDate',
            'toDate'
        ));
    }
}
