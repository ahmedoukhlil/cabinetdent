<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabinet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Patient extends Model
{
    use BelongsToCabinet, HasFactory, SoftDeletes;

    protected $fillable = [
        'cabinet_id', 'numero_fiche', 'nom', 'prenom', 'sexe', 'date_naissance',
        'telephone', 'nni', 'adresse', 'assureur_id', 'matricule_assure', 'notes', 'actif',
    ];

    protected $casts = ['date_naissance' => 'date', 'actif' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (Patient $patient) {
            if (empty($patient->numero_fiche)) {
                $patient->numero_fiche = (int) DB::table('patients')
                    ->where('cabinet_id', $patient->cabinet_id)
                    ->lockForUpdate()
                    ->max('numero_fiche') + 1;
            }
        });
    }

    public function rendezVous(): HasMany
    {
        return $this->hasMany(RendezVous::class);
    }

    public function assureur(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Assureur::class);
    }

    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class);
    }

    public function dents(): HasMany
    {
        return $this->hasMany(Dent::class);
    }

    public function plansTraitement(): HasMany
    {
        return $this->hasMany(PlanTraitement::class);
    }

    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }

    public function scopeRecherche(Builder $query, ?string $terme): Builder
    {
        $terme = trim((string) $terme);
        if ($terme === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($terme) {
            $q->where('nom', 'like', "%{$terme}%")
                ->orWhere('prenom', 'like', "%{$terme}%")
                ->orWhere('telephone', 'like', "%{$terme}%");
            if (ctype_digit($terme)) {
                $q->orWhere('numero_fiche', (int) $terme);
            }
        });
    }
}
