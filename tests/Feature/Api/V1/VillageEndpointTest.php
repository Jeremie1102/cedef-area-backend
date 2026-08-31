<?php

namespace Tests\Feature\Api\V1;

use App\Models\Cld;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VillageEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_recuperer_les_villages_dun_cld_autorise(): void
    {
        $user = User::factory()->create();
        $cld = Cld::factory()->create();
        Village::factory(3)->create(['cld_id' => $cld->id]);
        $user->clds()->attach($cld->id, ['statut' => 'active']);

        $response = $this->actingAsToken($user)->getJson("/api/v1/me/clds/{$cld->id}/villages");

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_refuse_acces_a_un_cld_non_autorise(): void
    {
        $user = User::factory()->create();
        $cld = Cld::factory()->create();
        Village::factory(2)->create(['cld_id' => $cld->id]);

        $response = $this->actingAsToken($user)->getJson("/api/v1/me/clds/{$cld->id}/villages");

        $response->assertStatus(403);
    }

    protected function actingAsToken(User $user): static
    {
        $token = $user->createToken('test')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }
}
