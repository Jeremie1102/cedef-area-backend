<?php

namespace Tests\Feature;

use App\Models\Cld;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCldMissionAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_utilisateur_peut_avoir_plusieurs_clds(): void
    {
        $user = User::factory()->create();
        $clds = Cld::factory(3)->create();

        $user->clds()->attach($clds->pluck('id'), ['statut' => 'active']);

        $this->assertCount(3, $user->clds);
    }

    public function test_un_cld_peut_etre_affecte_a_plusieurs_utilisateurs(): void
    {
        $cld = Cld::factory()->create();
        $users = User::factory(3)->create();

        $cld->users()->attach($users->pluck('id'), ['statut' => 'active']);

        $this->assertCount(3, $cld->users);
    }

    public function test_un_utilisateur_peut_avoir_plusieurs_missions(): void
    {
        $user = User::factory()->create();
        $missions = Mission::factory(3)->create();

        $user->missions()->attach($missions->pluck('id'), ['statut' => 'active']);

        $this->assertCount(3, $user->missions);
    }

    public function test_une_mission_peut_avoir_plusieurs_utilisateurs(): void
    {
        $mission = Mission::factory()->create();
        $users = User::factory(3)->create();

        $mission->users()->attach($users->pluck('id'), ['statut' => 'active']);

        $this->assertCount(3, $mission->users);
    }

    public function test_pivot_cld_user_conserve_les_metadonnees(): void
    {
        $user = User::factory()->create();
        $cld = Cld::factory()->create();

        $user->clds()->attach($cld->id, [
            'date_debut' => '2026-01-01',
            'statut' => 'active',
        ]);

        $pivot = $user->clds()->first()->pivot;

        $this->assertSame('2026-01-01', $pivot->date_debut);
        $this->assertSame('active', $pivot->statut);
    }
}
