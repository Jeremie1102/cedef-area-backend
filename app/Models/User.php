<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\AdminRole;
use App\Enums\UserFunction;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['nom', 'postnom', 'prenom', 'email', 'password', 'fonction', 'admin_role', 'photo_profil', 'actif'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'fonction' => UserFunction::class,
            'admin_role' => AdminRole::class,
            'actif' => 'boolean',
        ];
    }

    public function fullName(): string
    {
        return trim(collect([$this->nom, $this->postnom, $this->prenom])->filter()->implode(' '));
    }

    /**
     * Vrai si ce compte dispose du role administratif Assistant Technique —
     * seul point d'appui utilise par le Gate `access-admin` (voir
     * AppServiceProvider) : aucun controller ne doit tester `admin_role`
     * directement (section 6 du cahier des charges, etape 10).
     */
    public function isAssistantTechnique(): bool
    {
        return $this->admin_role === AdminRole::ASSISTANT_TECHNIQUE;
    }

    public function clds(): BelongsToMany
    {
        return $this->belongsToMany(Cld::class, 'cld_user')
            ->withPivot(['date_debut', 'date_fin', 'statut'])
            ->withTimestamps();
    }

    public function missions(): BelongsToMany
    {
        return $this->belongsToMany(Mission::class, 'mission_user')
            ->withPivot(['role', 'statut', 'date_affectation'])
            ->withTimestamps();
    }

    public function gpsPositions(): HasMany
    {
        return $this->hasMany(GpsPosition::class);
    }

    public function mediaBatches(): HasMany
    {
        return $this->hasMany(MediaBatch::class);
    }
}
