<?php

namespace App\Models;

use App\Models\Scopes\BelongsToAuthenticatedUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceImportMapping extends Model
{
    protected $fillable = [
        'user_id',
        'header_signature',
        'device_column_index',
        'datetime_column_index',
        'headers_snapshot',
        'name',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAuthenticatedUserScope);
    }

    protected function casts(): array
    {
        return [
            'headers_snapshot' => 'array',
            'device_column_index' => 'integer',
            'datetime_column_index' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function headerSignatureFromHeaders(array $headers): string
    {
        $normalized = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);

        return hash('sha256', implode('|', $normalized));
    }
}
