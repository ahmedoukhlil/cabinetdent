<?php

namespace App\Livewire;

use App\Models\Acte;
use App\Models\ActeTarif;
use App\Models\Assureur;
use App\Services\ActeImportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Tarifs et assurances')]
class Tarifs extends Component
{
    use WithFileUploads;

    #[Url(as: 'onglet')]
    public string $onglet = 'actes';

    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    public bool $inactifs = false;

    public bool $edition = false;

    /** prix par assureur : [assureur_id => '1200' | ''] */
    public array $acte = ['id' => null, 'code' => '', 'libelle' => '', 'type' => 'soin', 'prix' => '', 'tarifs' => []];

    public array $assureur = ['id' => null, 'nom' => '', 'taux' => ''];

    public bool $import = false;

    public $fichier = null;

    public ?array $rapport = null;

    public function mount(): void
    {
        $this->authorize('tarifs.manage');
        if (!in_array($this->onglet, ['actes', 'assurances'], true)) {
            $this->onglet = 'actes';
        }
    }

    public function nouvelActe(): void
    {
        $this->resetValidation();
        $this->acte = ['id' => null, 'code' => '', 'libelle' => '', 'type' => 'soin', 'prix' => '', 'tarifs' => []];
        $this->edition = true;
        $this->import = false;
    }

    public function modifierActe(int $id): void
    {
        $a = Acte::with('tarifs')->findOrFail($id);
        $this->resetValidation();
        $this->acte = [
            'id' => $a->id, 'code' => $a->code ?? '', 'libelle' => $a->libelle, 'type' => $a->type, 'prix' => (string) $a->prix,
            'tarifs' => $a->tarifs->mapWithKeys(fn ($t) => [$t->assureur_id => (string) $t->prix])->all(),
        ];
        $this->edition = true;
        $this->import = false;
    }

    public function annulerActe(): void
    {
        $this->resetValidation();
        $this->edition = false;
    }

    public function enregistrerActe(): void
    {
        $this->authorize('tarifs.manage');
        $this->validate([
            'acte.code' => ['nullable', 'string', 'max:20'],
            'acte.libelle' => ['required', 'string', 'max:150'],
            'acte.type' => ['required', \Illuminate\Validation\Rule::in(array_keys(Acte::TYPES))],
            'acte.prix' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'acte.tarifs.*' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ], [], ['acte.libelle' => 'libellé', 'acte.prix' => 'prix sans assurance', 'acte.tarifs.*' => 'prix assureur']);

        $assureurs = Assureur::pluck('id')->all();
        DB::transaction(function () use ($assureurs) {
            $acte = $this->acte['id'] ? Acte::findOrFail($this->acte['id']) : new Acte();
            $acte->fill(['code' => $this->acte['code'] ?: null, 'libelle' => $this->acte['libelle'], 'type' => $this->acte['type'], 'prix' => round((float) $this->acte['prix'], 2)])->save();

            foreach ($this->acte['tarifs'] as $assureurId => $prix) {
                if (!in_array((int) $assureurId, $assureurs, true)) {
                    continue;
                }
                if ($prix === '' || $prix === null) {
                    ActeTarif::where('acte_id', $acte->id)->where('assureur_id', $assureurId)->delete();
                } else {
                    ActeTarif::updateOrCreate(['acte_id' => $acte->id, 'assureur_id' => (int) $assureurId], ['prix' => round((float) $prix, 2)]);
                }
            }
        });

        $this->edition = false;
        $this->dispatch('toast', message: 'Acte enregistré', type: 'success');
    }

    public function basculerActe(int $id): void
    {
        $this->authorize('tarifs.manage');
        $a = Acte::findOrFail($id);
        $a->update(['actif' => !$a->actif]);
    }

    public function ouvrirImport(): void
    {
        $this->import = !$this->import;
        $this->edition = false;
        $this->reset('fichier', 'rapport');
        $this->resetValidation();
    }

    public function importer(ActeImportService $service): void
    {
        $this->authorize('tarifs.manage');
        $this->rapport = null;
        $this->validate(['fichier' => ['required', 'file', 'max:5120', 'extensions:xlsx', 'mimes:xlsx,zip']], [], ['fichier' => 'fichier']);

        // Copie sous un nom simple : le lecteur zip refuse certains chemins temporaires de Livewire.
        $copie = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        file_put_contents($copie, $this->fichier->get());
        try {
            $this->rapport = $service->importer(auth()->user(), $copie);
            $this->reset('fichier');
            $this->dispatch('toast', message: 'Import terminé', type: 'success');
        } catch (ValidationException $e) {
            foreach ($e->errors()['fichier'] ?? [] as $message) {
                $this->addError('fichier', $message);
            }
        } finally {
            @unlink($copie);
        }
    }

    public function modifierAssureur(int $id): void
    {
        $a = Assureur::findOrFail($id);
        $this->resetValidation();
        $this->assureur = ['id' => $a->id, 'nom' => $a->nom, 'taux' => (string) round($a->taux_pec * 100, 2)];
    }

    public function enregistrerAssureur(): void
    {
        $this->authorize('tarifs.manage');
        $this->validate([
            'assureur.nom' => ['required', 'string', 'max:100'],
            'assureur.taux' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [], ['assureur.nom' => 'nom', 'assureur.taux' => 'taux']);

        $assureur = $this->assureur['id'] ? Assureur::findOrFail($this->assureur['id']) : new Assureur();
        $assureur->fill(['nom' => $this->assureur['nom'], 'taux_pec' => Assureur::normaliserTaux((float) $this->assureur['taux'] / 100)])->save();

        $this->reset('assureur');
        $this->dispatch('toast', message: 'Assurance enregistrée', type: 'success');
    }

    public function basculerAssureur(int $id): void
    {
        $this->authorize('tarifs.manage');
        $a = Assureur::findOrFail($id);
        $a->update(['actif' => !$a->actif]);
    }

    public function render()
    {
        $terme = trim($this->recherche);

        return view('livewire.tarifs', [
            'actes' => Acte::with('tarifs')
                ->when(!$this->inactifs, fn ($q) => $q->where('actif', true))
                ->when($terme !== '', fn ($q) => $q->where(fn ($w) => $w->where('libelle', 'like', "%{$terme}%")->orWhere('code', 'like', "{$terme}%")))
                ->orderByDesc('actif')->orderBy('libelle')->get(),
            'nbActes' => Acte::where('actif', true)->count(),
            'assureursActifs' => Assureur::where('actif', true)->orderBy('nom')->get(),
            'assureurs' => Assureur::withCount('patients')->orderByDesc('actif')->orderBy('nom')->get(),
            'tarifsParAssureur' => ActeTarif::selectRaw('assureur_id, count(*) as n')->groupBy('assureur_id')->pluck('n', 'assureur_id'),
        ]);
    }
}
