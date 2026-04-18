<?php

namespace App\Http\Controllers\Concerns;

use App\Services\AttachmentService;
use Illuminate\Database\Eloquent\Model;

trait PersistsMorphAttachments
{
    /**
     * @param  array<int, mixed>  $uploads
     */
    protected function persistMorphAttachments(Model $model, array $uploads, int $userId, string $folderPrefix): void
    {
        app(AttachmentService::class)->storeUploadedFiles($model, $uploads, $userId, $folderPrefix);
    }
}
