<?php

namespace App\Domain\Academic\Models;

use App\Domain\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Term extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * All sections created and conducted under this specific term.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}