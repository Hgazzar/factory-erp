<?php

use App\Models\Item;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * يحوّل قيمة image_path القديمة إلى سجلات attachments ثم يحذف العمود.
     * لا يُحذف إلا عمود نصي معروف (مسار ملف) وليس مفاتيح أجنبية.
     */
    public function up(): void
    {
        if (Schema::hasColumn('items', 'image_path')) {
            $disk = Storage::disk('public');
            $rows = DB::table('items')
                ->whereNotNull('image_path')
                ->where('image_path', '!=', '')
                ->get(['id', 'user_id', 'image_path']);

            foreach ($rows as $row) {
                $path = ltrim(str_replace('\\', '/', (string) $row->image_path), '/');
                if ($path === '' || ! $disk->exists($path)) {
                    continue;
                }

                $full = $disk->path($path);
                $mime = is_file($full) ? (mime_content_type($full) ?: null) : null;
                $size = is_file($full) ? (int) filesize($full) : 0;

                DB::table('attachments')->insert([
                    'attachable_type' => Item::class,
                    'attachable_id' => $row->id,
                    'file_path' => $path,
                    'file_name' => basename($path),
                    'file_type' => $mime,
                    'file_size' => $size,
                    'user_id' => (int) $row->user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Schema::table('items', function (Blueprint $table): void {
                $table->dropColumn('image_path');
            });
        }

        $pathLikeColumnsByTable = [
            'customers' => ['image', 'photo', 'image_path', 'logo', 'logo_path'],
            'employees' => ['image', 'photo', 'image_path', 'logo', 'logo_path'],
            'journal_entries' => ['image', 'photo', 'image_path', 'logo', 'logo_path'],
            'suppliers' => ['image', 'photo', 'image_path', 'logo', 'logo_path'],
        ];

        foreach ($pathLikeColumnsByTable as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }
            $toDrop = [];
            foreach ($columns as $column) {
                if (Schema::hasColumn($tableName, $column)) {
                    $toDrop[] = $column;
                }
            }
            if ($toDrop === []) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($toDrop): void {
                $table->dropColumn($toDrop);
            });
        }
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->string('image_path', 500)->nullable()->after('description');
        });

        $attachments = DB::table('attachments')
            ->where('attachable_type', Item::class)
            ->orderBy('id')
            ->get(['attachable_id', 'file_path']);

        $firstByItem = [];
        foreach ($attachments as $att) {
            $iid = (int) $att->attachable_id;
            if (! isset($firstByItem[$iid])) {
                $firstByItem[$iid] = $att->file_path;
            }
        }

        foreach ($firstByItem as $itemId => $filePath) {
            DB::table('items')->where('id', $itemId)->update(['image_path' => $filePath]);
        }

        DB::table('attachments')->where('attachable_type', Item::class)->delete();
    }
};
