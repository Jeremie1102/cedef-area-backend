<?php

namespace App\Models;

use App\Enums\MissionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'titre', 'description', 'type_activite', 'date_debut_prevue', 'date_fin_prevue',
    'statut', 'sector_id', 'groupement_id', 'observations',
])]
class Mission extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'statut' => MissionStatus::class,
            'date_debut_prevue' => 'date',
            'date_fin_prevue' => 'date',
        ];
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function groupement(): BelongsTo
    {
        return $this->belongsTo(Groupement::class);
    }

    public function clds(): BelongsToMany
    {
        return $this->belongsToMany(Cld::class, 'cld_mission')->withTimestamps();
    }

    public function villages(): BelongsToMany
    {
        return $this->belongsToMany(Village::class, 'mission_village')->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'mission_user')
            ->withPivot(['role', 'statut', 'date_affectation'])
            ->withTimestamps();
    }

    public function gpsPositions(): HasMany
    {
        return $this->hasMany(GpsPosition::class);
    }

    public function mediaBatches(): HasMany
    {
        return $this->hasMany(MediaBatch::class);
    }
}
