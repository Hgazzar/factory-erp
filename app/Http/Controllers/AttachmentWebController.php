<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttachmentWebController extends Controller
{
    /**
     * Remove one polymorphic attachment row and its stored file only.
     * Never deletes or mutates the parent (attachable) model.
     */
    public function destroy(Attachment $attachment): RedirectResponse
    {
        $attachment->loadMissing('attachable');
        $parent = $attachment->attachable;
        abort_if(! $parent, 404);
        abort_unless(isset($parent->user_id), 403);
        abort_unless((int) $parent->user_id === (int) auth()->id(), 403);

        DB::transaction(function () use ($attachment): void {
            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
            $attachment->delete();
        });

        return back()->with('success', 'تم حذف المرفق.');
    }
}
