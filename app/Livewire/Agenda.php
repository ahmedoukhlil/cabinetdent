<?php

namespace App\Livewire;

use App\Livewire\Concerns\ChangeStatutRdv;
use App\Models\Patient;
use App\Models\RendezVous;
use App\Models\User;
use App\Services\RendezVousService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Agenda')]
class Agenda extends Component
{
    use ChangeStatutRdv;

    #[Url]
    public string $date = '';

    public ?int $patientId = null;

    public string $recherchePatient = '';

    public ?int $dentisteId = null;

    public string $heure = '';

    public string $acte = '';

    /** Création rapide d'un patient introuvable, directement depuis le formulaire de RDV. */
    public bool $creation = false;

    public array $nouveau = ['nom' => '', 'prenom' => '', 'telephone' => ''];

    public function mount(): void
    {
        $this->date = $this->dateValide($this->date);
        $user = auth()->user();
        $this->dentisteId = $user->estDentiste() ? $user->id : User::dentistesDu($user)->value('id');

        if ($patient = Patient::find(request()->integer('patient'))) {
            $this->choisirPatient($patient->id);
        }
    }

    private function dateValide(?string $date): string
    {
        try {
            return Carbon::createFromFormat('Y-m-d', (string) $date)->format('Y-m-d');
        } catch (\Throwable) {
            return today()->format('Y-m-d');
        }
    }

    public function updatedDate(): void
    {
        $this->date = $this->dateValide($this->date);
    }

    public function decaler(int $jours): void
    {
        $this->date = Carbon::parse($this->date)->addDays($jours)->format('Y-m-d');
    }

    public function choisirHeure(string $heure): void
    {
        if (preg_match('/^\d{2}:\d{2}$/', $heure)) {
            $this->heure = $heure;
            $this->resetValidation('heure');
        }
    }

    public function choisirPatient(int $id): void
    {
        $patient = Patient::findOrFail($id);
        $this->patientId = $patient->id;
        $this->recherchePatient = $patient->nom_complet;
        $this->creation = false;
    }

    public function nouveauPatient(): void
    {
        $this->authorize('patient.create');
        $this->creation = true;
        $this->patientId = null;
        // Pré-remplit avec la recherche tapée : un numéro va dans le téléphone, sinon dans le nom.
        $terme = trim($this->recherchePatient);
        if (preg_match('/^[\d\s+().-]+$/', $terme)) {
            $this->nouveau['telephone'] = $this->nouveau['telephone'] ?: $terme;
        } elseif ($this->nouveau['nom'] === '') {
            $this->nouveau['nom'] = $terme;
        }
    }

    public function annulerCreation(): void
    {
        $this->reset('creation', 'nouveau');
        $this->resetValidation(['nouveau.nom', 'nouveau.prenom', 'nouveau.telephone']);
    }

    public function updatedRecherchePatient(): void
    {
        $this->patientId = null;
    }

    public function creer(RendezVousService $service): void
    {
        $this->authorize('rendez-vous.create');

        $regles = $this->creation
            ? ['nouveau.nom' => ['required', 'string', 'max:100'], 'nouveau.prenom' => ['nullable', 'string', 'max:100'], 'nouveau.telephone' => ['nullable', 'string', 'max:30']]
            : ['patientId' => ['required', 'integer']];

        $this->validate($regles + [
            'dentisteId' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'heure' => ['required', 'date_format:H:i'],
            'acte' => ['nullable', 'string', 'max:150'],
        ], [
            'patientId.required' => 'Choisissez un patient dans la liste, ou créez-le.',
            'date.after_or_equal' => 'Impossible de planifier un RDV dans le passé.',
        ], ['nouveau.nom' => 'nom']);

        try {
            // Transaction : si le RDV est refusé (créneau pris…), le nouveau patient n'est pas créé.
            $rdv = DB::transaction(function () use ($service) {
                $patientId = $this->patientId;
                if ($this->creation) {
                    $this->authorize('patient.create');
                    $patientId = Patient::create(array_filter(array_map('trim', $this->nouveau), fn ($v) => $v !== ''))->id;
                }

                return $service->creer(auth()->user(), [
                    'patient_id' => $patientId,
                    'dentiste_id' => $this->dentisteId,
                    'date' => $this->date,
                    'heure' => $this->heure,
                    'acte_prevu' => $this->acte ?: null,
                ]);
            });
        } catch (AuthorizationException $e) {
            $this->addError('dentisteId', $e->getMessage());

            return;
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $champ => $messages) {
                $this->addError($champ === 'patient_id' ? 'patientId' : $champ, $messages[0]);
            }

            return;
        }

        $this->reset('patientId', 'recherchePatient', 'heure', 'acte', 'creation', 'nouveau');
        $this->dispatch('toast', message: "RDV planifié à {$rdv->heure_courte} · {$rdv->patient->nom_complet}", type: 'success');
    }

    public function render(RendezVousService $service)
    {
        $user = auth()->user();

        $suggestions = (!$this->patientId && !$this->creation && mb_strlen(trim($this->recherchePatient)) >= 2)
            ? Patient::recherche($this->recherchePatient)->where('actif', true)->orderBy('nom')->limit(6)->get()
            : collect();

        $rdvs = RendezVous::with(['patient', 'dentiste', 'facture'])
            ->duJour($this->date)
            ->visiblesPar($user)
            ->orderBy('heure')
            ->get()
            ->groupBy('dentiste_id');

        $dentistes = User::dentistesDu($user)
            ->when(!$user->aPermission('rendez-vous.edit'), fn ($q) => $q->whereKey($user->id))
            ->get();

        $jour = Carbon::parse($this->date);
        $cabinet = $user->cabinet;

        // Bandeau semaine : lundi à dimanche de la semaine affichée, avec le nombre de RDV actifs.
        $lundi = $jour->copy()->startOfWeek(Carbon::MONDAY);
        $compte = RendezVous::visiblesPar($user)->actifs()
            ->whereDate('date', '>=', $lundi)->whereDate('date', '<=', $lundi->copy()->addDays(6))
            ->get(['date'])->countBy(fn ($r) => $r->date->format('Y-m-d'));
        $semaine = collect(range(0, 6))->map(function ($i) use ($lundi, $compte, $cabinet) {
            $d = $lundi->copy()->addDays($i);

            return ['date' => $d, 'cle' => $d->format('Y-m-d'), 'rdv' => $compte[$d->format('Y-m-d')] ?? 0, 'ouvert' => $cabinet->estOuvertLe($d)];
        });

        $creneaux = ($this->dentisteId && $user->aPermission('rendez-vous.create'))
            ? $service->creneauxLibres($user, (int) $this->dentisteId, $this->date)
            : [];
        $ouvert = $cabinet->estOuvertLe($jour);
        $duree = $cabinet->duree_rdv_minutes;

        return view('livewire.agenda', compact('suggestions', 'rdvs', 'dentistes', 'jour', 'semaine', 'creneaux', 'ouvert', 'duree'));
    }
}
