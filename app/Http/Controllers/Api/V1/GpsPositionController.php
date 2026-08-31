<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreGpsPositionsRequest;
use App\Models\GpsPosition;
use App\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Réception des positions GPS envoyées par lot par le mobile (étape 9).
 *
 * Traite tout le lot en une passe avec un nombre de requêtes SQL indépendant
 * de sa taille (section 29) : une requête pour les doublons déjà connus, une
 * pour les missions référencées, une pour les missions affectées à l'agent,
 * puis un seul `insert()` groupé pour les nouvelles positions — jamais de
 * boucle `create()` position par position.
 *
 * L'autorisation « l'agent est-il affecté à cette mission » est ici une
 * vérification directe par ensemble d'identifiants plutôt qu'un appel à
 * `MissionPolicy` (voir `MediaBatchController`) : un lot peut référencer des
 * dizaines de missions différentes (voir section 40, plusieurs missions dans
 * un même lot), et vérifier chacune via une requête de policy séparée
 * annulerait l'intérêt des requêtes groupées ci-dessus.
 */
class GpsPositionController extends Controller
{
    public function store(StoreGpsPositionsRequest $request): JsonResponse
    {
        $user = $request->user();
        $positions = $request->validated()['positions'];

        $localIds = array_column($positions, 'local_id');
        $existingByLocalId = GpsPosition::where('user_id', $user->id)
            ->whereIn('local_id', $localIds)
            ->get(['local_id', 'id', 'uuid'])
            ->keyBy('local_id');

        $missionIds = array_values(array_unique(array_column($positions, 'mission_id')));
        $existingMissionIds = Mission::whereIn('id', $missionIds)->pluck('id')->all();
        $assignedMissionIds = $user->missions()->whereIn('missions.id', $missionIds)->pluck('missions.id')->all();

        $accepted = [];
        $alreadySynced = [];
        $rejected = [];
        $rowsToInsert = [];
        $now = Carbon::now();

        foreach ($positions as $position) {
            $localId = $position['local_id'];

            if ($existingByLocalId->has($localId)) {
                $existing = $existingByLocalId[$localId];
                $alreadySynced[] = ['local_id' => $localId, 'id' => $existing->id, 'uuid' => $existing->uuid];
                continue;
            }

            // Deux positions du même lot avec le même local_id (retransmission
            // maladroite côté client) : la seconde est un doublon local, pas
            // encore connu du serveur au moment de la requête ci-dessus.
            if (isset($rowsToInsert[$localId])) {
                $rejected[] = ['local_id' => $localId, 'reason' => 'local_id en double dans ce lot.'];
                continue;
            }

            $missionId = $position['mission_id'];
            if (! in_array($missionId, $existingMissionIds, true)) {
                $rejected[] = ['local_id' => $localId, 'reason' => 'Mission introuvable.'];
                continue;
            }
            if (! in_array($missionId, $assignedMissionIds, true)) {
                $rejected[] = ['local_id' => $localId, 'reason' => 'Mission non autorisée pour cet agent.'];
                continue;
            }

            $latitude = (float) $position['latitude'];
            $longitude = (float) $position['longitude'];
            if ($latitude < -90 || $latitude > 90) {
                $rejected[] = ['local_id' => $localId, 'reason' => 'Latitude hors intervalle (-90 à 90).'];
                continue;
            }
            if ($longitude < -180 || $longitude > 180) {
                $rejected[] = ['local_id' => $localId, 'reason' => 'Longitude hors intervalle (-180 à 180).'];
                continue;
            }
            $accuracy = array_key_exists('accuracy', $position) ? $position['accuracy'] : null;
            if ($accuracy !== null && $accuracy < 0) {
                $rejected[] = ['local_id' => $localId, 'reason' => 'Précision GPS invalide (doit être positive).'];
                continue;
            }

            $uuid = (string) Str::uuid();
            $rowsToInsert[$localId] = [
                'uuid' => $uuid,
                'local_id' => $localId,
                'user_id' => $user->id,
                'mission_id' => $missionId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'precision' => $accuracy,
                'altitude' => $position['altitude'] ?? null,
                'speed' => $position['speed'] ?? null,
                'heading' => $position['heading'] ?? null,
                // L'heure de capture réelle est conservée telle quelle (jamais
                // remplacée par l'heure d'arrivée serveur, voir section 22) :
                // seule `captured_at` permet de reconstituer le vrai parcours.
                'captured_at' => Carbon::parse($position['captured_at']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $accepted[] = ['local_id' => $localId, 'uuid' => $uuid];
        }

        if (! empty($rowsToInsert)) {
            DB::transaction(function () use ($rowsToInsert) {
                GpsPosition::insert(array_values($rowsToInsert));
            });

            // `insert()` groupé contourne les événements Eloquent (donc les
            // `id` auto-incrémentés ne sont pas connus après coup sans une
            // requête dédiée) : on les retrouve par `local_id`, déjà unique
            // par utilisateur, pour les inclure dans `accepted`.
            $insertedIds = GpsPosition::where('user_id', $user->id)
                ->whereIn('local_id', array_keys($rowsToInsert))
                ->pluck('id', 'local_id');
            foreach ($accepted as &$entry) {
                $entry['id'] = $insertedIds[$entry['local_id']] ?? null;
            }
            unset($entry);
        }

        return response()->json([
            'message' => 'Positions synchronisées.',
            'accepted' => $accepted,
            'already_synced' => $alreadySynced,
            'rejected' => $rejected,
        ]);
    }
}
