<?php

namespace App\Livewire;

use App\Models\Acte;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\RendezVous;
use App\Models\User;
use App\Services\ConsultationService;
use App\Services\RendezVousService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Fenêtre unique, présente dans la mise en page, pour facturer une consultation :
 * - depuis un rendez-vous (événement « consultation-ouvrir », rdv: id) ;
 * - sans rendez-vous (événement « consultation-sans-rdv », patient: id facultatif) :
 *   recherche ou création rapide du patient, inscription en salle d'attente, puis facturation.
 * Une fois facturée, elle émet « consultation-facturee » pour rafraîchir les listes.
 */
class ConsultationRapide extends Component
{
    /** null | 'patient' (choix du patient, sans RDV) | 'facture' */
    public ?string $etape = null;

    public ?int $rdvId = null;

    public string $recherche = '';

    public ?int $patientId = null;

    public bool $creation = false;

    public array $nouveau = ['nom' => '', 'prenom' => '', 'telephone' => ''];

    public string $dentisteId = '';

    public array $consultation = ['acte_id' => '', 'encaisser' => true, 'mode' => 'especes', 'reference' => ''];

    public ?int $dernierPaiement = null;

    #[On('consultation-ouvrir')]
    public function ouvrir(int $rdv): void
    {
        $r = RendezVous::with('facture')->find($rdv);
        if (!$r || $r->consultationFacturee() || !auth()->user()->can('facture.edit')) {
            return;
        }
        $this->resetValidation();
        $this->rdvId = $r->id;
        $this->dernierPaiement = null;
        $this->consultation = [
            'acte_id' => (string) (Acte::consultations()->orderBy('prix')->value('id') ?? ''),
            'encaisser' => auth()->user()->can('paiement.create'),
            'mode' => \App\Models\ModePaiement::parDefaut(),
            'reference' => '',
        ];
        $this->etape = 'facture';
    }

    #[On('consultation-sans-rdv')]
    public function sansRdv(?int $patient = null): void
    {
        $user = auth()->user();
        if (!$user->can('facture.edit')) {
            return;
        }
        $duJour = $patient ? RendezVous::with('facture')->where('patient_id', $patient)->duJour()
            ->whereIn('statut', [\App\Enums\StatutRdv::Arrive->value, \App\Enums\StatutRdv::EnCours->value, \App\Enums\StatutRdv::Termine->value])
            ->latest('heure')->get()->first(fn ($r) => !$r->consultationFacturee()) : null;
        if ($duJour) {
            $this->ouvrir($duJour->id);

            return;
        }
        $this->resetValidation();
        $this->reset('recherche', 'creation', 'nouveau', 'rdvId', 'dernierPaiement');
        $this->patientId = Patient::find($patient)?->id;
        $this->dentisteId = (string) ($user->estDentiste() ? $user->id : (User::dentistesDu($user)->value('id') ?? ''));
        $this->etape = 'patient';
    }

    public function choisirPatient(int $id): void
    {
        $this->patientId = Patient::find($id)?->id;
        $this->creation = false;
    }

    public function nouveauPatient(): void
    {
        $this->creation = true;
        // Pré-remplit le nom avec la recherche tapée, si ce n'est pas un numéro.
        if ($this->nouveau['nom'] === '' && !preg_match('/\d/', $this->recherche)) {
            $this->nouveau['nom'] = trim($this->recherche);
        }
    }

