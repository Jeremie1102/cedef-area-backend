<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de forme d'un lot de positions GPS envoyé par le mobile
 * (`POST /api/v1/gps-positions`) : présence et type des champs uniquement.
 *
 * Les règles *métier* (latitude/longitude hors intervalle, mission
 * inexistante ou non autorisée, doublon) sont volontairement **absentes**
 * d'ici et vérifiées position par position dans le contrôleur : une seule
 * position invalide dans un lot de 100 ne doit jamais faire échouer les 99
 * autres (section 19/28 du cahier des charges) — alors qu'un lot
 * structurellement malformé (type incorrect, champ requis absent) reste
 * rejeté en bloc, ce qui indique un bug client plutôt qu'une donnée de
 * terrain isolée.
 */
class StoreGpsPositionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Plafond serveur défensif, indépendant de la taille de lot choisie
            // côté mobile (voir `AppConfig.gpsSyncBatchSize`) : borne la taille
            // d'une requête quel que soit l'appelant.
            'positions' => ['required', 'array', 'min:1', 'max:200'],
            'positions.*.local_id' => ['required', 'string', 'max:64'],
            'positions.*.mission_id' => ['required', 'integer'],
            'positions.*.latitude' => ['required', 'numeric'],
            'positions.*.longitude' => ['required', 'numeric'],
            'positions.*.altitude' => ['nullable', 'numeric'],
            'positions.*.accuracy' => ['nullable', 'numeric'],
            'positions.*.speed' => ['nullable', 'numeric'],
            'positions.*.heading' => ['nullable', 'numeric'],
            'positions.*.captured_at' => ['required', 'date'],
        ];
    }
}
