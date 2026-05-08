<?php

namespace App\Models;

use App\Models\Scopes\CrmAppointmentTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmAppointment extends Model
{
    protected $table = 'crm_appointments';

    protected $fillable = [
        'user_id',
        'appointment_number',
        'customer_id',
        'title',
        'type',
        'status',
        'start_at',
        'end_at',
        'location',
        'assigned_to',
        'notes',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CrmAppointmentTenantScope);
    }

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public static function forTenant(int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return static::withoutGlobalScopes()->where('crm_appointments.user_id', $userId);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public static function typeLabels(): array
    {
        return [
            'call' => 'اتصال',
            'meeting' => 'اجتماع حضوري',
            'demo' => 'عرض تجريبي',
            'other' => 'أخرى',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            'planned' => 'مخطط',
            'done' => 'تم',
            'cancelled' => 'ملغي',
            'late' => 'متأخر',
        ];
    }

    public static function statusColors(): array
    {
        return [
            'planned' => '#00E9F9',
            'done' => '#10B981',
            'cancelled' => '#9CA3AF',
            'late' => '#F59E0B',
        ];
    }

    public static function generateNextNumberForTenant(int $tenantId): string
    {
        $numbers = static::forTenant($tenantId)
            ->where('appointment_number', 'like', 'APP-%')
            ->pluck('appointment_number');

        $max = 0;
        foreach ($numbers as $number) {
            if (preg_match('/^APP-(\d+)$/', (string) $number, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return 'APP-'.str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }
}
