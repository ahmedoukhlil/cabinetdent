<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AnalysePatient extends Model
{
    protected $table = 'analyses_patient';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'fkidPatient',
        'fkidCabinet',
        'fkidConsultation',
        'libelle',
        'type',
        'fichier_path',
        'fichier_nom',
        'fichier_mime',
        'fichier_taille',
        'date_analyse',
        'notes',
    ];

    protected $casts = [
        'date_analyse'     => 'date',
        'fkidPatient'      => 'integer',
        'fkidCabinet'      => 'integer',
        'fkidConsultation' => 'integer',
        'fichier_taille'   => 'integer',
    ];

    public function getUrlAttribute(): string
    {
        return Storage::url($this->fichier_path);
    }

    public function getEstImageAttribute(): bool
    {
        return str_starts_with($this->fichier_mime ?? '', 'image/');
    }

    public function getEstPdfAttribute(): bool
    {
        return $this->fichier_mime === 'application/pdf';
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'fkidPatient', 'ID');
    }
}
