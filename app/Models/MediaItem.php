<?php

namespace App\Models;

use App\Enums\SyncStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'uuid', 'local_id', 'media_batch_id', 'file_path', 'original_name',
    'mime_type', 'size', 'order', 'statut',
])]
class MediaItem extends Model
{
    use HasFactory, HasUuid;

    protected function casts(): array
    {
        return [
            'statut' => SyncStatus::class,
            'size' => 'integer',
            'order' => 'integer',
        ];
    }

    public function mediaBatch(): BelongsTo
    {
        return $this->belongsTo(MediaBatch::class);
    }
}
