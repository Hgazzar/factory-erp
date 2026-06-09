<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\ResolvesRouteBindingForTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetCustodyIssueLine extends Model
{
    use ResolvesRouteBindingForTenant;

    protected $table = 'fleet_custody_issue_lines';

    protected $fillable = [
        'user_id',
        'issue_id',
        'product_id',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit_price' => 'float',
        ];
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(FleetCustodyIssue::class, 'issue_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FleetProduct::class, 'product_id');
    }
}
