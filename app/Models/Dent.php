<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabinet;
use App\Support\Odontologie;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dent extends Model
{
    use BelongsToCabinet, SoftDeletes;

    protected $fillable = [
        'cabinet_id', 'patient_id', 'numero', 'presence', 'restauration', 'endodontie',
        'faces', 'constatations', 'notes', 'modifie_par',
    ];

    protected $casts = ['faces' => 'array', 'constatations' => 'array', 'numero' => 'integer'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modifie_par');
    }

    /** Données cliniques comparables, utilisées pour l'historique. */
    public function etat(): array
    {
        return [
            'presence' => $this->presence,
            'restauration' => $this->restauration,
            'endodontie' => $this->endodontie,
            'faces' => $this->faces ?? Odontologie::facesSaines(),
            'constatations' => array_values($this->constatations ?? []),
            'notes' => $this->notes,
        ];
    }

    /**
     * Statut de synthèse pour la couleur de l'odontogramme, par ordre de priorité.
     */
    public function synthese(): string
    {
        return self::syntheseDe($this->etat());
    }

    public static function syntheseDe(array $etat): string
    {
        $faces = $etat['faces'] ?? [];

        return match (true) {
            in_array($etat['restauration'], ['implant', 'couronne_implant'], true) => 'implant',
            $etat['presence'] !== 'presente' => 'absente',
            in_array('carie', $faces, true) || !empty(array_intersect($etat['constatations'] ?? [], ['carie_secondaire', 'abces', 'pulpite', 'necrose', 'fracture'])) => 'a_traiter',
            in_array($etat['restauration'], ['couronne', 'facette', 'pilier_bridge', 'pontique'], true) => 'prothese',
            $etat['endodontie'] !== 'non_traitee' => 'endo',
            in_array('obturation', $faces, true) => 'obturee',
            default => 'saine',
        };
    }
}
