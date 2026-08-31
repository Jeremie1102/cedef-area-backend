<?php

namespace App\Enums;

enum SyncStatus: string
{
    case PENDING = 'pending';
    case SYNCED = 'synced';
    case FAILED = 'failed';
}
