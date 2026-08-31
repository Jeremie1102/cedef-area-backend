<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SyncStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMediaBatchRequest;
use App\Http\Resources\Api\V1\MediaBatchResource;
use App\Models\Cld;
use App\Models\MediaBatch;
use App\Models\MediaItem;
use App\Models\Mission;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Réception des lots de médias envoyés par le mobile (étape 8).
 *
 * Un lot est toujours reçu en une seule requête multipart (métadonnées +
 * fichiers) : voir `StoreMediaBatchRequest`. Ce choix évite tout état
 * intermédiaire « lot reçu mais photos manquantes » (section 31 du cahier
 * des charges) — la requête réussit entièrement ou échoue entièrement.
 */
class MediaBatchController extends Controller
{
    public function store(StoreMediaBatchRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $mission = Mission::findOrFail($data['mission_id']);
        $this->authorize('view', $mission);

        $cld = null;
        if (! empty($data['cld_id'])) {
            $cld = Cld::findOrFail($data['cld_id']);
            $this->authorize('view', $cld);
            if (! $mission->clds()->where('clds.id', $cld->id)->exists()) {
                return response()->json([
                    'message' => 'Ce CLD ne fait pas partie du territoire de cette mission.',
                ], 422);
            }
        }

        $village = null;
        if (! empty($data['village_id'])) {
            $village = Village::findOrFail($data['village_id']);
            if ($cld === null || $village->cld_id !== $cld->id) {
                return response()->json([
                    'message' => 'Ce village n\'appartient pas au CLD indiqué.',
                ], 422);
            }
        }

        // Idempotence (section 10/11) : un lot déjà reçu pour cet agent est
        // reconnu par (user_id, local_id) — contrainte unique en base — et
        // retourné tel quel, sans retraiter les photos ni créer de doublon.
        $existing = MediaBatch::where('user_id', $user->id)
            ->where('local_id', $data['local_id'])
            ->first();
        if ($existing !== null) {
            return response()->json([
                'message' => 'Lot déjà synchronisé.',
                'batch' => new MediaBatchResource($existing->load('mediaItems')),
            ]);
        }

        $photos = $request->file('photos');
        $localIds = $data['photos_local_ids'];
        $directory = "media/agents/{$user->id}/missions/{$mission->id}";

        $storedPaths = [];
        try {
            $batch = DB::transaction(function () use (
                $user, $mission, $cld, $village, $data, $photos, $localIds, $directory, &$storedPaths
            ) {
                $batch = MediaBatch::create([
                    'local_id' => $data['local_id'],
                    'user_id' => $user->id,
                    'mission_id' => $mission->id,
                    'cld_id' => $cld?->id,
                    'village_id' => $village?->id,
                    'activite' => $data['activite'] ?? null,
                    'description' => $data['description'],
                    'date_activite' => $data['date_activite'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'precision' => $data['precision'] ?? null,
                    'statut' => SyncStatus::SYNCED,
                ]);

                foreach ($photos as $index => $photo) {
                    // Jamais le nom d'origine envoyé par le téléphone (section
                    // 18) : `store()` génère un nom serveur unique et sûr.
                    $path = $photo->store("$directory/{$batch->uuid}", 'public');
                    $storedPaths[] = $path;

                    MediaItem::create([
                        'media_batch_id' => $batch->id,
                        'local_id' => $localIds[$index],
                        'file_path' => $path,
                        'original_name' => $photo->getClientOriginalName(),
                        'mime_type' => $photo->getClientMimeType(),
                        'size' => $photo->getSize(),
                        'order' => $index,
                        'statut' => SyncStatus::SYNCED,
                    ]);
                }

                return $batch;
            });
        } catch (Throwable $e) {
            // Le rollback de la transaction annule les lignes déjà insérées ;
            // il ne touche pas au disque (hors SGBD), donc les fichiers déjà
            // écrits sont nettoyés manuellement (section 19 : jamais de
            // fichier orphelin sans référence en base).
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'Lot synchronisé avec succès.',
            'batch' => new MediaBatchResource($batch->load('mediaItems')),
        ], 201);
    }
}
