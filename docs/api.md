# API CEDEF AREA — v1

Base URL : `http://<host>/api/v1`

Toutes les réponses sont en JSON. Les routes protégées nécessitent l'en-tête :

```
Authorization: Bearer <token>
```

## Authentification

### POST /api/v1/auth/login

Authentifie un agent et retourne un token Sanctum.

- Auth requise : non
- Rate limiting : 5 tentatives / minute / IP

**Requête**

```json
{
    "email": "agent@example.com",
    "password": "motdepasse"
}
```

**Réponse 200**

```json
{
    "message": "Connexion réussie.",
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer",
    "user": {
        "id": 1,
        "nom": "Ondricka",
        "postnom": "Wisozk",
        "prenom": "Emmalee",
        "email": "agent@example.com",
        "fonction": "animateur",
        "photo_profil": null,
        "actif": true
    }
}
```

**Erreurs**
- `401` identifiants invalides
- `403` compte désactivé (`actif = false`)
- `422` champs manquants/invalides
- `429` trop de tentatives

---

### POST /api/v1/auth/logout

Révoque le token utilisé pour la requête.

- Auth requise : oui (`auth:sanctum`)

**Réponse 200**

```json
{ "message": "Déconnexion réussie." }
```

---

### GET /api/v1/auth/me

Retourne le profil de l'utilisateur connecté (déterminé uniquement via le token, jamais via un paramètre).

- Auth requise : oui

**Réponse 200**

```json
{
    "user": {
        "id": 1,
        "nom": "Ondricka",
        "postnom": "Wisozk",
        "prenom": "Emmalee",
        "email": "agent@example.com",
        "fonction": "animateur",
        "photo_profil": null,
        "actif": true
    }
}
```

## Données de l'agent connecté

### GET /api/v1/me/clds

CLD auxquels l'agent connecté est affecté (pagination Laravel standard, 20/page).

- Auth requise : oui

**Réponse 200**

```json
{
    "data": [
        {
            "id": 6,
            "nom": "CLD Wunsch",
            "groupement": {
                "id": 3,
                "nom": "Groupement stad",
                "sector": { "id": 2, "nom": "Secteur Lake Nikki" }
            },
            "affectation": { "date_debut": "2026-07-27", "date_fin": null, "statut": "active" }
        }
    ],
    "links": { "...": "..." },
    "meta": { "...": "..." }
}
```

---

### GET /api/v1/me/clds/{cld}/villages

Villages d'un CLD, uniquement si l'agent connecté y est affecté (pagination 50/page).

- Auth requise : oui

**Erreurs**
- `403` l'agent n'est pas affecté à ce CLD
- `404` le CLD n'existe pas

**Réponse 200**

```json
{
    "data": [
        { "id": 16, "nom": "Village Gibson", "cld_id": 6 }
    ]
}
```

---

### GET /api/v1/me/missions

Missions affectées à l'agent connecté, via la relation `mission_user` (pagination 20/page).

- Auth requise : oui

**Réponse 200 (extrait)**

```json
{
    "data": [
        {
            "id": 2,
            "titre": "...",
            "description": "...",
            "type_activite": "enquete",
            "statut": "planned",
            "date_debut_prevue": "2026-08-28",
            "date_fin_prevue": "2026-10-09",
            "observations": null,
            "sector": { "id": 2, "nom": "Secteur Lake Nikki" },
            "groupement": null,
            "clds": [{ "id": 3, "nom": "CLD Schamberger" }],
            "villages": [{ "id": 1, "nom": "Village Kreiger", "cld_id": 1 }],
            "affectation": { "role": "agent_terrain", "statut": "active", "date_affectation": "2026-08-27" }
        }
    ]
}
```

---

### GET /api/v1/bootstrap

Amorçage du mode hors ligne : profil, CLD affectés, villages de ces CLD, missions affectées — en un seul appel. Réponse non paginée mais naturellement bornée au périmètre de l'agent connecté.

- Auth requise : oui

**Réponse 200**

```json
{
    "user": { "...": "..." },
    "clds": [ "..." ],
    "villages": [ "..." ],
    "missions": [ "..." ]
}
```

