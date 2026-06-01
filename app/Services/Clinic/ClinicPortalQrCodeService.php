<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use RuntimeException;

final class ClinicPortalQrCodeService
{
    public function pngDataUri(string $url): string
    {
        return 'data:image/png;base64,'.base64_encode($this->pngBinary($url));
    }

    public function pngBinary(string $url): string
    {
        if (! class_exists(QRCode::class)) {
            throw new RuntimeException('QR generator package is not available.');
        }

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'scale' => 6,
            'imageBase64' => false,
        ]);

        return (new QRCode($options))->render($url);
    }
}
