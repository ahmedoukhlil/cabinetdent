<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DossierMedical extends Model
{
    protected $table = 'dossier_medical';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'fkidPatient',
        'fkidCabinet',
        'antecedents_personnels',
        'antecedents_familiaux',
        'antecedents_chirurgicaux',
        'groupe_sanguin',
        'allergies',
        'maladies_chroniques',
        'traitements_permanents',
        'alertes_dentaires',
        'alertes_dentaires_detail',
        'notes',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'fkidPatient', 'ID');
    }

    public function consultations()
    {
        return $this->hasMany(ConsultationMedicale::class, 'fkidPatient', 'fkidPatient')
                    ->where('fkidCabinet', $this->fkidCabinet)
                    ->orderBy('date_consultation', 'desc');
    }
}
