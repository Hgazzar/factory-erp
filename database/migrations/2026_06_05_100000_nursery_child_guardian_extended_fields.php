<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nursery_guardians', function (Blueprint $table) {
            if (! Schema::hasColumn('nursery_guardians', 'national_id')) {
                $table->string('national_id', 64)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('nursery_guardians', 'relationship_default')) {
                $table->string('relationship_default', 32)->nullable()->after('national_id');
            }
            if (! Schema::hasColumn('nursery_guardians', 'address')) {
                $table->string('address', 500)->nullable()->after('email');
            }
            if (! Schema::hasColumn('nursery_guardians', 'region')) {
                $table->string('region', 120)->nullable()->after('address');
            }
            if (! Schema::hasColumn('nursery_guardians', 'city')) {
                $table->string('city', 120)->nullable()->after('region');
            }
        });

        Schema::table('nursery_children', function (Blueprint $table) {
            if (! Schema::hasColumn('nursery_children', 'guardian_relationship')) {
                $table->string('guardian_relationship', 32)->nullable()->after('guardian_id');
            }
            if (! Schema::hasColumn('nursery_children', 'diseases')) {
                $table->text('diseases')->nullable()->after('allergies');
            }
            if (! Schema::hasColumn('nursery_children', 'health_notes')) {
                $table->text('health_notes')->nullable()->after('diseases');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nursery_children', function (Blueprint $table) {
            foreach (['guardian_relationship', 'diseases', 'health_notes'] as $col) {
                if (Schema::hasColumn('nursery_children', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('nursery_guardians', function (Blueprint $table) {
            foreach (['national_id', 'relationship_default', 'address', 'region', 'city'] as $col) {
                if (Schema::hasColumn('nursery_guardians', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
