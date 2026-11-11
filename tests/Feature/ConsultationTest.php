<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StatutRdv;
use App\Livewire\ConsultationRapide;
use App\Livewire\ConsultationsNonFacturees;
use App\Livewire\SalleAttente;
use App\Models\Acte;
use App\Models\ActeTarif;
use App\Models\Assureur;
use App\Models\Cabinet;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\RendezVous;
use App\Models\User;
use App\Services\ConsultationService;
use App\Services\FacturationService;
use App\Services\RendezVousService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ConsultationTest extends TestCase
{
    use RefreshDatabase;

    private User $secretaire;

    private User $dentiste;

    private Acte $consultation;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $cabinet = Cabinet::factory()->create();
        $this->secretaire = User::factory()->role(Role::Secretaire)->create(['cabinet_id' => $cabinet->id]);
        $this->dentiste = User::factory()->role(Role::Dentiste)->create(['cabinet_id' => $cabinet->id]);
        $this->actingAs($this->secretaire);
        $this->consultation = Acte::create(['libelle' => 'Consultation', 'type' => 'consultation', 'prix' => 1000]);
        $this->patient = Patient::create(['nom' => 'Ba', 'prenom' => 'Awa']);
    }

    private function rdv(StatutRdv $statut = StatutRdv::Planifie): RendezVous
    {
        return RendezVous::create([
            'patient_id' => $this->patient->id, 'dentiste_id' => $this->dentiste->id,
            'date' => today(), 'heure' => '09:00', 'ordre' => 1, 'statut' => $statut,
        ]);
    }

    public function test_consultation_cree_facture_validee_et_entree_en_caisse()
    {
        $rdv = $this->rdv(StatutRdv::Arrive);
        ['facture' => $f, 'paiement' => $p] = app(ConsultationService::class)
            ->facturer($this->secretaire, $rdv, ['acte_id' => $this->consultation->id, 'encaisser' => true, 'mode' => 'especes']);

        $this->assertSame('validee', $f->statut);
        $this->assertNotNull($f->numero);
        $this->assertSame(1000.0, $f->total);
        $this->assertSame(0.0, $f->restePatient());
        $this->assertSame($this->dentiste->id, $f->dentiste_id);
        $this->assertSame(1000.0, $p->montant);
        $this->assertSame($f->id, $rdv->fresh()->facture_id);
        $this->assertSame(1, Paiement::whereDate('created_at', today())->count(), 'Le paiement apparaît dans la caisse du jour.');

        $this->expectException(ValidationException::class);
        app(ConsultationService::class)->facturer($this->secretaire, $rdv->fresh(), ['acte_id' => $this->consultation->id]);
    }

    public function test_prix_et_part_assureur_et_devis_du_patient_preserve()
    {
        $cnam = Assureur::create(['nom' => 'CNAM', 'taux_pec' => 0.8]);
        ActeTarif::create(['acte_id' => $this->consultation->id, 'assureur_id' => $cnam->id, 'prix' => 500]);
        $this->patient->update(['assureur_id' => $cnam->id]);
        $devis = app(FacturationService::class)->brouillonPour($this->secretaire, $this->patient);

        ['facture' => $f, 'paiement' => $p] = app(ConsultationService::class)
            ->facturer($this->secretaire, $this->rdv(StatutRdv::Arrive), ['acte_id' => $this->consultation->id, 'encaisser' => true, 'mode' => 'mobile']);

        $this->assertSame(500.0, $f->total);
        $this->assertSame(400.0, $f->part_pec);
        $this->assertSame(100.0, $p->montant, 'Seule la part patient est encaissée.');
        $this->assertNotSame($devis->id, $f->id);
        $this->assertSame('brouillon', $devis->fresh()->statut);
    }

    public function test_regles_acte_consultation_annulation_et_droits()
    {
        $soin = Acte::create(['libelle' => 'Détartrage', 'prix' => 2500]);
        $service = app(ConsultationService::class);
        try {
            $service->facturer($this->secretaire, $this->rdv(), ['acte_id' => $soin->id]);
            $this->fail('Seuls les actes de type consultation.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        // Le dentiste peut facturer, pas encaisser.
        $rdv = $this->rdv(StatutRdv::EnCours);
        try {
            $service->facturer($this->dentiste, $rdv, ['acte_id' => $this->consultation->id, 'encaisser' => true, 'mode' => 'especes']);
            $this->fail('Encaissement réservé.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
        ['facture' => $f] = $service->facturer($this->dentiste, $rdv, ['acte_id' => $this->consultation->id]);
        $this->assertSame(1000.0, $f->restePatient());

        // Facture annulée : la consultation peut être refacturée.
        $proprietaire = User::factory()->role(Role::Proprietaire)->create(['cabinet_id' => $this->secretaire->cabinet_id]);
        app(FacturationService::class)->annuler($proprietaire, $f, 'Erreur de tarif');
        $this->assertFalse($rdv->fresh()->consultationFacturee());
        $this->assertNotNull($service->facturer($this->secretaire, $rdv->fresh(), ['acte_id' => $this->consultation->id])['facture']->numero);
    }

    public function test_arrivee_ouvre_la_fenetre_puis_facture_encaisse_et_propose_le_recu()
    {
        $rdv = $this->rdv();
        Livewire::test(SalleAttente::class)
            ->call('changerStatut', $rdv->id, 'arrive')
            ->assertDispatched('consultation-ouvrir', rdv: $rdv->id);

        $paiement = null;
        Livewire::test(ConsultationRapide::class)
            ->dispatch('consultation-ouvrir', rdv: $rdv->id)
            ->assertSee('Facturer la consultation')
            ->assertSee('Facturer et encaisser')
            ->set('consultation.mode', 'carte')
            ->call('facturer')
            ->assertHasNoErrors()
            ->assertDispatched('consultation-facturee')
            ->assertSet('etape', 'fait')
            ->assertSee('Imprimer le reçu')
            ->assertSee(route('paiements.recu', Paiement::first()));

        $this->assertSame(StatutRdv::Arrive, $rdv->fresh()->statut);
        $this->assertSame('carte', Paiement::first()->mode);
        Livewire::test(SalleAttente::class)->assertSee('Payée');
    }

    public function test_consultation_sans_rendez_vous_avec_nouveau_patient()
    {
        Livewire::test(ConsultationRapide::class)
            ->dispatch('consultation-sans-rdv')
            ->assertSet('etape', 'patient')
            ->set('recherche', 'Diallo')
            ->call('nouveauPatient')
            ->assertSet('nouveau.nom', 'Diallo')
            ->set('nouveau.telephone', '22 33 44 55')
            ->set('dentisteId', (string) $this->dentiste->id)
            ->call('inscrire')
            ->assertHasNoErrors()
            ->assertSet('etape', 'facture')
            ->call('facturer')
            ->assertSet('etape', 'fait');

        $rdv = RendezVous::whereHas('patient', fn ($q) => $q->where('nom', 'Diallo'))->firstOrFail();
        $this->assertSame(StatutRdv::Arrive, $rdv->statut);
        $this->assertTrue($rdv->date->isToday());
        $this->assertTrue($rdv->consultationFacturee());

        // Déjà en salle d'attente : pas de seconde inscription.
        $this->expectException(ValidationException::class);
        app(RendezVousService::class)->sansRendezVous($this->secretaire, $rdv->patient, $this->dentiste->id);
    }

    public function test_fiche_patient_reprend_le_rdv_du_jour_et_alerte_accueil()
    {
        $rdv = $this->rdv(StatutRdv::Termine);
        Livewire::test(ConsultationsNonFacturees::class)
            ->assertSee('1 consultation non facturée')
            ->assertSee($this->patient->nom_complet);

        // Depuis la fiche : le RDV du jour non facturé est repris, sans nouvelle inscription.
        Livewire::test(ConsultationRapide::class)
            ->dispatch('consultation-sans-rdv', patient: $this->patient->id)
            ->assertSet('etape', 'facture')
            ->assertSet('rdvId', $rdv->id)
            ->set('consultation.encaisser', false)
            ->call('facturer')
            ->assertSet('etape', null);

        Livewire::test(ConsultationsNonFacturees::class)
            ->assertDontSee('non facturée')
            ->assertSee('1 à encaisser (1 000 MRU)');
        $this->assertSame(1, RendezVous::count());
    }

    public function test_modale_creation_patient_rapide()
    {
        $this->assertSame(['telephone' => '38523000'], \App\Livewire\PatientRapide::preRemplir(' 38523000 '));
        $this->assertSame(['prenom' => 'Awa', 'nom' => 'Sall'], \App\Livewire\PatientRapide::preRemplir('Awa  Sall'));
        $this->assertSame(['nom' => 'Kane'], \App\Livewire\PatientRapide::preRemplir('Kane'));

        Livewire::test(\App\Livewire\PatientRapide::class)
            ->dispatch('patient-rapide-ouvrir', terme: '38523000', source: 'consultation')
            ->assertSet('ouvert', true)
            ->assertSet('form.telephone', '38523000')
            ->call('creer')
            ->assertHasErrors('form.nom')
            ->set('form.nom', 'Sall')
            ->call('creer')
            ->assertHasNoErrors()
            ->assertSet('ouvert', false)
            ->assertDispatched('patient-cree', source: 'consultation');

        $patient = Patient::where('telephone', '38523000')->firstOrFail();
        Livewire::test(ConsultationRapide::class)
            ->dispatch('consultation-sans-rdv')
            ->set('recherche', '38523000')
            ->dispatch('patient-cree', id: $patient->id, source: 'consultation')
            ->assertSet('patientId', $patient->id)
            ->dispatch('patient-cree', id: 999, source: 'autre')
            ->assertSet('patientId', $patient->id);
    }
}
