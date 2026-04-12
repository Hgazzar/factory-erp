<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChequeController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', '');
        $status = (string) $request->query('status', '');

        $cheques = Cheque::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('cheque_number', 'like', '%' . $search . '%')
                        ->orWhere('bank_name', 'like', '%' . $search . '%')
                        ->orWhere('party_name', 'like', '%' . $search . '%')
                        ->orWhere('beneficiary_name', 'like', '%' . $search . '%');
                });
            })
            ->when(in_array($type, ['incoming', 'outgoing'], true), fn ($query) => $query->where('type', $type))
            ->when(in_array($status, ['pending', 'cleared', 'bounced', 'cancelled'], true), fn ($query) => $query->where('status', $status))
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'incoming_total' => (float) Cheque::query()->where('type', 'incoming')->where('status', 'pending')->sum('amount'),
            'outgoing_total' => (float) Cheque::query()->where('type', 'outgoing')->where('status', 'pending')->sum('amount'),
            'due_today' => (float) Cheque::query()->whereDate('due_date', now()->toDateString())->where('status', 'pending')->sum('amount'),
            'bounced_total' => (float) Cheque::query()->where('status', 'bounced')->sum('amount'),
        ];

        return view('finance.cheques.index', compact('cheques', 'stats', 'search', 'type', 'status'));
    }

    public function createIncoming(): View
    {
        $customers = Customer::query()
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('name_ar')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'name_ar']);

        return view('finance.cheques.create-incoming', compact('customers'));
    }

    public function createOutgoing(): View
    {
        return view('finance.cheques.create-outgoing');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        DB::transaction(function () use ($request, $data): void {
            Cheque::query()->create([
                ...$data,
                'created_by' => $request->user()?->id,
            ]);
        });

        return redirect()
            ->route('finance.cheques.index')
            ->with('success', 'تم حفظ الشيك بنجاح.');
    }

    public function edit(Cheque $cheque): View
    {
        if ($cheque->type === 'incoming') {
            $customers = Customer::query()
                ->where(function ($query) {
                    $query->where('is_active', true)
                        ->orWhereNull('is_active');
                })
                ->orderBy('name_ar')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'name_ar']);

            return view('finance.cheques.create-incoming', compact('cheque', 'customers'));
        }

        return view('finance.cheques.create-outgoing', compact('cheque'));
    }

    public function update(Request $request, Cheque $cheque): RedirectResponse
    {
        $data = $this->validateData($request, $cheque->id);

        DB::transaction(function () use ($cheque, $data): void {
            $cheque->update($data);
        });

        return redirect()
            ->route('finance.cheques.index')
            ->with('success', 'تم تحديث بيانات الشيك بنجاح.');
    }

    public function destroy(Cheque $cheque): RedirectResponse
    {
        DB::transaction(function () use ($cheque): void {
            $cheque->delete();
        });

        return redirect()
            ->route('finance.cheques.index')
            ->with('success', 'تم حذف الشيك بنجاح.');
    }

    private function validateData(Request $request, ?int $chequeId = null): array
    {
        return $request->validate([
            'type' => ['required', 'in:incoming,outgoing'],
            'cheque_number' => ['required', 'string', 'max:100', 'unique:cheques,cheque_number,' . ($chequeId ?? 'NULL') . ',id'],
            'bank_name' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'party_name' => ['nullable', 'required_if:type,incoming', 'string', 'max:150'],
            'beneficiary_name' => ['nullable', 'required_if:type,outgoing', 'string', 'max:150'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['required', 'date'],
            'status' => ['required', 'in:pending,cleared,bounced,cancelled'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
    }
}
