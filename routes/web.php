<?php

use App\Http\Controllers\AccountingDashboardController;
use App\Http\Controllers\AccountWebController;
use App\Http\Controllers\ApAgingController;
use App\Http\Controllers\Api\ProductSearchController;
use App\Http\Controllers\ArAgingController;
use App\Http\Controllers\AuditLogWebController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BankReconciliationController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ChequeController;
use App\Http\Controllers\CommissionRuleWebController;
use App\Http\Controllers\CommissionWebController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\ContractWebController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\CustomerWebController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebitNoteController;
use App\Http\Controllers\DeliveryOrderWebController;
use App\Http\Controllers\DepartmentWebController;
use App\Http\Controllers\EinvoiceSettingsController;
use App\Http\Controllers\EmployeeWebController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FixedAssetController;
use App\Http\Controllers\HRDashboardController;
use App\Http\Controllers\InstallmentWebController;
use App\Http\Controllers\InventoryDashboardController;
use App\Http\Controllers\ItemBomController;
use App\Http\Controllers\ItemWebController;
use App\Http\Controllers\JournalEntryWebController;
use App\Http\Controllers\LedgerWebController;
use App\Http\Controllers\MachineWebController;
use App\Http\Controllers\NotificationWebController;
use App\Http\Controllers\OperationsDashboardController;
use App\Http\Controllers\OperationsShiftController;
use App\Http\Controllers\PaymentWebController;
use App\Http\Controllers\ProcurementDashboardController;
use App\Http\Controllers\ProductionEntryWebController;
use App\Http\Controllers\ProductionLineWebController;
use App\Http\Controllers\ProductionOrderWebController;
use App\Http\Controllers\ProductionReportWebController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfitLossReportWebController;
use App\Http\Controllers\PurchaseInvoiceWebController;
use App\Http\Controllers\PurchaseOrderWebController;
use App\Http\Controllers\PurchaseReportController;
use App\Http\Controllers\PurchaseReturnWebController;
use App\Http\Controllers\ReceiptWebController;
use App\Http\Controllers\ReceiveNoteWebController;
use App\Http\Controllers\SalesDashboardController;
use App\Http\Controllers\SalesInvoiceWebController;
use App\Http\Controllers\SalesOrderWebController;
use App\Http\Controllers\SalesPaymentWebController;
use App\Http\Controllers\SalesQuotationWebController;
use App\Http\Controllers\SalesReturnWebController;
use App\Http\Controllers\SalesTargetWebController;
use App\Http\Controllers\ServiceOrderWebController;
use App\Http\Controllers\ServicesDashboardController;
use App\Http\Controllers\StatementReportWebController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\SupplierWebController;
use App\Http\Controllers\TaxReportWebController;
use App\Http\Controllers\TechnicianServiceOrderController;
use App\Http\Controllers\TrialBalanceController;
use App\Http\Controllers\WarehouseWebController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

