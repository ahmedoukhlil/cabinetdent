<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 't_user';
    protected $primaryKey = 'Iduser';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'login',
        'password',
        'NomComplet',
        'IdClasseUser',
        'fonction',
        'fkidmedecin',
        'DtCr',
        'fkidcabinet'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relation avec le type d'utilisateur
    public function typeuser()
    {
        return $this->belongsTo(TypeUser::class, 'IdClasseUser', 'IdClasseUser0');
    }

    // Relation avec le médecin
    public function medecin()
    {
        return $this->belongsTo(Medecin::class, 'fkidmedecin', 'idMedecin');
    }

    // Relation avec le cabinet
    public function cabinet()
    {
        return $this->belongsTo(Infocabinet::class, 'fkidcabinet', 'idEntete');
    }

    // Méthodes de vérification des rôles
    public function isDocteurProprietaire()
    {
        return $this->typeuser && $this->typeuser->Libelle === 'Docteur Propriétaire';
    }

    public function isDocteur()
    {
        return $this->typeuser && $this->typeuser->Libelle === 'Docteur';
    }

    public function isSecretaire()
    {
        return $this->typeuser && $this->typeuser->Libelle === 'Secrétaire';
    }
}
