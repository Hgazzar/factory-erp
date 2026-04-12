<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Quotation;
use App\Services\UniversalImportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SalesQuotationWebController extends Controller
{
    public function importTemplate(): Response
    {
        $csv = "\xEF\xBB\xBF";
        $csv .= implode(',', [
            'Quotation Number',
            'Customer Code',
            'Quotation Date',
            'Valid Until',
            'Status',
            'Product Code',
            'Quantity',
            'Unit Price',
            'Discount Percent',
            'Tax Percent',
            'Total Amount',
        ])."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="quotations-import-template.csv"',
        ]);
    }

    public function import(Request $request, UniversalImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480'],
        ]);

        try {
            $summary = $importService->import($request->file('file'), UniversalImportService::ENTITY_QUOTATIONS);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sales.quotations.index')
            ->with('success', "تم استيراد عروض الأسعار. نجاح: {$summary['created']} إضافة، {$summary['updated']} تحديث. فشل: {$summary['failed']}.")
            ->with('import_result', $summary);
    }

    public function index(Request $request): View|\Illuminate\Http\Response
    {
        if ($request->get('export') === 'csv') {
            $rows = Quotation::with('customer')->orderByDesc('date')->orderByDesc('id')->limit(5000)->get();
            $csv = "\xEF\xBB\xBF";
            $csv .= "رقم العرض,العميل,التاريخ,صالح حتى,الإجمالي,الحالة\n";
            foreach ($rows as $q) {
                $csv .= '"'.str_replace('"', '""', (string) ($q->quotation_number ?: 'QT-'.str_pad((string) $q->id, 3, '0', STR_PAD_LEFT))).'","'
                    .str_replace('"', '""', $q->customer?->display_name ?? '').'","'
                    .($q->date?->format('Y-m-d') ?? '').'","'
                    .($q->valid_until?->format('Y-m-d') ?? '').'",'
                    .(float) ($q->total_amount ?? 0).',"'
                    .$this->statusLabelAr($q->status ?? '')."\"\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="quotations-'.date('Y-m-d').'.csv"',
            ]);
        }

        $query = Quotation::with('customer')->orderByDesc('date')->orderByDesc('id');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date('date_to'));
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            if ($q !== '') {
                $query->where(function ($qry) use ($q) {
                    if (preg_match('/^\d+$/', $q)) {
                        $qry->where('id', (int) $q);
                    }
                    $qry->orWhere('quotation_number', 'like', '%'.$q.'%');
                    $qry->orWhereHas('customer', function ($cq) use ($q) {
                        $cq->where('name', 'like', "%{$q}%")
                            ->orWhere('name_ar', 'like', "%{$q}%")
                            ->orWhere('code', 'like', "%{$q}%");
                    });
                });
            }
        }

        $quotations = $query->paginate(20)->withQueryString();

        $totalCountAll = Quotation::count();
        $pendingCount = Quotation::where('status', Quotation::STATUS_DRAFT)->count();
        $acceptedAmount = (float) Quotation::where('status', Quotation::STATUS_APPROVED)->sum('total_amount');
        $approvedOrConverted = Quotation::whereIn('status', [
            Quotation::STATUS_APPROVED,
            Quotation::STATUS_CONVERTED_TO_ORDER,
        ])->count();
        $conversionRate = $totalCountAll > 0 ? round(($approvedOrConverted / $totalCountAll) * 100, 1) : 0.0;

        $customers = Customer::query()
            ->orderByRaw('COALESCE(name_ar, name)')
            ->get();

        $statuses = [
            '' => 'جميع الحالات',
            Quotation::STATUS_DRAFT => 'مسودة',
            Quotation::STATUS_APPROVED => 'معتمد',
            Quotation::STATUS_REJECTED => 'مرفوض',
            Quotation::STATUS_CONVERTED_TO_ORDER => 'محوّل',
        ];

        return view('sales.quotations.index', [
            'quotations' => $quotations,
            'conversionRate' => $conversionRate,
            'acceptedAmount' => $acceptedAmount,
            'pendingCount' => $pendingCount,
            'totalCount' => $totalCountAll,
            'customers' => $customers,
            'statuses' => $statuses,
        ]);
    }

    public function create(): View
    {
        $customers = Customer::query()
            ->where('is_active', true)
            ->orderByRaw('COALESCE(name_ar, name)')
            ->get();

        $items = Item::active()
            ->orderBy('code')
            ->get()
            ->map(fn (Item $i) => [
                'id' => $i->id,
                'code' => $i->code,
                'name_ar' => $i->name_ar,
                'name_en' => $i->name_en,
                'selling_price' => $i->selling_price,
                'sale_price' => $i->sale_price,
            ])
            ->values();

        $nextQuotationNumber = Quotation::generateNextQuotationNumberForUser((int) (auth()->id() ?? 1));

        return view('sales.quotations.create', compact('customers', 'items', 'nextQuotationNumber'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:date'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $lines = $this->mapValidatedLines($data['lines']);

        if ($lines->isEmpty()) {
            return back()->withInput()->with('error', 'يجب إضافة على الأقل بنداً واحداً بقيمة صحيحة.');
        }

        $totalAmount = $lines->sum('line_total');

        $uid = (int) (auth()->id() ?? 1);
        $quotation = Quotation::create([
            'user_id' => $uid,
            'quotation_number' => Quotation::generateNextQuotationNumberForUser($uid),
            'customer_id' => $data['customer_id'],
            'date' => $data['date'],
            'valid_until' => $data['valid_until'],
            'notes' => $data['notes'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'terms' => $data['terms'] ?? null,
            'status' => Quotation::STATUS_DRAFT,
            'total_amount' => $totalAmount,
        ]);

        foreach ($lines as $line) {
            $quotation->items()->create($line);
        }

        return redirect()
            ->route('sales.quotations.index')
            ->with('success', 'تم حفظ عرض السعر بنجاح.');
    }

    public function edit(Quotation $quotation): View
    {
        $this->authorize('update', $quotation);

        $quotation->load(['items.item']);
        $initialLines = $quotation->items->map(fn ($row) => [
            'item_id' => $row->item_id,
            'quantity' => (float) $row->quantity,
            'unit_price' => (float) $row->unit_price,
            'discount_percent' => (float) $row->discount_percent,
            'tax_percent' => (float) $row->tax_percent,
        ])->values()->toArray();

        $customers = Customer::query()
            ->where('is_active', true)
            ->orderByRaw('COALESCE(name_ar, name)')
            ->get();

        $items = Item::active()
            ->orderBy('code')
            ->get()
            ->map(fn (Item $i) => [
                'id' => $i->id,
                'code' => $i->code,
                'name_ar' => $i->name_ar,
                'name_en' => $i->name_en,
                'selling_price' => $i->selling_price,
                'sale_price' => $i->sale_price,
            ])
            ->values();

        return view('sales.quotations.edit', compact('quotation', 'customers', 'items', 'initialLines'));
    }

    public function update(Request $request, Quotation $quotation): RedirectResponse
    {
        $this->authorize('update', $quotation);

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:date'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $lines = $this->mapValidatedLines($data['lines']);

        if ($lines->isEmpty()) {
            return back()->withInput()->with('error', 'يجب إضافة على الأقل بنداً واحداً بقيمة صحيحة.');
        }

        $totalAmount = $lines->sum('line_total');

        DB::transaction(function () use ($quotation, $data, $lines, $totalAmount) {
            $quotation->update([
                'customer_id' => $data['customer_id'],
                'date' => $data['date'],
                'valid_until' => $data['valid_until'],
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'total_amount' => $totalAmount,
            ]);

            $quotation->items()->delete();
            foreach ($lines as $line) {
                $quotation->items()->create($line);
            }
        });

        return redirect()
            ->route('sales.quotations.index')
            ->with('success', 'تم تحديث عرض السعر بنجاح.');
    }

    public function destroy(Quotation $quotation): RedirectResponse
    {
        $this->authorize('delete', $quotation);
        $quotation->delete();

        return redirect()
            ->route('sales.quotations.index')
            ->with('success', 'تم حذف عرض السعر.');
    }

    public function approve(Quotation $quotation): RedirectResponse
    {
        $this->authorize('approve', $quotation);

        if ($quotation->valid_until === null) {
            return back()->with('error', 'لا يمكن اعتماد عرض سعر دون تحديد تاريخ «صالح حتى»؛ يرجى تعديل العرض وإدخال التاريخ أولاً.');
        }

        $quotation->update(['status' => Quotation::STATUS_APPROVED]);

        return back()->with('success', 'تم اعتماد عرض السعر.');
    }

    public function convertToOrder(Quotation $quotation): RedirectResponse
    {
        if ($quotation->status !== Quotation::STATUS_APPROVED) {
            abort(403);
        }

        return redirect()
            ->route('sales.orders.create', ['quotation_id' => $quotation->id]);
    }

    public function print(Quotation $quotation): View
    {
        $quotation->load(['customer', 'items.item']);
        $company = CompanySetting::first();

        return view('sales.quotations.print', compact('quotation', 'company'));
    }

    public function pdf(Quotation $quotation): Response
    {
        $quotation->load(['customer', 'items.item']);
        $company = CompanySetting::query()->first();

        $logoDataUri = null;
        if ($company?->logo_url && str_starts_with((string) $company->logo_url, 'company/')) {
            if (Storage::disk('public')->exists($company->logo_url)) {
                $mime = Storage::disk('public')->mimeType($company->logo_url) ?: 'image/png';
                if (is_string($mime) && str_starts_with($mime, 'image/')) {
                    $bytes = Storage::disk('public')->get($company->logo_url);
                    if ($bytes !== false && $bytes !== '') {
                        $logoDataUri = 'data:'.$mime.';base64,'.base64_encode($bytes);
                    }
                }
            }
        }

        $qrDataUri = null;
        try {
            $verifyUrl = route('sales.quotations.print', $quotation, absolute: true);
            $qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data='.rawurlencode($verifyUrl);
            $resp = Http::timeout(8)->get($qrSrc);
            if ($resp->successful() && $resp->body() !== '') {
                $qrDataUri = 'data:image/png;base64,'.base64_encode($resp->body());
            }
        } catch (\Throwable $e) {
            Log::debug('Quotation QR fetch failed', ['quotation_id' => $quotation->id, 'message' => $e->getMessage()]);
        }

        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) ($quotation->quotation_number ?? 'QT-'.$quotation->id));
        $filename = 'quotation-'.$safe.'.pdf';

        try {
            return Pdf::loadView('sales.quotations.pdf', [
                'quotation' => $quotation,
                'company' => $company,
                'logoDataUri' => $logoDataUri,
                'qrDataUri' => $qrDataUri,
            ])
                ->setPaper('a4', 'portrait')
                ->setOption('isRemoteEnabled', false)
                ->setOption('isHtml5ParserEnabled', true)
                ->stream($filename);
        } catch (\Throwable $e) {
            Log::error('Quotation PDF generation failed', [
                'quotation_id' => $quotation->id,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            abort(500, 'تعذّر إنشاء ملف PDF. راجع سجلات الخادم.');
        }
    }

    /** بيانات عرض السعر لاستخدامها في نموذج أمر البيع */
    public function forOrder(Quotation $quotation): JsonResponse
    {
        if ($quotation->status !== Quotation::STATUS_APPROVED) {
            return response()->json([
                'message' => 'يجب اعتماد عرض السعر قبل استخدامه في أمر بيع.',
            ], 422);
        }

        $quotation->load(['customer', 'items.item']);
        $items = $quotation->items->map(fn ($row) => [
            'item_id' => $row->item_id,
            'quantity' => (float) $row->quantity,
            'unit_price' => (float) $row->unit_price,
            'discount_percent' => (float) $row->discount_percent,
            'tax_percent' => (float) $row->tax_percent,
            'line_total' => (float) $row->line_total,
            'description' => '',
        ])->values()->toArray();

        return response()->json([
            'customer_id' => $quotation->customer_id,
            'order_date' => $quotation->date->format('Y-m-d'),
            'expected_delivery' => $quotation->valid_until?->format('Y-m-d'),
            'items' => $items,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function mapValidatedLines(array $lines): \Illuminate\Support\Collection
    {
        return collect($lines)
            ->map(function ($line) {
                $qty = (float) $line['quantity'];
                $price = (float) $line['unit_price'];
                $discount = (float) ($line['discount_percent'] ?? 0);
                $tax = (float) ($line['tax_percent'] ?? 0);
                $subtotal = round($qty * $price * (1 - $discount / 100), 4);
                $lineNet = $subtotal;
                $lineTax = $lineNet * $tax / 100;
                $lineTotal = $lineNet + $lineTax;

                return [
                    'item_id' => (int) $line['item_id'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $subtotal,
                    'discount_percent' => $discount,
                    'tax_percent' => $tax,
                    'line_total' => round($lineTotal, 4),
                ];
            })
            ->filter(fn ($l) => $l['quantity'] > 0)
            ->values();
    }

    private function statusLabelAr(string $status): string
    {
        return match ($status) {
            Quotation::STATUS_DRAFT => 'مسودة',
            Quotation::STATUS_APPROVED => 'معتمد',
            Quotation::STATUS_REJECTED => 'مرفوض',
            Quotation::STATUS_CONVERTED_TO_ORDER => 'محوّل',
            default => $status,
        };
    }
}
