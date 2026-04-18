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

            $model->attachments()->create([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName() ?: basename($path),
                'file_type' => $file->getMimeType(),
                'file_size' => (int) ($file->getSize() ?: 0),
                'user_id' => $userId,
            ]);
        }
    }
}
