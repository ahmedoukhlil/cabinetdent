<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabinet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    use BelongsToCabinet;

    /** Modes de la plateforme (abonnements). Les cabinets gèrent les leurs : voir ModePaiement. */
    public const MODES = [
        'especes' => 'Espèces',
        'mobile' => 'Paiement mobile',
        'carte' => 'Carte bancaire',
        'virement' => 'Virement',
        'cheque' => 'Chèque',
    ];

    protected $fillable = ['cabinet_id', 'facture_id', 'patient_id', 'dentiste_id', 'payeur', 'type', 'mode', 'montant', 'reference', 'user_id'];

    protected $casts = ['montant' => 'float'];

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Montant signé : négatif pour un remboursement. */
    public function getMontantSigneAttribute(): float
    {
        return $this->type === 'remboursement' ? -$this->montant : $this->montant;
    }
}
