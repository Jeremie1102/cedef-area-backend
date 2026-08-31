<?php

namespace Tests\Feature\Api\V1;

use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_recuperer_ses_missions(): void
    {
        $user = User::factory()->create();
        $missions = Mission::factory(2)->create();
        $user->missions()->attach($missions->pluck('id'), ['statut' => 'active']);

        $response = $this->actingAsToken($user)->getJson('/api/v1/me/missions');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_ne_recupere_pas_les_missions_dun_autre_utilisateur(): void
    {
        $user = User::factory()->create();
        $autreUser = User::factory()->create();

        $mesMissions = Mission::factory(1)->create();
        $missionsAutrui = Mission::factory(2)->create();

        $user->missions()->attach($mesMissions->pluck('id'), ['statut' => 'active']);
        $autreUser->missions()->attach($missionsAutrui->pluck('id'), ['statut' => 'active']);

        $response = $this->actingAsToken($user)->getJson('/api/v1/me/missions');

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertCount(1, $ids);
        $this->assertFalse($ids->intersect($missionsAutrui->pluck('id'))->isNotEmpty());
    }

    protected function actingAsToken(User $user): static
    {
        $token = $user->createToken('test')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }
}
