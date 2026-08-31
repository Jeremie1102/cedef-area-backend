<?php

namespace Tests\Feature\Api\V1;

use App\Models\GpsPosition;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GpsPositionEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsToken(User $user): static
    {
        $token = $user->createToken('test')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    protected function assignedMission(User $user): Mission
    {
        $mission = Mission::factory()->create();
        $user->missions()->attach($mission->id, ['statut' => 'active']);

        return $mission;
    }

    protected function positionPayload(Mission $mission, array $overrides = []): array
    {
        return array_merge([
            'local_id' => 'GPS-'.fake()->uuid(),
            'mission_id' => $mission->id,
            'latitude' => -4.325,
            'longitude' => 15.322,
            'accuracy' => 12.4,
            'altitude' => 420.5,
            'speed' => 1.2,
            'heading' => 180.0,
            'captured_at' => now()->toIso8601String(),
        ], $overrides);
    }

    public function test_utilisateur_non_authentifie_est_rejete(): void
    {
        $mission = Mission::factory()->create();

        $response = $this->postJson('/api/v1/gps-positions', [
            'positions' => [$this->positionPayload($mission)],
        ]);

        $response->assertStatus(401);
    }

    public function test_recoit_un_lot_et_accepte_les_positions_valides(): void
    {
        $user = User::factory()->create();
        $mission = $this->assignedMission($user);

        $response = $this->actingAsToken($user)->postJson('/api/v1/gps-positions', [
            'positions' => [$this->positionPayload($mission), $this->positionPayload($mission)],
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('accepted'));
        $this->assertCount(0, $response->json('rejected'));
        $this->assertDatabaseCount('gps_positions', 2);
    }

    public function test_associe_les_positions_a_lutilisateur_authentifie_jamais_a_un_user_id_fourni(): void
    {
        $user = User::factory()->create();
        $autreUser = User::factory()->create();
        $mission = $this->assignedMission($user);

        $payload = $this->positionPayload($mission);
        $payload['user_id'] = $autreUser->id; // tentative d'usurpation, doit être ignorée

        $this->actingAsToken($user)->postJson('/api/v1/gps-positions', ['positions' => [$payload]])
            ->assertStatus(200);

        $this->assertDatabaseHas('gps_positions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('gps_positions', ['user_id' => $autreUser->id]);
    }

    public function test_rejette_une_position_pour_une_mission_a_laquelle_lagent_nest_pas_affecte(): void
    {
        $user = User::factory()->create();
        $missionDunAutre = Mission::factory()->create();

        $response = $this->actingAsToken($user)->postJson('/api/v1/gps-positions', [
            'positions' => [$this->positionPayload($missionDunAutre)],
        ]);

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('accepted'));
        $this->assertCount(1, $response->json('rejected'));
        $this->assertDatabaseCount('gps_positions', 0);
    }

    public function test_rejette_une_position_pour_une_mission_inexistante(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAsToken($user)->postJson('/api/v1/gps-positions', [
            'positions' => [$this->positionPayload(Mission::factory()->create(), ['mission_id' => 999999])],
        ]);

        $response->assertStatus(200);
        $this->assertSame('Mission introuvable.', $response->json('rejected.0.reason'));
        $this->assertDatabaseCount('gps_positions', 0);
    }

    public function test_latitude_valide_est_acceptee(): void
    {
        $user = User::factory()->create();
        $mission = $this->assignedMission($user);

        $this->actingAsToken($user)->postJson('/api/v1/gps-positions', [
            'positions' => [$this->positionPayload($mission, ['latitude' => -89.9])],
        ])->assertStatus(200)->assertJsonCount(1, 'accepted');
    }

    public function test_latitude_hors_intervalle_est_rejetee_sans_bloquer_le_reste_du_lot(): void
    {
        $user = User::factory()->create();
        $mission = $this->assignedMission($user);

        $response = $this->actingAsToken($user)->postJson('/api/v1/gps-positions', [
            'positions' => [
                $this->positionPayload($mission, ['latitude' => 95]),
                $this->positionPayload($mission),
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('accepted'));
        $this->assertCount(1, $response->json('rejected'));
        $this->assertDatabaseCount('gps_positions', 1);
    }

    public function test_longitude_valide_est_acceptee(): void
    {
        $user = User::factory()->create();
        $mission = $this->assignedMission($user);

        $this->actingAsToken($user)->postJson('/api/v1/gps-positions', [
            'positions' => [$this->positionPayload($mission, ['longitude' => 179.9])],
        ])->assertStatus(200)->assertJsonCount(1, 'accepted');
    }

    public function test_longitude_hors_intervalle_est_rejetee(): void
    {
        $user = User::factory()->create();
        $mission = $this->assignedMission($user);

        $response = $this->actingAsToken($user)->postJson('/api/v1/gps-positions', [
            'positions' => [$this->positionPayload($mission, ['longitude' => -185])],
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('rejected'));
        $this->assertDatabaseCount('gps_positions', 0);
    }

    public function test_local_id_est_obligatoire(): void
    {
        $user = User::factory()->create();
        $mission = $this->assignedMission($user);
        $payload = $this->positionPayload($mission);
        unset($payload['local_id']);

        $response = $this->actingAsToken($user)->postJson('/api/v1/gps-positions', [
            'positions' => [$payload],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('positions.0.local_id');
    }

    public function test_doublon_local_id_dans_le_meme_lot_est_rejete_une_seule_fois_conservee(): void
    {
        $user = User::factory()->create();
        $mission = $this->assignedMission($user);
        $payload = $this->positionPayload($mission);

        $response = $this->actingAsToken($user)->postJson('/api/v1/gps-positions', [
            'positions' => [$payload, $payload],
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('accepted'));
        $this->assertCount(1, $response->json('rejected'));
        $this->assertDatabaseCount('gps_positions', 1);
    }

    public function test_idempotence_le_meme_lot_envoye_plusieurs_fois_ne_cree_aucun_doublon(): void
    {
        $user = User::factory()->create();
        $mission = $this->assignedMission($user);
        $payload = [
            'positions' => [
                $this->positionPayload($mission),
                $this->positionPayload($mission),
                $this->positionPayload($mission),
            ],
        ];

        $first = $this->actingAsToken($user)->postJson('/api/v1/gps-positions', $payload);
        $first->assertStatus(200);
        $this->assertCount(3, $first->json('accepted'));

        for ($i = 0; $i < 4; $i++) {
            $retry = $this->actingAsToken($user)->postJson('/api/v1/gps-positions', $payload);
            $retry->assertStatus(200);
            $this->assertCount(0, $retry->json('accepted'));
            $this->assertCount(3, $retry->json('already_synced'));
        }

        $this->assertDatabaseCount('gps_positions', 3);
    }

    public function test_plusieurs_positions_dans_un_seul_lot(): void
    {
        $user = User::factory()->create();
        $mission = $this->assignedMission($user);
        $positions = collect(range(1, 25))->map(fn () => $this->positionPayload($mission))->all();

        $response = $this->actingAsToken($user)->postJson('/api/v1/gps-positions', [
            'positions' => $positions,
        ]);

        $response->assertStatus(200);
        $this->assertCount(25, $response->json('accepted'));
        $this->assertDatabaseCount('gps_positions', 25);
    }

    public function test_les_donnees_optionnelles_absentes_restent_nulles(): void
    {
        $user = User::factory()->create();
        $mission = $this->assignedMission($user);
        $payload = $this->positionPayload($mission);
        unset($payload['altitude'], $payload['accuracy'], $payload['speed'], $payload['heading']);

        $this->actingAsToken($user)->postJson('/api/v1/gps-positions', ['positions' => [$payload]])
            ->assertStatus(200);

        $position = GpsPosition::where('local_id', $payload['local_id'])->first();
        $this->assertNull($position->altitude);
        $this->assertNull($position->precision);
        $this->assertNull($position->speed);
        $this->assertNull($position->heading);
    }

    public function test_captured_at_est_conserve_tel_quel_jamais_remplace_par_lheure_serveur(): void
    {
        $user = User::factory()->create();
        $mission = $this->assignedMission($user);
        $capturedAt = now()->subHours(3);
        $payload = $this->positionPayload($mission, ['captured_at' => $capturedAt->toIso8601String()]);

        $this->actingAsToken($user)->postJson('/api/v1/gps-positions', ['positions' => [$payload]])
            ->assertStatus(200);

        $position = GpsPosition::where('local_id', $payload['local_id'])->first();
        // Comparaison à la seconde près : `toIso8601String()` tronque déjà les
        // microsecondes envoyées, la valeur stockée ne doit pas en plus être
        // rapprochée de l'heure d'arrivée de la requête.
        $this->assertSame($capturedAt->timestamp, $position->captured_at->timestamp);
    }

    public function test_lordre_chronologique_est_reconstitue_a_partir_de_captured_at_meme_recu_dans_le_desordre(): void
    {
        $user = User::factory()->create();
        $mission = $this->assignedMission($user);
        $t1000 = now()->setTime(10, 0);
        $t1005 = now()->setTime(10, 5);
        $t1002 = now()->setTime(10, 2);

        // Envoyées dans le désordre (reprise réseau tardive) : le serveur ne
        // doit jamais les considérer comme une erreur (section 24).
        $this->actingAsToken($user)->postJson('/api/v1/gps-positions', [
            'positions' => [
                $this->positionPayload($mission, ['captured_at' => $t1000->toIso8601String()]),
                $this->positionPayload($mission, ['captured_at' => $t1005->toIso8601String()]),
                $this->positionPayload($mission, ['captured_at' => $t1002->toIso8601String()]),
            ],
        ])->assertStatus(200);

        $ordered = GpsPosition::where('mission_id', $mission->id)->orderBy('captured_at')->pluck('captured_at');
        $this->assertTrue($ordered[0]->equalTo($t1000));
        $this->assertTrue($ordered[1]->equalTo($t1002));
        $this->assertTrue($ordered[2]->equalTo($t1005));
    }

    public function test_la_reponse_distingue_accepted_already_synced_et_rejected(): void
    {
        $user = User::factory()->create();
        $mission = $this->assignedMission($user);
        $alreadyThere = $this->positionPayload($mission);
        $this->actingAsToken($user)->postJson('/api/v1/gps-positions', ['positions' => [$alreadyThere]])
            ->assertStatus(200);

        $response = $this->actingAsToken($user)->postJson('/api/v1/gps-positions', [
            'positions' => [
                $alreadyThere, // déjà synchronisée
                $this->positionPayload($mission), // nouvelle, valide
                $this->positionPayload($mission, ['latitude' => 999]), // invalide
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('accepted'));
        $this->assertCount(1, $response->json('already_synced'));
        $this->assertCount(1, $response->json('rejected'));
        $this->assertArrayHasKey('uuid', $response->json('accepted.0'));
        $this->assertArrayHasKey('uuid', $response->json('already_synced.0'));
        $this->assertArrayHasKey('reason', $response->json('rejected.0'));
    }

    public function test_synchronisation_partielle_98_acceptees_2_rejetees(): void
    {
        $user = User::factory()->create();
        $mission = $this->assignedMission($user);
        $positions = collect(range(1, 98))->map(fn () => $this->positionPayload($mission))->all();
        $positions[] = $this->positionPayload($mission, ['latitude' => 999]);
        $positions[] = $this->positionPayload($mission, ['longitude' => 999]);

        $response = $this->actingAsToken($user)->postJson('/api/v1/gps-positions', [
            'positions' => $positions,
        ]);

        $response->assertStatus(200);
        $this->assertCount(98, $response->json('accepted'));
        $this->assertCount(2, $response->json('rejected'));
        $this->assertDatabaseCount('gps_positions', 98);
    }

    public function test_un_agent_ne_peut_pas_consulter_les_positions_dun_autre(): void
    {
        $user = User::factory()->create();
        $autreUser = User::factory()->create();
        $mission = $this->assignedMission($autreUser);
        GpsPosition::factory()->create(['user_id' => $autreUser->id, 'mission_id' => $mission->id]);

        // Aucune route de lecture n'existe encore pour cette étape (section 43) :
        // on vérifie simplement qu'envoyer un lot ne renvoie jamais les
        // positions de quelqu'un d'autre dans la réponse.
        $myMission = $this->assignedMission($user);
        $response = $this->actingAsToken($user)->postJson('/api/v1/gps-positions', [
            'positions' => [$this->positionPayload($myMission)],
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('accepted'));
        $this->assertDatabaseCount('gps_positions', 2); // la mienne + celle de l'autre agent, jamais mélangées
        $this->assertSame(1, GpsPosition::where('user_id', $user->id)->count());
    }
}
