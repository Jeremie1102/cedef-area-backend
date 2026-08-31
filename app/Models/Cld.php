<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['groupement_id', 'nom'])]
class Cld extends Model
{
    use HasFactory;

    public function groupement(): BelongsTo
    {
        return $this->belongsTo(Groupement::class);
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'cld_user')
            ->withPivot(['date_debut', 'date_fin', 'statut'])
            ->withTimestamps();
    }

    public function missions(): BelongsToMany
    {
        return $this->belongsToMany(Mission::class, 'cld_mission')->withTimestamps();
    }

    public function mediaBatches(): HasMany
    {
        return $this->hasMany(MediaBatch::class);
    }
}
