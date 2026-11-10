<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Stock;
use App\Models\Cabinet;
use App\Models\Consommable;
use App\Models\MouvementStock;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    private User $secretaire;

    private StockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $cabinet = Cabinet::factory()->create();
        $this->secretaire = User::factory()->role(Role::Secretaire)->create(['cabinet_id' => $cabinet->id]);
        $this->service = app(StockService::class);
        $this->actingAs($this->secretaire);
    }

    private function article(string $nom = 'Gants', float $min = 5): Consommable
    {
        return $this->service->enregistrerArticle($this->secretaire, ['nom' => $nom, 'categorie' => 'protection', 'unite' => 'boîte', 'quantite_min' => $min]);
    }

    private function mvt(Consommable $a, string $type, float $q, array $extra = []): MouvementStock
    {
        return $this->service->mouvement($this->secretaire, $a, ['type' => $type, 'quantite' => $q, ...$extra]);
    }

    public function test_entrees_prix_moyen_et_sorties_bornees()
    {
        $a = $this->article();
        $this->mvt($a, 'entree', 10, ['prix_unitaire' => 100]);
        $this->mvt($a, 'entree', 10, ['prix_unitaire' => 200]);
        $a->refresh();
        $this->assertSame(20.0, $a->quantite);
        $this->assertSame(150.0, $a->prix_achat);
        $this->assertSame(3000.0, $a->valeur());

        $m = $this->mvt($a, 'sortie', 15);
        $this->assertSame(-15.0, $m->quantite);
        $this->assertSame(5.0, $m->stock_apres);

        try {
            $this->mvt($a, 'sortie', 6);
            $this->fail('Sortie au-delà du stock.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
        try {
            $this->mvt($a, 'entree', 0);
            $this->fail('Quantité nulle.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_inventaire_avec_motif_trace_l_ecart()
    {
        $a = $this->article();
        $this->mvt($a, 'entree', 10);

        try {
            $this->mvt($a, 'ajustement', 8);
            $this->fail('Motif obligatoire.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $m = $this->mvt($a, 'ajustement', 8, ['motif' => 'Casse']);
        $this->assertSame(-2.0, $m->quantite);
        $this->assertSame(8.0, $a->fresh()->quantite);

        $this->assertSame(0.0, $this->mvt($a, 'ajustement', 0, ['motif' => 'Tout jeté'])->stock_apres);
    }

    public function test_alertes()
    {
        $a = $this->article(min: 5);
        $this->assertSame(['rupture'], $a->fresh()->alertes());

        $this->mvt($a, 'entree', 4, ['peremption' => today()->addDays(10)->format('Y-m-d')]);
        $this->assertSame(['faible', 'peremption_proche'], $a->fresh()->alertes());

        $this->mvt($a, 'entree', 10, ['peremption' => today()->addYear()->format('Y-m-d')]);
        $this->assertSame(['peremption_proche'], $a->fresh()->alertes(), 'La date la plus proche est conservée.');

        $a->fresh()->update(['peremption' => today()->subDay()]);
        $this->assertSame(['perime'], $a->fresh()->alertes());

        // Plus de stock : plus d'alerte de péremption, la date est effacée.
        $this->mvt($a->fresh(), 'sortie', 14);
        $this->assertSame(['rupture'], $a->fresh()->alertes());
        $this->assertNull($a->fresh()->peremption);
    }

    public function test_nom_unique_par_cabinet_et_isolation()
    {
        $this->article('Gants');
        try {
            $this->article('Gants');
            $this->fail();
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $autre = User::factory()->role(Role::Proprietaire)->create();
        $this->actingAs($autre);
        $this->assertNotNull($this->service->enregistrerArticle($autre, ['nom' => 'Gants', 'categorie' => 'protection', 'unite' => 'boîte', 'quantite_min' => 0])->id);
        $this->get(route('stock'))->assertOk();
        $this->assertSame(1, Consommable::count());
    }

    public function test_ecran_stock()
    {
        $a = $this->article('Composite A2', 3);

        Livewire::test(Stock::class)
            ->assertSee('Composite A2')
            ->assertSee('Rupture')
            ->call('ouvrirMouvement', $a->id, 'entree')
            ->set('mouvement.quantite', '6')
            ->set('mouvement.prix_unitaire', '1800')
            ->call('enregistrerMouvement')
            ->assertHasNoErrors()
            ->assertSee('10 800 MRU')
            ->call('ouvrirMouvement', $a->id, 'sortie')
            ->set('mouvement.quantite', '99')
            ->call('enregistrerMouvement')
            ->assertHasErrors('mouvement.quantite')
            ->set('filtre', 'alertes')
            ->assertSee('Aucune alerte.');
    }
}
