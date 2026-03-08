<?php

namespace App\Domain\Core\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * This ensures that no query can accidentally fetch data belonging to another institution.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // 1. If we have it explicitly in the session (e.g. set upon login)
        if (session()->has('institution_id')) {
            $builder->where($model->getTable() . '.institution_id', session('institution_id'));
        }
        // 2. Fallback to auth()->user() if session is somehow bypassed but user is valid
        elseif (auth()->hasUser() && auth()->user()->institution_id) {
            $builder->where($model->getTable() . '.institution_id', auth()->user()->institution_id);
        }
    }
}