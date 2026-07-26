<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientNotification extends Model
{
    protected $fillable = [
        'patient_id',
        'titre',
        'corps',
        'url',
        'lu_le',
    ];

    protected $casts = [
        'lu_le' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'ID');
    }
}
