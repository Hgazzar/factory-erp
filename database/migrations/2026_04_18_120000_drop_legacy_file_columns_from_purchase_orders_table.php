<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Removes obsolete per-row file columns from purchase_orders if present.
 * File evidence is stored via the polymorphic attachments table.
 */
return new class extends Migration
{
    private const LEGACY_COLUMNS = [
        'image',
        'image_path',
        'photo',
        'attachment',
        'attachment_path',
        'file_path',
        'document_path',
        'scan_path',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('purchase_orders')) {
            return;
        }

        $toDrop = [];
        foreach (self::LEGACY_COLUMNS as $column) {
            if (Schema::hasColumn('purchase_orders', $column)) {
                $toDrop[] = $column;
            }
        }

        if ($toDrop === []) {
            return;
        }

        Schema::table('purchase_orders', function (Blueprint $table) use ($toDrop): void {
            $table->dropColumn($toDrop);
        });
    }

    public function down(): void
    {
        // Forward-only cleanup: legacy columns are not recreated.
    }
};
