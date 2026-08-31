<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nom'])]
class Sector extends Model
{
    use HasFactory;

    public function groupements(): HasMany
    {
        return $this->hasMany(Groupement::class);
    }

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class);
    }
}
