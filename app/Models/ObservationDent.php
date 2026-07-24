<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObservationDent extends Model
{
    protected $table = 'observations_dent';

    protected $fillable = [
        'patient_id',
        'num_dent',
        'texte',
        'medecin_id',
        'cabinet_id',
        'created_by',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'ID');
    }

    public function medecin()
    {
        return $this->belongsTo(Medecin::class, 'medecin_id', 'idMedecin');
    }

    public function scopeForPatientDent($query, $patientId, $numDent)
    {
        return $query->where('patient_id', $patientId)->where('num_dent', $numDent);
    }
}
