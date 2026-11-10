<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabinet;
use Illuminate\Database\Eloquent\Model;

class Assureur extends Model
{
    use BelongsToCabinet;

    protected $fillable = ['cabinet_id', 'nom', 'taux_pec', 'actif'];

    protected $casts = ['taux_pec' => 'float', 'actif' => 'boolean'];

    public function patients(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Patient::class);
    }

    /** Accepte 80 ou 0,8 et ramène toujours entre 0 et 1. */
    public static function normaliserTaux(float $valeur): float
    {
        $taux = max(0, $valeur);
        if ($taux > 1) {
            $taux /= 100;
        }

        return round(min(1, $taux), 4);
    }

    public function getPourcentageAttribute(): string
    {
        return rtrim(rtrim(number_format($this->taux_pec * 100, 2, ',', ''), '0'), ',').' %';
    }
}
