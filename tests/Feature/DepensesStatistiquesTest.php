<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Caisse;
use App\Models\Cabinet;
use App\Models\Depense;
use App\Models\Patient;
use App\Models\User;
use App\Services\DepenseService;
use App\Services\FacturationService;
use App\Services\StatistiquesService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class DepensesStatistiquesTest extends TestCase
{
    use RefreshDatabase;

    private User $proprietaire;

    private User $dentiste;

    private User $secretaire;

    private DepenseService $depenses;

    protected function setUp(): void
    {
        parent::setUp();
        $cabinet = Cabinet::factory()->create();
        $this->proprietaire = User::factory()->role(Role::Proprietaire)->create(['cabinet_id' => $cabinet->id, 'name' => 'Salem']);
        $this->dentiste = User::factory()->role(Role::Dentiste)->create(['cabinet_id' => $cabinet->id, 'name' => 'Khadija']);
        $this->secretaire = User::factory()->role(Role::Secretaire)->create(['cabinet_id' => $cabinet->id]);
        $this->depenses = app(DepenseService::class);
        $this->actingAs($this->secretaire);
    }

    private function depense(float $montant, ?int $dentiste = null, ?User $auteur = null): Depense
    {
        return $this->depenses->enregistrer($auteur ?? $this->secretaire, [
            'date_depense' => today()->format('Y-m-d'), 'categorie' => 'prothesiste', 'libelle' => 'Couronne',
            'montant' => $montant, 'mode' => 'especes', 'dentiste_id' => $dentiste,
        ]);
    }

    public function test_saisie_controlee_et_suppression_reservee_au_proprietaire()
    {
        $d = $this->depense(1500, $this->dentiste->id);
        $this->assertSame($this->secretaire->id, $d->user_id);

        foreach ([['montant' => 0], ['date_depense' => today()->addDay()->format('Y-m-d')], ['dentiste_id' => User::factory()->role(Role::Dentiste)->create()->id]] as $invalide) {
            try {
                $this->depenses->enregistrer($this->secretaire, [
                    'date_depense' => today()->format('Y-m-d'), 'categorie' => 'divers', 'libelle' => 'X', 'montant' => 10, 'mode' => 'especes', ...$invalide,
                ]);
                $this->fail('Doit être refusé : '.json_encode($invalide));
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }

        try {
            $this->depenses->supprimer($this->secretaire, $d, 'Erreur');
            $this->fail();
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
        try {
            $this->depenses->enregistrer($this->dentiste, ['date_depense' => today()->format('Y-m-d'), 'categorie' => 'divers', 'libelle' => 'X', 'montant' => 10, 'mode' => 'especes']);
            $this->fail('Le dentiste ne saisit pas de dépense.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        $this->depenses->supprimer($this->proprietaire, $d, 'Doublon');
        $this->assertSoftDeleted($d);
        $this->assertSame('Doublon', Depense::withTrashed()->find($d->id)->motif_suppression);
    }

    public function test_caisse_affiche_le_solde_net_des_depenses()
    {
        $this->depense(1200);

        Livewire::test(Caisse::class)
            ->set('depense.libelle', 'Gants')
            ->set('depense.montant', '300')
            ->call('enregistrerDepense')
            ->assertHasNoErrors()
            ->assertSee('Gants')
            ->assertSee('−300 MRU', false)
            ->assertSee('-1 500 MRU', false);

        $this->get(route('caisse.imprimer'))->assertOk()->assertSee('Gants')->assertSee('-1 500 MRU');

        $etranger = User::factory()->role(Role::Proprietaire)->create();
        $this->actingAs($etranger)->get(route('caisse'))->assertOk()->assertDontSee('Gants');
    }

    public function test_statistiques_par_praticien()
    {
        $facturation = app(FacturationService::class);
        $p1 = Patient::create(['cabinet_id' => $this->dentiste->cabinet_id, 'nom' => 'A']);
        $p2 = Patient::create(['cabinet_id' => $this->dentiste->cabinet_id, 'nom' => 'B']);

        foreach ([[$p1, [3000, 1000]], [$p2, [2000]]] as [$patient, $prix]) {
            $f = $facturation->brouillonPour($this->dentiste, $patient);
            foreach ($prix as $px) {
                $facturation->ajouterLigne($this->dentiste, $f, ['libelle' => 'Soin', 'quantite' => 1, 'prix_unitaire' => $px]);
            }
            $f = $facturation->valider($this->secretaire, $f->fresh());
        }
        $facturation->enregistrerPaiement($this->secretaire, $f, ['payeur' => 'patient', 'type' => 'encaissement', 'mode' => 'especes', 'montant' => 2000]);
        $this->depense(500, $this->dentiste->id);
        $this->depense(800);

        // Un brouillon ne compte pas.
        $b = $facturation->brouillonPour($this->dentiste, $p1);
        $facturation->ajouterLigne($this->dentiste, $b, ['libelle' => 'Devis', 'quantite' => 1, 'prix_unitaire' => 99999]);

        $stats = app(StatistiquesService::class)->parPraticien($this->proprietaire, today()->startOfMonth(), today());
        $k = $stats['lignes']->firstWhere('id', $this->dentiste->id);

        $this->assertSame(6000.0, $k['facture']);
        $this->assertSame(3, $k['actes']);
        $this->assertSame(2, $k['patients']);
        $this->assertSame(2000.0, $k['moyenne']);
        $this->assertSame(2000.0, $k['encaisse']);
        $this->assertSame(500.0, $k['depenses']);
        $this->assertSame(1500.0, $k['bilan']);
        $this->assertSame(800.0, $stats['lignes']->firstWhere('id', null)['depenses']);
        $this->assertSame(700.0, $stats['total']['bilan']);

        $this->actingAs($this->secretaire)->get(route('statistiques'))->assertForbidden();
        $this->actingAs($this->proprietaire)->get(route('statistiques'))->assertOk()->assertSee('Dr Khadija')->assertSee('6 000 MRU');
    }
}
