<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CrmActivityTenantScope implements Scope
{
    /** أنشطة عملاء المستأجر الحالي (مالك العميل في customers.user_id). */
    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::check()) {
            return;
        }

        $builder->whereHas('customer', function (Builder $q) {
            $q->where('customers.user_id', Auth::id());
        });
    }
}
