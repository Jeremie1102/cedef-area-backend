<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['cld_id', 'nom'])]
class Village extends Model
{
    use HasFactory;

    public function cld(): BelongsTo
    {
        return $this->belongsTo(Cld::class);
    }

    public function missions(): BelongsToMany
    {
        return $this->belongsToMany(Mission::class, 'mission_village')->withTimestamps();
    }

    public function mediaBatches(): HasMany
    {
        return $this->hasMany(MediaBatch::class);
    }
}
