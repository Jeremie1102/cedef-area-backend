<?php

namespace Database\Seeders;

use App\Enums\UserFunction;
use App\Models\Cld;
use App\Models\Groupement;
use App\Models\Mission;
use App\Models\Sector;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Seeder;

/**
 * Jeu de données fictif pour valider les relations du modèle CEDEF AREA.
 * Ne représente aucune donnée réelle de terrain.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $secteurs = Sector::factory(2)->create();

        $clds = collect();
        $villages = collect();

        foreach ($secteurs as $secteur) {
            $groupements = Groupement::factory(2)->create(['sector_id' => $secteur->id]);

            foreach ($groupements as $groupement) {
                $cldsDuGroupement = Cld::factory(2)->create(['groupement_id' => $groupement->id]);
                $clds = $clds->merge($cldsDuGroupement);

                foreach ($cldsDuGroupement as $cld) {
                    $villagesDuCld = Village::factory(3)->create(['cld_id' => $cld->id]);
                    $villages = $villages->merge($villagesDuCld);
                }
            }
        }

        $agents = collect(UserFunction::cases())->map(
            fn (UserFunction $fonction) => User::factory()->create(['fonction' => $fonction])
        );

        foreach ($agents as $agent) {
            $agent->clds()->attach(
                $clds->random(2)->pluck('id'),
                ['date_debut' => now()->subMonth(), 'statut' => 'active']
            );
        }

        $missions = Mission::factory(3)->create([
            'sector_id' => fn () => $secteurs->random()->id,
        ]);

        foreach ($missions as $mission) {
            $mission->users()->attach(
                $agents->random(2)->pluck('id'),
                ['role' => 'agent_terrain', 'statut' => 'active', 'date_affectation' => now()]
            );
            $mission->clds()->attach($clds->random(2)->pluck('id'));
            $mission->villages()->attach($villages->random(3)->pluck('id'));
        }
    }
}
