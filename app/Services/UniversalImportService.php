<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Item;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UniversalImportService
{
    public const ENTITY_CUSTOMERS = 'customers';

    public const ENTITY_PRODUCTS = 'products';

    public const ENTITY_ACCOUNTS = 'accounts';

    public const ENTITY_SALES_ORDERS = 'sales_orders';

    public const ENTITY_QUOTATIONS = 'quotations';

    public const ENTITY_PURCHASE_ORDERS = 'purchase_orders';

    public const ENTITY_EXPENSES = 'expenses';

    public function __construct(
        private readonly ExcelImportService $excelImportService
    ) {}

    /**
     * @param  array{
     *   create_missing_references?:bool
     * }  $options
     * @return array{
     *   created:int,
     *   updated:int,
     *   failed:int,
     *   errors:array<int,array{line:int,reason:string}>
     * }
     */
    public function import(UploadedFile $file, string $entity, array $options = []): array
    {
        return match ($entity) {
            self::ENTITY_CUSTOMERS => $this->importCustomers($file),
            self::ENTITY_PRODUCTS => $this->importProducts($file, (bool) ($options['create_missing_references'] ?? true)),
            self::ENTITY_ACCOUNTS => $this->importAccounts($file, (bool) ($options['create_missing_references'] ?? true)),
            self::ENTITY_SALES_ORDERS => $this->importSalesOrders($file, (bool) ($options['create_missing_references'] ?? true)),
            self::ENTITY_QUOTATIONS => $this->importQuotations($file, (bool) ($options['create_missing_references'] ?? true)),
            self::ENTITY_PURCHASE_ORDERS => $this->importPurchaseOrders($file, (bool) ($options['create_missing_references'] ?? true)),
            self::ENTITY_EXPENSES => $this->importExpenses($file, (bool) ($options['create_missing_references'] ?? true)),
            default => throw new \RuntimeException("الكيان '{$entity}' غير مدعوم حالياً في UniversalImportService."),
        };
    }

    /**
     * Customer headers supported (client sheet):
     * Customer Code, Customer Name, Arabic Name, Email, Phone, Mobile, VAT Number, Credit Limit, Payment Terms (Days), Active
     */
    public function importCustomers(UploadedFile $file): array
    {
        /*
         * لا نلفّ الاستيراد بـ DB::transaction هنا: ExcelImportService يمسك استثناء كل سطر ويكمّل،
         * وفي PostgreSQL أي خطأ SQL يُجهض المعاملة بالكامل فيبقى الاتصال في حالة aborted حتى ROLLBACK،
         * فيظهر "current transaction is aborted" لكل الأسطر التالية.
         *
         * تطابق السجلات يشمل المحذوف ناعماً: الفهرس الفريد (user_id, code) يبقى سارياً على الصفوف المحذوفة،
         * لذا البحث بدون withTrashed() يؤدي إلى محاولة create() وتصادم Unique violation.
         */
        return $this->excelImportService->importSimple(
            $file,
            ['Customer Name'],
            function (array $row, int $line) {
                $userId = (int) (auth()->id() ?? 1);

                $code = $this->value($row, ['Customer Code', 'Code', 'code']);
                $name = $this->value($row, ['Customer Name', 'Name', 'name']);
                $nameAr = $this->value($row, ['Arabic Name', 'Name Ar', 'name_ar']);
                $email = $this->value($row, ['Email', 'email']);
                $phone = $this->value($row, ['Phone', 'phone']);
                $mobile = $this->value($row, ['Mobile', 'mobile']);
                $vatNumber = $this->value($row, ['VAT Number', 'Tax Number', 'vat_number', 'tax_number']);
                $creditLimit = $this->toDecimal($this->value($row, ['Credit Limit', 'credit_limit']));
                $paymentTermsDays = $this->toInt($this->value($row, ['Payment Terms (Days)', 'Payment Terms', 'payment_terms_days']));
                $activeValue = $this->value($row, ['Active', 'Is Active', 'is_active', 'status']);

                if (! $name) {
                    throw new \RuntimeException("حقل Customer Name مطلوب في السطر {$line}.");
                }

                $status = $this->toActiveStatus($activeValue);
                $isActive = $status === 'active';

                if (! $code) {
                    $code = Customer::generateNextCodeForUser($userId);
                }

                $data = [
                    'name' => $name,
                    'name_ar' => $nameAr ?: null,
                    'email' => $email ?: null,
                    'phone' => $phone ?: null,
                    'mobile' => $mobile ?: null,
                    'vat_number' => $vatNumber ?: null,
                    'tax_number' => $vatNumber ?: null,
                    'credit_limit' => $creditLimit,
                    'payment_terms_days' => $paymentTermsDays,
                    'is_active' => $isActive,
                    'status' => $status,
                ];

                $existing = Customer::withTrashed()
                    ->where('user_id', $userId)
                    ->where('code', $code)
                    ->first();
                if (! $existing && $email) {
                    $existing = Customer::withTrashed()
                        ->where('user_id', $userId)
                        ->where('email', $email)
                        ->first();
                }

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->update($data);

                    return 'updated';
                }

                Customer::query()->create(array_merge([
                    'user_id' => $userId,
                    'code' => $code,
                ], $data));

                return 'created';
            }
        );
    }

    /**
     * Product headers supported (client sheet):
     * Code, Name, Name Ar, Barcode, S K U, Category, Product Type, Cost Price, Sale Price, Minimum Stock, Reorder Level, Is Active
     */
    public function importProducts(UploadedFile $file, bool $createMissingReferences = true): array
    {
        return DB::transaction(function () use ($file, $createMissingReferences) {
            $userId = (int) (auth()->id() ?? 1);

            return $this->excelImportService->importSimple(
                $file,
                ['Code', 'Name'],
                function (array $row, int $line) use ($createMissingReferences, $userId) {
                    $code = $this->value($row, ['Code', 'code']);
                    $name = $this->value($row, ['Name', 'name']);
                    $nameAr = $this->value($row, ['Name Ar', 'Arabic Name', 'name_ar']);
                    $categoryName = $this->value($row, ['Category', 'category']);
                    $productType = $this->value($row, ['Product Type', 'type']);

                    if (! $code || ! $name) {
                        throw new \RuntimeException("حقول Code وName مطلوبة في السطر {$line}.");
                    }

                    $categoryId = $this->resolveItemCategoryId($categoryName, $createMissingReferences);
                    $type = $this->normalizeItemType($productType);

                    $payload = [
                        'name_en' => $name,
                        'name_ar' => $nameAr ?: $name,
                        'barcode' => $this->value($row, ['Barcode', 'barcode']) ?: null,
                        'sku' => $this->value($row, ['S K U', 'SKU', 'sku']) ?: null,
                        'category_id' => $categoryId,
                        'type' => $type,
                        'cost' => $this->toDecimal($this->value($row, ['Cost Price', 'cost', 'purchase_price'])) ?? 0,
                        'sale_price' => $this->toDecimal($this->value($row, ['Sale Price', 'sale_price'])) ?? 0,
                        'selling_price' => $this->toDecimal($this->value($row, ['Sale Price', 'selling_price'])) ?? 0,
                        'min_stock' => $this->toDecimal($this->value($row, ['Minimum Stock', 'min_stock'])) ?? 0,
                        'min_stock_level' => $this->toDecimal($this->value($row, ['Reorder Level', 'min_stock_level'])) ?? 0,
                        'reorder_level' => $this->toDecimal($this->value($row, ['Reorder Level', 'reorder_level'])) ?? 0,
                        'is_active' => $this->toBool($this->value($row, ['Is Active', 'Active', 'is_active']), true),
                    ];

                    $item = Item::withoutGlobalScopes()
                        ->where('user_id', $userId)
                        ->where('code', $code)
                        ->first();
                    if ($item) {
                        $item->update($payload);

                        return 'updated';
                    }

                    Item::withoutGlobalScopes()->create(array_merge([
                        'user_id' => $userId,
                        'code' => $code,
                        'unit_id' => $this->defaultUnitId(),
                    ], $payload));

                    return 'created';
                }
            );
        });
    }

    /**
     * Account headers supported (client sheet):
     * Code, Name, Name Ar, Account Type, Parent Account Code, Is Active, Allow Direct Posting, Opening Balance
     */
    public function importAccounts(UploadedFile $file, bool $createMissingReferences = true): array
    {
        return DB::transaction(function () use ($file, $createMissingReferences) {
            $userId = (int) (auth()->id() ?? 1);

            return $this->excelImportService->importSimple(
                $file,
                ['Code', 'Name'],
                function (array $row, int $line) use ($createMissingReferences, $userId) {
                    $code = $this->value($row, ['Code', 'code']);
                    $name = $this->value($row, ['Name', 'name']);
                    $nameAr = $this->value($row, ['Name Ar', 'name_ar']);
                    $parentCode = $this->value($row, ['Parent Account Code', 'parent_account_code']);
                    $typeRaw = $this->value($row, ['Account Type', 'type']);

                    if (! $code || ! $name) {
                        throw new \RuntimeException("حقول Code وName مطلوبة في السطر {$line}.");
                    }

                    $type = $this->normalizeAccountType($typeRaw, $createMissingReferences);
                    $parentId = $this->resolveParentAccountId($parentCode, $createMissingReferences, $userId);

                    $payload = [
                        'name_en' => $name,
                        'name_ar' => $nameAr ?: $name,
                        'type' => $type,
                        'parent_id' => $parentId,
                        'opening_balance' => $this->toDecimal($this->value($row, ['Opening Balance', 'opening_balance'])) ?? 0,
                        'is_active' => $this->toBool($this->value($row, ['Is Active', 'Active', 'is_active']), true),
                        'allow_direct_posting' => $this->toBool($this->value($row, ['Allow Direct Posting', 'allow_direct_posting']), true),
                    ];

                    $existing = DB::table('accounts')->where('code', $code)->where('user_id', $userId)->first();
                    if ($existing) {
                        DB::table('accounts')->where('id', $existing->id)->update(array_merge($payload, [
                            'updated_at' => now(),
                        ]));

                        return 'updated';
                    }

                    DB::table('accounts')->insert(array_merge(['code' => $code, 'user_id' => $userId], $payload, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));

                    return 'created';
                }
            );
        });
    }

    /**
     * Sales Orders headers:
     * Order Number, Customer Code, Order Date, Status, Product Code, Quantity, Unit Price, V A T Amount, Total Amount
     */
    public function importSalesOrders(UploadedFile $file, bool $createMissingReferences = true): array
    {
        return DB::transaction(function () use ($file, $createMissingReferences) {
            $userId = (int) (auth()->id() ?? 1);
            $ordersByNumber = [];
            $preparedOrderNumbers = [];
            $orderOps = [];
            $processedRows = 0;
            $successfulHeaders = [];

            $summary = $this->excelImportService->importSimple(
                $file,
                ['Order Number', 'Customer Code', 'Product Code'],
                function (array $row, int $line) use ($createMissingReferences, $userId, &$ordersByNumber, &$preparedOrderNumbers, &$orderOps, &$processedRows, &$successfulHeaders) {
                    $processedRows++;
                    $orderNumber = $this->value($row, ['Order Number', 'order_number']);
                    $customerCode = $this->value($row, ['Customer Code', 'customer_code']);
                    $itemCode = $this->value($row, ['Product Code', 'Item Code', 'product_code']);

                    if (! $orderNumber || ! $customerCode || ! $itemCode) {
                        throw new \RuntimeException("حقول Order Number / Customer Code / Product Code مطلوبة في السطر {$line}.");
                    }

                    $customerId = $this->resolveCustomerId($customerCode, $createMissingReferences);
                    $itemId = $this->resolveItemId($itemCode, $createMissingReferences, $userId);

                    $orderDate = $this->normalizeDateValue($this->value($row, ['Order Date', 'order_date'])) ?: now()->toDateString();
                    $statusRaw = $this->value($row, ['Status', 'status']) ?: 'معلق';
                    $status = $this->normalizeOrderStatus($statusRaw);

                    $quantity = $this->toDecimal($this->value($row, ['Quantity', 'qty'])) ?? 0;
                    $unitPrice = $this->toDecimal($this->value($row, ['Unit Price', 'unit_price'])) ?? 0;
                    $vatAmount = $this->toDecimal($this->value($row, ['V A T Amount', 'VAT Amount', 'vat_amount', 'tax_amount'])) ?? 0;
                    $lineTotal = $this->toDecimal($this->value($row, ['Total Amount', 'total_amount'])) ?? (($quantity * $unitPrice) + $vatAmount);
                    $taxPercent = ($quantity * $unitPrice) > 0 ? (($vatAmount / ($quantity * $unitPrice)) * 100) : 0;

                    if (! isset($ordersByNumber[$orderNumber])) {
                        $order = SalesOrder::withoutGlobalScopes()
                            ->where('user_id', $userId)
                            ->where('order_number', $orderNumber)
                            ->first();
                        if (! $order) {
                            $order = SalesOrder::withoutGlobalScopes()->create([
                                'user_id' => $userId,
                                'order_number' => $orderNumber,
                                'customer_id' => $customerId,
                                'order_date' => $orderDate,
                                'status' => $status,
                                'total' => 0,
                            ]);
                            $orderOps[$orderNumber] = 'created';
                        } else {
                            $order->update([
                                'customer_id' => $customerId,
                                'order_date' => $orderDate,
                                'status' => $status,
                            ]);
                            $orderOps[$orderNumber] = 'updated';
                        }

                        $ordersByNumber[$orderNumber] = $order;
                    }

                    /** @var SalesOrder $order */
                    $order = $ordersByNumber[$orderNumber];

                    // Replace existing children once per order, then append all rows from file.
                    if (! isset($preparedOrderNumbers[$orderNumber])) {
                        $order->items()->delete();
                        $preparedOrderNumbers[$orderNumber] = true;
                    }

                    $order->items()->create([
                        'item_id' => $itemId,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'tax_percent' => round($taxPercent, 4),
                        'vat_amount' => $vatAmount,
                        'line_total' => $lineTotal,
                        'total_amount' => $lineTotal,
                    ]);

                    $orderTotal = (float) $order->items()->sum('line_total');
                    $order->update(['total' => $orderTotal]);
                    $successfulHeaders[$orderNumber] = true;

                    return $orderOps[$orderNumber] ?? 'updated';
                }
            );

            $summary['total_rows_processed'] = $processedRows;
            $summary['successful_headers'] = count($successfulHeaders);

            return $summary;
        });
    }

    /**
     * Quotations headers:
     * Quotation Number, Customer Code, Quotation Date, Valid Until, Status, Product Code, Quantity, Unit Price, Discount Percent, Tax Percent, Total Amount
     */
    public function importQuotations(UploadedFile $file, bool $createMissingReferences = true): array
    {
        return DB::transaction(function () use ($file, $createMissingReferences) {
            $userId = (int) (auth()->id() ?? 1);

            return $this->excelImportService->importSimple(
                $file,
                [
                    ['Quotation Number', 'Quote Number', 'Quotation No', 'quotation_number'],
                    ['Customer Code', 'customer_code', 'Customer Name', 'Customer', 'customer_name'],
                    ['Product Code', 'Item Code', 'product_code', 'SKU', 'Sku'],
                ],
                function (array $row, int $line) use ($createMissingReferences, $userId) {
                    $quotationNumber = $this->value($row, ['Quotation Number', 'Quote Number', 'Quotation No', 'quotation_number']);
                    $customerCode = $this->value($row, ['Customer Code', 'customer_code']);
                    $customerName = $this->value($row, ['Customer Name', 'Customer', 'customer_name']);
                    $itemCode = $this->value($row, ['Product Code', 'Item Code', 'product_code', 'SKU', 'Sku']);

                    if (! $quotationNumber || ! $itemCode) {
                        throw new \RuntimeException("حقول رقم العرض (Quotation Number / Quote Number) و Product Code مطلوبة في السطر {$line}.");
                    }

                    $customerId = $this->resolveCustomerIdForQuotationImport($customerCode, $customerName, $createMissingReferences, $userId);
                    $itemId = $this->resolveItemId($itemCode, $createMissingReferences, $userId);

                    $quotationDate = $this->normalizeDateValue($this->value($row, ['Quotation Date', 'Quote Date', 'Date', 'quotation_date'])) ?: now()->toDateString();
                    $validUntil = $this->normalizeDateValue($this->value($row, ['Valid Until', 'Expiry Date', 'valid_until'])) ?: null;
                    $status = $this->normalizeQuotationStatus($this->value($row, ['Status', 'status']));

                    $quantity = $this->toDecimal($this->value($row, ['Quantity', 'qty'])) ?? 0;
                    $unitPrice = $this->toDecimal($this->value($row, ['Unit Price', 'unit_price'])) ?? 0;
                    $discountPercent = $this->toDecimal($this->value($row, ['Discount Percent', 'discount_percent'])) ?? 0;
                    $taxPercent = $this->toDecimal($this->value($row, ['Tax Percent', 'VAT Percent', 'V A T Rate', 'VAT Rate', 'Tax Rate', 'tax_percent'])) ?? 0;

                    $lineNet = $quantity * $unitPrice * (1 - ($discountPercent / 100));
                    $lineTax = $lineNet * ($taxPercent / 100);
                    $computedLineTotal = $lineNet + $lineTax;
                    $lineTotal = $this->toDecimal($this->value($row, ['Total Amount', 'line_total', 'total_amount'])) ?? $computedLineTotal;

                    $quotation = Quotation::withoutGlobalScopes()
                        ->where('user_id', $userId)
                        ->where('quotation_number', $quotationNumber)
                        ->first();
                    if (! $quotation) {
                        $quotation = Quotation::withoutGlobalScopes()->create([
                            'user_id' => $userId,
                            'quotation_number' => $quotationNumber,
                            'customer_id' => $customerId,
                            'date' => $quotationDate,
                            'valid_until' => $validUntil,
                            'status' => $status,
                            'total_amount' => 0,
                        ]);
                        $op = 'created';
                    } else {
                        $quotation->update([
                            'customer_id' => $customerId,
                            'date' => $quotationDate,
                            'valid_until' => $validUntil,
                            'status' => $status,
                        ]);
                        $op = 'updated';
                    }

                    $existingLine = $quotation->items()
                        ->where('item_id', $itemId)
                        ->where('unit_price', $unitPrice)
                        ->first();

                    $linePayload = [
                        'item_id' => $itemId,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => round($lineNet, 4),
                        'discount_percent' => round($discountPercent, 4),
                        'tax_percent' => round($taxPercent, 4),
                        'line_total' => round($lineTotal, 4),
                    ];

                    if ($existingLine) {
                        $existingLine->update($linePayload);
                    } else {
                        $quotation->items()->create($linePayload);
                    }

                    $quotation->update([
                        'total_amount' => (float) $quotation->items()->sum('line_total'),
                    ]);

                    return $op;
                },
                [
                    'header_keywords' => ['Quotation Number', 'Quote Number', 'Customer Code', 'Product Code', 'Item Code'],
                ]
            );
        });
    }

    /**
     * Purchase Orders headers:
     * Order Number, Supplier Code, Order Date, Status, Product Code, Quantity, Unit Price, V A T Amount, Total Amount
     */
    public function importPurchaseOrders(UploadedFile $file, bool $createMissingReferences = true): array
    {
        return DB::transaction(function () use ($file, $createMissingReferences) {
            $userId = (int) (auth()->id() ?? 1);
            $ordersByNumber = [];
            $preparedOrderNumbers = [];
            $orderOps = [];
            $processedRows = 0;
            $successfulHeaders = [];
            $blankAutoOrderNumber = null;

            $summary = $this->excelImportService->importSimple(
                $file,
                ['Supplier Code', 'Product Code'],
                function (array $row, int $line) use ($createMissingReferences, $userId, &$ordersByNumber, &$preparedOrderNumbers, &$orderOps, &$processedRows, &$successfulHeaders, &$blankAutoOrderNumber) {
                    $processedRows++;
                    $orderNumber = $this->value($row, ['Order Number', 'order_number']);
                    $supplierCode = $this->value($row, ['Supplier Code', 'supplier_code']);
                    $itemCode = $this->value($row, ['Product Code', 'Item Code', 'product_code']);

                    if (! $supplierCode || ! $itemCode) {
                        throw new \RuntimeException("حقول Supplier Code / Product Code مطلوبة في السطر {$line}.");
                    }

                    if (! $orderNumber) {
                        if ($blankAutoOrderNumber === null) {
                            $blankAutoOrderNumber = PurchaseOrder::generateNextOrderNumberForUser($userId);
                        }
                        $orderNumber = $blankAutoOrderNumber;
                    } else {
                        $blankAutoOrderNumber = null;
                    }

                    $supplierId = $this->resolveSupplierId($supplierCode, $createMissingReferences, $userId);
                    $itemId = $this->resolveItemId($itemCode, $createMissingReferences, $userId);

                    $orderDate = $this->normalizeDateValue($this->value($row, ['Order Date', 'order_date'])) ?: now()->toDateString();
                    $expectedDeliveryDate = $this->normalizeDateValue($this->value($row, ['Expected Delivery Date', 'Expected Delivery', 'expected_delivery_date']));
                    $statusRaw = $this->value($row, ['Status', 'status']) ?: 'معلق';
                    $status = $this->normalizeOrderStatus($statusRaw);

                    $quantity = $this->toDecimal($this->value($row, ['Quantity', 'qty'])) ?? 0;
                    $unitPrice = $this->toDecimal($this->value($row, ['Unit Price', 'unit_price'])) ?? 0;
                    $taxPercent = $this->toDecimal($this->value($row, ['VAT Rate', 'V A T Rate', 'Tax Rate', 'tax_percent', 'vat_rate']));
                    $vatAmount = $this->toDecimal($this->value($row, ['V A T Amount', 'VAT Amount', 'vat_amount', 'tax_amount']));
                    $lineTotal = $this->toDecimal($this->value($row, ['Line Total', 'Total Amount', 'line_total', 'total_amount']));

                    if ($taxPercent === null && $vatAmount !== null && ($quantity * $unitPrice) > 0) {
                        $taxPercent = ($vatAmount / ($quantity * $unitPrice)) * 100;
                    }
                    if ($taxPercent === null) {
                        $taxPercent = 0;
                    }
                    if ($vatAmount === null) {
                        $vatAmount = ($quantity * $unitPrice) * ($taxPercent / 100);
                    }
                    if ($lineTotal === null) {
                        $lineTotal = ($quantity * $unitPrice) + $vatAmount;
                    }
                    $description = $this->value($row, ['Description', 'description']);

                    if (! isset($ordersByNumber[$orderNumber])) {
                        $order = PurchaseOrder::withoutGlobalScopes()
                            ->where('user_id', $userId)
                            ->where('order_number', $orderNumber)
                            ->first();
                        if (! $order) {
                            $order = PurchaseOrder::withoutGlobalScopes()->create([
                                'user_id' => $userId,
                                'order_number' => $orderNumber,
                                'supplier_id' => $supplierId,
                                'order_date' => $orderDate,
                                'expected_delivery_date' => $expectedDeliveryDate,
                                'status' => $status,
                                'currency' => 'SAR',
                                'subtotal' => 0,
                                'total_tax' => 0,
                                'total' => 0,
                            ]);
                            $orderOps[$orderNumber] = 'created';
                        } else {
                            $order->update([
                                'supplier_id' => $supplierId,
                                'order_date' => $orderDate,
                                'expected_delivery_date' => $expectedDeliveryDate,
                                'status' => $status,
                            ]);
                            $orderOps[$orderNumber] = 'updated';
                        }

                        $ordersByNumber[$orderNumber] = $order;
                    }

                    /** @var PurchaseOrder $order */
                    $order = $ordersByNumber[$orderNumber];

                    // Replace existing children once per order, then append all rows from file.
                    if (! isset($preparedOrderNumbers[$orderNumber])) {
                        $order->items()->delete();
                        $preparedOrderNumbers[$orderNumber] = true;
                    }

                    $order->items()->create([
                        'item_id' => $itemId,
                        'description' => $description,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'tax_percent' => round($taxPercent, 4),
                        'vat_amount' => $vatAmount,
                        'line_total' => $lineTotal,
                        'total_amount' => $lineTotal,
                    ]);

                    $subtotal = (float) $order->items()->sum(DB::raw('quantity * unit_price'));
                    $tax = (float) $order->items()->sum('vat_amount');
                    $total = (float) $order->items()->sum('line_total');

                    $order->update([
                        'subtotal' => $subtotal,
                        'total_tax' => $tax,
                        'total' => $total,
                    ]);
                    $successfulHeaders[$orderNumber] = true;

                    return $orderOps[$orderNumber] ?? 'updated';
                },
                ['min_header_row_index' => 2]
            );

            $summary['total_rows_processed'] = $processedRows;
            $summary['successful_headers'] = count($successfulHeaders);

            return $summary;
        });
    }

    /**
     * Expenses: header row is auto-detected (skips junk rows): first row within scan that contains
     * a money column (Amount / Total Amount / …) AND a date column (Expense Date / Date / …).
     *
     * Headers: Amount, Total Amount, Expense Date, Category, Description (optional: Expense Number, Account Code, Tax, Status, Reference).
     */
    public function importExpenses(UploadedFile $file, bool $createMissingReferences = true): array
    {
        return DB::transaction(function () use ($file, $createMissingReferences) {
            $userId = (int) (auth()->id() ?? 1);
            $createdById = $userId > 0 ? $userId : (int) (DB::table('users')->min('id') ?? 0);
            if ($createdById <= 0) {
                throw new \RuntimeException('لا يوجد مستخدم في النظام لربط سندات المصروفات (created_by).');
            }

            $requiredHeaderGroups = [
                ['Amount', 'Total Amount', 'Net Amount', 'Subtotal', 'Grand Total', 'المبلغ', 'الإجمالي'],
                ['Expense Date', 'ExpenseDate', 'Date', 'Transaction Date', 'Posted Date', 'تاريخ المصروف', 'التاريخ', 'تاريخ'],
            ];

            return $this->excelImportService->importSimple(
                $file,
                $requiredHeaderGroups,
                function (array $row, int $line) use ($createMissingReferences, $createdById, $userId) {
                    $expenseNumber = $this->value($row, ['Expense Number', 'expense_number', 'رقم المصروف']);
                    $expenseDateRaw = $this->value($row, [
                        'Expense Date',
                        'ExpenseDate',
                        'Date',
                        'Transaction Date',
                        'Posted Date',
                        'Expense date',
                        'تاريخ المصروف',
                        'التاريخ',
                        'تاريخ',
                    ]);
                    $expenseDate = $this->normalizeDateValue($expenseDateRaw);
                    if (! $expenseDate) {
                        throw new \RuntimeException("حقل التاريخ مطلوب أو غير صالح في السطر {$line}.");
                    }

                    $taxAmount = $this->toDecimal($this->value($row, ['Tax Amount', 'tax_amount', 'VAT', 'ضريبة'])) ?? 0.0;
                    $totalAmountFromFile = $this->toDecimal($this->value($row, [
                        'Total Amount',
                        'total_amount',
                        'Grand Total',
                        'Total',
                        'الإجمالي',
                    ]));

                    $amount = $this->toDecimal($this->value($row, [
                        'Amount',
                        'amount',
                        'Net Amount',
                        'Subtotal',
                        'المبلغ',
                    ]));

                    if ($amount === null && $totalAmountFromFile !== null) {
                        $amount = max(0.0, $totalAmountFromFile - $taxAmount);
                    }

                    if ($amount === null) {
                        throw new \RuntimeException("حقل المبلغ (Amount أو Total Amount) مطلوب أو غير رقمي في السطر {$line}.");
                    }

                    $accountCode = $this->value($row, ['Account Code', 'account_code']);
                    $accountName = $this->value($row, ['Account Name', 'account_name']);
                    $expenseAccountId = $this->resolveExpenseAccountId($accountCode, $accountName, $createMissingReferences);
                    if ($expenseAccountId === null) {
                        $expenseAccountId = $this->defaultExpenseAccountIdForUser($userId, $createMissingReferences);
                    }

                    $categoryName = $this->value($row, ['Category', 'category', 'Expense Category', 'التصنيف', 'تصنيف المصروف']);
                    $expenseCategoryId = $this->resolveOrCreateExpenseCategoryId($categoryName, $userId, $createMissingReferences);

                    $description = $this->value($row, ['Description', 'description', 'الوصف']);
                    $notesColumn = $this->value($row, ['Notes', 'notes', 'ملاحظات', 'ملاحظات إضافية']);
                    $notes = $this->mergeExpenseDescriptionAndNotes($description, $notesColumn);
                    $totalAmount = $totalAmountFromFile ?? ($amount + $taxAmount);
                    $status = $this->normalizeExpenseStatus($this->value($row, ['Status', 'status']));
                    $reference = $this->value($row, ['Reference', 'reference']);

                    $payload = [
                        'user_id' => $userId,
                        'expense_account_id' => $expenseAccountId,
                        'expense_category_id' => $expenseCategoryId,
                        'date' => $expenseDate,
                        'reference' => $reference,
                        'amount' => $amount,
                        'tax_amount' => $taxAmount,
                        'total_amount' => $totalAmount,
                        'notes' => $notes,
                        'status' => $status,
                        'type' => 'expense',
                        'payment_method' => 'cash',
                        'created_by' => $createdById,
                    ];

                    if ($expenseNumber !== null && $expenseNumber !== '') {
                        $existing = Payment::withoutGlobalScopes()
                            ->where('user_id', $userId)
                            ->where('expense_number', $expenseNumber)
                            ->where('type', 'expense')
                            ->first();

                        if ($existing) {
                            $existing->update($payload);

                            return 'updated';
                        }

                        Payment::withoutGlobalScopes()->create(array_merge(['expense_number' => $expenseNumber], $payload));

                        return 'created';
                    }

                    $newNumber = Payment::generateNextExpenseNumberForUser($userId);
                    Payment::withoutGlobalScopes()->create(array_merge(['expense_number' => $newNumber], $payload));

                    return 'created';
                },
                [
                    'header_scan_limit' => 100,
                ]
            );
        });
    }

    private function value(array $row, array $names): ?string
    {
        $normalizedRows = [];
        foreach ($row as $key => $val) {
            $normalizedRows[$this->normalizeHeaderName((string) $key)] = $val;
        }

        foreach ($names as $name) {
            $normalizedName = $this->normalizeHeaderName($name);
            if (array_key_exists($normalizedName, $normalizedRows)) {
                $value = trim((string) $normalizedRows[$normalizedName]);

                return $value === '' ? null : $value;
            }
        }

        return null;
    }

    private function resolveCustomerId(string $customerCode, bool $createMissing): int
    {
        $userId = (int) (auth()->id() ?? 1);

        $customer = Customer::withoutGlobalScopes()
            ->where('code', $customerCode)
            ->where('user_id', $userId)
            ->first();
        if ($customer) {
            return (int) $customer->id;
        }

        if (! $createMissing) {
            throw new \RuntimeException("العميل بالكود {$customerCode} غير موجود.");
        }

        $customer = Customer::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => $customerCode,
            'name' => $customerCode,
            'status' => 'active',
            'is_active' => true,
        ]);

        return (int) $customer->id;
    }

    private function resolveCustomerIdForQuotationImport(?string $customerCode, ?string $customerName, bool $createMissing, int $userId): int
    {
        $code = $customerCode !== null ? trim($customerCode) : '';
        $name = $customerName !== null ? trim($customerName) : '';

        if ($code !== '') {
            return $this->resolveCustomerId($code, $createMissing);
        }

        if ($name === '') {
            throw new \RuntimeException('حقل Customer Code أو Customer Name مطلوب.');
        }

        $customer = Customer::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where(function ($q) use ($name) {
                $q->where('name', $name)->orWhere('name_ar', $name);
            })
            ->first();

        if ($customer) {
            return (int) $customer->id;
        }

        if (! $createMissing) {
            throw new \RuntimeException("العميل «{$name}» غير موجود.");
        }

        $newCode = Customer::generateNextCodeForUser($userId);
        $customer = Customer::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => $newCode,
            'name' => $name,
            'name_ar' => $name,
            'status' => 'active',
            'is_active' => true,
        ]);

        return (int) $customer->id;
    }

    private function resolveSupplierId(string $supplierCode, bool $createMissing, ?int $userId = null): int
    {
        $userId ??= (int) (auth()->id() ?? 1);

        $supplier = Supplier::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', $supplierCode)
            ->first();
        if ($supplier) {
            return (int) $supplier->id;
        }

        if (! $createMissing) {
            throw new \RuntimeException("المورد بالكود {$supplierCode} غير موجود.");
        }

        $supplier = Supplier::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => $supplierCode,
            'name' => $supplierCode,
            'is_active' => true,
        ]);

        return (int) $supplier->id;
    }

    private function resolveItemId(string $itemCode, bool $createMissing, ?int $userId = null): int
    {
        $userId ??= (int) (auth()->id() ?? 1);

        $item = Item::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', $itemCode)
            ->first();
        if ($item) {
            return (int) $item->id;
        }

        if (! $createMissing) {
            throw new \RuntimeException("الصنف بالكود {$itemCode} غير موجود.");
        }

        $item = Item::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => $itemCode,
            'name_ar' => $itemCode,
            'name_en' => $itemCode,
            'unit_id' => $this->defaultUnitId(),
            'type' => Item::TYPE_FINISHED_GOOD,
            'cost' => 0,
            'sale_price' => 0,
            'selling_price' => 0,
            'min_stock' => 0,
            'is_active' => true,
        ]);

        return (int) $item->id;
    }

    private function defaultUnitId(): int
    {
        $unitId = (int) (DB::table('units')->min('id') ?? 0);
        if ($unitId <= 0) {
            throw new \RuntimeException('لا توجد وحدات قياس في النظام. أضف وحدة قياس واحدة على الأقل قبل الاستيراد.');
        }

        return $unitId;
    }

    private function normalizeOrderStatus(?string $status): string
    {
        $v = mb_strtolower(trim((string) ($status ?? '')));

        return match ($v) {
            'completed', 'complete', 'مكتمل' => 'مكتمل',
            'cancelled', 'canceled', 'ملغي' => 'ملغي',
            default => 'معلق',
        };
    }

    private function normalizeExpenseStatus(?string $status): string
    {
        $v = mb_strtolower(trim((string) ($status ?? '')));

        return match ($v) {
            'posted', 'approved', 'paid', 'pay', 'settled', 'complete', 'completed',
            'رحل', 'معتمد', 'مدفوع', 'تم الدفع', 'مكتمل' => 'posted',
            'cancelled', 'canceled', 'ملغي' => 'cancelled',
            default => 'draft',
        };
    }

    /**
     * يجمع الوصف وعمود الملاحظات (كما في تصديرات ERP: Description + Notes).
     */
    private function mergeExpenseDescriptionAndNotes(?string $description, ?string $notes): ?string
    {
        $d = $description !== null ? trim($description) : '';
        $n = $notes !== null ? trim($notes) : '';
        if ($d === '' && $n === '') {
            return null;
        }
        if ($d === '') {
            return $n;
        }
        if ($n === '' || $n === $d) {
            return $d;
        }

        return $d."\n\n".$n;
    }

    private function normalizeQuotationStatus(?string $status): string
    {
        $v = mb_strtolower(trim((string) ($status ?? '')));

        return match ($v) {
            'approved', 'معتمد' => Quotation::STATUS_APPROVED,
            'rejected', 'مرفوض' => Quotation::STATUS_REJECTED,
            'converted', 'converted_to_order', 'محوّل' => Quotation::STATUS_CONVERTED_TO_ORDER,
            default => Quotation::STATUS_DRAFT,
        };
    }

    private function resolveExpenseAccountId(?string $accountCode, ?string $accountName, bool $createMissing): ?int
    {
        if (! $accountCode && ! $accountName) {
            return null;
        }

        $userId = (int) (auth()->id() ?? 1);
        $normalizedCode = $this->normalizeAccountCodeForLookup($accountCode);

        // Codes are unique per user across all account types; look up by code first to avoid duplicate insert
        // when the file references e.g. cash (asset) 1000 as "Account Code".
        if ($normalizedCode !== null) {
            $byCode = Account::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->where('code', $normalizedCode)
                ->first();
            if ($byCode) {
                if ($this->isUsableExpensePostingAccount($byCode)) {
                    return (int) $byCode->id;
                }
                Log::warning('Expense import skipped account code: not an expense ledger account', [
                    'user_id' => $userId,
                    'code' => $normalizedCode,
                    'account_id' => $byCode->id,
                    'type' => $byCode->type,
                ]);

                return null;
            }
        }

        $expenseQuery = Account::query()->where('type', Account::TYPE_EXPENSE);
        if ($accountName) {
            $account = (clone $expenseQuery)->where(function ($q) use ($accountName) {
                $q->where('name_ar', $accountName)->orWhere('name_en', $accountName);
            })->first();
            if ($account) {
                return (int) $account->id;
            }
        }

        if (! $createMissing) {
            throw new \RuntimeException('حساب المصروف غير موجود.');
        }

        $code = $normalizedCode ?? Account::generateNextNumericCodeForUser($userId, null);
        $name = $accountName ?: $code;
        $account = Account::query()->create([
            'user_id' => $userId,
            'code' => $code,
            'name_ar' => $name,
            'name_en' => $name,
            'type' => Account::TYPE_EXPENSE,
            'opening_balance' => 0,
            'is_active' => true,
            'allow_direct_posting' => true,
        ]);

        return (int) $account->id;
    }

    private function normalizeAccountCodeForLookup(?string $accountCode): ?string
    {
        if ($accountCode === null) {
            return null;
        }
        $trimmed = trim($accountCode);
        if ($trimmed === '') {
            return null;
        }
        if (is_numeric($trimmed)) {
            return (string) (int) (float) $trimmed;
        }

        return $trimmed;
    }

    private function isUsableExpensePostingAccount(Account $account): bool
    {
        if ($account->type !== Account::TYPE_EXPENSE) {
            return false;
        }

        return (bool) (($account->parent_id !== null || $account->allow_direct_posting)
            && ($account->is_active || $account->is_active === null));
    }

    /**
     * @param  bool  $createIfMissing  عند true يُنشأ حساب مصروف افتراضي إن لم يوجد أي حساب (مثلاً بعد استيراد من نظام يضع كود نقدي 1000 وليس حساب مصروف).
     */
    private function defaultExpenseAccountIdForUser(int $userId, bool $createIfMissing = false): int
    {
        $base = Account::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('type', Account::TYPE_EXPENSE);

        $account = (clone $base)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('parent_id')
                        ->orWhere('allow_direct_posting', true);
                })->where(function ($q) {
                    $q->where('is_active', true)
                        ->orWhereNull('is_active');
                });
            })
            ->orderBy('code')
            ->first();

        if (! $account) {
            $account = (clone $base)->orderBy('code')->orderBy('id')->first();
        }

        if ($account) {
            return (int) $account->id;
        }

        if ($createIfMissing) {
            $code = Account::generateNextNumericCodeForUser($userId, null);
            $created = Account::withoutGlobalScopes()->create([
                'user_id' => $userId,
                'code' => $code,
                'name_ar' => 'مصروفات عامة (استيراد)',
                'name_en' => 'General expenses (import)',
                'type' => Account::TYPE_EXPENSE,
                'parent_id' => null,
                'opening_balance' => 0,
                'is_active' => true,
                'allow_direct_posting' => true,
            ]);

            return (int) $created->id;
        }

        throw new \RuntimeException('لا يوجد حساب مصروف. أضف أعمدة Account Code / Account Name في الملف، أو فعّل إنشاء المراجع الناقصة، أو أنشئ حساب مصروف واحد على الأقل من المحاسبة.');
    }

    /**
     * @return int|null ExpenseCategory id, or null when category column is empty
     */
    private function resolveOrCreateExpenseCategoryId(?string $categoryName, int $userId, bool $createMissing): ?int
    {
        $name = $categoryName !== null ? trim($categoryName) : '';
        if ($name === '') {
            return null;
        }

        $category = ExpenseCategory::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where(function ($q) use ($name) {
                $q->where('name_ar', $name)
                    ->orWhere('name_en', $name)
                    ->orWhere('code', $name);
            })
            ->first();

        if ($category) {
            return (int) $category->id;
        }

        if (! $createMissing) {
            Log::info('Expense import: category not found, leaving null', ['name' => $name, 'user_id' => $userId]);

            return null;
        }

        $created = ExpenseCategory::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'code' => ExpenseCategory::generateNextCodeForUser($userId),
            'name_ar' => $name,
            'name_en' => $name,
            'parent_id' => null,
            'is_taxable' => false,
            'status' => 'active',
        ]);

        return (int) $created->id;
    }

    private function normalizeHeaderName(string $name): string
    {
        $name = preg_replace('/^\xEF\xBB\xBF/', '', $name) ?? $name;
        $name = mb_strtolower(trim($name));
        $name = str_replace("\xC2\xA0", ' ', $name); // NBSP
        $name = preg_replace('/\p{Cf}+/u', '', $name) ?? $name; // zero-width chars
        $name = preg_replace('/[^\p{L}\p{N}]+/u', '', $name) ?? $name; // keep only letters/digits

        return $name;
    }

    private function toBool(?string $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }

        $normalized = mb_strtolower(trim($value));

        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'on', 'active', 'enabled', 'نعم', 'نشط', 'مفعل'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'n', 'off', 'inactive', 'disabled', 'لا', 'غير نشط', 'معطل'], true)) {
            return false;
        }

        return $default;
    }

    private function toActiveStatus(?string $value): string
    {
        return $this->toBool($value, true) ? 'active' : 'inactive';
    }

    private function toDecimal(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = str_replace([',', ' '], ['', ''], $value);
        if ($normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private function toInt(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function normalizeDateValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim($value);
        if ($raw === '') {
            return null;
        }

        // Numeric values can be Excel serials or Unix timestamps.
        if (is_numeric($raw)) {
            $numeric = (float) $raw;

            // Unix timestamp in seconds (roughly years 2001..2100).
            if ($numeric >= 1000000000 && $numeric <= 4102444800) {
                return Carbon::createFromTimestamp((int) $numeric)->toDateString();
            }

            // Unix timestamp in milliseconds.
            if ($numeric >= 1000000000000 && $numeric <= 4102444800000) {
                return Carbon::createFromTimestamp((int) floor($numeric / 1000))->toDateString();
            }

            // Excel serial dates are day offsets from 1899-12-30.
            if ($numeric > 0 && $numeric < 600000) {
                $days = (int) floor($numeric);

                return Carbon::create(1899, 12, 30)->addDays($days)->toDateString();
            }
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveItemCategoryId(?string $categoryName, bool $createMissing): ?int
    {
        if (! $categoryName) {
            return null;
        }

        $existing = DB::table('item_categories')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($categoryName)])
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        if (! $createMissing) {
            return null;
        }

        return (int) DB::table('item_categories')->insertGetId([
            'name' => $categoryName,
            'name_ar' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function normalizeItemType(?string $rawType): string
    {
        if (! $rawType) {
            return Item::TYPE_FINISHED_GOOD;
        }

        $v = mb_strtolower(trim($rawType));

        return match (true) {
            in_array($v, ['goods', 'finished', 'finished_good', 'product', 'منتج'], true) => Item::TYPE_FINISHED_GOOD,
            in_array($v, ['raw', 'raw_material', 'material', 'مواد خام'], true) => Item::TYPE_RAW_MATERIAL,
            in_array($v, ['service', 'خدمة'], true) => Item::TYPE_SERVICE,
            default => Item::TYPE_FINISHED_GOOD,
        };
    }

    private function normalizeAccountType(?string $rawType, bool $allowCustom): string
    {
        if (! $rawType) {
            return 'expense';
        }

        $v = mb_strtolower(trim($rawType));

        $mapped = match ($v) {
            'asset', 'assets', 'اصل', 'أصل', 'اصول', 'أصول' => 'asset',
            'liability', 'liabilities', 'التزام', 'الالتزامات' => 'liability',
            'equity', 'حقوق ملكية', 'حقوق الملكية' => 'equity',
            'expense', 'expenses', 'مصروف', 'مصروفات' => 'expense',
            'revenue', 'income', 'ايراد', 'إيراد', 'ايرادات', 'إيرادات' => 'revenue',
            default => null,
        };

        if ($mapped !== null) {
            return $mapped;
        }

        if ($allowCustom) {
            return $v;
        }

        throw new \RuntimeException("نوع الحساب '{$rawType}' غير معروف.");
    }

    private function resolveParentAccountId(?string $parentCode, bool $createMissing, int $userId): ?int
    {
        if (! $parentCode) {
            return null;
        }

        $parent = DB::table('accounts')->where('code', $parentCode)->where('user_id', $userId)->first();
        if ($parent) {
            return (int) $parent->id;
        }

        if (! $createMissing) {
            return null;
        }

        return (int) DB::table('accounts')->insertGetId([
            'user_id' => $userId,
            'code' => $parentCode,
            'name_ar' => $parentCode,
            'name_en' => $parentCode,
            'type' => 'expense',
            'parent_id' => null,
            'opening_balance' => 0,
            'is_bank' => false,
            'is_active' => true,
            'allow_direct_posting' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
