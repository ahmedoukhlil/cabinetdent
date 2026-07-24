<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanTraitementDentaire extends Model
{
    protected $table = 'plan_traitement_dentaire';

    protected $fillable = [
        'patient_id',
        'num_dent',
        'acte_id',
        'acte_libelle',
        'medecin_id',
        'statut',
        'prix_ref',
        'cabinet_id',
        'detail_facture_id',
        'facture_id',
        'created_by',
        'updated_by',
        'notes',
    ];

    protected $casts = [
        'prix_ref' => 'float',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'ID');
    }

    public function acte()
    {
        return $this->belongsTo(Acte::class, 'acte_id', 'ID');
    }

    public function medecin()
    {
        return $this->belongsTo(Medecin::class, 'medecin_id', 'idMedecin');
    }

    public function detailFacture()
    {
        return $this->belongsTo(Detailfacturepatient::class, 'detail_facture_id', 'idDetfacture');
    }

    public function facture()
    {
        return $this->belongsTo(Facture::class, 'facture_id', 'Idfacture');
    }

    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function isFacturable(): bool
    {
        return $this->statut !== 'termine';
    }
}
