<?php

use App\Models\Commission;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\Account;
use App\Models\User;
use App\Notifications\ContractReminderNotification;
use App\Notifications\InstallmentDueNotification;
use App\Notifications\PendingCommissionsNotification;
use App\Services\FinancialSuperPurgeService;
use App\Services\UniversalImportService;
use App\Services\ZatcaService;
use App\Support\AgentDebugLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('financial:purge-user {userId : معرّف المستخدم (مستأجر) في جدول users} {--force : تنفيذ دون سؤال تأكيد}', function () {
    $userId = (int) $this->argument('userId');
    if ($userId < 2) {
        $this->error('لا يُسمح بمسح بيانات المستخدم 1 (مالك النظام).');

        return 1;
    }

    if (! $this->option('force')) {
        if (! $this->confirm("سيتم مسح كل المدفوعات والقيود وحسابات الدليل للمستخدم {$userId}. المتابعة؟", false)) {
            return 1;
        }
    }

    /** @var FinancialSuperPurgeService $purge */
    $purge = app(FinancialSuperPurgeService::class);
    $stats = $purge->purge($userId);

    $this->info('تم التنفيذ.');
    $this->table(array_keys($stats), [array_map('strval', array_values($stats))]);

    return 0;
})->purpose('مسح تجريبي شامل: مدفوعات + قيود + دليل حسابات مستخدم (PostgreSQL/MySQL) — للصيانة أو إعادة التجربة');

Artisan::command('zatca:generate-csr', function () {
    try {
        /** @var ZatcaService $zatca */
        $zatca = app(ZatcaService::class);
        $setting = $zatca->generateAndStoreCsrForEinvoiceSettings();

        $csrPath = $setting->csr_path;
        if ($csrPath === null || $csrPath === '') {
            $this->error('لم يُسجَّل مسار CSR بعد التوليد.');

            return 1;
        }

        $content = Storage::disk('local')->get($csrPath);
        if ($content === false || $content === '') {
            $this->error('تعذّر قراءة ملف CSR من التخزين المحلي: '.$csrPath);

            return 1;
        }

        $this->info('تم حفظ CSR والمفتاح الخاص.');
        $this->line('المسار: '.$csrPath);
        $this->newLine();
        $this->comment('--- بداية محتوى CSR (انسخ ما بين الخطوط) ---');
        $this->line($content);
        $this->comment('--- نهاية محتوى CSR ---');

        return 0;
    } catch (\Throwable $e) {
        $this->error($e->getMessage());

        return 1;
    }
})->purpose('Generate ZATCA CSR via ZatcaService and print the CSR PEM for easy copy');

Artisan::command('contracts:create-draft-invoices', function () {
    $today = now()->toDateString();
    $contracts = Contract::where('status', 'active')
        ->whereNotNull('next_invoice_date')
        ->whereDate('next_invoice_date', '<=', $today)
        ->with('items')
        ->get();
    $count = 0;
    foreach ($contracts as $contract) {
        try {
            if ($contract->createDraftInvoice()) {
                $count++;
            }
        } catch (\Throwable $e) {
            $this->error("Contract {$contract->id}: ".$e->getMessage());
        }
    }
    $this->info("Created {$count} draft invoice(s) from contracts.");
})->purpose('Create draft sales invoices for contracts due today');

Artisan::command('system:scan-notifications', function () {
    $admins = User::where('role', 'admin')->get();
    if ($admins->isEmpty()) {
        $this->info('No admin users to notify.');

        return;
    }

    $commissionCount = Commission::where('status', 'pending_approval')->count();
    $activeContracts = Contract::where('status', 'active')->whereNotNull('end_date')->get();
    $contractsDueCount = $activeContracts->filter(fn ($c) => $c->isDueForReminder())->count();
    $overdueInstallmentsAmount = Installment::where('due_date', '<=', now()->startOfDay())
        ->whereColumn('paid_amount', '<', 'amount')
        ->get()
        ->sum(fn ($i) => (float) $i->amount - (float) $i->paid_amount);

    foreach ($admins as $admin) {
        DatabaseNotification::where('notifiable_type', User::class)
            ->where('notifiable_id', $admin->id)
            ->whereIn('type', [
                PendingCommissionsNotification::class,
                ContractReminderNotification::class,
                InstallmentDueNotification::class,
            ])->delete();

        if ($commissionCount > 0) {
            $admin->notify(new PendingCommissionsNotification($commissionCount));
        }
        if ($contractsDueCount > 0) {
            $admin->notify(new ContractReminderNotification($contractsDueCount));
        }
        if ($overdueInstallmentsAmount > 0) {
            $admin->notify(new InstallmentDueNotification($overdueInstallmentsAmount));
        }
    }

    $this->info('System notifications scanned and created successfully.');
})->purpose('Scan system state and create actionable notifications');

