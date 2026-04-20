<?php

declare(strict_types=1);

namespace App\Support;

final class ErpFormat
{
    public static function moneyDecimals(): int
    {
        return max(0, min(8, (int) config('accounting.display_money_decimal_places', 2)));
    }

    public static function quantityDecimals(): int
    {
        return max(0, min(8, (int) config('accounting.display_quantity_decimal_places', 2)));
    }

    public static function money(float|string|int|null $value): string
    {
        return number_format((float) $value, self::moneyDecimals(), '.', ',');
    }

    public static function qty(float|string|int|null $value): string
    {
        return number_format((float) $value, self::quantityDecimals(), '.', ',');
    }
}
