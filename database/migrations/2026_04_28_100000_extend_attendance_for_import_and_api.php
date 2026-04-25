<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('attendance_device_id', 64)->nullable()->after('code');
            $table->index(['user_id', 'attendance_device_id']);
        });

        Schema::rename('attendance_logs', 'attendance_logs_legacy');

        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('employee_device_id', 128)->nullable();
            $table->dateTime('logged_at');
            $table->string('direction', 10);
            $table->string('source', 40)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['attendance_id', 'logged_at'], 'att_logs_v2_att_logged_idx');
            $table->index(['user_id', 'logged_at'], 'att_logs_v2_user_logged_idx');
            $table->index(['user_id', 'employee_id', 'logged_at'], 'att_logs_v2_user_emp_logged_idx');
        });

        if (Schema::hasTable('attendance_logs_legacy')) {
            DB::table('attendance_logs_legacy')->orderBy('id')->chunk(500, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('attendance_logs')->insert([
                        'user_id' => $row->user_id,
                        'attendance_id' => $row->attendance_id,
                        'employee_id' => null,
                        'employee_device_id' => null,
                        'logged_at' => $row->logged_at,
                        'direction' => $row->direction,
                        'source' => $row->source,
                        'meta' => $row->meta,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);
                }
            });

            Schema::drop('attendance_logs_legacy');
        }

        Schema::create('attendance_import_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('header_signature', 64);
            $table->unsignedSmallInteger('device_column_index');
            $table->unsignedSmallInteger('datetime_column_index');
            $table->json('headers_snapshot')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'header_signature']);
        });

        Schema::create('attendance_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('name')->default('default');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_api_tokens');
        Schema::dropIfExists('attendance_import_mappings');

        Schema::rename('attendance_logs', 'attendance_logs_with_import');

        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            $table->dateTime('logged_at');
            $table->string('direction', 10);
            $table->string('source', 40)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['attendance_id', 'logged_at']);
            $table->index(['user_id', 'logged_at']);
        });

        DB::table('attendance_logs_with_import')
            ->whereNotNull('attendance_id')
            ->orderBy('id')
            ->chunk(500, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('attendance_logs')->insert([
                        'user_id' => $row->user_id,
                        'attendance_id' => $row->attendance_id,
                        'logged_at' => $row->logged_at,
                        'direction' => $row->direction,
                        'source' => $row->source,
                        'meta' => $row->meta,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);
                }
            });

        Schema::drop('attendance_logs_with_import');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'attendance_device_id']);
            $table->dropColumn('attendance_device_id');
        });
    }
};
