<?php

namespace App\Services;

use App\Models\Depense;
use App\Models\FactureLigne;
use App\Models\Paiement;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Indicateurs par praticien sur une période (documentation §10.9 et §10.10).
 * - facturé : lignes des factures validées datées dans la période ;
 * - encaissé : paiements nets (encaissements − remboursements) de la période ;
 * - dépenses : dépenses rattachées au praticien ; les frais du cabinet restent à part ;
 * - bilan : encaissé − dépenses.
 */
class StatistiquesService
{
    public function parPraticien(User $user, CarbonInterface $du, CarbonInterface $au): array
    {
        $lignes = FactureLigne::query()
            ->join('factures', 'factures.id', '=', 'facture_lignes.facture_id')
            ->where('factures.cabinet_id', $user->cabinet_id)
            ->where('factures.statut', 'validee')
            ->whereDate('factures.date_facture', '>=', $du->toDateString())
            ->whereDate('factures.date_facture', '<=', $au->toDateString())
            ->get(['factures.dentiste_id', 'factures.patient_id', 'facture_lignes.montant']);

        $paiements = Paiement::query()
            ->whereBetween('created_at', [$du->copy()->startOfDay(), $au->copy()->endOfDay()])
            ->get(['dentiste_id', 'type', 'montant']);

        $depenses = Depense::query()
            ->whereDate('date_depense', '>=', $du->toDateString())
            ->whereDate('date_depense', '<=', $au->toDateString())
            ->get(['dentiste_id', 'montant']);

        $ligne = function (?int $dentisteId, string $nom) use ($lignes, $paiements, $depenses): array {
            $meme = fn ($id) => ($id === null ? null : (int) $id) === $dentisteId;
            $l = $lignes->filter(fn ($x) => $meme($x->dentiste_id));
            $encaisse = $paiements->filter(fn ($p) => $meme($p->dentiste_id))
                ->sum(fn ($p) => $p->type === 'remboursement' ? -$p->montant : $p->montant);
            $dep = $depenses->filter(fn ($d) => $meme($d->dentiste_id))->sum('montant');

            return [
                'id' => $dentisteId,
                'nom' => $nom,
                'facture' => round((float) $l->sum('montant'), 2),
                'actes' => $l->count(),
                'patients' => $l->pluck('patient_id')->unique()->count(),
                'moyenne' => $l->count() ? round((float) $l->avg('montant'), 2) : 0.0,
                'encaisse' => round((float) $encaisse, 2),
                'depenses' => round((float) $dep, 2),
                'bilan' => round((float) $encaisse - $dep, 2),
            ];
        };

        $praticiens = User::where('cabinet_id', $user->cabinet_id)
            ->whereIn('role', ['proprietaire', 'dentiste'])
            ->orderBy('name')->get();

        $rows = $praticiens->map(fn ($p) => $ligne($p->id, 'Dr '.$p->name));
        $sansPraticien = $ligne(null, 'Cabinet (non attribué)');
        if ($sansPraticien['actes'] || $sansPraticien['encaisse'] || $sansPraticien['depenses']) {
            $rows->push($sansPraticien);
        }

        return ['lignes' => $rows->values(), 'total' => $this->total($rows)];
    }

    private function total(Collection $rows): array
    {
        $actes = $rows->sum('actes');
        $facture = $rows->sum('facture');

        return [
            'facture' => $facture,
            'actes' => $actes,
            'patients' => null,
            'moyenne' => $actes ? round($facture / $actes, 2) : 0.0,
            'encaisse' => $rows->sum('encaisse'),
            'depenses' => $rows->sum('depenses'),
            'bilan' => $rows->sum('bilan'),
        ];
    }
}
