<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ResolvesRouteBindingForTenant
{
    /**
     * @param  mixed  $value
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $field = $field ?: $this->getRouteKeyName();

        $model = static::withoutGlobalScopes()
            ->where($field, $value)
            ->first();

        if (! $model) {
            abort(404);
        }

        if (! auth()->check()) {
            abort(403);
        }

        if ((int) $model->user_id !== (int) auth()->id()) {
            abort(403);
        }

        return $model;
    }
}
