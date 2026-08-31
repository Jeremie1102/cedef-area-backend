<?php

namespace App\Enums;

enum MissionStatus: string
{
    case PLANNED = 'planned';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
