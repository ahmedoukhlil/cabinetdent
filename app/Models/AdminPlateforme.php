<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/** Administrateur de la plateforme Sysdental (tous les cabinets, abonnements). */
class AdminPlateforme extends Authenticatable
{
    protected $table = 'admins_plateforme';

    protected $fillable = ['name', 'email', 'password', 'actif', 'derniere_connexion_at'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['actif' => true];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'actif' => 'boolean', 'derniere_connexion_at' => 'datetime'];
    }
}
