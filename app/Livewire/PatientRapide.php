<?php

namespace App\Livewire;

use App\Models\Patient;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modale de création rapide d'un patient, ouverte depuis une recherche infructueuse.
 * À la création, émet « patient-cree » avec l'identifiant et la source qui l'a demandée.
 */
class PatientRapide extends Component
{
    public bool $ouvert = false;

    public string $source = '';

    public array $form = ['nom' => '', 'prenom' => '', 'telephone' => '', 'sexe' => '', 'date_naissance' => ''];

    #[On('patient-rapide-ouvrir')]
    public function ouvrir(string $terme = '', string $source = ''): void
    {
        if (!auth()->user()->can('patient.create')) {
            return;
        }
        $this->resetValidation();
        $this->reset('form');
        $this->source = $source;
        $this->form = array_merge($this->form, self::preRemplir($terme));
        $this->ouvert = true;
    }

    /** Un numéro va dans le téléphone ; sinon « Prénom Nom » : le dernier mot devient le nom. */
    public static function preRemplir(string $terme): array
    {
        $terme = trim(preg_replace('/\s+/', ' ', $terme));
        if ($terme === '') {
            return [];
        }
        if (preg_match('/^[\d\s+().-]+$/', $terme)) {
            return ['telephone' => $terme];
        }
        $mots = explode(' ', $terme);
        if (count($mots) === 1) {
            return ['nom' => $terme];
        }
        $nom = array_pop($mots);

        return ['prenom' => implode(' ', $mots), 'nom' => $nom];
    }

    public function fermer(): void
    {
        $this->ouvert = false;
    }

    public function creer(): void
    {
        $this->authorize('patient.create');
        $donnees = $this->validate([
            'form.nom' => ['required', 'string', 'max:100'],
            'form.prenom' => ['nullable', 'string', 'max:100'],
            'form.telephone' => ['nullable', 'string', 'max:30'],
            'form.sexe' => ['nullable', Rule::in(['F', 'M'])],
            'form.date_naissance' => ['nullable', 'date', 'before_or_equal:today'],
        ], [], ['form.nom' => 'nom', 'form.date_naissance' => 'date de naissance'])['form'];

        $patient = Patient::create(array_filter(array_map(fn ($v) => is_string($v) ? trim($v) : $v, $donnees), fn ($v) => $v !== '' && $v !== null));

        $this->ouvert = false;
        $this->dispatch('patient-cree', id: $patient->id, source: $this->source);
        $this->dispatch('toast', message: "Patient créé · fiche N° {$patient->numero_fiche}", type: 'success');
    }

    public function render()
    {
        return view('livewire.patient-rapide');
    }
}
