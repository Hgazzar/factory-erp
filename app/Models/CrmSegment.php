<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CrmSegment extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'name',
        'type',
        'status',
        'color',
        'criteria',
        'last_refreshed_at',
    ];

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'last_refreshed_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            'behavioral' => 'سلوكية',
            'demographic' => 'ديموغرافية',
            'geographic' => 'جغرافية',
            'value_based' => 'قيمة العميل',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            'draft' => 'مسودة',
            'active' => 'نشطة',
            'archived' => 'مؤرشفة',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'crm_segment_customer', 'segment_id', 'customer_id')
            ->withTimestamps();
    }
}
