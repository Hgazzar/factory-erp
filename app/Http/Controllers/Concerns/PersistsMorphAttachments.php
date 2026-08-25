<?php

namespace App\Http\Controllers\Concerns;

use App\Services\AttachmentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

trait PersistsMorphAttachments
{
    /**
     * @param  array<int, mixed>  $uploads
     */
    protected function persistMorphAttachments(Model $model, array $uploads, int $userId, string $folderPrefix): void
    {
        app(AttachmentService::class)->storeUploadedFiles($model, $uploads, $userId, $folderPrefix);
    }

    /**
     * رفع/استبدال/حذف صورة الأفاتار (مميزة ببادئة في اسم الملف دون عمود إضافي).
     */
    protected function persistAvatarUpload(Model $model, ?UploadedFile $file, int $userId, string $folderPrefix, bool $remove = false): void
    {
        if (! method_exists($model, 'attachments')) {
            return;
        }

        if ($remove || ($file instanceof UploadedFile && $file->isValid())) {
            $this->deleteAvatarAttachments($model);
        }

        if ($remove || ! ($file instanceof UploadedFile) || ! $file->isValid()) {
            return;
        }

        $folder = trim($folderPrefix, '/').'/'.$model->getKey();
        Storage::disk('public')->makeDirectory($folder);

        $path = $file->store($folder, 'public');
        if (! is_string($path) || $path === '') {
            throw new RuntimeException('تعذّر حفظ ملف الصورة على التخزين.');
        }

        $original = $file->getClientOriginalName() ?: basename($path);
        $prefix = $this->avatarFileNamePrefix();
        $storedName = $prefix.$original;
        if (mb_strlen($storedName) > 255) {
            $ext = pathinfo($original, PATHINFO_EXTENSION);
            $base = pathinfo($original, PATHINFO_FILENAME);
            $keep = 255 - mb_strlen($prefix) - (($ext !== '') ? mb_strlen($ext) + 1 : 0);
            $storedName = $prefix.mb_substr($base, 0, max(1, $keep)).($ext !== '' ? '.'.$ext : '');
        }

        try {
            $model->attachments()->create([
                'file_path' => $path,
                'file_name' => $storedName,
                'file_type' => $file->getMimeType() ?: 'image/jpeg',
                'file_size' => (int) ($file->getSize() ?: 0),
                'user_id' => $userId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('nursery.avatar_attachment_failed', [
                'model' => $model::class,
                'id' => $model->getKey(),
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            Storage::disk('public')->delete($path);

            throw new RuntimeException('تعذّر ربط صورة الأفاتار بالسجل: '.$e->getMessage(), 0, $e);
        }
    }

    protected function deleteAvatarAttachments(Model $model): void
    {
        if (! method_exists($model, 'attachments')) {
            return;
        }

        $prefix = $this->avatarFileNamePrefix();
        // لا نستخدم LIKE مباشرة: البادئة تحتوي "_" وهو حرف بدل في SQL LIKE.
        $avatars = $model->attachments()
            ->orderBy('id')
            ->get()
            ->filter(fn ($attachment) => str_starts_with((string) $attachment->file_name, $prefix));

        foreach ($avatars as $attachment) {
            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
            $attachment->delete();
        }
    }

    /**
     * يجب أن يطابق {@see HasAttachments::AVATAR_FILE_PREFIX} — لا نقرأ ثابت الـ trait مباشرة (غير مسموح في PHP).
     */
    protected function avatarFileNamePrefix(): string
    {
        return '__avatar__:';
    }
}
