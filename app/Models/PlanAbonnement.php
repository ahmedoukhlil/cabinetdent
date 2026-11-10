<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanAbonnement extends Model
{
    protected $table = 'plans_abonnement';

    protected $fillable = ['nom', 'prix_mensuel', 'max_utilisateurs', 'max_stockage_mo', 'actif'];

    protected $casts = ['prix_mensuel' => 'float', 'max_utilisateurs' => 'integer', 'max_stockage_mo' => 'integer', 'actif' => 'boolean'];
}
