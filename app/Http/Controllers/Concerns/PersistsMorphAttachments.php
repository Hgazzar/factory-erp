<?php

namespace App\Http\Controllers\Concerns;

use App\Traits\HasAttachments;
use App\Services\AttachmentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
        $path = $file->store($folder, 'public');
        $original = $file->getClientOriginalName() ?: basename($path);

        $model->attachments()->create([
            'file_path' => $path,
            'file_name' => HasAttachments::AVATAR_FILE_PREFIX.$original,
            'file_type' => $file->getMimeType(),
            'file_size' => (int) ($file->getSize() ?: 0),
            'user_id' => $userId,
        ]);
    }

    protected function deleteAvatarAttachments(Model $model): void
    {
        if (! method_exists($model, 'attachments')) {
            return;
        }

        $prefix = HasAttachments::AVATAR_FILE_PREFIX;
        $avatars = $model->attachments()
            ->where('file_name', 'like', $prefix.'%')
            ->get();

        foreach ($avatars as $attachment) {
            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
            $attachment->delete();
        }
    }
}
