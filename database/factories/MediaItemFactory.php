<?php

namespace Database\Factories;

use App\Enums\SyncStatus;
use App\Models\MediaBatch;
use App\Models\MediaItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaItem>
 */
class MediaItemFactory extends Factory
{
    protected $model = MediaItem::class;

    public function definition(): array
    {
        return [
            'media_batch_id' => MediaBatch::factory(),
            'file_path' => 'media/'.fake()->uuid().'.jpg',
            'original_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(50_000, 5_000_000),
            'order' => 0,
            'statut' => SyncStatus::SYNCED,
        ];
    }
}
