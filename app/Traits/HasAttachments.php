<?php

namespace App\Traits;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

/**
 * Polymorphic attachments via the shared {@see Attachment} model.
 *
 * Deletes stored files and rows when the parent is permanently removed.
 * For models using {@see \Illuminate\Database\Eloquent\SoftDeletes}, files are
 * removed only on {@see Model::forceDeleting()} so soft-deleted records keep their files.
 */
trait HasAttachments
{
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    protected static function bootHasAttachments(): void
    {
        static::deleting(function (Model $model): void {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            foreach ($model->attachments()->get() as $attachment) {
                if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                    Storage::disk('public')->delete($attachment->file_path);
                }
            }

            $model->attachments()->delete();
        });
    }
}
