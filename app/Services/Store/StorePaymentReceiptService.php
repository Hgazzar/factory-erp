<?php

declare(strict_types=1);

namespace App\Services\Store;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final class StorePaymentReceiptService
{
    /**
     * @return string relative path on public disk
     */
    public function store(int $tenantUserId, UploadedFile $file): string
    {
        $mime = (string) $file->getMimeType();
        if (! str_starts_with($mime, 'image/')) {
            throw new InvalidArgumentException('يُقبل رفع صورة إيصال التحويل فقط (JPG/PNG/WebP).');
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('حجم صورة الإيصال يجب ألا يتجاوز 5 ميغابايت.');
        }

        return $file->store('store-payment-receipts/'.$tenantUserId, 'public');
    }

    public function publicUrl(?string $relativePath): ?string
    {
        if ($relativePath === null || trim($relativePath) === '') {
            return null;
        }

        if (! Storage::disk('public')->exists($relativePath)) {
            return null;
        }

        return Storage::disk('public')->url($relativePath);
    }
}
