<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditTrail extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('AuditTrail records are immutable and cannot be updated.');
        });
        static::deleting(function () {
            throw new \RuntimeException('AuditTrail records are immutable and cannot be deleted.');
        });
    }

    public static function log(string $action, string $tableName, $recordId, ?array $oldValues, ?array $newValues): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
