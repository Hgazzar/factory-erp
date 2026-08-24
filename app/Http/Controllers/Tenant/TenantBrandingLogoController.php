<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantSetting;
use App\Services\Tenant\TenantBrandingService;
use Illuminate\Http\Response;

/**
 * Serve tenant logos from disk, with DB blob fallback (survives ephemeral deploys).
 */
final class TenantBrandingLogoController extends Controller
{
    public function __invoke(int $tenantUserId, TenantBrandingService $branding): Response
    {
        if ($tenantUserId < 1) {
            abort(404);
        }

        $setting = TenantSetting::query()->where('tenant_user_id', $tenantUserId)->first();
        if ($setting === null) {
            abort(404);
        }

        $payload = $branding->resolveLogoBinary($setting);
        if ($payload === null) {
            abort(404);
        }

        ['bytes' => $bytes, 'mime' => $mime] = $payload;
        $etag = '"'.sha1($bytes).'"';

        return response($bytes, 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string) strlen($bytes),
            'Cache-Control' => 'public, max-age=86400, immutable',
            'ETag' => $etag,
        ]);
    }
}
