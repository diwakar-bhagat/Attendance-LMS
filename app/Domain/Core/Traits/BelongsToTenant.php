<?php

namespace App\Domain\Core\Traits;

use App\Domain\Core\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    /**
     * The "booted" method of the model.
     * Hooks into the model events to automatically set global scopes and missing attributes.
     */
    protected static function booted(): void
    {
        // Add the global scope so every query filters by tenant_id
        static::addGlobalScope(new TenantScope);

        // On creation, automatically assign the institution_id so we never accidentally orphan a record
        static::creating(function (Model $model) {
            if (empty($model->institution_id)) {
                if (session()->has('institution_id')) {
                    $model->institution_id = session('institution_id');
                }
                elseif (auth()->hasUser() && auth()->user()->institution_id) {
                    $model->institution_id = auth()->user()->institution_id;
                }
            }
        });
    }
}