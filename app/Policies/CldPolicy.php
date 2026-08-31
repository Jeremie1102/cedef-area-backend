<?php

namespace App\Policies;

use App\Models\Cld;
use App\Models\User;

class CldPolicy
{
    public function view(User $user, Cld $cld): bool
    {
        return $user->clds()->where('clds.id', $cld->id)->exists();
    }
}
