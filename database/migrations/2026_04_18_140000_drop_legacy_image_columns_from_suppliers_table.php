<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops obsolete image/photo columns from suppliers when present.
 * Files are stored via the polymorphic attachments table.
 */
return new class extends Migration
{
    private const LEGACY_COLUMNS = [
        'image',
        'image_path',
        'photo',
        'logo',
        'logo_path',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('suppliers')) {
            return;
        }

        $toDrop = [];
        foreach (self::LEGACY_COLUMNS as $column) {
            if (Schema::hasColumn('suppliers', $column)) {
                $toDrop[] = $column;
            }
        }

        if ($toDrop === []) {
            return;
        }

        Schema::table('suppliers', function (Blueprint $table) use ($toDrop): void {
            $table->dropColumn($toDrop);
        });
    }

    public function down(): void
    {
        // Forward-only cleanup.
    }
};
