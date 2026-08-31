<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CldResource;
use App\Http\Resources\Api\V1\MissionResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Resources\Api\V1\VillageResource;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BootstrapController extends Controller
{
    /**
     * Jeu de données minimal permettant à Flutter d'amorcer son fonctionnement hors ligne :
     * profil de l'agent, CLD affectés, villages de ces CLD, missions affectées.
     * Volumétrie naturellement bornée par le périmètre propre à l'agent connecté.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $clds = $user->clds()->with('groupement.sector')->get();

        $villages = Village::query()
            ->whereIn('cld_id', $clds->pluck('id'))
            ->get();

        $missions = $user->missions()
            ->with(['sector', 'groupement', 'clds', 'villages'])
            ->orderByDesc('date_debut_prevue')
            ->get();

        return response()->json([
            'user' => new UserResource($user),
            'clds' => CldResource::collection($clds),
            'villages' => VillageResource::collection($villages),
            'missions' => MissionResource::collection($missions),
        ]);
    }
}
