<?php

namespace Database\Factories;

use App\Models\GpsPosition;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GpsPosition>
 */
class GpsPositionFactory extends Factory
{
    protected $model = GpsPosition::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'mission_id' => Mission::factory(),
            'latitude' => fake()->latitude(-13, -4),
            'longitude' => fake()->longitude(12, 31),
            'precision' => fake()->randomFloat(2, 2, 15),
            'altitude' => fake()->randomFloat(2, 200, 900),
            'speed' => fake()->randomFloat(2, 0, 5),
            'heading' => fake()->randomFloat(2, 0, 359),
            'captured_at' => now(),
        ];
    }
}
