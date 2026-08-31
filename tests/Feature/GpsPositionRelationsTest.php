<?php

namespace Tests\Feature;

use App\Models\GpsPosition;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class GpsPositionRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_une_mission_possede_plusieurs_positions_gps(): void
    {
        $mission = Mission::factory()->create();
        GpsPosition::factory(4)->create(['mission_id' => $mission->id]);

        $this->assertCount(4, $mission->gpsPositions);
    }

    public function test_une_position_gps_appartient_a_une_mission_et_a_un_utilisateur(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();
        $position = GpsPosition::factory()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
        ]);

        $this->assertTrue($position->user->is($user));
        $this->assertTrue($position->mission->is($mission));
    }

    public function test_uuid_est_genere_automatiquement(): void
    {
        $position = GpsPosition::factory()->create();

        $this->assertNotEmpty($position->uuid);
        $this->assertTrue(Str::isUuid($position->uuid));
    }

    public function test_local_id_est_unique_par_utilisateur(): void
    {
        $user = User::factory()->create();

        GpsPosition::factory()->create(['user_id' => $user->id, 'local_id' => 'device-abc-1']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        GpsPosition::factory()->create(['user_id' => $user->id, 'local_id' => 'device-abc-1']);
    }

    public function test_suppression_utilisateur_avec_historique_gps_est_bloquee(): void
    {
        $user = User::factory()->create();
        GpsPosition::factory()->create(['user_id' => $user->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('users')->where('id', $user->id)->delete();
    }
}
