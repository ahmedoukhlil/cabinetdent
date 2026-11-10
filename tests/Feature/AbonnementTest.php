<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Plateforme\Cabinets;
use App\Models\Abonnement;
use App\Models\AdminPlateforme;
use App\Models\Cabinet;
use App\Models\Patient;
use App\Models\PlanAbonnement;
use App\Models\User;
use App\Services\AbonnementService;
use App\Services\EquipeService;
use App\Services\FichierPatientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AbonnementTest extends TestCase
{
    use RefreshDatabase;

    private AdminPlateforme $admin;

    private Cabinet $cabinet;

    private User $proprietaire;

    private PlanAbonnement $plan;

    private AbonnementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-01-10 09:00'));
        $this->admin = AdminPlateforme::create(['name' => 'Admin', 'email' => 'admin@exemple.test', 'password' => 'motdepasse-long']);
        $this->cabinet = Cabinet::factory()->create();
        $this->proprietaire = User::factory()->role(Role::Proprietaire)->create(['cabinet_id' => $this->cabinet->id]);
        $this->plan = PlanAbonnement::create(['nom' => 'Cabinet', 'prix_mensuel' => 3000, 'max_utilisateurs' => 2, 'max_stockage_mo' => 1]);
        $this->service = app(AbonnementService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function payer(Abonnement $a, int $mois = 1)
    {
        return $this->service->enregistrerPaiement($this->admin, $a, ['mois' => $mois, 'mode' => 'virement']);
    }

    public function test_paiement_part_de_la_date_la_plus_tardive()
    {
        $a = $this->service->demarrerEssai($this->cabinet, $this->plan, 14);
        $this->assertSame('2026-01-24', $a->essai_fin->toDateString());

        // Payé pendant l'essai : la période démarre à la fin de l'essai.
        $p = $this->payer($a, 2);
        $this->assertSame('2026-01-24', $p->periode_debut->toDateString());
        $this->assertSame('2026-03-24', $a->fresh()->periode_fin->toDateString());
        $this->assertSame(6000.0, $p->montant);
        $this->assertSame('actif', $a->fresh()->statut);

        // Payé en avance : prolonge la période en cours.
        $this->assertSame('2026-04-24', $this->payer($a->fresh())->periode_fin->toDateString());

        // Payé en retard : repart d'aujourd'hui.
        Carbon::setTestNow(Carbon::parse('2026-06-01'));
        $this->assertSame('2026-07-01', $this->payer($a->fresh())->periode_fin->toDateString());
    }

    public function test_cycle_impaye_grace_suspension_et_reprise()
    {
        $a = $this->service->demarrerEssai($this->cabinet, $this->plan, 5); // fin 15/01
        $this->service->verifier($a, Carbon::parse('2026-01-15'));
        $this->assertSame('essai', $a->statut);

        $this->service->verifier($a, Carbon::parse('2026-01-16'));
        $this->assertSame('impaye', $a->statut);
        $this->assertSame('2026-01-16', $a->impaye_depuis->toDateString());
        $this->assertTrue(AbonnementService::accesAutorise($this->cabinet->fresh()), 'Impayé : accès maintenu pendant la grâce.');

        // Grâce de 5 jours : suspendu à partir du 22.
        $this->service->verifier($a, Carbon::parse('2026-01-21'));
        $this->assertSame('impaye', $a->statut);
        $this->service->verifier($a, Carbon::parse('2026-01-22'));
        $this->assertSame('suspendu', $a->statut);
        $this->assertFalse(AbonnementService::accesAutorise($this->cabinet->fresh()));

        $this->actingAs($this->proprietaire)->get(route('accueil'))->assertRedirect(route('login'));
        $this->post('/connexion', ['email' => $this->proprietaire->email, 'password' => 'password'])->assertSessionHasErrors('email');

        $this->payer($a->fresh());
        $this->assertSame('actif', $a->fresh()->statut);
        $this->assertNull($a->fresh()->suspendu_at);
        $this->assertTrue(AbonnementService::accesAutorise($this->cabinet->fresh()));

        // Fin de période → impayé.
        $this->assertSame(1, $this->service->verifierTous(Carbon::parse('2026-02-16')));
        $this->assertSame('impaye', $a->fresh()->statut);
    }

    public function test_resiliation_finale()
    {
        $a = $this->service->demarrerEssai($this->cabinet, $this->plan);
        try {
            $this->service->demarrerEssai($this->cabinet, $this->plan);
            $this->fail('Un seul abonnement en cours.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $this->service->resilier($a, 'Fermeture du cabinet');
        $this->assertFalse(AbonnementService::accesAutorise($this->cabinet->fresh()));
        try {
            $this->payer($a->fresh());
            $this->fail('Résilié est final.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $nouveau = $this->service->demarrerEssai($this->cabinet->fresh(), $this->plan, 0);
        $this->payer($nouveau);
        $this->assertSame($nouveau->id, $this->cabinet->fresh()->abonnement->id);
        $this->assertTrue(AbonnementService::accesAutorise($this->cabinet->fresh()));
    }

    public function test_quotas_utilisateurs_et_stockage()
    {
        Storage::fake('local');
        $this->service->demarrerEssai($this->cabinet, $this->plan);
        $this->actingAs($this->proprietaire);
        $equipe = app(EquipeService::class);

        $second = $equipe->creer($this->proprietaire, ['name' => 'B', 'email' => 'b@exemple.test', 'role' => 'secretaire', 'password' => 'motdepasse1']);
        try {
            $equipe->creer($this->proprietaire, ['name' => 'C', 'email' => 'c@exemple.test', 'role' => 'dentiste', 'password' => 'motdepasse1']);
            $this->fail('Quota de 2 comptes.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
        $equipe->basculerActif($this->proprietaire, $second);
        $this->assertNotNull($equipe->creer($this->proprietaire, ['name' => 'C', 'email' => 'c@exemple.test', 'role' => 'dentiste', 'password' => 'motdepasse1'])->id);

        // 1 Mo : un fichier de 600 Ko passe, un second non (suppressions logiques comptées).
        $patient = Patient::create(['cabinet_id' => $this->cabinet->id, 'nom' => 'X']);
        $fichiers = app(FichierPatientService::class);
        $f = $fichiers->ajouter($this->proprietaire, $patient, UploadedFile::fake()->create('a.pdf', 600, 'application/pdf'), ['categorie' => 'autre']);
        $fichiers->supprimer($this->proprietaire, $f);
        $this->expectException(ValidationException::class);
        $fichiers->ajouter($this->proprietaire, $patient, UploadedFile::fake()->create('b.pdf', 600, 'application/pdf'), ['categorie' => 'autre']);
    }

    public function test_espace_plateforme_separe()
    {
        $this->get(route('plateforme.cabinets'))->assertRedirect(route('plateforme.login'));
        $this->actingAs($this->proprietaire)->get(route('plateforme.cabinets'))->assertRedirect(route('plateforme.login'));

        $this->post(route('plateforme.login'), ['email' => 'admin@exemple.test', 'password' => 'motdepasse-long'])->assertRedirect(route('plateforme.cabinets'));
        $this->get(route('plateforme.cabinets'))->assertOk()->assertSee($this->cabinet->nom);
        $this->get(route('plateforme.plans'))->assertOk()->assertSee('Cabinet');

        Livewire::actingAs($this->admin, 'plateforme')->test(Cabinets::class)
            ->set('creation', true)
            ->set('nouveau', ['nom' => 'Clinique Atlantique', 'telephone' => '', 'proprietaire' => 'Dr Sow', 'email' => 'sow@exemple.test', 'password' => 'motdepasse1', 'plan_id' => (string) $this->plan->id, 'essai_jours' => '30'])
            ->call('creer')
            ->assertHasNoErrors()
            ->assertSee('Clinique Atlantique')
            ->set('paiement.mois', '3')
            ->call('enregistrerPaiement')
            ->assertHasNoErrors();

        $nouveau = Cabinet::where('nom', 'Clinique Atlantique')->first();
        $this->assertSame('actif', $nouveau->abonnement->statut);
        $this->assertSame('2026-05-09', $nouveau->abonnement->periode_fin->toDateString());
        $this->assertTrue(User::where('email', 'sow@exemple.test')->first()->estProprietaire());
    }

    public function test_bandeau_impaye_pour_le_proprietaire()
    {
        $a = $this->service->demarrerEssai($this->cabinet, $this->plan, 0);
        $this->service->verifier($a, Carbon::parse('2026-01-11'));
        $secretaire = User::factory()->role(Role::Secretaire)->create(['cabinet_id' => $this->cabinet->id]);

        $this->actingAs($this->proprietaire)->get(route('accueil'))->assertOk()->assertSee('Abonnement impayé')->assertSee('17/01/2026');
        $this->actingAs($secretaire)->get(route('accueil'))->assertOk()->assertDontSee('Abonnement impayé');
    }
}
