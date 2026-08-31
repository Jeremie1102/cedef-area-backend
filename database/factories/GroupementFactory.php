<?php

namespace Database\Factories;

use App\Models\Groupement;
use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Groupement>
 */
class GroupementFactory extends Factory
{
    protected $model = Groupement::class;

    public function definition(): array
    {
        return [
            'sector_id' => Sector::factory(),
            'nom' => 'Groupement '.fake()->unique()->citySuffix(),
        ];
    }
}
