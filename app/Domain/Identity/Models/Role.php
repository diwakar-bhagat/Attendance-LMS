<?php

namespace App\Domain\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /**
     * Disable timestamps if not populated by seeder
     * Setting this to true since we have them in migrations
     */
    public $timestamps = true;

    protected $fillable = [
        'name',
        'display_name',
    ];

    /**
     * All users possessing this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}