### POST /api/v1/media-batches

Synchronise un lot de médias créé hors ligne sur le mobile : métadonnées du lot et fichiers photo, dans une seule requête `multipart/form-data`. Reçoit lot **et** photos ensemble (pas de flux séparé) : il n'existe donc pas d'état intermédiaire « lot reçu, photos manquantes » — la requête réussit entièrement ou échoue entièrement (transaction + nettoyage des fichiers déjà écrits en cas d'échec).

- Auth requise : oui
- Content-Type : `multipart/form-data`

**Champs**

| Champ | Type | Requis | Description |
|---|---|---|---|
| `local_id` | string | oui | Identifiant local stable généré par le mobile — clé d'idempotence (avec l'utilisateur authentifié). |
| `mission_id` | integer | oui | Id serveur de la mission (`missions.id`). L'agent doit y être affecté. |
| `cld_id` | integer | non | Id serveur du CLD. Doit être accessible à l'agent et faire partie du territoire de la mission. |
| `village_id` | integer | non | Id serveur du village. Doit appartenir au `cld_id` fourni. |
| `activite` | string | non | Libellé de l'activité. |
| `description` | string | oui | Non vide, 2000 caractères max. |
| `date_activite` | date (`Y-m-d`) | non | Date de l'activité de terrain. |
| `latitude`, `longitude`, `precision` | numeric | non | Position GPS capturée au niveau du lot. |
| `photos[]` | file[] | oui | 1 à 30 fichiers image (`jpeg`/`jpg`/`png`/`webp`, 15 Mo max chacun). |
| `photos_local_ids[]` | string[] | oui | Un identifiant local par photo, **dans le même ordre** que `photos[]` — l'ordre d'arrivée devient `media_items.order`. |

**Réponse 201 (nouveau lot)**

```json
{
    "message": "Lot synchronisé avec succès.",
    "batch": {
        "id": 42,
        "uuid": "...",
        "local_id": "BATCH-7f8e9a",
        "mission_id": 2,
        "cld_id": 6,
        "village_id": 16,
        "activite": "Sensibilisation",
        "description": "Séance de sensibilisation du CLD.",
        "date_activite": "2026-08-29",
        "statut": "synced",
        "created_at": "2026-08-29T10:42:00+00:00",
        "items": [
            { "id": 101, "uuid": "...", "local_id": "item-a", "order": 0, "original_name": "a.jpg", "mime_type": "image/jpeg", "size": 812345, "url": "http://.../storage/media/agents/1/missions/2/batches/.../xxxx.jpg", "statut": "synced" }
        ]
    }
}
```

**Réponse 200 (idempotence — `local_id` déjà reçu pour cet utilisateur)**

Même forme que ci-dessus (`message: "Lot déjà synchronisé."`), sans retraiter ni dupliquer photos ou lot — voir `unique(user_id, local_id)` sur `media_batches`. Renvoyer le même `local_id` un nombre quelconque de fois (réseau instable, retentatives) ne crée jamais qu'une seule ligne.

**Erreurs**
- `401` token absent/invalide
- `403` agent non affecté à la mission, ou CLD non accessible à l'agent
- `404` mission, CLD ou village inexistant
- `422` description vide, aucune photo, type de fichier interdit, fichier trop volumineux, CLD hors du territoire de la mission, village n'appartenant pas au CLD indiqué, décalage entre `photos[]` et `photos_local_ids[]`

**Idempotence et local_id**

La clé d'idempotence est `(user_id, local_id)` (contrainte unique en base). Le mobile génère `local_id` avant tout envoi et le conserve inchangé pour toutes les tentatives d'un même lot ; le serveur n'a donc jamais besoin de savoir si une tentative précédente a réellement atteint le réseau pour éviter un doublon.

**Stockage des fichiers**

Disque `public`, sous `media/agents/{user_id}/missions/{mission_id}/batches/{batch_uuid}/`. Le nom de fichier est généré par le serveur (jamais le nom fourni par le téléphone, conservé uniquement comme métadonnée `original_name`).

---

### POST /api/v1/gps-positions

