<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shifts') && ! Schema::hasColumn('shifts', 'grace_minutes')) {
            Schema::table('shifts', function (Blueprint $table): void {
                $table->unsignedSmallInteger('grace_minutes')
                    ->default(0)
                    ->after('end_time')
                    ->comment('دقائق السماح للتأخير والانصراف المبكر');
            });
        }

        if (Schema::hasTable('employees') && ! Schema::hasColumn('employees', 'shift_id')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->foreignId('shift_id')
                    ->nullable()
                    ->after('department_id')
                    ->constrained('shifts')
                    ->nullOnDelete();
                $table->index(['user_id', 'shift_id']);
            });
        }

        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table): void {
                if (! Schema::hasColumn('attendances', 'shift_id')) {
                    $table->foreignId('shift_id')
                        ->nullable()
                        ->after('employee_id')
                        ->constrained('shifts')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('attendances', 'minutes_early_departure')) {
                    $table->unsignedSmallInteger('minutes_early_departure')
                        ->default(0)
                        ->after('minutes_late');
                }
            });
        }

        if (Schema::hasTable('pay_slips') && ! Schema::hasColumn('pay_slips', 'early_departure_hours')) {
            Schema::table('pay_slips', function (Blueprint $table): void {
                $table->decimal('early_departure_hours', 10, 2)
                    ->default(0)
                    ->after('late_hours');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pay_slips') && Schema::hasColumn('pay_slips', 'early_departure_hours')) {
            Schema::table('pay_slips', function (Blueprint $table): void {
                $table->dropColumn('early_departure_hours');
            });
        }

        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table): void {
                if (Schema::hasColumn('attendances', 'shift_id')) {
                    $table->dropConstrainedForeignId('shift_id');
                }
                if (Schema::hasColumn('attendances', 'minutes_early_departure')) {
                    $table->dropColumn('minutes_early_departure');
                }
            });
        }

        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'shift_id')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('shift_id');
            });
        }

        if (Schema::hasTable('shifts') && Schema::hasColumn('shifts', 'grace_minutes')) {
            Schema::table('shifts', function (Blueprint $table): void {
                $table->dropColumn('grace_minutes');
            });
        }
    }
};
