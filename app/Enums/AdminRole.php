<?php

namespace App\Enums;

/**
 * Role administratif d'un utilisateur, independant de sa fonction de
 * terrain (voir App\Models\User::$fonction). `null` (aucune valeur) signifie
 * qu'un compte n'a aucun droit d'administration — c'est le cas de tous les
 * agents de terrain.
 *
 * Un seul role pour cette premiere version (section 5 du cahier des
 * charges, etape 10) : un systeme RBAC plus riche pourra etre ajoute plus
 * tard en completant cet enum, sans rien casser (voir User::isAssistantTechnique()
 * et le Gate `access-admin`, seuls points d'appui utilises par les routes).
 */
enum AdminRole: string
{
    case ASSISTANT_TECHNIQUE = 'assistant_technique';
}
