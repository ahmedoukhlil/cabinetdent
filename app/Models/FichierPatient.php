<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabinet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FichierPatient extends Model
{
    use BelongsToCabinet, SoftDeletes;

    protected $table = 'fichiers_patient';

    public const CATEGORIES = [
        'radio' => 'Radiographie',
        'scanner' => 'Scanner / cone beam',
        'photo' => 'Photographie',
        'compte_rendu' => 'Compte rendu',
        'biologie' => 'Biologie',
        'autre' => 'Autre document',
    ];

    public const MIMES = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    public const TAILLE_MAX_KO = 10240;

    protected $fillable = ['cabinet_id', 'patient_id', 'categorie', 'titre', 'date_examen', 'dents', 'chemin', 'nom_origine', 'mime', 'taille', 'ajoute_par'];

    protected $casts = ['date_examen' => 'date', 'dents' => 'array', 'taille' => 'integer'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ajoute_par');
    }

    public function estImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    public function getTailleLisibleAttribute(): string
    {
        return $this->taille >= 1048576
            ? number_format($this->taille / 1048576, 1, ',', ' ').' Mo'
            : max(1, (int) round($this->taille / 1024)).' Ko';
    }
}
