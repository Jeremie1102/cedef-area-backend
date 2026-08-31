<?php

namespace Database\Factories;

use App\Enums\SyncStatus;
use App\Models\MediaBatch;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaBatch>
 */
class MediaBatchFactory extends Factory
{
    protected $model = MediaBatch::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'mission_id' => Mission::factory(),
            'cld_id' => null,
            'village_id' => null,
            'activite' => fake()->randomElement(['distribution', 'sensibilisation', 'suivi de terrain']),
            'description' => fake()->sentence(10),
            'date_activite' => fake()->date(),
            'latitude' => fake()->latitude(-13, -4),
            'longitude' => fake()->longitude(12, 31),
            'precision' => fake()->randomFloat(2, 2, 15),
            'statut' => SyncStatus::SYNCED,
        ];
    }
}
