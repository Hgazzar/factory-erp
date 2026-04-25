<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AttendanceApiToken extends Model
{
    protected $fillable = [
        'user_id',
        'token_hash',
        'name',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array{plain: string, token: self}
     */
    public static function issueForUser(int $userId, string $name = 'default'): array
    {
        $plain = Str::random(48);
        $token = self::query()->create([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $plain),
            'name' => $name,
        ]);

        return ['plain' => $plain, 'token' => $token];
    }

    public static function findValidByPlainToken(string $plain): ?self
    {
        if ($plain === '') {
            return null;
        }

        return self::query()->where('token_hash', hash('sha256', $plain))->first();
    }
}
