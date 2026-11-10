<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cabinet extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'telephone', 'adresse', 'duree_rdv_minutes', 'heure_ouverture', 'heure_fermeture', 'jours_ouverture', 'actif'];

    public const JOURS = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 7 => 'Dim'];

    /** Lundi à vendredi par défaut. */
    public const JOURS_DEFAUT = [1, 2, 3, 4, 5];

    protected $casts = ['actif' => 'boolean', 'duree_rdv_minutes' => 'integer', 'jours_ouverture' => 'array'];

    public function estOuvertLe(\Carbon\CarbonInterface $jour): bool
    {
        return in_array($jour->isoWeekday(), $this->jours_ouverture ?? self::JOURS_DEFAUT, true);
    }

    public function abonnement(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        // Abonnement courant : le plus récent.
        return $this->hasOne(Abonnement::class)->latestOfMany();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
