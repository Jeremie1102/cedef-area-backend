<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_rate_limiting_bloque_apres_5_tentatives(): void
    {
        User::factory()->create([
            'email' => 'agent@cedef.test',
            'password' => Hash::make('secret123'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'agent@cedef.test',
                'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'agent@cedef.test',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_bootstrap_ne_contient_jamais_le_mot_de_passe(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/bootstrap');

        $response->assertStatus(200);
        $this->assertStringNotContainsString('"password"', $response->getContent());
    }
}
