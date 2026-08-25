<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class AttachmentService
{
    /**
     * @param  array<int, mixed>  $uploads
     */
    public function storeUploadedFiles(Model $model, array $uploads, int $userId, string $folderPrefix): void
    {
        $folder = trim($folderPrefix, '/').'/'.$model->getKey();

        foreach ($uploads as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store($folder, 'public');
            if (! is_string($path) || $path === '') {
                continue;
            }

            $original = $file->getClientOriginalName() ?: basename($path);
            if (mb_strlen($original) > 255) {
                $ext = pathinfo($original, PATHINFO_EXTENSION);
                $base = pathinfo($original, PATHINFO_FILENAME);
                $keep = 255 - (($ext !== '') ? mb_strlen($ext) + 1 : 0);
                $original = mb_substr($base, 0, max(1, $keep)).($ext !== '' ? '.'.$ext : '');
            }

            $model->attachments()->create([
                'file_path' => $path,
                'file_name' => $original,
                'file_type' => $file->getMimeType() ?: null,
                'file_size' => (int) ($file->getSize() ?: 0),
                'user_id' => $userId,
            ]);
        }
    }
}
