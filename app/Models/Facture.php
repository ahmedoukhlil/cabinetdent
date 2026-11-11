<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabinet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facture extends Model
{
    use BelongsToCabinet;

    public const STATUTS = ['brouillon' => 'Brouillon', 'validee' => 'Validée', 'annulee' => 'Annulée'];

    protected $fillable = [
        'cabinet_id', 'patient_id', 'dentiste_id', 'numero', 'annee', 'sequence', 'statut', 'date_facture',
        'assureur_id', 'taux_pec', 'total', 'part_pec', 'part_patient', 'regle_patient', 'regle_pec',
        'validee_at', 'annulee_at', 'motif_annulation', 'cree_par',
    ];

    protected $casts = [
        'date_facture' => 'date', 'validee_at' => 'datetime', 'annulee_at' => 'datetime',
        'taux_pec' => 'float', 'total' => 'float', 'part_pec' => 'float', 'part_patient' => 'float',
        'regle_patient' => 'float', 'regle_pec' => 'float',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentiste(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentiste_id');
    }

    public function assureur(): BelongsTo
    {
        return $this->belongsTo(Assureur::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(FactureLigne::class)->orderBy('id');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class)->orderBy('id');
    }

    public function restePatient(): float
    {
        return round($this->part_patient - $this->regle_patient, 2);
    }

    public function restePec(): float
    {
        return round($this->part_pec - $this->regle_pec, 2);
    }

    /** La facture comporte une part prise en charge par une assurance. */
    public function estAssuree(): bool
    {
        return $this->part_pec > 0 && $this->taux_pec > 0;
    }

    /**
     * Part assurance et part patient de chaque ligne, au taux de la facture.
     * L'écart d'arrondi est porté par la dernière ligne pour retomber sur les totaux.
     *
     * @return array<int, array{pec: float, patient: float}> indexé par id de ligne
     */
    public function repartitionLignes(): array
    {
        $parts = [];
        $cumul = 0.0;
        $lignes = $this->lignes->values();
        foreach ($lignes as $i => $ligne) {
            $pec = $i === $lignes->count() - 1
                ? round($this->part_pec - $cumul, 2)
                : round($ligne->montant * $this->taux_pec, 2);
            $cumul += $pec;
            $parts[$ligne->id] = ['pec' => $pec, 'patient' => round($ligne->montant - $pec, 2)];
        }

        return $parts;
    }

    public function tauxLisible(): string
    {
        return rtrim(rtrim(number_format($this->taux_pec * 100, 2, ',', ''), '0'), ',')."\u{00A0}%";
    }

    public function estSoldee(): bool
    {
        return $this->statut === 'validee' && $this->restePatient() <= 0 && $this->restePec() <= 0;
    }

    public function getLibelleAttribute(): string
    {
        return $this->numero ? "Facture {$this->numero}" : 'Devis (brouillon)';
    }

    public function scopeImpayees(Builder $query): Builder
    {
        return $query->where('statut', 'validee')
            ->where(fn ($q) => $q->whereColumn('regle_patient', '<', 'part_patient')->orWhereColumn('regle_pec', '<', 'part_pec'));
    }
}
