<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\DossierDentaire;
use App\Models\Cabinet;
use App\Models\Dent;
use App\Models\DentHistorique;
use App\Models\Patient;
use App\Models\User;
use App\Services\DossierDentaireService;
use App\Support\Odontologie;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class DossierDentaireTest extends TestCase
{
    use RefreshDatabase;

    private User $dentiste;

    private Patient $patient;

    private DossierDentaireService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dentiste = User::factory()->role(Role::Dentiste)->create(['cabinet_id' => Cabinet::factory()->create()->id]);
        $this->patient = Patient::create(['cabinet_id' => $this->dentiste->cabinet_id, 'nom' => 'Sidi']);
        $this->service = app(DossierDentaireService::class);
        $this->actingAs($this->dentiste);
    }

    private function etat(array $surcharge = []): array
    {
        return array_merge([
            'presence' => 'presente', 'restauration' => 'aucune', 'endodontie' => 'non_traitee',
            'faces' => Odontologie::facesSaines(), 'constatations' => [], 'notes' => '',
        ], $surcharge);
    }

    public function test_enregistre_plusieurs_dents_avec_historique_sans_doublon()
    {
        $etat = $this->etat(['faces' => ['O' => 'carie'] + Odontologie::facesSaines()]);
        $this->service->enregistrerDents($this->dentiste, $this->patient, [36, 37], $etat);
        $this->service->enregistrerDents($this->dentiste, $this->patient, [36, 37], $etat);

        $this->assertSame(2, Dent::count());
        $this->assertSame(2, DentHistorique::count(), 'Un enregistrement identique ne doit pas créer d’historique.');
        $this->assertSame('a_traiter', Dent::firstWhere('numero', 36)->synthese());
    }

    public function test_refuse_dent_ou_valeur_hors_referentiel()
    {
        foreach ([[[19], $this->etat()], [[11], $this->etat(['restauration' => 'or_massif'])], [[11], $this->etat(['faces' => ['X' => 'carie']])], [[], $this->etat()]] as [$dents, $etat]) {
            try {
                $this->service->enregistrerDents($this->dentiste, $this->patient, $dents, $etat);
                $this->fail('La saisie aurait dû être refusée.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame(0, Dent::count());
    }

    public function test_dent_absente_perd_ses_faces()
    {
        $this->service->enregistrerDents($this->dentiste, $this->patient, [18], $this->etat([
            'presence' => 'absente', 'faces' => ['O' => 'carie'] + Odontologie::facesSaines(), 'endodontie' => 'traitee',
        ]));

        $dent = Dent::first();
        $this->assertSame(Odontologie::facesSaines(), $dent->faces);
        $this->assertSame('non_traitee', $dent->endodontie);
        $this->assertSame('absente', $dent->synthese());
    }

    public function test_secretaire_et_autre_cabinet_refuses()
    {
        $secretaire = User::factory()->role(Role::Secretaire)->create(['cabinet_id' => $this->dentiste->cabinet_id]);
        $etranger = User::factory()->role(Role::Dentiste)->create();

        foreach ([$secretaire, $etranger] as $auteur) {
            try {
                $this->service->enregistrerDents($auteur, $this->patient, [11], $this->etat());
                $this->fail('Accès aurait dû être refusé.');
            } catch (AuthorizationException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->actingAs($secretaire)->get(route('dossier', $this->patient))->assertForbidden();
        $this->actingAs($etranger)->get(route('dossier', $this->patient))->assertNotFound();
    }

    public function test_cycle_du_plan_de_traitement()
    {
        $ligne = $this->service->ajouterLigne($this->dentiste, $this->patient, ['libelle' => 'Composite', 'quantite' => 2, 'prix_unitaire' => 1500, 'dents' => [36]]);
        $plan = $ligne->plan;
        $this->assertSame('brouillon', $plan->statut);
        $this->assertEqualsWithDelta(3000, $plan->load('lignes')->total(), 0.001);

        // Réaliser un soin avant acceptation est interdit.
        try {
            $this->service->basculerRealise($this->dentiste, $ligne);
            $this->fail();
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $this->service->changerStatutPlan($this->dentiste, $plan, 'propose');
        $this->service->changerStatutPlan($this->dentiste, $plan->fresh(), 'accepte');
        $this->assertNotNull($plan->fresh()->accepte_at);

        $this->service->basculerRealise($this->dentiste, $ligne->fresh());
        $this->assertSame('en_cours', $plan->fresh()->statut);
        $this->assertSame('realise', $ligne->fresh()->statut);

        // Un soin réalisé ne peut plus être supprimé.
        try {
            $this->service->supprimerLigne($this->dentiste, $ligne->fresh());
            $this->fail();
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $this->service->changerStatutPlan($this->dentiste, $plan->fresh(), 'termine');
        $this->assertSame('termine', $plan->fresh()->statut);

        // Un nouveau soin ouvre un nouveau plan.
        $nouvelle = $this->service->ajouterLigne($this->dentiste, $this->patient, ['libelle' => 'Contrôle', 'quantite' => 1, 'prix_unitaire' => 0]);
        $this->assertNotSame($plan->id, $nouvelle->plan_traitement_id);
    }

    public function test_transition_de_plan_interdite()
    {
        $ligne = $this->service->ajouterLigne($this->dentiste, $this->patient, ['libelle' => 'Couronne', 'quantite' => 1, 'prix_unitaire' => 20000]);

        $this->expectException(ValidationException::class);
        $this->service->changerStatutPlan($this->dentiste, $ligne->plan, 'termine');
    }

    public function test_odontogramme_anatomique_couleurs_et_lien_avec_le_plan()
    {
        $this->service->enregistrerDents($this->dentiste, $this->patient, [36], $this->etat(['faces' => ['O' => 'carie'] + Odontologie::facesSaines()]));
        $this->service->enregistrerDents($this->dentiste, $this->patient, [11], $this->etat(['restauration' => 'couronne']));
        $plan = $this->service->ajouterLigne($this->dentiste, $this->patient, ['libelle' => 'Composite', 'quantite' => 1, 'prix_unitaire' => 2500, 'dents' => [36, 24]])->plan;

        $conditions = collect(Odontologie::conditionsGraphe(\App\Models\Dent::where('patient_id', $this->patient->id)->get()->keyBy('numero'), [36, 24, 99]));
        $this->assertSame([36], $conditions->firstWhere('label', 'À traiter (carie…)')['teeth']);
        $this->assertSame([11], $conditions->firstWhere('label', 'Prothèse')['teeth']);
        $this->assertSame([24], $conditions->firstWhere('label', 'Soin prévu au plan')['teeth'], 'Dent cariée : son état prime ; dent invalide ignorée.');

        $ligne = $plan->lignes()->first();
        Livewire::test(DossierDentaire::class, ['patient' => $this->patient])
            ->call('definirSelection', [36, 11, 99, 36])
            ->assertSet('selection', [36, 11])
            ->call('selectionnerLigne', $ligne->id)
            ->assertSet('selection', [24, 36])
            ->assertSee('data-odontogramme', false)
            ->assertSee('#ea580c', false); // couleur « soin prévu » (JSON échappé dans l'attribut)
    }

    public function test_composant_selection_et_enregistrement()
    {
        Livewire::test(DossierDentaire::class, ['patient' => $this->patient])
            ->assertSee('Odontogramme')
            ->call('basculerDent', 21)
            ->assertSet('selection', [21])
            ->call('basculerDent', 22, true)
            ->assertSet('selection', [21, 22])
            ->call('cyclerFace', 'V')
            ->assertSet('etat.faces.V', 'carie')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertSame(['carie'], array_values(array_unique(Dent::pluck('faces')->pluck('V')->all())));

        Livewire::test(DossierDentaire::class, ['patient' => $this->patient])
            ->call('basculerDent', 21)
            ->assertSet('etat.faces.V', 'carie')
            ->call('basculerDent', 99)
            ->assertSet('selection', [21]);
    }

    public function test_soin_autocomplete_depuis_le_catalogue_des_actes()
    {
        $cab = $this->dentiste->cabinet_id;
        $composite = \App\Models\Acte::create(['cabinet_id' => $cab, 'code' => 'C2', 'libelle' => 'Composite 2 faces', 'prix' => 3500]);
        \App\Models\Acte::create(['cabinet_id' => $cab, 'code' => 'EXT', 'libelle' => 'Extraction simple', 'prix' => 2000]);
        \App\Models\Acte::create(['cabinet_id' => $cab, 'libelle' => 'Composite ancien', 'prix' => 1, 'actif' => false]);
        $etranger = User::factory()->role(Role::Proprietaire)->create();
        $acteEtranger = \App\Models\Acte::withoutGlobalScopes()->create(['cabinet_id' => $etranger->cabinet_id, 'libelle' => 'Composite étranger', 'prix' => 1]);

        Livewire::test(DossierDentaire::class, ['patient' => $this->patient])
            ->set('ligne.libelle', 'compo')
            ->assertSee('Composite 2 faces')
            ->assertDontSee('Composite ancien')
            ->assertDontSee('Composite étranger')
            ->assertDontSee('Extraction simple')
            ->set('ligne.libelle', 'EXT')
            ->assertSee('Extraction simple')
            ->call('choisirActe', $composite->id)
            ->assertSet('ligne.libelle', 'Composite 2 faces')
            ->assertSet('ligne.prix_unitaire', 3500.0)
            ->assertSet('ligne.acte_id', $composite->id)
            ->set('ligne.prix_unitaire', 3000)
            ->call('ajouterLigne')
            ->assertHasNoErrors();

        $ligne = \App\Models\PlanTraitementLigne::latest('id')->first();
        $this->assertSame($composite->id, $ligne->acte_id);
        $this->assertSame(3000.0, $ligne->prix_unitaire, 'Le prix de référence reste modifiable.');

        // Un acte d'un autre cabinet transmis directement est ignoré.
        $libre = $this->service->ajouterLigne($this->dentiste, $this->patient, ['acte_id' => $acteEtranger->id, 'libelle' => 'Soin libre', 'quantite' => 1, 'prix_unitaire' => 100]);
        $this->assertNull($libre->acte_id);
    }

    public function test_selection_rapide_par_groupe()
    {
        Livewire::test(DossierDentaire::class, ['patient' => $this->patient])
            ->call('selectionnerGroupe', 'q1')
            ->assertSet('selection', [11, 12, 13, 14, 15, 16, 17, 18])
            ->call('selectionnerGroupe', 's2')
            ->assertSet('selection', [11, 12, 13, 14, 15, 16, 17, 18, 21, 22, 23])
            ->call('selectionnerGroupe', 'q1')
            ->assertSet('selection', [21, 22, 23])
            ->call('selectionnerGroupe', 'inconnu')
            ->assertSet('selection', [21, 22, 23])
            ->call('selectionnerGroupe', 'bouche')
            ->assertCount('selection', 32)
            ->call('selectionnerGroupe', 'bouche')
            ->assertSet('selection', []);
    }
}
