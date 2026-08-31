<?php

namespace Database\Factories;

use App\Models\Cld;
use App\Models\Groupement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cld>
 */
class CldFactory extends Factory
{
    protected $model = Cld::class;

    public function definition(): array
    {
        return [
            'groupement_id' => Groupement::factory(),
            'nom' => 'CLD '.fake()->unique()->lastName(),
        ];
    }
}
