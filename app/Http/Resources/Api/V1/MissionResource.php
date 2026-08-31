<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'description' => $this->description,
            'type_activite' => $this->type_activite,
            'statut' => $this->statut?->value,
            'date_debut_prevue' => $this->date_debut_prevue?->format('Y-m-d'),
            'date_fin_prevue' => $this->date_fin_prevue?->format('Y-m-d'),
            'observations' => $this->observations,
            'sector' => new SectorResource($this->whenLoaded('sector')),
            'groupement' => new GroupementResource($this->whenLoaded('groupement')),
            'clds' => CldResource::collection($this->whenLoaded('clds')),
            'villages' => VillageResource::collection($this->whenLoaded('villages')),
            'affectation' => $this->whenPivotLoaded('mission_user', fn () => [
                'role' => $this->pivot->role,
                'statut' => $this->pivot->statut,
                'date_affectation' => $this->pivot->date_affectation,
            ]),
        ];
    }
}
