<?php

namespace App\Models;

use App\Enums\StatutRdv;
use App\Models\Concerns\BelongsToCabinet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RendezVous extends Model
{
    use BelongsToCabinet, HasFactory;

    protected $table = 'rendez_vous';

    protected $fillable = [
        'cabinet_id', 'patient_id', 'dentiste_id', 'date', 'heure', 'ordre',
        'acte_prevu', 'facture_id', 'statut', 'arrive_at', 'debut_at', 'fin_at', 'cree_par',
    ];

    protected $casts = [
        'date' => 'date',
        'statut' => StatutRdv::class,
        'arrive_at' => 'datetime',
        'debut_at' => 'datetime',
        'fin_at' => 'datetime',
    ];

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    /** Consultation déjà facturée (facture non annulée). */
    public function consultationFacturee(): bool
    {
        return $this->facture_id && $this->facture?->statut !== 'annulee';
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentiste(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentiste_id');
    }

    public function getHeureCourteAttribute(): string
    {
        return substr((string) $this->heure, 0, 5);
    }

    public function scopeDuJour(Builder $query, $date = null): Builder
    {
        return $query->whereDate('date', $date ?? today());
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('statut', '!=', StatutRdv::Annule->value);
    }

    /** Restreint aux RDV du dentiste lorsque l'utilisateur n'a que la permission « own ». */
    public function scopeVisiblesPar(Builder $query, User $user): Builder
    {
        if (!$user->aPermission('rendez-vous.edit') && $user->aPermission('rendez-vous.own')) {
            $query->where('dentiste_id', $user->id);
        }

        return $query;
    }
}