Artisan::command('db:show-sanitized', function () {
    $url = (string) config('database.connections.pgsql.url');
    $parsed = $url !== '' ? parse_url($url) : false;
    $payload = [
        'hypothesisId' => 'H_DB_RESOLVED',
        'bootstrap_config_cached' => file_exists(base_path('bootstrap/cache/config.php')),
        'has_env_DATABASE_URL' => (bool) getenv('DATABASE_URL'),
        'has_env_DB_URL' => (bool) getenv('DB_URL'),
        'config_pgsql_url_non_empty' => $url !== '',
        'host' => is_array($parsed) ? ($parsed['host'] ?? null) : null,
        'port' => is_array($parsed) && isset($parsed['port']) ? (int) $parsed['port'] : null,
        'user' => is_array($parsed) ? ($parsed['user'] ?? null) : null,
        'password_in_url' => is_array($parsed) && isset($parsed['pass']) && $parsed['pass'] !== '',
        'database' => is_array($parsed) && isset($parsed['path']) ? ltrim((string) $parsed['path'], '/') : null,
        'default_connection' => config('database.default'),
    ];
    $this->line(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    AgentDebugLog::line('H_DB_RESOLVED', 'routes/console.php:db:show-sanitized', 'sanitized_db_config', $payload);
})->purpose('Debug: show sanitized DB connection info (session 8193d6)');

Artisan::command('import:universal {entity} {file} {--no-create-missing}', function () {
    $entity = (string) $this->argument('entity');
    $filePath = (string) $this->argument('file');
    $createMissing = ! (bool) $this->option('no-create-missing');

    if (! file_exists($filePath)) {
        $this->error("الملف غير موجود: {$filePath}");

        return 1;
    }

    $file = new SymfonyUploadedFile(
        $filePath,
        basename($filePath),
        null,
        null,
        true
    );

    try {
        /** @var UniversalImportService $service */
        $service = app(UniversalImportService::class);
        $summary = $service->import($file, $entity, [
            'create_missing_references' => $createMissing,
        ]);
    } catch (\Throwable $e) {
        $this->error($e->getMessage());

        return 1;
    }

    $this->info("Import done. Created: {$summary['created']} | Updated: {$summary['updated']} | Failed: {$summary['failed']}");
    if (! empty($summary['errors'])) {
        foreach ($summary['errors'] as $err) {
            $line = $err['line'] ?? '-';
            $reason = $err['reason'] ?? 'Unknown error';
            $this->warn("Line {$line}: {$reason}");
        }
    }

    return 0;
})->purpose('Universal import for customers/products/accounts/sales_orders/purchase_orders/expenses');

Artisan::command('demo:cleanup {--execute : Execute deletion (default is dry-run)}', function () {
    $connection = config('database.default');
    $schema = (string) (config("database.connections.{$connection}.schema") ?: 'public');
    $timestamp = now()->format('Ymd_His');
    $backupPath = storage_path("backups/pre_demo_cleanup_{$timestamp}.json");

    if (! is_dir(dirname($backupPath))) {
        mkdir(dirname($backupPath), 0755, true);
    }

    $tables = collect(DB::select(
        'SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_type = ? ORDER BY table_name',
        [$schema, 'BASE TABLE']
    ))->pluck('table_name')->all();

    $backupPayload = [
        'generated_at' => now()->toIso8601String(),
        'connection' => $connection,
        'schema' => $schema,
        'tables' => [],
    ];

    foreach ($tables as $table) {
        $backupPayload['tables'][$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
    }

    file_put_contents(
        $backupPath,
        json_encode($backupPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    $this->info('Backup saved: '.$backupPath);

    // Strict filter only for these keywords and only in code/name/name_ar.
    $keywords = ['demo', 'test', 'تجريبي', 'اختبار'];

    $tableColumns = function (string $table) use ($schema): array {
        return collect(DB::select(
            'SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ?',
            [$schema, $table]
        ))->pluck('column_name')->all();
    };
    $tableExists = fn (string $table): bool => $tableColumns($table) !== [];
    $pickColumns = fn (array $columns): array => array_values(array_intersect($columns, ['code', 'name', 'name_ar']));

    $applyStrictDemoFilter = function ($query, array $columns) use ($keywords) {
        $query->where(function ($outer) use ($columns, $keywords) {
            foreach ($columns as $column) {
                $outer->orWhere(function ($w) use ($column, $keywords) {
                    foreach ($keywords as $keyword) {
                        $w->orWhereRaw(
                            'LOWER(COALESCE(CAST("'.$column.'" AS TEXT), \'\')) LIKE ?',
                            ['%'.Str::lower($keyword).'%']
                        );
                    }
                });
            }
        });
    };

    $collectDemoIds = function (string $table) use ($tableExists, $tableColumns, $pickColumns, $applyStrictDemoFilter) {
        if (! $tableExists($table)) {
            return collect();
        }
        $columns = $pickColumns($tableColumns($table));
        if ($columns === []) {
            return collect();
        }

        $query = DB::table($table);
        $applyStrictDemoFilter($query, $columns);

        return $query->pluck('id');
    };

    $customerIds = $collectDemoIds('customers');
    $supplierIds = $collectDemoIds('suppliers');
    $itemIds = $collectDemoIds('items');

    $rootIdSets = [
        'customer_id' => $customerIds->all(),
        'supplier_id' => $supplierIds->all(),
        'item_id' => $itemIds->all(),
    ];

    $entityLinkedTables = [];
    foreach (DB::select(
        'SELECT table_name, column_name
         FROM information_schema.columns
         WHERE table_schema = ? AND column_name IN (?, ?, ?)
         ORDER BY table_name, column_name',
        [$schema, 'customer_id', 'supplier_id', 'item_id']
    ) as $row) {
        $table = $row->table_name;
        $column = $row->column_name;
        if (in_array($table, ['customers', 'suppliers', 'items', 'accounts'], true)) {
            continue;
        }
        if (($rootIdSets[$column] ?? []) === []) {
            continue;
        }
        $entityLinkedTables[$table] ??= [];
        if (! in_array($column, $entityLinkedTables[$table], true)) {
            $entityLinkedTables[$table][] = $column;
        }
    }

    $tableIds = [];
    $summary = [
        'customers' => $customerIds->count(),
        'suppliers' => $supplierIds->count(),
        'items' => $itemIds->count(),
    ];

    foreach ($entityLinkedTables as $table => $columns) {
        $idColumnExists = in_array('id', $tableColumns($table), true);
        $query = DB::table($table)->where(function ($q) use ($columns, $rootIdSets) {
            foreach ($columns as $index => $column) {
                $ids = $rootIdSets[$column] ?? [];
                if ($ids === []) {
                    continue;
                }
                if ($index === 0) {
                    $q->whereIn($column, $ids);
                } else {
                    $q->orWhereIn($column, $ids);
                }
            }
        });

        $count = (clone $query)->count();
        $summary[$table] = $count;

        if ($idColumnExists && $count > 0) {
            $tableIds[$table] = (clone $query)->pluck('id')->all();
        }
    }

    $demoJournalEntryIds = collect();
    foreach ($tableIds as $table => $ids) {
        if ($ids === []) {
            continue;
        }
        $columns = $tableColumns($table);
        if (! in_array('journal_entry_id', $columns, true)) {
            continue;
        }
        $idsFound = DB::table($table)
            ->whereIn('id', $ids)
            ->whereNotNull('journal_entry_id')
            ->pluck('journal_entry_id');
        $demoJournalEntryIds = $demoJournalEntryIds->merge($idsFound);
    }
    $demoJournalEntryIds = $demoJournalEntryIds->unique()->values();

    if ($tableExists('journal_entries')) {
        $summary['journal_entries'] = $demoJournalEntryIds->count();
    }

    $impactedAccountIds = collect();
    if ($tableExists('journal_items') && $demoJournalEntryIds->isNotEmpty()) {
        $summary['journal_items'] = DB::table('journal_items')
            ->whereIn('journal_entry_id', $demoJournalEntryIds->all())
            ->count();

        $impactedAccountIds = DB::table('journal_items')
            ->whereIn('journal_entry_id', $demoJournalEntryIds->all())
            ->pluck('account_id')
            ->unique()
            ->values();
    } else {
        $summary['journal_items'] = 0;
    }

    if ($tableExists('ledger')) {
        $ledgerColumns = $tableColumns('ledger');
        if (in_array('journal_entry_id', $ledgerColumns, true) && $demoJournalEntryIds->isNotEmpty()) {
            $summary['ledger'] = DB::table('ledger')->whereIn('journal_entry_id', $demoJournalEntryIds->all())->count();
        } else {
            $summary['ledger'] = 0;
        }
    }

    // BOM links for demo items (not covered by generic *_id scan because columns are finished_item_id/component_item_id)
    $bomComponentIds = collect();
    if ($tableExists('item_bom_components') && $itemIds->isNotEmpty()) {
        $bomColumns = $tableColumns('item_bom_components');
        $bomQuery = DB::table('item_bom_components')->where(function ($q) use ($itemIds, $bomColumns) {
            if (in_array('finished_item_id', $bomColumns, true)) {
                $q->whereIn('finished_item_id', $itemIds->all());
            }
            if (in_array('component_item_id', $bomColumns, true)) {
                $q->orWhereIn('component_item_id', $itemIds->all());
            }
        });
        $summary['item_bom_components'] = (clone $bomQuery)->count();
        if (in_array('id', $bomColumns, true) && ($summary['item_bom_components'] ?? 0) > 0) {
            $bomComponentIds = (clone $bomQuery)->pluck('id');
        }
    } else {
        $summary['item_bom_components'] = 0;
    }

    $financialImpact = 0.0;
    if ($tableExists('sales_invoices') && $customerIds->isNotEmpty()) {
        $salesCols = $tableColumns('sales_invoices');
        $amountCol = in_array('total', $salesCols, true) ? 'total' : (in_array('grand_total', $salesCols, true) ? 'grand_total' : null);
        if ($amountCol !== null) {
            $financialImpact += (float) DB::table('sales_invoices')
                ->whereIn('customer_id', $customerIds->all())
                ->sum($amountCol);
        }
    }
    if ($tableExists('purchase_invoices') && $supplierIds->isNotEmpty()) {
        $purchaseCols = $tableColumns('purchase_invoices');
        $amountCol = in_array('total', $purchaseCols, true) ? 'total' : (in_array('grand_total', $purchaseCols, true) ? 'grand_total' : null);
        if ($amountCol !== null) {
            $financialImpact += (float) DB::table('purchase_invoices')
                ->whereIn('supplier_id', $supplierIds->all())
                ->sum($amountCol);
        }
    }

    $rows = collect($summary)->sortKeys()->map(fn ($count, $table) => [$table, (int) $count])->values()->all();

    $this->newLine();
    $this->info('Strict demo/test cleanup dry-run summary:');
    $this->table(['Table Name', 'Number of Demo Records to be Deleted'], $rows);
    $this->line('Total financial impact (demo invoices to purge): '.number_format($financialImpact, 2));
    $this->line('Accounts table: NOT deleted. Structure and codes remain intact.');

    if (! $this->option('execute')) {
        $this->warn('Dry-run only. No data deleted. Wait for explicit approval, then run with --execute.');

        return 0;
    }

    DB::transaction(function () use (
        $entityLinkedTables,
        $rootIdSets,
        $summary,
        $demoJournalEntryIds,
        $impactedAccountIds,
        $tableColumns,
        $bomComponentIds
    ) {
        foreach ($entityLinkedTables as $table => $columns) {
            if (($summary[$table] ?? 0) <= 0) {
                continue;
            }
            DB::table($table)->where(function ($q) use ($columns, $rootIdSets) {
                foreach ($columns as $index => $column) {
                    $ids = $rootIdSets[$column] ?? [];
                    if ($ids === []) {
                        continue;
                    }
                    if ($index === 0) {
                        $q->whereIn($column, $ids);
                    } else {
                        $q->orWhereIn($column, $ids);
                    }
                }
            })->delete();
        }

        if (($summary['journal_entries'] ?? 0) > 0) {
            if (($summary['ledger'] ?? 0) > 0 && in_array('journal_entry_id', $tableColumns('ledger'), true)) {
                DB::table('ledger')->whereIn('journal_entry_id', $demoJournalEntryIds->all())->delete();
            }

            // journal_items will be removed by cascade on journal_entries, but keep explicit for clarity/safety.
            DB::table('journal_items')->whereIn('journal_entry_id', $demoJournalEntryIds->all())->delete();
            DB::table('journal_entries')->whereIn('id', $demoJournalEntryIds->all())->delete();
        }

        // Delete BOM links pointing to demo items before deleting items themselves (FK safety).
        if (($summary['item_bom_components'] ?? 0) > 0 && $bomComponentIds->isNotEmpty()) {
            DB::table('item_bom_components')->whereIn('id', $bomComponentIds->all())->delete();
        }

        if (($summary['items'] ?? 0) > 0) {
            DB::table('items')->whereIn('id', $rootIdSets['item_id'])->delete();
        }
        if (($summary['suppliers'] ?? 0) > 0) {
            DB::table('suppliers')->whereIn('id', $rootIdSets['supplier_id'])->delete();
        }
        if (($summary['customers'] ?? 0) > 0) {
            DB::table('customers')->whereIn('id', $rootIdSets['customer_id'])->delete();
        }

        // Keep accounts structure; only reset balances for accounts with no remaining real transactions.
        if ($impactedAccountIds->isNotEmpty()) {
            $accountColumns = $tableColumns('accounts');
            foreach ($impactedAccountIds as $accountId) {
                $remainingJournalItems = DB::table('journal_items')->where('account_id', $accountId)->count();
                if ($remainingJournalItems > 0) {
                    continue;
                }

                $payload = [];
                if (in_array('opening_balance', $accountColumns, true)) {
                    $payload['opening_balance'] = 0;
                }
                if (in_array('balance', $accountColumns, true)) {
                    $payload['balance'] = 0;
                }
                if ($payload !== []) {
                    DB::table('accounts')->where('id', $accountId)->update($payload);
                }
            }
        }
    });

    $this->info('Deletion completed safely in a single DB transaction (accounts table preserved).');

    return 0;
})->purpose('Strict demo/test cleanup with backup + dry-run summary; deletes only keyword-matched demo entities and linked records');

Schedule::command('contracts:create-draft-invoices')->daily();
Schedule::command('system:scan-notifications')->everyFifteenMinutes();
Schedule::command('clinic:send-whatsapp-reminders')->hourly();

Artisan::command('accounts:rebuild-current-balance {--dry-run : Preview without updating rows}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $updated = 0;

    DB::transaction(function () use (&$updated, $dryRun) {
        Account::withoutGlobalScopes()
            ->select(['id', 'user_id', 'type', 'opening_balance'])
            ->orderBy('id')
            ->chunkById(500, function ($accounts) use (&$updated, $dryRun) {
                foreach ($accounts as $account) {
                    $sum = DB::table('journal_items')
                        ->where('account_id', $account->id)
                        ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
                        ->first();

                    $debit = (float) ($sum->d ?? 0);
                    $credit = (float) ($sum->c ?? 0);
                    $movement = $debit - $credit;
                    $type = (string) ($account->type ?? '');
                    $signedMovement = in_array($type, ['liability', 'revenue', 'equity'], true)
                        ? -$movement
                        : $movement;
                    $current = (float) ($account->opening_balance ?? 0) + $signedMovement;

                    if (! $dryRun) {
                        Account::withoutGlobalScopes()
                            ->where('id', $account->id)
                            ->where('user_id', $account->user_id)
                            ->update(['current_balance' => $current]);
                    }

                    $updated++;
                }
            });
    });

    $verb = $dryRun ? 'تمت المعاينة' : 'تم التحديث';
    $this->info("{$verb} لعدد {$updated} حساب.");

    return 0;
})->purpose('Rebuild accounts.current_balance from opening balance and historical journal items');
