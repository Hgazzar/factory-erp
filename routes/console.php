<?php

use App\Models\Commission;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\User;
use App\Notifications\ContractReminderNotification;
use App\Notifications\InstallmentDueNotification;
use App\Notifications\PendingCommissionsNotification;
use App\Services\UniversalImportService;
use App\Services\ZatcaService;
use App\Support\AgentDebugLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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

Schedule::command('contracts:create-draft-invoices')->daily();
Schedule::command('system:scan-notifications')->everyFifteenMinutes();
