<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cabinet extends Model
{
    protected $table = 'cabinet';
    protected $primaryKey = 'idCabinet';
    public $timestamps = false;

    protected $fillable = [
        'NomCabinet',
        'Adresse',
        'Telephone',
        'Email',
        'Logo',
        'Masquer'
    ];

    public function factures()
    {
        return $this->hasMany(Facture::class, 'fkidCabinet', 'idCabinet');
    }

    public function medecins()
    {
        return $this->hasMany(Medecin::class, 'fkidCabinet', 'idCabinet');
    }
} 