<?php

use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * يحوّل إيصال المصروف القديم (عمود receipt_path) إلى سجل attachments ثم يفرّغ العمود.
     */
    public function up(): void
    {
        if (! Schema::hasTable('attachments') || ! Schema::hasTable('payments')) {
            return;
        }

        if (! Schema::hasColumn('payments', 'receipt_path')) {
            return;
        }

        $disk = Storage::disk('public');
        $rows = DB::table('payments')
            ->where('type', 'expense')
            ->whereNotNull('receipt_path')
            ->where('receipt_path', '!=', '')
            ->get(['id', 'user_id', 'receipt_path']);

        foreach ($rows as $row) {
            $path = ltrim(str_replace('\\', '/', (string) $row->receipt_path), '/');
            if ($path === '' || ! $disk->exists($path)) {
                DB::table('payments')->where('id', $row->id)->update(['receipt_path' => null]);

                continue;
            }

            $already = DB::table('attachments')
                ->where('attachable_type', Payment::class)
                ->where('attachable_id', $row->id)
                ->where('file_path', $path)
                ->exists();

            if ($already) {
                DB::table('payments')->where('id', $row->id)->update(['receipt_path' => null]);

                continue;
            }

            $full = $disk->path($path);
            $mime = is_file($full) ? (mime_content_type($full) ?: null) : null;
            $size = is_file($full) ? (int) filesize($full) : 0;

            DB::table('attachments')->insert([
                'attachable_type' => Payment::class,
                'attachable_id' => $row->id,
                'file_path' => $path,
                'file_name' => basename($path),
                'file_type' => $mime,
                'file_size' => $size,
                'user_id' => (int) $row->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('payments')->where('id', $row->id)->update(['receipt_path' => null]);
        }
    }

    public function down(): void
    {
        // لا نعيد ملء receipt_path تلقائياً لتجنب فقدان المرفقات المتعددة.
    }
};
