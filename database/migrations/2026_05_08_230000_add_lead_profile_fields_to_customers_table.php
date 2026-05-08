<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'first_name')) {
                $table->string('first_name', 120)->nullable()->after('name');
            }
            if (! Schema::hasColumn('customers', 'last_name')) {
                $table->string('last_name', 120)->nullable()->after('first_name');
            }
            if (! Schema::hasColumn('customers', 'company_name')) {
                $table->string('company_name', 255)->nullable()->after('contact_name');
            }
            if (! Schema::hasColumn('customers', 'job_title')) {
                $table->string('job_title', 160)->nullable()->after('company_name');
            }
            if (! Schema::hasColumn('customers', 'website')) {
                $table->string('website', 500)->nullable()->after('email');
            }
            if (! Schema::hasColumn('customers', 'source_details')) {
                $table->text('source_details')->nullable()->after('source');
            }
            if (! Schema::hasColumn('customers', 'lead_sector')) {
                $table->string('lead_sector', 80)->nullable()->after('lead_rating');
            }
            if (! Schema::hasColumn('customers', 'lead_company_size')) {
                $table->string('lead_company_size', 40)->nullable()->after('lead_sector');
            }
            if (! Schema::hasColumn('customers', 'lead_budget')) {
                $table->decimal('lead_budget', 15, 2)->nullable()->after('lead_company_size');
            }
            if (! Schema::hasColumn('customers', 'lead_description')) {
                $table->text('lead_description')->nullable()->after('lead_budget');
            }
            if (! Schema::hasColumn('customers', 'lead_requirements')) {
                $table->text('lead_requirements')->nullable()->after('lead_description');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            foreach ([
                'lead_requirements',
                'lead_description',
                'lead_budget',
                'lead_company_size',
                'lead_sector',
                'source_details',
                'website',
                'job_title',
                'company_name',
                'last_name',
                'first_name',
            ] as $col) {
                if (Schema::hasColumn('customers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
