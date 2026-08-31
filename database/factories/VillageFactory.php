<?php

namespace Database\Factories;

use App\Models\Cld;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Village>
 */
class VillageFactory extends Factory
{
    protected $model = Village::class;

    public function definition(): array
    {
        return [
            'cld_id' => Cld::factory(),
            'nom' => 'Village '.fake()->unique()->lastName(),
        ];
    }
}
