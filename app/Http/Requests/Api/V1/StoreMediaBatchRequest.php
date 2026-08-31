<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validation de forme d'un lot de médias envoyé par le mobile
 * (`POST /api/v1/media-batches`) : présence et type des champs, contraintes
 * sur les fichiers. La cohérence des relations (mission/CLD/village,
 * affectation de l'agent) est vérifiée dans le contrôleur, une fois les
 * modèles résolus — voir `MediaBatchController::store`.
 */
class StoreMediaBatchRequest extends FormRequest
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
            // L'existence de la mission/du CLD/du village n'est volontairement
            // pas validée ici via `exists:` : le contrôleur la vérifie lui-même
            // (`findOrFail`) pour retourner 404, distinct du 422 réservé aux
            // champs mal formés (voir section 24 du cahier des charges).
            'local_id' => ['required', 'string', 'max:64'],
            'mission_id' => ['required', 'integer'],
            'cld_id' => ['nullable', 'integer'],
            'village_id' => ['nullable', 'integer'],
            'activite' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'date_activite' => ['nullable', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'precision' => ['nullable', 'numeric', 'min:0'],
            // Limite alignée sur `AppConfig.mediaBatchMaxPhotos` côté Flutter.
            'photos' => ['required', 'array', 'min:1', 'max:30'],
            // 15 Mo : large marge au-dessus d'une photo compressée côté mobile
            // (voir `AppConfig.mediaCompressionQuality`/`mediaCompressionMaxDimension`),
            // tout en bornant la taille d'une requête.
            'photos.*' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:15360'],
            'photos_local_ids' => ['required', 'array'],
            'photos_local_ids.*' => ['required', 'string', 'max:64'],
        ];
    }

    /**
     * `photos` et `photos_local_ids` doivent avoir le même nombre d'éléments
     * (un identifiant local par photo, dans le même ordre — voir section 16
     * du cahier des charges) : une règle `size:` classique ne peut pas
     * comparer deux champs entre eux, d'où cette vérification manuelle.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $photos = $this->file('photos') ?? [];
            $localIds = $this->input('photos_local_ids') ?? [];
            if (count($photos) !== count($localIds)) {
                $validator->errors()->add(
                    'photos_local_ids',
                    'Le nombre d\'identifiants locaux doit correspondre au nombre de photos.',
                );
            }
        });
    }
}
