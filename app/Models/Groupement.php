<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['sector_id', 'nom'])]
class Groupement extends Model
{
    use HasFactory;

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function clds(): HasMany
    {
        return $this->hasMany(Cld::class);
    }

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class);
    }
}
