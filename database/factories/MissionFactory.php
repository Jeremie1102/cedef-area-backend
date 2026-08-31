<?php

namespace Database\Factories;

use App\Enums\MissionStatus;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mission>
 */
class MissionFactory extends Factory
{
    protected $model = Mission::class;

    public function definition(): array
    {
        return [
            'titre' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'type_activite' => fake()->randomElement(['sensibilisation', 'suivi', 'formation', 'enquete']),
            'date_debut_prevue' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'date_fin_prevue' => fake()->optional()->dateTimeBetween('+1 month', '+2 months')?->format('Y-m-d'),
            'statut' => MissionStatus::PLANNED,
            'sector_id' => null,
            'groupement_id' => null,
            'observations' => null,
        ];
    }
}
