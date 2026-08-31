<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'uuid', 'local_id', 'user_id', 'mission_id', 'latitude', 'longitude',
    'precision', 'altitude', 'speed', 'heading', 'captured_at',
])]
class GpsPosition extends Model
{
    use HasFactory, HasUuid;

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'precision' => 'decimal:2',
            'altitude' => 'decimal:2',
            'speed' => 'decimal:2',
            'heading' => 'decimal:2',
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
}
