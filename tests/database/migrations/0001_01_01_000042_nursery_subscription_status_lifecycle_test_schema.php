<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nursery_subscriptions')) {
            return;
        }

        $today = now()->toDateString();

        DB::table('nursery_subscriptions')
            ->where('status', '!=', 'cancelled')
            ->orderBy('id')
            ->each(function (object $row) use ($today): void {
                $paid = filter_var($row->is_paid, FILTER_VALIDATE_BOOL);
                $current = (string) $row->status;

                $newStatus = match (true) {
                    $paid => 'paid',
                    (string) $row->ends_on < $today => 'expired',
                    default => in_array($current, ['unpaid', 'paid', 'expired'], true) ? $current : 'unpaid',
                };

                if ($newStatus !== $current) {
                    DB::table('nursery_subscriptions')->where('id', $row->id)->update(['status' => $newStatus]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('nursery_subscriptions')) {
            return;
        }

        DB::table('nursery_subscriptions')
            ->whereIn('status', ['unpaid', 'paid', 'expired'])
            ->update(['status' => 'active']);
    }
};
