<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersistsMorphAttachments;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JournalEntryWebController extends Controller
{
    use PersistsMorphAttachments;
    use ResolvesOperationsTenant;

    public function index(Request $request): View
    {
        $query = JournalEntry::query()
            ->withCount('items')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', "%{$term}%")
                    ->orWhere('reference', 'like', "%{$term}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        // نوع القيد: حالياً لا يوجد حقل في DB، يمكن إضافته لاحقاً
        if ($request->filled('type')) {
            // $query->where('entry_type', $request->type);
        }
        if ($request->filled('account_id')) {
            $accountId = (int) $request->input('account_id');
            if ($accountId > 0) {
                $query->whereHas('items', fn ($q) => $q->where('account_id', $accountId));
            }
        }

        $entries = $query->paginate(20)->withQueryString();

        $filterAccounts = Account::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar']);

        return view('finance.journals.index', compact('entries', 'filterAccounts'));
    }

    public function create(): View
    {
        $accounts = Account::orderBy('code')->get();

        return view('finance.journals.create', compact('accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = $this->resolveOperationsTenantUserId();
        $data = $request->validate([
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', Rule::exists('accounts', 'id')->where('user_id', $uid)],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.cost_center' => ['nullable', 'string', 'max:120'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'attachments' => ['nullable', 'array', 'max:20'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv'],
        ], [
            'date.required' => 'تاريخ القيد مطلوب.',
            'date.date' => 'تاريخ القيد غير صالح.',
            'lines.required' => 'أضف سطور القيد.',
            'lines.min' => 'يجب إضافة سطرين على الأقل في القيد.',
            'lines.*.account_id.required' => 'اختر الحساب في كل سطر.',
            'lines.*.account_id.exists' => 'أحد الحسابات غير صالح أو لا يخص حسابك.',
            'lines.*.debit.numeric' => 'مبلغ المدين يجب أن يكون رقماً.',
            'lines.*.debit.min' => 'مبلغ المدين لا يمكن أن يكون سالباً.',
            'lines.*.credit.numeric' => 'مبلغ الدائن يجب أن يكون رقماً.',
            'lines.*.credit.min' => 'مبلغ الدائن لا يمكن أن يكون سالباً.',
        ]);

        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }

        $lines = collect($data['lines'])
            ->map(function ($line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                return [
                    'account_id' => (int) $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'cost_center' => isset($line['cost_center']) && $line['cost_center'] !== ''
                        ? (string) $line['cost_center']
                        : null,
                    'debit' => $debit,
                    'credit' => $credit,
                ];
            })
            ->filter(function ($line) {
                return $line['debit'] > 0 || $line['credit'] > 0;
            })
            ->values();

        if ($lines->count() < 2) {
            return back()
                ->withInput()
                ->withErrors(['lines' => 'يجب أن يحتوي القيد على سطرين على الأقل بمبالغ مدين أو دائن.']);
        }

        $totalDebit = $lines->sum('debit');
        $totalCredit = $lines->sum('credit');

        if ($totalDebit <= 0 || abs($totalDebit - $totalCredit) > 0.0001) {
            return back()
                ->withInput()
                ->withErrors(['balance' => 'القيد غير متوازن. يجب أن يكون إجمالي المدين مساوياً لإجمالي الدائن وأكبر من صفر.']);
        }

        $actorId = (int) auth()->id();

        DB::transaction(function () use ($data, $lines, $totalDebit, $uid, $uploads, $actorId) {
            $entry = JournalEntry::create([
                'user_id' => $uid,
                'created_by' => $actorId,
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'total' => $totalDebit,
            ]);

            foreach ($lines as $line) {
                $entry->items()->create($line);
            }

            $this->persistMorphAttachments($entry, $uploads, $uid, 'journal-entries');
        });

        return redirect()
            ->route('finance.journals.index')
            ->with('success', 'تم حفظ القيد المحاسبي بنجاح.');
    }

    public function show(JournalEntry $journal): View
    {
        $journal->load(['items.account', 'attachments']);

        return view('finance.journals.show', ['entry' => $journal]);
    }

    public function edit(JournalEntry $journal): View
    {
        $accounts = Account::orderBy('code')->get();
        $journal->load(['items', 'attachments']);

        return view('finance.journals.edit', ['entry' => $journal, 'accounts' => $accounts]);
    }

    public function update(Request $request, JournalEntry $journal): RedirectResponse
    {
        $uid = $this->resolveOperationsTenantUserId();
        $data = $request->validate([
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', Rule::exists('accounts', 'id')->where('user_id', $uid)],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.cost_center' => ['nullable', 'string', 'max:120'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'attachments' => ['nullable', 'array', 'max:20'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv'],
        ], [
            'date.required' => 'تاريخ القيد مطلوب.',
            'date.date' => 'تاريخ القيد غير صالح.',
            'lines.required' => 'أضف سطور القيد.',
            'lines.min' => 'يجب إضافة سطرين على الأقل في القيد.',
            'lines.*.account_id.required' => 'اختر الحساب في كل سطر.',
            'lines.*.account_id.exists' => 'أحد الحسابات غير صالح أو لا يخص حسابك.',
            'lines.*.debit.numeric' => 'مبلغ المدين يجب أن يكون رقماً.',
            'lines.*.debit.min' => 'مبلغ المدين لا يمكن أن يكون سالباً.',
            'lines.*.credit.numeric' => 'مبلغ الدائن يجب أن يكون رقماً.',
            'lines.*.credit.min' => 'مبلغ الدائن لا يمكن أن يكون سالباً.',
        ]);

        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }

        $lines = collect($data['lines'])
            ->map(function ($line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                return [
                    'account_id' => (int) $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'cost_center' => isset($line['cost_center']) && $line['cost_center'] !== ''
                        ? (string) $line['cost_center']
                        : null,
                    'debit' => $debit,
                    'credit' => $credit,
                ];
            })
            ->filter(fn ($line) => $line['debit'] > 0 || $line['credit'] > 0)
            ->values();

        if ($lines->count() < 2) {
            return back()
                ->withInput()
                ->withErrors(['lines' => 'يجب أن يحتوي القيد على سطرين على الأقل بمبالغ مدين أو دائن.']);
        }

        $totalDebit = $lines->sum('debit');
        $totalCredit = $lines->sum('credit');
        if ($totalDebit <= 0 || abs($totalDebit - $totalCredit) > 0.0001) {
            return back()
                ->withInput()
                ->withErrors(['balance' => 'القيد غير متوازن. يجب أن يكون إجمالي المدين مساوياً لإجمالي الدائن وأكبر من صفر.']);
        }

        DB::transaction(function () use ($journal, $data, $lines, $totalDebit, $uid, $uploads) {
            $journal->update([
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'total' => $totalDebit,
            ]);
            $journal->items()->delete();
            foreach ($lines as $line) {
                $journal->items()->create($line);
            }

            $this->persistMorphAttachments($journal, $uploads, $uid, 'journal-entries');
        });

        return redirect()
            ->route('finance.journals.index')
            ->with('success', 'تم تحديث القيد بنجاح.');
    }

    public function destroy(JournalEntry $journal): RedirectResponse
    {
        $journal->items()->delete();
        $journal->delete();

        return redirect()
            ->route('finance.journals.index')
            ->with('success', 'تم حذف القيد.');
    }
}