Synchronise un **lot** de positions GPS collectées hors ligne. Une seule requête JSON pour plusieurs positions (jamais une requête par position, voir section 6/29 du cahier des charges).

- Auth requise : oui
- Content-Type : `application/json`

**Requête**

```json
{
    "positions": [
        {
            "local_id": "GPS-20260829-00001",
            "mission_id": 12,
            "latitude": -4.123456,
            "longitude": 15.123456,
            "altitude": 420.5,
            "accuracy": 12.4,
            "speed": 1.2,
            "heading": 180.0,
            "captured_at": "2026-08-29T10:42:15Z"
        }
    ]
}
```

| Champ | Requis | Description |
|---|---|---|
| `local_id` | oui | Identifiant local stable — clé d'idempotence avec l'utilisateur authentifié. |
| `mission_id` | oui | Id serveur de la mission. L'agent doit y être affecté. |
| `latitude` | oui | Entre -90 et 90. |
| `longitude` | oui | Entre -180 et 180. |
| `altitude`, `accuracy`, `speed`, `heading` | non | `accuracy` doit être positive si fournie ; conservées `null` si absentes, jamais devinées. |
| `captured_at` | oui | Date/heure de capture réelle (idéalement UTC) — jamais remplacée par l'heure d'arrivée serveur. |

Jusqu'à 200 positions par requête (le mobile envoie par lots de 100, voir `AppConfig.gpsSyncBatchSize`).

**Réponse 200**

```json
{
    "message": "Positions synchronisées.",
    "accepted": [
        { "local_id": "GPS-20260829-00001", "id": 501, "uuid": "..." }
    ],
    "already_synced": [
        { "local_id": "GPS-20260829-00000", "id": 498, "uuid": "..." }
    ],
    "rejected": [
        { "local_id": "GPS-20260829-00002", "reason": "Latitude hors intervalle (-90 à 90)." }
    ]
}
```

Chaque position du lot se retrouve dans exactement une des trois listes — jamais silencieusement ignorée. `id` est l'identifiant auto-incrémenté Laravel (utilisé par le mobile comme `server_id` local) ; `uuid` est renvoyé pour information mais n'a pas besoin d'être conservé côté mobile (l'idempotence repose sur `local_id`, pas sur l'uuid).

**Synchronisation partielle** : une position invalide (coordonnées hors intervalle, mission non autorisée, `local_id` dupliqué dans le lot) est placée dans `rejected` **sans** faire échouer les autres positions du même lot — sauf en cas de champ structurellement absent/mal typé (`local_id` manquant, `latitude` non numérique, ...), qui fait échouer la requête entière en 422 (signe d'un bug client, pas d'une donnée de terrain isolée).

**Idempotence** : clé `(user_id, local_id)` (contrainte unique en base), comme pour les lots de médias. Renvoyer le même lot un nombre quelconque de fois ne crée jamais de doublon — les positions déjà connues reviennent dans `already_synced`.

**Ordre chronologique** : `captured_at` est conservé tel quel (jamais remplacé par l'heure de réception) ; des positions reçues dans le désordre (reprise réseau tardive) ne sont jamais considérées comme une erreur — le parcours sera reconstitué en triant par `captured_at`.

**Erreurs**
- `401` token absent/invalide — arrêter l'envoi des lots suivants côté mobile, conserver toutes les positions.
- `422` lot structurellement malformé (`positions` absent/vide, champ requis manquant ou mal typé).

**Performance** : un seul `INSERT` groupé pour toutes les positions acceptées d'un lot (pas une requête par position), et au plus 3 requêtes de lecture (doublons déjà connus, missions référencées, missions affectées à l'agent) quelle que soit la taille du lot.

## Sécurité

- L'utilisateur est **toujours** déterminé via le token Sanctum (`$request->user()`), jamais via un identifiant fourni par le client.
- Un agent ne peut jamais lister les CLD, villages ou missions d'un autre agent.
- Un agent ne peut synchroniser un lot de médias ou de positions GPS que pour une mission à laquelle il est affecté, et un CLD auquel il a accès.
- Le mot de passe et le `remember_token` ne sont jamais exposés dans les réponses JSON.
- Les comptes `actif = false` ne peuvent pas obtenir de token.
