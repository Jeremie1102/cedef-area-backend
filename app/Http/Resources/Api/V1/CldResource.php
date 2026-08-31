<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CldResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'groupement' => new GroupementResource($this->whenLoaded('groupement')),
            'affectation' => $this->whenPivotLoaded('cld_user', fn () => [
                'date_debut' => $this->pivot->date_debut,
                'date_fin' => $this->pivot->date_fin,
                'statut' => $this->pivot->statut,
            ]),
        ];
    }
}
