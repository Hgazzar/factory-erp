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
    /**
     * Prefix stored in attachment file_name to mark nursery avatar photos (no schema change).
     */
    public const AVATAR_FILE_PREFIX = '__avatar__:';

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isAvatarAttachment(Attachment $attachment): bool
    {
        return str_starts_with((string) $attachment->file_name, self::AVATAR_FILE_PREFIX);
    }

    /**
     * صورة الأفاتار إن وُجدت، وإلا أول مرفق صورة عام.
     */
    public function firstImageUrl(): ?string
    {
        $attachments = $this->relationLoaded('attachments')
            ? $this->attachments
            : $this->attachments()->orderBy('id')->get();

        $avatar = $attachments->first(fn (Attachment $att): bool => $this->isAvatarAttachment($att) && filled($att->file_path));
        if ($avatar) {
            return asset('storage/'.ltrim((string) $avatar->file_path, '/'));
        }

        foreach ($attachments as $att) {
            if ($this->isAvatarAttachment($att)) {
                continue;
            }
            $mime = strtolower((string) ($att->file_type ?? ''));
            $name = strtolower((string) ($att->file_name ?? ''));
            $isImage = str_starts_with($mime, 'image/')
                || (bool) preg_match('/\.(jpe?g|png|gif|webp|bmp)$/', $name);

            if ($isImage && filled($att->file_path)) {
                return asset('storage/'.ltrim((string) $att->file_path, '/'));
            }
        }

        return null;
    }

    public function avatarAttachment(): ?Attachment
    {
        $attachments = $this->relationLoaded('attachments')
            ? $this->attachments
            : $this->attachments()->orderByDesc('id')->get();

        return $attachments->first(fn (Attachment $att): bool => $this->isAvatarAttachment($att));
    }

    /**
     * @return \Illuminate\Support\Collection<int, Attachment>
     */
    public function documentAttachments()
    {
        $attachments = $this->relationLoaded('attachments')
            ? $this->attachments
            : $this->attachments()->orderBy('id')->get();

        return $attachments->reject(fn (Attachment $att): bool => $this->isAvatarAttachment($att))->values();
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
