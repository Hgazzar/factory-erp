<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Tenant\TenantBrandingService;
use Illuminate\Console\Command;

final class RestoreTenantLogosCommand extends Command
{
    protected $signature = 'tenant:restore-logos';

    protected $description = 'أعد كتابة شعارات المستأجرين من قاعدة البيانات إلى القرص (بعد Redeploy بدون Volume)';

    public function handle(TenantBrandingService $branding): int
    {
        $restored = $branding->restoreLogosToDisk();
        $this->info("Restored {$restored} tenant logo(s) to local storage.");

        return self::SUCCESS;
    }
}
