<?php

namespace App\Livewire;

use App\Models\Patient;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Patients')]
class Patients extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $recherche = '';

    #[Url]
    public ?int $fiche = null;

    public bool $afficherInactifs = false;

    public bool $formulaireOuvert = false;

    public ?int $patientId = null;

    public array $form = [];

    public function mount(): void
    {
        if (request()->boolean('nouveau') && auth()->user()->can('patient.create')) {
            $this->nouveau();
        }
    }

    public function updatedRecherche(): void
    {
        $this->resetPage();
    }

    private function vide(): array
    {
        return ['nom' => '', 'prenom' => '', 'sexe' => '', 'date_naissance' => '', 'telephone' => '', 'nni' => '', 'adresse' => '', 'assureur_id' => '', 'matricule_assure' => '', 'notes' => ''];
    }

    public function nouveau(): void
    {
        $this->authorize('patient.create');
        $this->reset('patientId');
        $this->form = $this->vide();
        $this->resetValidation();
        $this->formulaireOuvert = true;
    }

    public function modifier(int $id): void
    {
        $this->authorize('patient.edit');
        $patient = Patient::findOrFail($id);
        $this->patientId = $patient->id;
        $this->form = array_merge($this->vide(), array_map(fn ($v) => $v ?? '', $patient->only(array_keys($this->vide()))));
        $this->form['date_naissance'] = $patient->date_naissance?->format('Y-m-d') ?? '';
        $this->resetValidation();
        $this->formulaireOuvert = true;
    }

    public function enregistrer(): void
    {
        $this->authorize($this->patientId ? 'patient.edit' : 'patient.create');

        $donnees = $this->validate([
            'form.nom' => ['required', 'string', 'max:100'],
            'form.prenom' => ['nullable', 'string', 'max:100'],
            'form.sexe' => ['nullable', Rule::in(['F', 'M'])],
            'form.date_naissance' => ['nullable', 'date', 'before_or_equal:today'],
            'form.telephone' => ['nullable', 'string', 'max:30'],
            'form.nni' => ['nullable', 'string', 'max:30'],
            'form.adresse' => ['nullable', 'string', 'max:255'],
            'form.assureur_id' => ['nullable', Rule::exists('assureurs', 'id')->where('cabinet_id', auth()->user()->cabinet_id)],
            'form.matricule_assure' => ['nullable', 'string', 'max:50'],
            'form.notes' => ['nullable', 'string', 'max:2000'],
        ], [], ['form.nom' => 'nom', 'form.date_naissance' => 'date de naissance'])['form'];

        $donnees = array_map(fn ($v) => $v === '' ? null : $v, $donnees);
        $creation = !$this->patientId;

        $patient = $creation ? new Patient() : Patient::findOrFail($this->patientId);
        $patient->fill($donnees)->save();

        $this->formulaireOuvert = false;
        $this->fiche = $patient->id;
        $this->dispatch('toast', message: $creation ? "Patient créé · fiche N° {$patient->numero_fiche}" : 'Fiche mise à jour', type: 'success');
    }

    public function basculerActif(int $id): void
    {
        $this->authorize('patient.edit');
        $patient = Patient::findOrFail($id);
        $patient->update(['actif' => !$patient->actif]);
    }

    public function supprimer(int $id): void
    {
        $this->authorize('patient.delete');
        Patient::findOrFail($id)->delete();
        $this->fiche = null;
        $this->dispatch('toast', message: 'Patient supprimé (récupérable)', type: 'success');
    }

    public function render()
    {
        $patients = Patient::recherche($this->recherche)
            ->when(!$this->afficherInactifs, fn ($q) => $q->where('actif', true))
            ->orderBy('nom')->orderBy('prenom')
            ->paginate(15);

        $patientOuvert = $this->fiche
            ? Patient::with(['assureur', 'factures' => fn ($q) => $q->latest('id')->limit(5), 'rendezVous' => fn ($q) => $q->with('dentiste')->latest('date')->latest('heure')->limit(10)])->find($this->fiche)
            : null;

        return view('livewire.patients', [
            'patients' => $patients,
            'patientOuvert' => $patientOuvert,
            'assureurs' => \App\Models\Assureur::where('actif', true)->orderBy('nom')->get(),
        ]);
    }
}
