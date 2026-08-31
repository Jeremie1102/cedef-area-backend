<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_reussi(): void
    {
        $user = User::factory()->create([
            'email' => 'agent@cedef.test',
            'password' => Hash::make('secret123'),
            'actif' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'agent@cedef.test',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'token', 'token_type', 'user' => ['id', 'nom', 'email']])
            ->assertJsonPath('user.id', $user->id);

        $response->assertJsonMissingPath('user.password');
        $this->assertStringNotContainsString('"password"', $response->getContent());
    }

    public function test_login_mauvais_mot_de_passe(): void
    {
        User::factory()->create([
            'email' => 'agent@cedef.test',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'agent@cedef.test',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_utilisateur_inexistant(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inconnu@cedef.test',
            'password' => 'whatever123',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_utilisateur_inactif(): void
    {
        User::factory()->create([
            'email' => 'inactif@cedef.test',
            'password' => Hash::make('secret123'),
            'actif' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactif@cedef.test',
            'password' => 'secret123',
        ]);

        $response->assertStatus(403);
    }

    public function test_login_validation_422(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_acces_me_avec_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)->assertJsonPath('user.id', $user->id);
    }

    public function test_acces_me_sans_token(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_acces_me_avec_token_invalide(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer token-invalide-inexistant')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }
}
