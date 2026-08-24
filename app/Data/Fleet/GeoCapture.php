<?php

declare(strict_types=1);

namespace App\Data\Fleet;

/**
 * لقطة الموقع المرسلة من تطبيق المندوب لحظة الحدث.
 */
final class GeoCapture
{
    public function __construct(
        public readonly ?float $lat = null,
        public readonly ?float $lng = null,
        public readonly ?int $accuracy = null,
        public readonly bool $isMocked = false,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        $lat = $data['lat'] ?? null;
        $lng = $data['lng'] ?? null;
        $accuracy = $data['accuracy'] ?? null;

        return new self(
            lat: is_numeric($lat) ? (float) $lat : null,
            lng: is_numeric($lng) ? (float) $lng : null,
            accuracy: is_numeric($accuracy) ? (int) round((float) $accuracy) : null,
            isMocked: filter_var($data['is_mocked'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );
    }

    public function hasCoordinates(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }
}
