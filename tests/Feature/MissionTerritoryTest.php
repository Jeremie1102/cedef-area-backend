<?php

namespace Tests\Feature;

use App\Models\Cld;
use App\Models\Mission;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionTerritoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_une_mission_peut_cibler_plusieurs_clds(): void
    {
        $mission = Mission::factory()->create();
        $clds = Cld::factory(3)->create();

        $mission->clds()->attach($clds->pluck('id'));

        $this->assertCount(3, $mission->clds);
    }

    public function test_une_mission_peut_cibler_plusieurs_villages(): void
    {
        $mission = Mission::factory()->create();
        $villages = Village::factory(4)->create();

        $mission->villages()->attach($villages->pluck('id'));

        $this->assertCount(4, $mission->villages);
    }

    public function test_un_cld_connait_ses_missions(): void
    {
        $cld = Cld::factory()->create();
        $mission = Mission::factory()->create();

        $mission->clds()->attach($cld->id);

        $this->assertTrue($cld->missions->contains($mission));
    }
}
