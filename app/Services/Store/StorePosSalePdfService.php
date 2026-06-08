<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Contracts\Core\Documents\DocumentGeneratorInterface;
use App\Models\CompanySetting;
use App\Models\PosSale;
use App\Models\TenantProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class StorePosSalePdfService implements DocumentGeneratorInterface
{
    /**
     * @return array{0: \Barryvdh\DomPDF\PDF, 1: string}
     */
    public function makeReceiptPdf(PosSale $sale, int $tenantUserId): array
    {
        $sale->loadMissing(['items.product']);

        $company = CompanySetting::forTenant($tenantUserId);
        $logoDataUri = $this->resolveLogoDataUri($company?->logo_url);
        $currency = CompanySetting::resolvedCurrencyCode($tenantUserId);

        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $sale->invoice_number);
        $filename = 'store-invoice-'.($safe ?: ('sale-'.$sale->id)).'.pdf';

        $pdf = Pdf::loadView('store.pdf.receipt', [
            'sale' => $sale,
            'company' => $company,
            'logoDataUri' => $logoDataUri,
            'currency' => $currency,
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true);

        return [$pdf, $filename];
    }

    public function streamReceipt(PosSale $sale, int $tenantUserId): Response
    {
        try {
            [$pdf, $filename] = $this->makeReceiptPdf($sale, $tenantUserId);

            return $pdf->stream($filename);
        } catch (\Throwable $e) {
            Log::error('Store invoice PDF failed', [
                'pos_sale_id' => $sale->id,
                'message' => $e->getMessage(),
            ]);

            abort(500, 'تعذّر إنشاء فاتورة PDF.');
        }
    }

    public function storeReceiptFile(PosSale $sale, int $tenantUserId): string
    {
        return $this->storeFile($sale, $tenantUserId);
    }

    public function storeFile(object $subject, int $tenantUserId): string
    {
        if (! $subject instanceof PosSale) {
            throw new \InvalidArgumentException('StorePosSalePdfService expects a PosSale instance.');
        }

        [$pdf, $filename] = $this->makeReceiptPdf($subject, $tenantUserId);
        $relative = 'store-invoices/'.$tenantUserId.'/'.$filename;
        Storage::disk('local')->put($relative, $pdf->output());

        return Storage::disk('local')->path($relative);
    }

    public function signedInvoiceUrl(PosSale $sale): ?string
    {
        if ($sale->sale_channel !== PosSale::CHANNEL_ONLINE_STORE) {
            return null;
        }

        $profile = TenantProfile::forTenantUser((int) $sale->user_id);
        $slug = $profile?->slug ?? $profile?->domain;

        if ($slug === null || trim($slug) === '') {
            return null;
        }

        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'store.portal.invoice.pdf',
            now()->addDays(14),
            [
                'tenant_slug' => $slug,
                'saleId' => $sale->id,
            ],
        );
    }

    private function resolveLogoDataUri(?string $logoPath): ?string
    {
        if ($logoPath === null || ! str_starts_with($logoPath, 'company/')) {
            return null;
        }

        if (! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($logoPath) ?: 'image/png';

        if (! is_string($mime) || ! str_starts_with($mime, 'image/')) {
            return null;
        }

        $bytes = Storage::disk('public')->get($logoPath);

        if ($bytes === false || $bytes === '') {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }
}
