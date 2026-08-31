<?php

namespace Tests\Feature;

use App\Models\MediaBatch;
use App\Models\MediaItem;
use App\Models\Mission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_une_mission_possede_plusieurs_lots_de_medias(): void
    {
        $mission = Mission::factory()->create();
        MediaBatch::factory(2)->create(['mission_id' => $mission->id]);

        $this->assertCount(2, $mission->mediaBatches);
    }

    public function test_un_lot_contient_plusieurs_photos(): void
    {
        $batch = MediaBatch::factory()->create();
        MediaItem::factory(5)->create(['media_batch_id' => $batch->id]);

        $this->assertCount(5, $batch->mediaItems);
    }

    public function test_une_photo_appartient_a_un_seul_lot(): void
    {
        $batch = MediaBatch::factory()->create();
        $item = MediaItem::factory()->create(['media_batch_id' => $batch->id]);

        $this->assertTrue($item->mediaBatch->is($batch));
    }

    public function test_uuid_media_batch_et_media_item_sont_generes(): void
    {
        $batch = MediaBatch::factory()->create();
        $item = MediaItem::factory()->create(['media_batch_id' => $batch->id]);

        $this->assertNotEmpty($batch->uuid);
        $this->assertNotEmpty($item->uuid);
    }

    public function test_suppression_lot_supprime_ses_photos_en_cascade(): void
    {
        $batch = MediaBatch::factory()->create();
        MediaItem::factory(3)->create(['media_batch_id' => $batch->id]);

        $batch->delete();

        $this->assertDatabaseCount('media_items', 0);
    }
}
