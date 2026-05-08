<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_appointments')) {
            Schema::create('crm_appointments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->string('appointment_number', 30)->nullable()->index();
                $table->string('title', 255)->nullable();
                $table->enum('type', ['call', 'meeting', 'demo', 'other'])->default('other');
                $table->enum('status', ['planned', 'done', 'cancelled', 'late'])->default('planned');
                $table->dateTime('start_at')->nullable();
                $table->dateTime('end_at')->nullable();
                $table->string('location', 255)->nullable();
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('crm_appointments', function (Blueprint $table): void {
            if (! Schema::hasColumn('crm_appointments', 'appointment_number')) {
                $table->string('appointment_number', 30)->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('crm_appointments', 'type')) {
                $table->enum('type', ['call', 'meeting', 'demo', 'other'])->default('other')->after('title');
            }
            if (! Schema::hasColumn('crm_appointments', 'status')) {
                $table->enum('status', ['planned', 'done', 'cancelled', 'late'])->default('planned')->after('type');
            }
            if (! Schema::hasColumn('crm_appointments', 'start_at')) {
                $table->dateTime('start_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('crm_appointments', 'end_at')) {
                $table->dateTime('end_at')->nullable()->after('start_at');
            }
            if (! Schema::hasColumn('crm_appointments', 'location')) {
                $table->string('location', 255)->nullable()->after('end_at');
            }
            if (! Schema::hasColumn('crm_appointments', 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')->nullable()->after('location');
            }
        });

        if (Schema::hasColumn('crm_appointments', 'scheduled_at')) {
            DB::statement('UPDATE crm_appointments SET start_at = COALESCE(start_at, scheduled_at) WHERE start_at IS NULL');
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("UPDATE crm_appointments SET appointment_number = 'APP-' || LPAD(id::text, 3, '0') WHERE appointment_number IS NULL OR appointment_number = ''");
        } else {
            DB::statement("UPDATE crm_appointments SET appointment_number = CONCAT('APP-', LPAD(id, 3, '0')) WHERE appointment_number IS NULL OR appointment_number = ''");
        }

        Schema::table('crm_appointments', function (Blueprint $table): void {
            $table->index(['user_id', 'start_at'], 'crm_appointments_user_start_idx');
            $table->index('appointment_number', 'crm_appointments_number_idx');
        });
    }

    public function down(): void
    {
        // Keep backward compatibility; do not drop columns in rollback.
    }
};

