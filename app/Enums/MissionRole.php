<?php

namespace App\Enums;

/**
 * Role d'un agent dans une mission donnee (pivot `mission_user.role`) — a ne
 * pas confondre avec App\Enums\UserFunction, la fonction generale de
 * l'utilisateur (section 17 du cahier des charges, etape 10). Un animateur
 * (fonction) peut par exemple etre `responsable_terrain` (role) sur une
 * mission et simple `agent_terrain` sur une autre.
 *
 * Aucun enum n'existait pour ce champ avant l'API admin ; `agent_terrain`
 * reprend la valeur deja utilisee dans les donnees de demonstration et la
 * documentation existante (voir docs/api.md, GET /me/missions).
 */
enum MissionRole: string
{
    case AGENT_TERRAIN = 'agent_terrain';
    case RESPONSABLE_TERRAIN = 'responsable_terrain';
}
