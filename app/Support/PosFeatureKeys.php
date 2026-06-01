<?php

declare(strict_types=1);

namespace App\Support;

final class PosFeatureKeys
{
    /**
     * Placeholder for premium barcode parsing/lookup workflows.
     */
    public const ADVANCED_BARCODE = 'pos_advanced_barcode';

    /**
     * Placeholder for multi-warehouse POS stock routing.
     */
    public const MULTI_WAREHOUSE = 'pos_multi_warehouse';

    /**
     * Allow cashiers to override line unit price at checkout.
     */
    public const MANUAL_PRICE_OVERRIDE = 'pos_manual_price_override';
}
