<?php

namespace App\Livewire;

use App\Models\Dent;
use App\Models\DentHistorique;
use App\Models\Patient;
use App\Models\PlanTraitement;
use App\Models\PlanTraitementLigne;
use App\Services\DossierDentaireService;
use App\Support\Odontologie;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class DossierDentaire extends Component
{
    #[Locked]
    public int $patientId;

    /** @var int[] Dents sélectionnées sur l'odontogramme. */
    public array $selection = [];

    public array $etat = [];

    public array $ligne = ['acte_id' => null, 'libelle' => '', 'quantite' => 1, 'prix_unitaire' => 0];

    public bool $lignePourSelection = true;

    public function mount(Patient $patient): void
    {
        $this->authorize('dossier.view');
        $this->patientId = $patient->id;
        $this->etat = $this->etatVide();
    }

    private function patient(): Patient
    {
        return Patient::findOrFail($this->patientId);
    }

    private function etatVide(): array
    {
        return [
            'presence' => 'presente', 'restauration' => 'aucune', 'endodontie' => 'non_traitee',
            'faces' => Odontologie::facesSaines(), 'constatations' => [], 'notes' => '',
        ];
    }

    public function basculerDent(int $numero, bool $multiple = false): void
    {
        if (!Odontologie::estDentValide($numero)) {
            return;
        }

        if ($multiple) {
            $this->selection = in_array($numero, $this->selection, true)
                ? array_values(array_diff($this->selection, [$numero]))
                : [...$this->selection, $numero];
        } else {
            $this->selection = $this->selection === [$numero] ? [] : [$numero];
        }

        $this->chargerEtat();
    }

    /** Sélection venant de l'odontogramme anatomique (liste complète des dents cochées). */
    public function definirSelection(array $numeros): void
    {
        $this->selection = array_values(array_unique(array_filter(array_map('intval', $numeros), [Odontologie::class, 'estDentValide'])));
        $this->chargerEtat();
    }

    /** Sélectionne les dents d'une ligne du plan (lien plan / facture ↔ odontogramme). */
    public function selectionnerLigne(int $id): void
    {
        $ligne = PlanTraitementLigne::find($id);
        if ($ligne && $ligne->dents) {
            $this->definirSelection($ligne->dents);
        }
    }

    public function viderSelection(): void
    {
        $this->selection = [];
        $this->chargerEtat();
    }

    /** Préremplit le formulaire avec l'état de la dent quand une seule est sélectionnée. */
    private function chargerEtat(): void
    {
        $this->resetValidation();
        $dent = count($this->selection) === 1
            ? Dent::where('patient_id', $this->patientId)->where('numero', $this->selection[0])->first()
            : null;

        $this->etat = $dent ? array_merge($dent->etat(), ['notes' => $dent->notes ?? '']) : $this->etatVide();
    }

    public function cyclerFace(string $face): void
    {
        if (!in_array($face, Odontologie::FACES, true)) {
            return;
        }
        $etats = array_keys(Odontologie::ETATS_FACE);
        $actuel = array_search($this->etat['faces'][$face] ?? 'sain', $etats, true);
        $this->etat['faces'][$face] = $etats[($actuel + 1) % count($etats)];
    }

    public function enregistrer(DossierDentaireService $service): void
    {
        $this->executer(function () use ($service) {
            $service->enregistrerDents(auth()->user(), $this->patient(), $this->selection, $this->etat);
            $n = count($this->selection);
            $this->dispatch('toast', message: $n > 1 ? "{$n} dents enregistrées" : "Dent {$this->selection[0]} enregistrée", type: 'success');
        });
    }

    /** Choix d'un acte du catalogue : libellé et prix de référence (modifiable). */
    public function choisirActe(int $id): void
    {
        $acte = \App\Models\Acte::where('actif', true)->findOrFail($id);
        $this->ligne['acte_id'] = $acte->id;
        $this->ligne['libelle'] = $acte->libelle;
        $this->ligne['prix_unitaire'] = $acte->prixPour($this->patient()->assureur_id);
    }

    /** Le libellé retapé à la main détache l'acte choisi. */
    public function updatedLigneLibelle(): void
    {
        $this->ligne['acte_id'] = null;
    }

    public function ajouterLigne(DossierDentaireService $service): void
    {
        $this->executer(function () use ($service) {
            $service->ajouterLigne(auth()->user(), $this->patient(), $this->ligne + [
                'dents' => $this->lignePourSelection ? $this->selection : [],
            ]);
            $this->ligne = ['acte_id' => null, 'libelle' => '', 'quantite' => 1, 'prix_unitaire' => 0];
            $this->dispatch('toast', message: 'Soin ajouté au plan', type: 'success');
        });
    }

    public function supprimerLigne(int $id, DossierDentaireService $service): void
    {
        $this->executer(fn () => $service->supprimerLigne(auth()->user(), PlanTraitementLigne::findOrFail($id)));
    }

    public function basculerRealise(int $id, DossierDentaireService $service): void
    {
        $this->executer(fn () => $service->basculerRealise(auth()->user(), PlanTraitementLigne::findOrFail($id)));
    }

    public function facturer(int $id, \App\Services\FacturationService $facturation): void
    {
        $this->executer(function () use ($id, $facturation) {
            $ligne = PlanTraitementLigne::findOrFail($id);
            $facturation->facturerLignePlan(auth()->user(), $ligne);
            $this->dispatch('toast', message: "« {$ligne->libelle} » ajouté au devis", type: 'success');
        });
    }

    public function changerStatutPlan(int $id, string $statut, DossierDentaireService $service): void
    {
        $this->executer(function () use ($id, $statut, $service) {
            $service->changerStatutPlan(auth()->user(), PlanTraitement::findOrFail($id), $statut);
            $this->dispatch('toast', message: 'Plan : '.PlanTraitement::STATUTS[$statut], type: 'success');
        });
    }

    private function executer(callable $action): void
    {
        try {
            $action();
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator?->errors() ?? $e->errors());
            $this->dispatch('toast', message: collect($e->errors())->flatten()->first(), type: 'error');
        } catch (AuthorizationException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function render(DossierDentaireService $service)
    {
        $patient = $this->patient();
        $dents = Dent::where('patient_id', $patient->id)->get()->keyBy('numero');
        $plan = $service->planActif($patient)?->load(['lignes.factureLigne.facture']);

        return view('livewire.dossier-dentaire', [
            'patient' => $patient,
            'dents' => $dents,
            'plan' => $plan,
            'odontogramme' => [
                'selection' => $this->selection,
                'conditions' => Odontologie::conditionsGraphe($dents, $plan ? $plan->lignes->where('statut', 'prevu')->pluck('dents')->filter()->flatten()->all() : []),
            ],
            'plansClos' => PlanTraitement::where('patient_id', $patient->id)->whereIn('statut', ['termine', 'annule'])->withCount('lignes')->latest('id')->limit(5)->get(),
            'historique' => DentHistorique::with('auteur')->where('patient_id', $patient->id)
                ->when(count($this->selection) === 1, fn ($q) => $q->where('numero', $this->selection[0]))
                ->latest('id')->limit(8)->get(),
            'peutModifier' => auth()->user()->can('dossier.edit'),
            'peutFacturer' => auth()->user()->can('facture.edit'),
            'actesProposes' => ($peutModifier = auth()->user()->can('dossier.edit')) && !$this->ligne['acte_id'] && mb_strlen(trim($this->ligne['libelle'])) >= 1
                ? \App\Models\Acte::with('tarifs')->where('actif', true)
                    ->where(fn ($q) => $q->where('libelle', 'like', '%'.trim($this->ligne['libelle']).'%')->orWhere('code', 'like', trim($this->ligne['libelle']).'%'))
                    ->orderBy('libelle')->limit(8)->get()
                : collect(),
        ])->title('Dossier dentaire · '.$patient->nom_complet);
    }
}
