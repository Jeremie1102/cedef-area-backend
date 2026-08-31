<?php

namespace App\Models;

use App\Enums\SyncStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'uuid', 'local_id', 'user_id', 'mission_id', 'cld_id', 'village_id',
    'activite', 'description', 'date_activite', 'latitude', 'longitude', 'precision', 'statut',
])]
class MediaBatch extends Model
{
    use HasFactory, HasUuid;

    protected function casts(): array
    {
        return [
            'statut' => SyncStatus::class,
            'date_activite' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'precision' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function cld(): BelongsTo
    {
        return $this->belongsTo(Cld::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function mediaItems(): HasMany
    {
        return $this->hasMany(MediaItem::class);
    }
}
