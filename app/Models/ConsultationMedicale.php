<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationMedicale extends Model
{
    protected $table = 'consultation_medicale';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'fkidPatient',
        'fkidMedecin',
        'fkidCabinet',
        'fkidFacture',
        'date_consultation',
        'motif',
        'tension_arterielle',
        'anesthesie',
        'examen_clinique',
        'diagnostic',
        'conduite_a_tenir',
        'medicaments_prescrits',
        'examens_demandes',
        'ordonnances_ids',
        'notes',
    ];

    protected $casts = [
        'date_consultation'    => 'datetime',
        'medicaments_prescrits' => 'array',
        'examens_demandes'      => 'array',
        'ordonnances_ids'       => 'array',
        'fkidPatient'          => 'integer',
        'fkidMedecin'          => 'integer',
        'fkidCabinet'          => 'integer',
        'fkidFacture'          => 'integer',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'fkidPatient', 'ID');
    }

    public function medecin()
    {
        return $this->belongsTo(Medecin::class, 'fkidMedecin', 'idMedecin');
    }
}
