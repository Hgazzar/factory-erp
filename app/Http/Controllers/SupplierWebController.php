<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersistsMorphAttachments;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\DebitNote;
use App\Models\Payment;
use App\Models\PurchaseReturn;
use App\Models\ReceiveNote;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\SupplierDocument;
use App\Services\ExcelImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierWebController extends Controller
{
    use PersistsMorphAttachments;

    public function index(Request $request): View|Response
    {
        $query = Supplier::query()->orderBy('code');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        if ($request->get('export') === 'csv') {
            $rows = $query->limit(5000)->get();
            $csv = "\xEF\xBB\xBF";
            $csv .= "الرمز,الاسم,البريد,الهاتف,النوع,الحالة\n";
            foreach ($rows as $s) {
                $csv .= '"'.str_replace('"', '""', $s->code ?? '').'","'.str_replace('"', '""', $s->getLocalizedDisplayName()).'","'.str_replace('"', '""', $s->email ?? '').'","'.str_replace('"', '""', $s->phone ?? $s->mobile ?? '').'","'.str_replace('"', '""', $s->supplier_type ?? '').'","'.($s->is_active ? 'نشط' : 'غير نشط')."\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="suppliers-'.date('Y-m-d').'.csv"',
            ]);
        }

        $suppliers = $query->paginate(20)->withQueryString();

        return view('purchases.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        $nextCode = Supplier::generateNextCodeForUser((int) (auth()->id() ?? 1));

        return view('purchases.suppliers.create', compact('nextCode'));
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $uid = (int) (auth()->id() ?? 1);

        if (empty($data['code'] ?? null)) {
            $data['code'] = Supplier::generateNextCodeForUser($uid);
        }
        $data['user_id'] = $uid;
        $data['is_active'] = $request->boolean('is_active', true);

        $supplierData = collect($data)->except(['documents', 'attachments'])->filter(fn ($v) => $v !== null && $v !== '')->all();
        $supplier = Supplier::create($supplierData);

        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }
        $this->persistMorphAttachments($supplier, $uploads, $uid, 'suppliers');

        return redirect()
            ->route('purchases.suppliers.show', $supplier)
            ->with('success', 'تم إضافة المورد بنجاح.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load(['documents', 'attachments']);

        return view('purchases.suppliers.show', compact('supplier'));
    }

    public function downloadDocument(Supplier $supplier, SupplierDocument $document): StreamedResponse
    {
        if ($document->supplier_id !== $supplier->id) {
            abort(404);
        }
        $path = Storage::disk('public')->path($document->file_path);
        if (! is_file($path)) {
            abort(404);
        }

        return response()->streamDownload(function () use ($path) {
            echo file_get_contents($path);
        }, $document->file_name, [
            'Content-Type' => $document->file_type ?? 'application/octet-stream',
        ]);
    }

    public function edit(Supplier $supplier): View
    {
        $supplier->load(['attachments']);

        return view('purchases.suppliers.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validated();

        // عند عدم إرسال الحقل (الـ checkbox غير محدد) يكون false
        $data['is_active'] = $request->boolean('is_active');

        $supplier->update(collect($data)->except('attachments')->all());

        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }
        $this->persistMorphAttachments($supplier, $uploads, (int) (auth()->id() ?? 1), 'suppliers');

        return redirect()
            ->route('purchases.suppliers.index')
            ->with('success', 'تم تحديث بيانات المورد بنجاح.');
    }

    public function importTemplate(): Response
    {
        $csv = "\xEF\xBB\xBF";
        $csv .= implode(',', [
            'code',
            'name',
            'email',
            'phone',
            'supplier_type',
            'rating',
            'tax_number',
            'commercial_register',
            'currency',
            'is_active',
        ])."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="suppliers-import-template.csv"',
        ]);
    }

    public function import(Request $request, ExcelImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:20480',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }
                    $extension = strtolower($value->getClientOriginalExtension());
                    if ($extension === '') {
                        $fail('تعذر تحديد نوع الملف. يُرجى استخدام ملف بامتداد واضح: csv، txt، xls، xlsx.');

                        return;
                    }
                    if (! in_array($extension, ['csv', 'txt', 'xlsx', 'xls'], true)) {
                        $fail('نوع الملف غير مدعوم. يُسمح فقط بامتدادات: csv، txt، xls، xlsx.');
                    }
                },
            ],
        ], [
            'file.required' => 'يرجى اختيار ملف للاستيراد.',
            'file.file' => 'يجب أن يكون المرفق ملفاً صالحاً.',
            'file.max' => 'حجم الملف يتجاوز الحد المسموح (20 ميجابايت).',
        ]);

        try {
            $summary = DB::transaction(function () use ($request, $importService) {
                $userId = (int) (auth()->id() ?? 1);

                return $importService->importSimple(
                    $request->file('file'),
                    ['name', 'code', 'email'],
                    function (array $row, int $line) use ($userId) {
                        $name = $row['name'] ?? null;
                        $code = $row['code'] ?? null;
                        $email = $row['email'] ?? null;
                        $phone = $row['phone'] ?? null;

                        if (! $name) {
                            throw new \RuntimeException("حقل name مطلوب في السطر رقم {$line}.");
                        }

                        if (! $code && ! $email) {
                            throw new \RuntimeException("يجب توفير code أو email على الأقل في السطر رقم {$line}.");
                        }

                        $data = [
                            'name' => $name,
                            'email' => $email ?: null,
                            'phone' => $phone ?: null,
                            'supplier_type' => $row['supplier_type'] ?: null,
                            'rating' => $row['rating'] !== '' ? (int) $row['rating'] : null,
                            'tax_number' => $row['tax_number'] ?: null,
                            'commercial_register' => $row['commercial_register'] ?: null,
                            'currency' => $row['currency'] ?: null,
                        ];

                        if (array_key_exists('is_active', $row) && $row['is_active'] !== '') {
                            $val = mb_strtolower((string) $row['is_active']);
                            $data['is_active'] = in_array($val, ['1', 'true', 'yes', 'نعم', 'نشط'], true);
                        }

                        $existing = Supplier::withoutGlobalScopes()
                            ->where('user_id', $userId)
                            ->when($code, fn ($q) => $q->where('code', $code))
                            ->when(! $code && $email, fn ($q) => $q->where('email', $email))
                            ->first();

                        if ($existing) {
                            $existing->update($data);

                            return 'updated';
                        }

                        $match = ['user_id' => $userId];
                        if ($code) {
                            $match['code'] = $code;
                        } else {
                            $match['code'] = Supplier::generateNextCodeForUser($userId);
                        }

                        Supplier::withoutGlobalScopes()->create(array_merge($match, $data));

                        return 'created';
                    }
                );
            });
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.suppliers.index')
            ->with('success', "تم استيراد الموردين بنجاح. تمت إضافة {$summary['created']} وتحديث {$summary['updated']}.");
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if (
            $supplier->purchaseOrders()->exists()
            || $supplier->purchaseInvoices()->exists()
            || Payment::query()->where('supplier_id', $supplier->id)->exists()
            || ReceiveNote::query()->where('supplier_id', $supplier->id)->exists()
            || PurchaseReturn::query()->where('supplier_id', $supplier->id)->exists()
            || StockIn::query()->where('supplier_id', $supplier->id)->exists()
            || DebitNote::query()->where('supplier_id', $supplier->id)->exists()
        ) {
            return redirect()
                ->route('purchases.suppliers.index')
                ->with('error', 'لا يمكن حذف المورد لوجود مستندات أو حركات مرتبطة به (أوامر شراء، فواتير، سندات، …).');
        }

        $supplier->delete();

        return redirect()
            ->route('purchases.suppliers.index')
            ->with('success', 'تم حذف المورد.');
    }
}
