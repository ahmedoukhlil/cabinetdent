<?php

namespace App\Livewire;

use App\Models\Consommable;
use App\Models\MouvementStock;
use App\Services\StockService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Stock')]
class Stock extends Component
{
    #[Url]
    public string $filtre = 'tous';

    #[Url(as: 'q')]
    public string $recherche = '';

    public array $article = ['id' => null, 'nom' => '', 'categorie' => 'protection', 'unite' => 'boîte', 'quantite_min' => '0'];

    public bool $formulaireArticle = false;

    public ?int $articleMouvement = null;

    public array $mouvement = ['type' => 'entree', 'quantite' => '', 'prix_unitaire' => '', 'peremption' => '', 'motif' => ''];

    public function mount(): void
    {
        $this->authorize('stock.manage');
    }

    public function nouvelArticle(): void
    {
        $this->resetValidation();
        $this->reset('article');
        $this->formulaireArticle = true;
    }

    public function modifierArticle(int $id): void
    {
        $a = Consommable::findOrFail($id);
        $this->resetValidation();
        $this->article = ['id' => $a->id, 'nom' => $a->nom, 'categorie' => $a->categorie, 'unite' => $a->unite, 'quantite_min' => (string) $a->quantite_min];
        $this->formulaireArticle = true;
    }

    public function enregistrerArticle(StockService $service): void
    {
        $existant = $this->article['id'] ? Consommable::findOrFail($this->article['id']) : null;
        if ($this->executer(fn () => $service->enregistrerArticle(auth()->user(), $this->article, $existant), 'article.')) {
            $this->dispatch('toast', message: $existant ? 'Article mis à jour' : 'Article ajouté', type: 'success');
            $this->formulaireArticle = false;
            $this->reset('article');
        }
    }

    public function basculerActif(int $id): void
    {
        $this->authorize('stock.manage');
        $a = Consommable::findOrFail($id);
        $a->update(['actif' => !$a->actif]);
    }

    public function ouvrirMouvement(int $id, string $type): void
    {
        $this->resetValidation();
        $this->articleMouvement = Consommable::findOrFail($id)->id;
        $this->mouvement = ['type' => array_key_exists($type, MouvementStock::TYPES) ? $type : 'entree', 'quantite' => '', 'prix_unitaire' => '', 'peremption' => '', 'motif' => ''];
    }

    public function enregistrerMouvement(StockService $service): void
    {
        $article = Consommable::findOrFail($this->articleMouvement);
        $m = $this->executer(fn () => $service->mouvement(auth()->user(), $article, [
            ...$this->mouvement,
            'prix_unitaire' => $this->mouvement['prix_unitaire'] === '' ? null : $this->mouvement['prix_unitaire'],
            'peremption' => $this->mouvement['peremption'] ?: null,
        ]), 'mouvement.');
        if ($m) {
            $this->articleMouvement = null;
            $this->dispatch('toast', message: MouvementStock::TYPES[$m->type].' enregistrée · stock : '.rtrim(rtrim(number_format($m->stock_apres, 2, ',', ' '), '0'), ',').' '.$article->unite, type: 'success');
        }
    }

    private function executer(callable $action, string $prefixe): mixed
    {
        try {
            return $action();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $champ => $messages) {
                $this->addError($prefixe.$champ, $messages[0]);
            }
        } catch (AuthorizationException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }

        return null;
    }

    public function render()
    {
        $tous = Consommable::orderByDesc('actif')->orderBy('nom')->get();
        $actifs = $tous->where('actif', true);
        $compteurs = collect(array_keys(Consommable::ALERTES))
            ->mapWithKeys(fn ($a) => [$a => $actifs->filter(fn ($c) => in_array($a, $c->alertes(), true))->count()]);

        $terme = mb_strtolower(trim($this->recherche));
        $articles = $tous
            ->when($this->filtre === 'alertes', fn ($c) => $c->filter(fn ($a) => $a->actif && $a->alertes()))
            ->when($terme !== '', fn ($c) => $c->filter(fn ($a) => str_contains(mb_strtolower($a->nom), $terme)))
            ->values();

        return view('livewire.stock', [
            'articles' => $articles,
            'compteurs' => $compteurs,
            'valeur' => $actifs->sum(fn ($a) => $a->valeur()),
            'nbArticles' => $actifs->count(),
            'enMouvement' => $this->articleMouvement ? $tous->firstWhere('id', $this->articleMouvement) : null,
            'historique' => MouvementStock::with(['consommable', 'auteur'])->latest('id')->limit(15)->get(),
            'categories' => Consommable::CATEGORIES,
        ]);
    }
}
