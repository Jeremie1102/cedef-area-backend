<?php

namespace Tests\Feature\Api\V1;

use App\Models\Cld;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CldEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_recuperer_ses_clds(): void
    {
        $user = User::factory()->create();
        $clds = Cld::factory(2)->create();
        $user->clds()->attach($clds->pluck('id'), ['statut' => 'active']);

        $response = $this->actingAsToken($user)->getJson('/api/v1/me/clds');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_ne_voit_pas_les_clds_dun_autre_utilisateur(): void
    {
        $user = User::factory()->create();
        $autreUser = User::factory()->create();

        $mesClds = Cld::factory(1)->create();
        $cldsAutrui = Cld::factory(3)->create();

        $user->clds()->attach($mesClds->pluck('id'), ['statut' => 'active']);
        $autreUser->clds()->attach($cldsAutrui->pluck('id'), ['statut' => 'active']);

        $response = $this->actingAsToken($user)->getJson('/api/v1/me/clds');

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertCount(1, $ids);
        $this->assertFalse($ids->intersect($cldsAutrui->pluck('id'))->isNotEmpty());
    }

    protected function actingAsToken(User $user): static
    {
        $token = $user->createToken('test')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }
}
