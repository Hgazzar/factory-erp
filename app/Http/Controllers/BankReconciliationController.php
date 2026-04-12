<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BankReconciliation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BankReconciliationController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');

        $baseQuery = BankReconciliation::query()
            ->with('account')
            ->when(
                in_array($status, ['draft', 'completed'], true),
                fn ($query) => $query->where('status', $status)
            )
            ->latest('statement_date')
            ->latest('id');

        $reconciliations = $baseQuery->paginate(15)->withQueryString();
        $stats = $this->buildStats((clone $baseQuery)->get());

        return view('finance.bank-reconciliations.index', compact('reconciliations', 'stats', 'status'));
    }

    public function create(): View
    {
        $accounts = Account::query()
            ->where('is_bank', true)
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en']);

        return view('finance.bank-reconciliations.create', compact('accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('is_bank', true)),
            ],
            'statement_date' => ['required', 'date'],
            'statement_balance' => ['required', 'numeric'],
            'book_balance' => ['required', 'numeric'],
            'status' => ['required', 'in:draft,completed'],
        ]);

        BankReconciliation::query()->create([
            'reconciliation_number' => $this->nextReconciliationNumber(),
            'account_id' => (int) $data['account_id'],
            'statement_date' => $data['statement_date'],
            'statement_balance' => (float) $data['statement_balance'],
            'book_balance' => (float) $data['book_balance'],
            'difference' => (float) $data['statement_balance'] - (float) $data['book_balance'],
            'status' => $data['status'],
        ]);

        return redirect()
            ->route('finance.bank-reconciliations.index')
            ->with('success', 'تم إنشاء تسوية البنك بنجاح.');
    }

    private function buildStats($rows): array
    {
        $total = $rows->count();
        $pending = $rows->where('status', 'draft')->count();
        $completed = $rows->where('status', 'completed')->count();

        return [
            'total_reconciliations' => $total,
            'pending_reconciliations' => $pending,
            'total_items' => $total,
            'completed_reconciliations' => $completed,
        ];
    }

    private function nextReconciliationNumber(): string
    {
        $year = (string) now()->year;
        $prefix = 'BR-' . $year . '-';

        $lastNumber = BankReconciliation::query()
            ->where('reconciliation_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('reconciliation_number');

        if (! $lastNumber) {
            return $prefix . '0001';
        }

        $lastSequence = (int) substr($lastNumber, -4);

        return $prefix . str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);
    }
}
