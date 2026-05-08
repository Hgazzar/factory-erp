<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (! Schema::hasColumn('customers', 'crm_status')) {
                    $table->string('crm_status', 32)->default('potential')->after('status');
                }
                if (! Schema::hasColumn('customers', 'source')) {
                    $table->string('source', 120)->nullable()->after('crm_status');
                }
                if (! Schema::hasColumn('customers', 'assigned_user_id')) {
                    $table->foreignId('assigned_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('customers', 'converted_at')) {
                    $table->timestamp('converted_at')->nullable()->after('source');
                }
            });

            if (Schema::hasColumn('customers', 'crm_status')) {
                DB::table('customers')->whereNull('crm_status')->update(['crm_status' => 'potential']);
                DB::table('customers')->where('crm_status', '')->update(['crm_status' => 'potential']);
                $driver = Schema::getConnection()->getDriverName();
                if ($driver === 'mysql' || $driver === 'mariadb') {
                    DB::statement("UPDATE customers SET crm_status = 'active' WHERE status = 'active' OR status IS NULL OR status = ''");
                    DB::statement("UPDATE customers SET crm_status = 'not_interested' WHERE status = 'inactive'");
                } else {
                    DB::table('customers')->where('status', 'inactive')->update(['crm_status' => 'not_interested']);
                    DB::table('customers')->where(function ($q) {
                        $q->where('status', 'active')->orWhereNull('status')->orWhere('status', '');
                    })->update(['crm_status' => 'active']);
                }
            }

        }

        if (! Schema::hasTable('crm_appointments')) {
            Schema::create('crm_appointments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->string('title', 255)->nullable();
                $table->timestamp('scheduled_at');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'scheduled_at']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('crm_appointments')) {
            Schema::dropIfExists('crm_appointments');
        }

        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'assigned_user_id')) {
                $table->dropForeign(['assigned_user_id']);
                $table->dropColumn('assigned_user_id');
            }
            if (Schema::hasColumn('customers', 'converted_at')) {
                $table->dropColumn('converted_at');
            }
            if (Schema::hasColumn('customers', 'source')) {
                $table->dropColumn('source');
            }
            if (Schema::hasColumn('customers', 'crm_status')) {
                $table->dropColumn('crm_status');
            }
        });
    }
};
