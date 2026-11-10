<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/** Administrateur de la plateforme Sysdental (tous les cabinets, abonnements). */
class AdminPlateforme extends Authenticatable
{
    protected $table = 'admins_plateforme';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
