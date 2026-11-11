<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Factures;
use App\Models\Acte;
use App\Models\Assureur;
use App\Models\Cabinet;
use App\Models\Facture;
use App\Models\Patient;
use App\Models\User;
use App\Services\DossierDentaireService;
use App\Services\FacturationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class FacturationTest extends TestCase
{
    use RefreshDatabase;

    private User $proprietaire;

    private User $secretaire;

    private Patient $patient;

    private FacturationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $cabinet = Cabinet::factory()->create();
        $this->proprietaire = User::factory()->role(Role::Proprietaire)->create(['cabinet_id' => $cabinet->id]);
        $this->secretaire = User::factory()->role(Role::Secretaire)->create(['cabinet_id' => $cabinet->id]);
        $this->patient = Patient::create(['cabinet_id' => $cabinet->id, 'nom' => 'Ba']);
        $this->service = app(FacturationService::class);
        $this->actingAs($this->secretaire);
    }

    private function factureValidee(float $prix = 10000): Facture
    {
        $f = $this->service->brouillonPour($this->secretaire, $this->patient);
        $this->service->ajouterLigne($this->secretaire, $f, ['libelle' => 'Couronne', 'quantite' => 1, 'prix_unitaire' => $prix]);

        return $this->service->valider($this->secretaire, $f);
    }

    private function payer(Facture $f, float $montant, string $payeur = 'patient', string $type = 'encaissement', ?User $auteur = null)
    {
        return $this->service->enregistrerPaiement($auteur ?? $this->secretaire, $f, ['payeur' => $payeur, 'type' => $type, 'mode' => 'especes', 'montant' => $montant]);
    }

    public function test_normalisation_du_taux()
    {
        $this->assertSame(0.8, Assureur::normaliserTaux(80));
        $this->assertSame(0.8, Assureur::normaliserTaux(0.8));
        $this->assertSame(1.0, Assureur::normaliserTaux(250));
        $this->assertSame(0.0, Assureur::normaliserTaux(-5));
    }

    public function test_numerotation_sequentielle_attribuee_a_la_validation()
    {
        $brouillon = $this->service->brouillonPour($this->secretaire, $this->patient);
        $this->assertNull($brouillon->numero);
        $this->assertSame($brouillon->id, $this->service->brouillonPour($this->secretaire, $this->patient)->id, 'Le devis ouvert doit être réutilisé.');

        $f1 = $this->factureValidee();
        $f2 = $this->factureValidee();
        $annee = now()->year;
        $this->assertSame(["1-{$annee}", "2-{$annee}"], [$f1->numero, $f2->numero]);

        // Un autre cabinet repart à 1.
        $autre = User::factory()->role(Role::Secretaire)->create();
        $this->actingAs($autre);
        $p =Patient::create(['cabinet_id' => $autre->cabinet_id, 'nom' => 'X']);
        $f = $this->service->brouillonPour($autre, $p);
        $this->service->ajouterLigne($autre, $f, ['libelle' => 'Acte', 'quantite' => 1, 'prix_unitaire' => 100]);
        $this->assertSame("1-{$annee}", $this->service->valider($autre, $f)->numero);
    }

    public function test_facture_vide_ou_validee_non_modifiable()
    {
        $f = $this->service->brouillonPour($this->secretaire, $this->patient);
        try {
            $this->service->valider($this->secretaire, $f);
            $this->fail();
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $validee = $this->factureValidee();
        $this->expectException(ValidationException::class);
        $this->service->ajouterLigne($this->secretaire, $validee, ['libelle' => 'Ajout', 'quantite' => 1, 'prix_unitaire' => 10]);
    }

    public function test_acte_du_catalogue_et_repartition_assureur()
    {
        $assureur = Assureur::create(['cabinet_id' => $this->patient->cabinet_id, 'nom' => 'Mutuelle', 'taux_pec' => 0.8]);
        $this->patient->update(['assureur_id' => $assureur->id]);
        $acte = Acte::create(['cabinet_id' => $this->patient->cabinet_id, 'libelle' => 'Composite', 'prix' => 3333]);

        $f = $this->service->brouillonPour($this->secretaire, $this->patient);
        $this->service->ajouterLigne($this->secretaire, $f, ['acte_id' => $acte->id, 'quantite' => 3]);
        $f = $this->service->valider($this->secretaire, $f->fresh());

        $this->assertSame(9999.0, $f->total);
        $this->assertSame(7999.2, $f->part_pec);
        $this->assertSame(1999.8, $f->part_patient);
        $this->assertSame($f->total, round($f->part_pec + $f->part_patient, 2));

        // Le taux est figé après validation.
        $assureur->update(['taux_pec' => 0.5]);
        $this->assertSame(0.8, $f->fresh()->taux_pec);
    }

    public function test_paiements_sans_trop_percu_et_remboursements()
    {
        $f = $this->factureValidee(10000);
        $this->payer($f, 4000);
        $this->assertSame(6000.0, $f->fresh()->restePatient());

        try {
            $this->payer($f, 6001);
            $this->fail('Le trop-perçu doit être refusé.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        try {
            $this->payer($f, 100, 'patient', 'remboursement');
            $this->fail('La secrétaire ne rembourse pas.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        try {
            $this->payer($f, 4001, 'patient', 'remboursement', $this->proprietaire);
            $this->fail('On ne rembourse pas plus que reçu.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $this->payer($f, 1000, 'patient', 'remboursement', $this->proprietaire);
        $this->payer($f, 7000);
        $this->assertTrue($f->fresh()->estSoldee());
        $this->assertSame(0, Facture::impayees()->count());

        try {
            $this->payer($f, 1, 'assureur');
            $this->fail('Pas de part assureur.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_annulation()
    {
        $f = $this->factureValidee();
        $this->payer($f, 500);

        try {
            $this->service->annuler($this->secretaire, $f->fresh(), 'Erreur');
            $this->fail();
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
        try {
            $this->service->annuler($this->proprietaire, $f->fresh(), 'Erreur');
            $this->fail('Annulation avec paiement net interdite.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $this->payer($f, 500, 'patient', 'remboursement', $this->proprietaire);
        $this->service->annuler($this->proprietaire, $f->fresh(), 'Erreur de saisie');
        $this->assertSame('annulee', $f->fresh()->statut);

        $brouillon = $this->service->brouillonPour($this->secretaire, $this->patient);
        $this->service->annuler($this->secretaire, $brouillon);
        $this->assertNull(Facture::find($brouillon->id));
    }

    public function test_soin_du_plan_facture_une_seule_fois_et_reinscriptible_apres_annulation()
    {
        $dossier = app(DossierDentaireService::class);
        $ligne = $dossier->ajouterLigne($this->proprietaire, $this->patient, ['libelle' => 'Composite', 'quantite' => 1, 'prix_unitaire' => 3500, 'dents' => [36]]);

        try {
            $this->service->facturerLignePlan($this->proprietaire, $ligne);
            $this->fail('Plan non accepté.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $dossier->changerStatutPlan($this->proprietaire, $ligne->plan, 'propose');
        $dossier->changerStatutPlan($this->proprietaire, $ligne->plan->fresh(), 'accepte');

        $fl = $this->service->facturerLignePlan($this->proprietaire, $ligne);
        $this->assertSame([36], $fl->dents);
        $this->assertSame(3500.0, $fl->facture->fresh()->total);

        try {
            $this->service->facturerLignePlan($this->proprietaire, $ligne);
            $this->fail('Double facturation.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        try {
            $dossier->supprimerLigne($this->proprietaire, $ligne->fresh());
            $this->fail('Soin facturé non supprimable.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $facture = $this->service->valider($this->proprietaire, $fl->facture);
        $this->service->annuler($this->proprietaire, $facture, 'Erreur');
        $this->assertNotNull($this->service->facturerLignePlan($this->proprietaire, $ligne)->id);
    }

    public function test_isolation_et_ecran()
    {
        $f = $this->factureValidee();
        $etranger = User::factory()->role(Role::Proprietaire)->create();

        $this->actingAs($etranger)->get(route('factures.imprimer', $f))->assertNotFound();
        $this->actingAs($etranger)->get(route('factures', ['facture' => $f->id]))->assertOk()->assertDontSee($f->numero);

        $this->actingAs($this->secretaire)->get(route('factures.imprimer', $f))->assertOk()->assertSee($f->numero);
        $this->get(route('tarifs'))->assertForbidden();

        Livewire::test(Factures::class, ['facture' => $f->id])
            ->set('facture', $f->id)
            ->set('paiement.montant', 2500)
            ->call('enregistrerPaiement')
            ->assertHasNoErrors();
        $this->assertSame(2500.0, $f->fresh()->regle_patient);
        $this->get(route('caisse'))->assertOk()->assertSee('2 500 MRU');
    }

    public function test_un_devis_peut_etre_cree_depuis_ecran_facturation()
    {
        $composant = Livewire::test(Factures::class)
            ->call('nouvelleFacture')
            ->assertSet('creationOuverte', true)
            ->set('recherchePatient', 'Ba')
            ->assertSee('Fiche N° '.$this->patient->numero_fiche)
            ->call('creerPourPatient', $this->patient->id)
            ->assertSet('creationOuverte', false)
            ->assertSet('filtre', 'brouillons');

        $facture = Facture::where('patient_id', $this->patient->id)->where('statut', 'brouillon')->firstOrFail();
        $composant->assertSet('facture', $facture->id);
    }

    public function test_impressions_repartition_assurance_patient_et_formats()
    {
        $assureur = Assureur::create(['cabinet_id' => $this->patient->cabinet_id, 'nom' => 'Mutuelle', 'taux_pec' => 0.8]);
        $this->patient->update(['assureur_id' => $assureur->id]);
        $f = $this->service->brouillonPour($this->secretaire, $this->patient);
        $this->service->ajouterLigne($this->secretaire, $f, ['libelle' => 'Consultation', 'quantite' => 1, 'prix_unitaire' => 1001]);
        $this->service->ajouterLigne($this->secretaire, $f, ['libelle' => 'Détartrage', 'quantite' => 1, 'prix_unitaire' => 2503]);
        $f = $this->service->valider($this->secretaire, $f)->load('lignes');

        $parts = $f->repartitionLignes();
        $this->assertEqualsWithDelta($f->part_pec, array_sum(array_column($parts, 'pec')), 0.001, 'Les parts par ligne retombent sur le total assurance.');
        $this->assertEqualsWithDelta($f->part_patient, array_sum(array_column($parts, 'patient')), 0.001);

        $p = $this->payer($f, $f->part_patient);
        $this->actingAs($this->proprietaire);
        $this->get(route('factures.imprimer', $f))->assertOk()
            ->assertSee("À la charge de l'assurance", false)->assertSee('À la charge du patient')
            ->assertSee('Part assurance')->assertSee('data-choix="a5"', false);
        $this->get(route('paiements.recu', $p))->assertOk()
            ->assertSee('Le patient')->assertSee('au titre de la part patient')
            ->assertSee('Part assurance')->assertSee("dû par l'assurance", false);

        // Sans assurance : tout est à la charge du patient, sans blocs de répartition.
        $this->patient = Patient::create(['cabinet_id' => $this->patient->cabinet_id, 'nom' => 'Sans assurance']);
        $f2 = $this->factureValidee(500);
        $this->get(route('factures.imprimer', $f2))->assertOk()
            ->assertSee('la totalité est à la charge du patient')->assertDontSee("À la charge de l'assurance", false);
    }
}
