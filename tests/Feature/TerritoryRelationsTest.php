<?php

namespace Tests\Feature;

use App\Models\Cld;
use App\Models\Groupement;
use App\Models\Sector;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerritoryRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_secteur_possede_plusieurs_groupements(): void
    {
        $secteur = Sector::factory()->create();
        Groupement::factory(3)->create(['sector_id' => $secteur->id]);

        $this->assertCount(3, $secteur->groupements);
    }

    public function test_un_groupement_appartient_a_un_secteur(): void
    {
        $secteur = Sector::factory()->create();
        $groupement = Groupement::factory()->create(['sector_id' => $secteur->id]);

        $this->assertTrue($groupement->sector->is($secteur));
    }

    public function test_un_groupement_possede_plusieurs_clds(): void
    {
        $groupement = Groupement::factory()->create();
        Cld::factory(4)->create(['groupement_id' => $groupement->id]);

        $this->assertCount(4, $groupement->clds);
    }

    public function test_un_cld_appartient_a_un_groupement(): void
    {
        $groupement = Groupement::factory()->create();
        $cld = Cld::factory()->create(['groupement_id' => $groupement->id]);

        $this->assertTrue($cld->groupement->is($groupement));
    }

    public function test_un_cld_possede_plusieurs_villages(): void
    {
        $cld = Cld::factory()->create();
        Village::factory(5)->create(['cld_id' => $cld->id]);

        $this->assertCount(5, $cld->villages);
    }

    public function test_un_village_appartient_a_un_cld(): void
    {
        $cld = Cld::factory()->create();
        $village = Village::factory()->create(['cld_id' => $cld->id]);

        $this->assertTrue($village->cld->is($cld));
    }
}
