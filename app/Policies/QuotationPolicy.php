<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function update(User $user, Quotation $quotation): bool
    {
        return $quotation->status === Quotation::STATUS_DRAFT;
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        return $quotation->status === Quotation::STATUS_DRAFT;
    }

    public function approve(User $user, Quotation $quotation): bool
    {
        return $quotation->status === Quotation::STATUS_DRAFT;
    }
}
