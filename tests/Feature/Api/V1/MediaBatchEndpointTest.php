<?php

namespace Tests\Feature\Api\V1;

use App\Models\Cld;
use App\Models\MediaBatch;
use App\Models\MediaItem;
use App\Models\Mission;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaBatchEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    protected function actingAsToken(User $user): static
    {
        $token = $user->createToken('test')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    /**
     * Mission affectée à $user, avec éventuellement un CLD (et un village de
     * ce CLD) rattachés à son territoire.
     *
     * @return array{0: Mission, 1: ?Cld, 2: ?Village}
     */
    protected function assignedMission(User $user, bool $withTerritory = false): array
    {
        $mission = Mission::factory()->create();
        $user->missions()->attach($mission->id, ['statut' => 'active']);

        $cld = null;
        $village = null;
        if ($withTerritory) {
            $cld = Cld::factory()->create();
            $village = Village::factory()->create(['cld_id' => $cld->id]);
            $mission->clds()->attach($cld->id);
            $user->clds()->attach($cld->id, ['statut' => 'active']);
        }

        return [$mission, $cld, $village];
    }

    protected function validPayload(Mission $mission, ?Cld $cld = null, ?Village $village = null): array
    {
        return [
            'local_id' => 'BATCH-'.fake()->uuid(),
            'mission_id' => $mission->id,
            'cld_id' => $cld?->id,
            'village_id' => $village?->id,
            'activite' => 'Sensibilisation',
            'description' => 'Séance de sensibilisation du CLD.',
            'date_activite' => now()->format('Y-m-d'),
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
            'photos_local_ids' => ['item-a', 'item-b'],
        ];
    }

    public function test_utilisateur_non_authentifie_est_rejete(): void
    {
        $mission = Mission::factory()->create();

        $response = $this->postJson('/api/v1/media-batches', $this->validPayload($mission));

        $response->assertStatus(401);
    }

    public function test_cree_un_lot_avec_plusieurs_photos(): void
    {
        $user = User::factory()->create();
        [$mission, $cld, $village] = $this->assignedMission($user, withTerritory: true);

        $response = $this->actingAsToken($user)
            ->postJson('/api/v1/media-batches', $this->validPayload($mission, $cld, $village));

        $response->assertStatus(201);
        $response->assertJsonPath('batch.statut', 'synced');
        $this->assertCount(2, $response->json('batch.items'));
        $this->assertDatabaseCount('media_batches', 1);
        $this->assertDatabaseCount('media_items', 2);
    }

    public function test_associe_le_lot_a_lutilisateur_authentifie_jamais_a_un_user_id_fourni(): void
    {
        $user = User::factory()->create();
        $autreUser = User::factory()->create();
        [$mission] = $this->assignedMission($user);

        $payload = $this->validPayload($mission);
        $payload['user_id'] = $autreUser->id; // tentative d'usurpation, doit être ignorée

        $response = $this->actingAsToken($user)->postJson('/api/v1/media-batches', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('media_batches', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('media_batches', ['user_id' => $autreUser->id]);
    }

    public function test_refuse_une_mission_a_laquelle_lagent_nest_pas_affecte(): void
    {
        $user = User::factory()->create();
        $missionDunAutre = Mission::factory()->create();

        $response = $this->actingAsToken($user)
            ->postJson('/api/v1/media-batches', $this->validPayload($missionDunAutre));

        $response->assertStatus(403);
        $this->assertDatabaseCount('media_batches', 0);
    }

    public function test_refuse_une_mission_inexistante(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload(Mission::factory()->create());
        $payload['mission_id'] = 999999;

        $response = $this->actingAsToken($user)->postJson('/api/v1/media-batches', $payload);

        $response->assertStatus(404);
    }

    public function test_refuse_un_cld_auquel_lagent_na_pas_acces(): void
    {
        $user = User::factory()->create();
        [$mission] = $this->assignedMission($user);
        $cldDunAutre = Cld::factory()->create();
        $mission->clds()->attach($cldDunAutre->id);

        $response = $this->actingAsToken($user)
            ->postJson('/api/v1/media-batches', $this->validPayload($mission, $cldDunAutre));

        $response->assertStatus(403);
        $this->assertDatabaseCount('media_batches', 0);
    }

    public function test_refuse_un_cld_hors_du_territoire_de_la_mission(): void
    {
        $user = User::factory()->create();
        [$mission] = $this->assignedMission($user);
        $cldHorsMission = Cld::factory()->create();
        $user->clds()->attach($cldHorsMission->id, ['statut' => 'active']);

        $response = $this->actingAsToken($user)
            ->postJson('/api/v1/media-batches', $this->validPayload($mission, $cldHorsMission));

        $response->assertStatus(422);
        $this->assertDatabaseCount('media_batches', 0);
    }

    public function test_refuse_un_village_qui_nappartient_pas_au_cld_indique(): void
    {
        $user = User::factory()->create();
        [$mission, $cld] = $this->assignedMission($user, withTerritory: true);
        $villageDunAutreCld = Village::factory()->create();

        $response = $this->actingAsToken($user)
            ->postJson('/api/v1/media-batches', $this->validPayload($mission, $cld, $villageDunAutreCld));

        $response->assertStatus(422);
        $this->assertDatabaseCount('media_batches', 0);
    }

    public function test_la_description_est_obligatoire(): void
    {
        $user = User::factory()->create();
        [$mission] = $this->assignedMission($user);
        $payload = $this->validPayload($mission);
        $payload['description'] = '';

        $response = $this->actingAsToken($user)->postJson('/api/v1/media-batches', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('description');
    }

    public function test_refuse_un_lot_sans_aucune_photo(): void
    {
        $user = User::factory()->create();
        [$mission] = $this->assignedMission($user);
        $payload = $this->validPayload($mission);
        $payload['photos'] = [];
        $payload['photos_local_ids'] = [];

        $response = $this->actingAsToken($user)->postJson('/api/v1/media-batches', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('photos');
    }

    public function test_refuse_un_type_de_fichier_interdit(): void
    {
        $user = User::factory()->create();
        [$mission] = $this->assignedMission($user);
        $payload = $this->validPayload($mission);
        $payload['photos'] = [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')];
        $payload['photos_local_ids'] = ['item-a'];

        $response = $this->actingAsToken($user)->postJson('/api/v1/media-batches', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('photos.0');
        $this->assertDatabaseCount('media_batches', 0);
    }

    public function test_refuse_un_fichier_trop_volumineux(): void
    {
        $user = User::factory()->create();
        [$mission] = $this->assignedMission($user);
        $payload = $this->validPayload($mission);
        // 15360 Ko est la limite (voir StoreMediaBatchRequest) : 16000 la dépasse.
        $payload['photos'] = [UploadedFile::fake()->image('trop_gros.jpg')->size(16000)];
        $payload['photos_local_ids'] = ['item-a'];

        $response = $this->actingAsToken($user)->postJson('/api/v1/media-batches', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('photos.0');
    }

    public function test_conserve_lordre_des_photos(): void
    {
        $user = User::factory()->create();
        [$mission] = $this->assignedMission($user);
        $payload = $this->validPayload($mission);
        $payload['photos'] = [
            UploadedFile::fake()->image('premiere.jpg'),
            UploadedFile::fake()->image('deuxieme.jpg'),
            UploadedFile::fake()->image('troisieme.jpg'),
        ];
        $payload['photos_local_ids'] = ['p1', 'p2', 'p3'];

        $response = $this->actingAsToken($user)->postJson('/api/v1/media-batches', $payload);

        $response->assertStatus(201);
        $items = MediaItem::where('media_batch_id', $response->json('batch.id'))
            ->orderBy('order')
            ->get();
        $this->assertSame(['p1', 'p2', 'p3'], $items->pluck('local_id')->all());
        $this->assertSame([0, 1, 2], $items->pluck('order')->all());
    }

    public function test_stocke_les_fichiers_sur_le_disque_public(): void
    {
        $user = User::factory()->create();
        [$mission] = $this->assignedMission($user);

        $response = $this->actingAsToken($user)
            ->postJson('/api/v1/media-batches', $this->validPayload($mission));

        $response->assertStatus(201);
        $items = MediaItem::where('media_batch_id', $response->json('batch.id'))->get();
        foreach ($items as $item) {
            Storage::disk('public')->assertExists($item->file_path);
        }
    }

    public function test_ne_fait_jamais_confiance_au_nom_de_fichier_du_telephone(): void
    {
        $user = User::factory()->create();
        [$mission] = $this->assignedMission($user);
        $payload = $this->validPayload($mission);
        $payload['photos'] = [UploadedFile::fake()->image('nom douteux !.jpg')];
        $payload['photos_local_ids'] = ['item-a'];

        $response = $this->actingAsToken($user)->postJson('/api/v1/media-batches', $payload);

        $response->assertStatus(201);
        $item = MediaItem::where('media_batch_id', $response->json('batch.id'))->first();
        // Le chemin de stockage est généré côté serveur (voir
        // MediaBatchController::store), jamais dérivé du nom fourni par le
        // téléphone : seule la métadonnée `original_name` le conserve.
        $this->assertNotSame($item->original_name, basename($item->file_path));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+\.jpg$/', basename($item->file_path));
        $this->assertSame('nom douteux !.jpg', $item->original_name);
    }

    public function test_idempotence_un_meme_local_id_envoye_plusieurs_fois_ne_cree_quun_seul_lot(): void
    {
        $user = User::factory()->create();
        [$mission] = $this->assignedMission($user);
        $payload = $this->validPayload($mission);

        $first = $this->actingAsToken($user)->postJson('/api/v1/media-batches', $payload);
        $first->assertStatus(201);

        for ($i = 0; $i < 4; $i++) {
            $retry = $this->actingAsToken($user)->postJson('/api/v1/media-batches', $payload);
            $retry->assertStatus(200);
            $retry->assertJsonPath('batch.local_id', $payload['local_id']);
        }

        $this->assertDatabaseCount('media_batches', 1);
        $this->assertDatabaseCount('media_items', 2);
    }

    public function test_idempotence_par_utilisateur_deux_agents_peuvent_utiliser_le_meme_local_id(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        [$missionA] = $this->assignedMission($userA);
        [$missionB] = $this->assignedMission($userB);
        $payload = $this->validPayload($missionA);
        $sameLocalId = $payload['local_id'];

        $this->actingAsToken($userA)->postJson('/api/v1/media-batches', $payload)->assertStatus(201);

        // Le guard Sanctum mémorise l'utilisateur résolu pour la durée du
        // test : sans ce reset, la deuxième requête (token différent)
        // s'authentifierait encore comme $userA.
        $this->app['auth']->forgetGuards();

        $payloadB = $this->validPayload($missionB);
        $payloadB['local_id'] = $sameLocalId;
        $this->actingAsToken($userB)->postJson('/api/v1/media-batches', $payloadB)->assertStatus(201);

        $this->assertDatabaseCount('media_batches', 2);
    }

    public function test_rollback_et_nettoyage_des_fichiers_si_lenregistrement_echoue_en_cours_de_route(): void
    {
        $user = User::factory()->create();
        [$mission] = $this->assignedMission($user);
        $payload = $this->validPayload($mission);
        // Deux photos avec le même local_id : la contrainte unique
        // (media_batch_id, local_id) échoue au deuxième INSERT, une fois la
        // première photo déjà écrite sur le disque — simule une panne en
        // cours de traitement du lot (section 19).
        $payload['photos_local_ids'] = ['meme-id', 'meme-id'];

        try {
            $this->actingAsToken($user)->postJson('/api/v1/media-batches', $payload);
        } catch (\Throwable) {
            // L'exception de contrainte n'est volontairement pas absorbée par
            // le contrôleur (voir MediaBatchController::store) : seul le
            // nettoyage compte ici.
        }

        $this->assertDatabaseCount('media_batches', 0);
        $this->assertDatabaseCount('media_items', 0);
        $files = Storage::disk('public')->allFiles();
        $this->assertEmpty($files, 'Aucun fichier orphelin ne doit subsister après un échec en cours de lot.');
    }
}
