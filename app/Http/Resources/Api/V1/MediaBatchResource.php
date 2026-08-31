<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaBatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'local_id' => $this->local_id,
            'mission_id' => $this->mission_id,
            'cld_id' => $this->cld_id,
            'village_id' => $this->village_id,
            'activite' => $this->activite,
            'description' => $this->description,
            'date_activite' => $this->date_activite?->format('Y-m-d'),
            'statut' => $this->statut?->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => MediaItemResource::collection($this->whenLoaded('mediaItems')),
        ];
    }
}
