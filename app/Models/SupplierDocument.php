<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'file_path',
        'file_name',
        'file_type',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function isPdf(): bool
    {
        return str_starts_with(strtolower($this->file_type ?? ''), 'application/pdf') || str_contains(strtolower($this->file_type ?? ''), 'pdf');
    }

    public function isImage(): bool
    {
        return str_starts_with(strtolower($this->file_type ?? ''), 'image/');
    }
}
