<?php

namespace App\Domain\Academic\Models;

use App\Domain\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'code',
        'title',
        'credits',
    ];

    /**
     * Physical sections spawned off this abstract course definition.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}