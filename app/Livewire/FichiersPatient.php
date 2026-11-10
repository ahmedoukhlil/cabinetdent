<?php

namespace App\Livewire;

use App\Models\FichierPatient;
use App\Models\Patient;
use App\Services\FichierPatientService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Section « Radios et documents » du dossier dentaire. */
class FichiersPatient extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $patientId;

    /** Dents sélectionnées sur l'odontogramme du composant parent. */
    #[Reactive]
    public array $selection = [];

    public $fichier = null;

    public string $categorie = 'radio';

    public string $titre = '';

    public string $dateExamen = '';

    public bool $lierSelection = true;

    public bool $filtrerSelection = false;

    public function mount(Patient $patient, array $selection = []): void
    {
        $this->authorize('dossier.view');
        $this->patientId = $patient->id;
        $this->selection = $selection;
        $this->dateExamen = today()->format('Y-m-d');
    }

    public function ajouter(FichierPatientService $service): void
    {
        try {
            $service->ajouter(auth()->user(), Patient::findOrFail($this->patientId), $this->fichier, [
                'categorie' => $this->categorie,
                'titre' => $this->titre,
                'date_examen' => $this->dateExamen ?: null,
                'dents' => $this->lierSelection ? $this->selection : [],
            ]);
            $this->reset('fichier', 'titre');
            $this->resetValidation();
            $this->dispatch('toast', message: 'Fichier ajouté au dossier', type: 'success');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $champ => $messages) {
                $this->addError($champ === 'date_examen' ? 'dateExamen' : $champ, $messages[0]);
            }
        } catch (AuthorizationException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function supprimer(int $id, FichierPatientService $service): void
    {
        try {
            $service->supprimer(auth()->user(), FichierPatient::where('patient_id', $this->patientId)->findOrFail($id));
            $this->dispatch('toast', message: 'Fichier retiré du dossier', type: 'success');
        } catch (AuthorizationException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $fichiers = FichierPatient::with('auteur')
            ->where('patient_id', $this->patientId)
            ->latest('date_examen')->latest('id')
            ->get();

        if ($this->filtrerSelection && $this->selection) {
            $fichiers = $fichiers->filter(fn ($f) => array_intersect($f->dents ?? [], $this->selection))->values();
        }

        return view('livewire.fichiers-patient', [
            'fichiers' => $fichiers,
            'categories' => FichierPatient::CATEGORIES,
        ]);
    }
}