    public function inscrire(RendezVousService $service): void
    {
        $user = auth()->user();
        $regles = ['dentisteId' => ['required', 'integer']];
        if ($this->creation) {
            $regles += ['nouveau.nom' => ['required', 'string', 'max:100'], 'nouveau.prenom' => ['nullable', 'string', 'max:100'], 'nouveau.telephone' => ['nullable', 'string', 'max:30']];
        } else {
            $regles += ['patientId' => ['required', 'integer']];
        }
        $this->validate($regles, ['patientId.required' => 'Choisissez un patient ou créez-le.'], ['dentisteId' => 'dentiste', 'nouveau.nom' => 'nom']);

        try {
            if ($this->creation) {
                if (!$user->can('patient.create')) {
                    throw new AuthorizationException('Vous ne pouvez pas créer de patient.');
                }
                $patient = Patient::create(array_filter($this->nouveau, fn ($v) => $v !== ''));
            } else {
                $patient = Patient::findOrFail($this->patientId);
            }
            $rdv = $service->sansRendezVous($user, $patient, (int) $this->dentisteId, 'Consultation sans rendez-vous');
        } catch (ValidationException $e) {
            $this->addError('patientId', collect($e->errors())->flatten()->first());

            return;
        } catch (AuthorizationException $e) {
            $this->addError('patientId', $e->getMessage());

            return;
        }

        $this->dispatch('consultation-facturee');
        $this->dispatch('toast', message: "{$patient->nom_complet} inscrit en salle d'attente", type: 'success');
        $this->ouvrir($rdv->id);
    }

    public function facturer(ConsultationService $service): void
    {
        $rdv = RendezVous::find($this->rdvId);
        if (!$rdv) {
            return;
        }
        try {
            ['facture' => $facture, 'paiement' => $paiement] = $service->facturer(auth()->user(), $rdv, $this->consultation);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $champ => $messages) {
                $this->addError('consultation.'.$champ, $messages[0]);
            }

            return;
        } catch (AuthorizationException $e) {
            $this->addError('consultation.acte_id', $e->getMessage());

            return;
        }

        $this->dispatch('consultation-facturee');
        $this->dispatch('toast', type: 'success', message: $paiement
            ? "Facture {$facture->numero} encaissée · ".number_format($paiement->montant, 0, ',', ' ').' MRU'
            : "Facture {$facture->numero} créée".($facture->restePatient() > 0 ? ', à encaisser' : ''));

        if ($paiement) {
            // On garde la fenêtre ouverte sur une confirmation avec le reçu à imprimer.
            $this->dernierPaiement = $paiement->id;
            $this->etape = 'fait';
        } else {
            $this->fermer();
        }
    }

    public function fermer(): void
    {
        $this->etape = null;
        $this->rdvId = null;
    }

    public function render()
    {
        $user = auth()->user();
        $donnees = ['user' => $user, 'rdv' => null, 'actes' => collect(), 'apercu' => null, 'patients' => collect(), 'patientChoisi' => null, 'dentistes' => collect(), 'paiement' => null];

        if ($this->etape === 'patient') {
            $terme = trim($this->recherche);
            $donnees['patients'] = mb_strlen($terme) >= 2 ? Patient::recherche($terme)->orderBy('nom')->limit(6)->get() : collect();
            $donnees['patientChoisi'] = $this->patientId ? Patient::find($this->patientId) : null;
            $donnees['dentistes'] = User::dentistesDu($user)->orderBy('name')->get();
        }

        if ($this->etape === 'facture' && ($rdv = RendezVous::with(['patient.assureur', 'dentiste'])->find($this->rdvId))) {
            $assureur = $rdv->patient->assureur?->actif ? $rdv->patient->assureur : null;
            $actes = Acte::with('tarifs')->consultations()->orderBy('prix')->get();
            if ($acte = $actes->firstWhere('id', (int) $this->consultation['acte_id'])) {
                $total = $acte->prixPour($rdv->patient->assureur_id);
                $pec = round($total * ($assureur?->taux_pec ?? 0), 2);
                $donnees['apercu'] = ['total' => $total, 'pec' => $pec, 'patient' => round($total - $pec, 2), 'assureur' => $assureur?->nom];
            }
            $donnees['rdv'] = $rdv;
            $donnees['actes'] = $actes;
        }

        if ($this->etape === 'fait') {
            $donnees['paiement'] = Paiement::with('patient', 'facture')->find($this->dernierPaiement);
        }

        return view('livewire.consultation-rapide', $donnees);
    }
}