/*
|--------------------------------------------------------------------------
| System Configuration & Security
|--------------------------------------------------------------------------
*/
if (app()->environment('production')) {
    URL::forceScheme('https');
}

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Technician Access
    Route::middleware('technician_or_admin')->prefix('services/technician')->name('services.technician.')->group(function () {
        Route::get('/', [TechnicianServiceOrderController::class, 'index'])->name('index');
        Route::patch('orders/{order}', [TechnicianServiceOrderController::class, 'update'])->name('orders.update');
    });

    // Notifications & Profile
    Route::get('/notifications', [NotificationWebController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationWebController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Storage Access (Fallback for missing symlink)
|--------------------------------------------------------------------------
*/
Route::get('/storage/{path}', function (string $path) {
    $path = ltrim($path, '/');
    if (! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
        abort(404);
    }

    return \Illuminate\Support\Facades\Storage::disk('public')->response($path);
})->where('path', '.*');

/*
|--------------------------------------------------------------------------
| Admin Panel (Inventory, Production, Finance)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->group(function () {

    // Inventory & Items
    Route::get('api/products/search', [ProductSearchController::class, 'search'])->name('api.products.search');
    Route::get('items/{item}', [ItemWebController::class, 'show'])->whereNumber('item')->name('items.show');
    Route::put('items/{item}/bom', [ItemBomController::class, 'update'])->whereNumber('item')->name('items.bom.update');
    Route::resource('items', ItemWebController::class)->except('show');
    Route::post('items/import', [ItemWebController::class, 'import'])->name('items.import');
    Route::get('items/import/template', [ItemWebController::class, 'importTemplate'])->name('items.import-template');
    Route::resource('warehouses', WarehouseWebController::class);

    // Inventory Management
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryDashboardController::class, 'index'])->name('dashboard');
        Route::resource('transfers', \App\Http\Controllers\StockTransferController::class);
        Route::get('transfers/items-by-warehouse', [\App\Http\Controllers\StockTransferController::class, 'itemsByWarehouse'])->name('transfers.items-by-warehouse');
        Route::resource('adjustments', \App\Http\Controllers\StockAdjustmentController::class);
        Route::get('adjustments/items-for-adjustment', [\App\Http\Controllers\StockAdjustmentController::class, 'itemsForAdjustment'])->name('adjustments.items-for-adjustment');
        Route::resource('stock-in', StockInController::class);
        Route::resource('audits', \App\Http\Controllers\InventoryAuditController::class);
        Route::post('audits/{audit}/approve', [\App\Http\Controllers\InventoryAuditController::class, 'approve'])->name('audits.approve');
        Route::get('movements', [\App\Http\Controllers\StockMovementController::class, 'index'])->name('movements.index');
        Route::resource('price-lists', \App\Http\Controllers\PriceListController::class);
    });

    // Production Line & Orders
    Route::resource('production-lines', ProductionLineWebController::class);
    Route::resource('machines', MachineWebController::class);
    Route::prefix('production-orders')->name('production-orders.')->group(function () {
        Route::get('/', [ProductionOrderWebController::class, 'index'])->name('index');
        Route::get('/create', [ProductionOrderWebController::class, 'create'])->name('create');
        Route::get('/bom-suggestions/{item}', [ProductionOrderWebController::class, 'bomSuggestions'])->name('bom-suggestions');
        Route::post('/', [ProductionOrderWebController::class, 'store'])->name('store');
        Route::get('/{production_order}/ingredient-shortage', [ProductionOrderWebController::class, 'ingredientShortage'])->name('ingredient-shortage');
        Route::post('/{production_order}/prefill-purchase', [ProductionOrderWebController::class, 'prefillPurchaseOrder'])->name('prefill-purchase');
        Route::post('/{production_order}/complete', [ProductionOrderWebController::class, 'complete'])->name('complete');
        Route::get('/{production_order}', [ProductionOrderWebController::class, 'show'])->name('show');
    });

    // Finance (Start of the module)
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', fn () => redirect()->route('finance.dashboard'))->name('index');
        Route::get('dashboard', [AccountingDashboardController::class, 'index'])->name('dashboard');
        Route::get('accounts', [AccountWebController::class, 'index'])->name('accounts.index');
        Route::get('accounts/import/template', [AccountWebController::class, 'importTemplate'])->name('accounts.import-template');
        Route::post('accounts/import', [AccountWebController::class, 'import'])->name('accounts.import');
        Route::get('accounts/create', [AccountWebController::class, 'create'])->name('accounts.create');
        Route::post('accounts', [AccountWebController::class, 'store'])->name('accounts.store');
        Route::put('accounts/{account}', [AccountWebController::class, 'update'])->name('accounts.update');
        Route::delete('accounts/{account}', [AccountWebController::class, 'destroy'])->name('accounts.destroy');
        Route::patch('accounts/{account}/toggle-active', [AccountWebController::class, 'toggleActive'])->name('accounts.toggle-active');
        // القيود والدفاتر
        Route::resource('journals', JournalEntryWebController::class);
        Route::get('ledger', [LedgerWebController::class, 'index'])->name('ledger.index');

        // سندات القبض والصرف
        Route::resource('receipts', ReceiptWebController::class)->only(['index', 'create', 'store']);
        Route::get('payments/supplier-purchase-invoices', [PaymentWebController::class, 'supplierPurchaseInvoices'])->name('payments.supplier-purchase-invoices');
        Route::resource('payments', PaymentWebController::class)->only(['index', 'create', 'store']);

        // الشيكات
        Route::prefix('cheques')->name('cheques.')->group(function () {
            Route::get('/', [ChequeController::class, 'index'])->name('index');
            Route::get('create/incoming', [ChequeController::class, 'createIncoming'])->name('create-incoming');
            Route::get('create/outgoing', [ChequeController::class, 'createOutgoing'])->name('create-outgoing');
            Route::post('/', [ChequeController::class, 'store'])->name('store');
            Route::resource('/', ChequeController::class)->except(['index', 'store', 'create']);
        });

        // موديول المصروفات (تم التحديث لدعم الطباعة والرفع)
        Route::get('expenses/{expense}/print', [ExpenseController::class, 'print'])->name('expenses.print');
        Route::get('expenses/{expense}/pdf', [ExpenseController::class, 'pdf'])->name('expenses.pdf');
        Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
        Route::get('expenses/import/template', [ExpenseController::class, 'importTemplate'])->name('expenses.import-template');
        Route::post('expenses/import', [ExpenseController::class, 'import'])->name('expenses.import');
        // يجب تسجيله قبل resource('expenses') وإلا يُفسَّر «categories» كمعرّف مصروف ويُستدعى show غير الموجود
        Route::resource('expenses/categories', ExpenseCategoryController::class)->names('expenses.categories');
        Route::resource('expenses', ExpenseController::class);

        // الأصول الثابتة، مراكز التكلفة، والحسابات البنكية
        Route::resource('fixed-assets', FixedAssetController::class);
        Route::resource('cost-centers', CostCenterController::class);
        Route::resource('bank-accounts', BankAccountController::class);

        // إشعارات الخصم والإضافة
        Route::post('credit-notes/{creditNote}/approve', [CreditNoteController::class, 'approve'])->name('credit-notes.approve');
        Route::post('credit-notes/{creditNote}/cancel', [CreditNoteController::class, 'cancel'])->name('credit-notes.cancel');
        Route::resource('credit-notes', CreditNoteController::class);

        Route::post('debit-notes/{debitNote}/approve', [DebitNoteController::class, 'approve'])->name('debit-notes.approve');
        Route::post('debit-notes/{debitNote}/cancel', [DebitNoteController::class, 'cancel'])->name('debit-notes.cancel');
        Route::resource('debit-notes', DebitNoteController::class);

        // الموازنات والتقارير المالية
        Route::post('budgets/{budget}/activate', [BudgetController::class, 'activate'])->name('budgets.activate');
        Route::post('budgets/{budget}/close', [BudgetController::class, 'close'])->name('budgets.close');
        Route::post('budgets/{budget}/archive', [BudgetController::class, 'archive'])->name('budgets.archive');
        Route::get('budgets/{budget}/export', [BudgetController::class, 'export'])->name('budgets.export');
        Route::resource('budgets', BudgetController::class);
        Route::get('bank-reconciliations', [BankReconciliationController::class, 'index'])->name('bank-reconciliations.index');
        Route::get('bank-reconciliations/create', [BankReconciliationController::class, 'create'])->name('bank-reconciliations.create');
        Route::post('bank-reconciliations', [BankReconciliationController::class, 'store'])->name('bank-reconciliations.store');
        Route::get('reports/trial-balance', [TrialBalanceController::class, 'index'])->name('reports.trial-balance');
        Route::get('reports/ar-aging', [ArAgingController::class, 'index'])->name('reports.ar-aging');
        Route::get('reports/ap-aging', [ApAgingController::class, 'index'])->name('reports.ap-aging');
        Route::get('reports/profit-loss', [ProfitLossReportWebController::class, 'index'])->name('reports.profit-loss');
    });

    // الموارد البشرية (HR)
    Route::prefix('hr')->name('hr.')->group(function () {
        Route::redirect('/', '/hr/dashboard');
        Route::get('dashboard', [HRDashboardController::class, 'index'])->name('dashboard');
        Route::resource('departments', DepartmentWebController::class)->except(['show']);
        Route::resource('employees', EmployeeWebController::class)->except(['show']);
    });

    // المشتريات (Purchases)
    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/', [ProcurementDashboardController::class, 'index'])->name('dashboard');
        Route::get('suppliers/{supplier}/documents/{document}/download', [SupplierWebController::class, 'downloadDocument'])->name('suppliers.documents.download');
        Route::get('suppliers/import/template', [SupplierWebController::class, 'importTemplate'])->name('suppliers.import-template');
        Route::post('suppliers/import', [SupplierWebController::class, 'import'])->name('suppliers.import');
        Route::resource('suppliers', SupplierWebController::class);
        Route::get('orders', [PurchaseOrderWebController::class, 'index'])->name('orders.index');
        Route::get('orders/import/template', [PurchaseOrderWebController::class, 'importTemplate'])->name('orders.import-template');
        Route::post('orders/import', [PurchaseOrderWebController::class, 'import'])->name('orders.import');
        Route::get('orders/create', [PurchaseOrderWebController::class, 'create'])->name('orders.create');
        Route::post('orders', [PurchaseOrderWebController::class, 'store'])->name('orders.store');
        Route::get('orders/{order}/edit', [PurchaseOrderWebController::class, 'edit'])->name('orders.edit');
        Route::put('orders/{order}', [PurchaseOrderWebController::class, 'update'])->name('orders.update');
        Route::delete('orders/{order}', [PurchaseOrderWebController::class, 'destroy'])->name('orders.destroy');
        Route::get('orders/{order}', [PurchaseOrderWebController::class, 'show'])->name('orders.show');
        Route::get('receive-notes', [ReceiveNoteWebController::class, 'index'])->name('receive-notes.index');
        Route::get('receive-notes/create', [ReceiveNoteWebController::class, 'create'])->name('receive-notes.create');
        Route::post('receive-notes', [ReceiveNoteWebController::class, 'store'])->name('receive-notes.store');
        Route::get('receive-notes/import/template', [ReceiveNoteWebController::class, 'importTemplate'])->name('receive-notes.import-template');
        Route::post('receive-notes/import', [ReceiveNoteWebController::class, 'import'])->name('receive-notes.import');
        Route::get('invoices/import/template', [PurchaseInvoiceWebController::class, 'importTemplate'])->name('invoices.import-template');
        Route::post('invoices/import', [PurchaseInvoiceWebController::class, 'import'])->name('invoices.import');
        Route::resource('invoices', PurchaseInvoiceWebController::class)->only(['index', 'create', 'store']);
        Route::get('returns/invoices-by-supplier', [PurchaseReturnWebController::class, 'invoicesBySupplier'])->name('returns.invoices-by-supplier');
        Route::get('returns/invoice-items/{invoice}', [PurchaseReturnWebController::class, 'invoiceItems'])->name('returns.invoice-items');
        Route::resource('returns', PurchaseReturnWebController::class)->only(['index', 'create', 'store'])->names('returns');
        Route::get('reports', [PurchaseReportController::class, 'index'])->name('reports.index');
    });

    // المبيعات (Sales)
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SalesDashboardController::class, 'index'])->name('dashboard');
        Route::get('customers/import/template', [CustomerWebController::class, 'importTemplate'])->name('customers.import-template');
        Route::post('customers/import', [CustomerWebController::class, 'import'])->name('customers.import');
        Route::resource('customers', CustomerWebController::class);
        Route::get('invoices/import/template', [SalesInvoiceWebController::class, 'importTemplate'])->name('invoices.import-template');
        Route::post('invoices/import', [SalesInvoiceWebController::class, 'import'])->name('invoices.import');
        Route::get('orders', [SalesOrderWebController::class, 'index'])->name('orders.index');
        Route::get('orders/import/template', [SalesOrderWebController::class, 'importTemplate'])->name('orders.import-template');
        Route::post('orders/import', [SalesOrderWebController::class, 'import'])->name('orders.import');
        Route::get('orders/create', [SalesOrderWebController::class, 'create'])->name('orders.create');
        Route::post('orders', [SalesOrderWebController::class, 'store'])->name('orders.store');
        Route::get('orders/{sales_order}', [SalesOrderWebController::class, 'show'])->name('orders.show');
        Route::get('orders/{sales_order}/print', [SalesOrderWebController::class, 'print'])->name('orders.print');
        Route::get('orders/{sales_order}/delivery-orders/create', [DeliveryOrderWebController::class, 'create'])->name('orders.delivery-orders.create');
        Route::post('orders/{sales_order}/delivery-orders', [DeliveryOrderWebController::class, 'store'])->name('orders.delivery-orders.store');
        Route::get('delivery-orders', [DeliveryOrderWebController::class, 'index'])->name('delivery-orders.index');
        Route::get('delivery-orders/{delivery_order}', [DeliveryOrderWebController::class, 'show'])->name('delivery-orders.show');
        Route::post('delivery-orders/{delivery_order}/deliver', [DeliveryOrderWebController::class, 'deliver'])->name('delivery-orders.deliver');
        Route::get('quotations', [SalesQuotationWebController::class, 'index'])->name('quotations.index');
        Route::get('quotations/import/template', [SalesQuotationWebController::class, 'importTemplate'])->name('quotations.import-template');
        Route::post('quotations/import', [SalesQuotationWebController::class, 'import'])->name('quotations.import');
        Route::get('quotations/create', [SalesQuotationWebController::class, 'create'])->name('quotations.create');
        Route::post('quotations', [SalesQuotationWebController::class, 'store'])->name('quotations.store');
        Route::get('quotations/{quotation}/edit', [SalesQuotationWebController::class, 'edit'])->name('quotations.edit');
        Route::put('quotations/{quotation}', [SalesQuotationWebController::class, 'update'])->name('quotations.update');
        Route::delete('quotations/{quotation}', [SalesQuotationWebController::class, 'destroy'])->name('quotations.destroy');
        Route::post('quotations/{quotation}/approve', [SalesQuotationWebController::class, 'approve'])->name('quotations.approve');
        Route::get('quotations/{quotation}/convert-to-order', [SalesQuotationWebController::class, 'convertToOrder'])->name('quotations.convert-to-order');
        Route::get('quotations/{quotation}/for-order', [SalesQuotationWebController::class, 'forOrder'])->name('quotations.for-order');
        Route::get('quotations/{quotation}/print', [SalesQuotationWebController::class, 'print'])->name('quotations.print');
        Route::get('quotations/{quotation}/pdf', [SalesQuotationWebController::class, 'pdf'])->name('quotations.pdf');
        Route::get('invoices/{invoice}/print', [SalesInvoiceWebController::class, 'print'])->name('invoices.print');
        Route::resource('invoices', SalesInvoiceWebController::class)->only(['index', 'create', 'store']);
        Route::get('payments/customer-outstanding', [SalesPaymentWebController::class, 'customerOutstanding'])->name('payments.customer-outstanding');
        Route::resource('payments', SalesPaymentWebController::class)->only(['index', 'create', 'store']);
        Route::get('returns/invoices-by-customer', [SalesReturnWebController::class, 'invoicesByCustomer'])->name('returns.invoices-by-customer');
        Route::get('returns/invoice-items/{invoice}', [SalesReturnWebController::class, 'invoiceItems'])->name('returns.invoice-items');
        Route::resource('returns', SalesReturnWebController::class)->only(['index', 'create', 'store'])->names('returns');
        Route::get('installments/invoices-for-customer', [InstallmentWebController::class, 'invoicesForCustomer'])->name('installments.invoices-for-customer');
        Route::post('installments/{installment}/send-reminder', [InstallmentWebController::class, 'sendReminder'])->name('installments.send-reminder');
        Route::resource('installments', InstallmentWebController::class)->only(['index', 'create', 'store'])->names('installments');
        Route::resource('targets', SalesTargetWebController::class)->only(['index', 'create', 'store'])->names('targets');
        Route::resource('contracts', ContractWebController::class)->only(['index', 'create', 'store'])->names('contracts');
        Route::get('einvoice/settings', [EinvoiceSettingsController::class, 'edit'])->name('einvoice.settings.edit');
        Route::put('einvoice/settings', [EinvoiceSettingsController::class, 'update'])->name('einvoice.settings.update');
        Route::post('einvoice/settings/onboarding', [EinvoiceSettingsController::class, 'completeOnboarding'])->name('einvoice.settings.onboarding');
        Route::post('commissions/calculate', [CommissionWebController::class, 'calculate'])->name('commissions.calculate');
        Route::get('commissions/rules', [CommissionRuleWebController::class, 'index'])->name('commissions.rules.index');
        Route::post('commissions/rules', [CommissionRuleWebController::class, 'store'])->name('commissions.rules.store');
        Route::resource('commissions', CommissionWebController::class)->only(['index'])->names('commissions');
    });

    // الخدمات والصيانة (لوحة + طلبات الخدمة)
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('dashboard', [ServicesDashboardController::class, 'index'])->name('dashboard');
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [ServiceOrderWebController::class, 'index'])->name('index');
            Route::get('create', [ServiceOrderWebController::class, 'create'])->name('create');
            Route::post('/', [ServiceOrderWebController::class, 'store'])->name('store');
            Route::get('{order}', [ServiceOrderWebController::class, 'show'])->name('show');
            Route::post('{order}/assign', [ServiceOrderWebController::class, 'assign'])->name('assign');
            Route::post('{order}/parts', [ServiceOrderWebController::class, 'addPart'])->name('parts.store');
            Route::post('{order}/complete', [ServiceOrderWebController::class, 'complete'])->name('complete');
            Route::post('{order}/cancel', [ServiceOrderWebController::class, 'cancel'])->name('cancel');
        });
    });

    // إعدادات المنشأة والسجلات
    Route::get('settings/company', [CompanySettingsController::class, 'edit'])->name('settings.company.edit');
    Route::put('settings/company', [CompanySettingsController::class, 'update'])->name('settings.company.update');
    Route::get('system/audit-log', [AuditLogWebController::class, 'index'])->name('system.audit.index');

    Route::get('reports/profit-loss', fn () => redirect()->route('finance.reports.profit-loss'));
    Route::get('reports/statement', [StatementReportWebController::class, 'index'])->name('reports.statement.index');
    Route::get('reports/tax', [TaxReportWebController::class, 'index'])->name('reports.tax.index');
    Route::get('reports/production/{record}', [ProductionReportWebController::class, 'show'])->name('reports.production.show');
    Route::get('reports/production', [ProductionReportWebController::class, 'index'])->name('reports.production.index');
});

/*
|--------------------------------------------------------------------------
| موديول العمليات (Operations)
|--------------------------------------------------------------------------
*/
Route::prefix('operations')->name('operations.')->middleware(['auth'])->group(function () {
    Route::get('dashboard', [OperationsDashboardController::class, 'index'])->name('dashboard.index');
    Route::get('production-entry', [ProductionEntryWebController::class, 'create'])->name('production-entry.create');
    Route::post('production-entry', [ProductionEntryWebController::class, 'store'])->name('production-entry.store');
    Route::get('production-entry/item-by-barcode', [ProductionEntryWebController::class, 'itemByBarcode'])->name('production-entry.item-by-barcode');

    Route::middleware('role:supervisor')->group(function () {
        Route::get('shifts', [OperationsShiftController::class, 'index'])->name('shifts.index');
        Route::post('shifts', [OperationsShiftController::class, 'store'])->name('shifts.store');
        Route::post('shifts/{productionShift}/start', [OperationsShiftController::class, 'start'])->name('shifts.start');
        Route::post('shifts/{productionShift}/complete', [OperationsShiftController::class, 'complete'])->name('shifts.complete');
    });
});

/*
|--------------------------------------------------------------------------
| TEMPORARY — إزالة بعد التشغيل: تشغيل الهجرات وتفريغ الكاش (بدون مصادقة = خطر أمني)
|--------------------------------------------------------------------------
*/
Route::get('/force-deploy', function () {
    Artisan::call('migrate', ['--force' => true]);
    Artisan::call('config:clear');
    Artisan::call('view:clear');

    return 'Success! System Updated.';
});

require __DIR__.'/auth.php';
