<?php

declare(strict_types=1);

use App\Support\ErpFormat;

if (! function_exists('erp_money')) {
    function erp_money(float|string|int|null $value): string
    {
        return ErpFormat::money($value);
    }
}

if (! function_exists('erp_qty')) {
    function erp_qty(float|string|int|null $value): string
    {
        return ErpFormat::qty($value);
    }
}
