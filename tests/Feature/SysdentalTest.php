<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StatutRdv;
use App\Livewire\Agenda;
use App\Livewire\MaJournee;
use App\Livewire\Patients;
use App\Livewire\SalleAttente;
use App\Models\Cabinet;
use App\Models\Patient;
use App\Models\RendezVous;
use App\Models\User;
use App\Services\RendezVousService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SysdentalTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateur(Role $role, ?Cabinet $cabinet = null): User
    {
        return User::factory()->role($role)->create(['cabinet_id' => ($cabinet ?? Cabinet::factory()->create())->id]);
    }

    private function rdv(User $dentiste, Patient $patient, StatutRdv $statut = StatutRdv::Planifie, string $heure = '09:00:00'): RendezVous
    {
        return RendezVous::create([
            'cabinet_id' => $dentiste->cabinet_id, 'patient_id' => $patient->id, 'dentiste_id' => $dentiste->id,
            'date' => today(), 'heure' => $heure, 'ordre' => 1, 'statut' => $statut,
            'arrive_at' => $statut === StatutRdv::Arrive ? now() : null,
            'debut_at' => $statut === StatutRdv::EnCours ? now() : null,
        ]);
    }

    private function donneesRdv(Patient $patient, User $dentiste, string $heure): array
    {
        return ['patient_id' => $patient->id, 'dentiste_id' => $dentiste->id, 'date' => today()->toDateString(), 'heure' => $heure];
    }

    public function test_connexion_et_redirection_des_invites()
    {
        $user = $this->utilisateur(Role::Secretaire);

        $this->get('/')->assertRedirect('/connexion');
        $this->post('/connexion', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/');
        $this->get('/')->assertOk()->assertSee('Rechercher un patient');
    }

    public function test_compte_desactive_refuse()
    {
        $user = $this->utilisateur(Role::Dentiste);
        $user->update(['actif' => false]);

        $this->post('/connexion', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_numero_de_fiche_sequentiel_par_cabinet_et_isolation()
    {
        $a = $this->utilisateur(Role::Secretaire);
        $b = $this->utilisateur(Role::Secretaire);

        $p1 = Patient::create(['cabinet_id' => $a->cabinet_id, 'nom' => 'Un']);
        $p2 = Patient::create(['cabinet_id' => $a->cabinet_id, 'nom' => 'Deux']);
        $p3 = Patient::create(['cabinet_id' => $b->cabinet_id, 'nom' => 'Autre']);

        $this->assertSame([1, 2, 1], [$p1->numero_fiche, $p2->numero_fiche, $p3->numero_fiche]);

        $this->actingAs($a);
        $this->assertNull(Patient::find($p3->id));
        Livewire::test(Patients::class)->assertSee('Deux')->assertDontSee('Autre');
    }

    public function test_creation_patient_et_permission_suppression()
    {
        $secretaire = $this->utilisateur(Role::Secretaire);
        $this->actingAs($secretaire);

        Livewire::test(Patients::class)
            ->call('nouveau')
            ->set('form.nom', 'Vall')
            ->set('form.telephone', '22223333')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $patient = Patient::firstWhere('nom', 'Vall');
        $this->assertSame($secretaire->cabinet_id, $patient->cabinet_id);

        Livewire::test(Patients::class)->call('supprimer', $patient->id)->assertForbidden();
    }

    public function test_creation_rdv_conflit_et_ordre()
    {
        $secretaire = $this->utilisateur(Role::Secretaire);
        $dentiste = $this->utilisateur(Role::Dentiste, $secretaire->cabinet);
        $patient = Patient::create(['cabinet_id' => $secretaire->cabinet_id, 'nom' => 'Ba']);
        $service = app(RendezVousService::class);
        $this->actingAs($secretaire);

        $r1 = $service->creer($secretaire, $this->donneesRdv($patient, $dentiste, '09:00'));
        $r2 = $service->creer($secretaire, $this->donneesRdv($patient, $dentiste, '09:30'));
        $this->assertSame([1, 2], [$r1->ordre, $r2->ordre]);

        $this->expectException(ValidationException::class);
        $service->creer($secretaire, $this->donneesRdv($patient, $dentiste, '09:00'));
    }

    public function test_rdv_refuse_patient_ou_dentiste_d_un_autre_cabinet()
    {
        $secretaire = $this->utilisateur(Role::Secretaire);
        $dentiste = $this->utilisateur(Role::Dentiste, $secretaire->cabinet);
        $etranger = $this->utilisateur(Role::Dentiste);
        $patientEtranger = Patient::create(['cabinet_id' => $etranger->cabinet_id, 'nom' => 'X']);
        $patient = Patient::create(['cabinet_id' => $secretaire->cabinet_id, 'nom' => 'Y']);
        $this->actingAs($secretaire);
        $service = app(RendezVousService::class);

        foreach ([[$patientEtranger, $dentiste], [$patient, $etranger]] as [$p, $d]) {
            try {
                $service->creer($secretaire, $this->donneesRdv($p, $d, '10:00'));
                $this->fail('Le rendez-vous aurait dû être refusé.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_dentiste_ne_planifie_que_pour_lui()
    {
        $dentiste = $this->utilisateur(Role::Dentiste);
        $confrere = $this->utilisateur(Role::Dentiste, $dentiste->cabinet);
        $patient = Patient::create(['cabinet_id' => $dentiste->cabinet_id, 'nom' => 'Z']);
        $this->actingAs($dentiste);

        $this->expectException(AuthorizationException::class);
        app(RendezVousService::class)->creer($dentiste, $this->donneesRdv($patient, $confrere, '10:00'));
    }

    public function test_cycle_de_vie_et_transitions_interdites()
    {
        $secretaire = $this->utilisateur(Role::Secretaire);
        $dentiste = $this->utilisateur(Role::Dentiste, $secretaire->cabinet);
        $patient = Patient::create(['cabinet_id' => $dentiste->cabinet_id, 'nom' => 'Sy']);
        $rdv = $this->rdv($dentiste, $patient);
        $service = app(RendezVousService::class);
        $this->actingAs($secretaire);

        $service->changerStatut($secretaire, $rdv, StatutRdv::Arrive);
        $this->assertNotNull($rdv->fresh()->arrive_at);

        try {
            $service->changerStatut($secretaire, $rdv, StatutRdv::EnCours);
            $this->fail('La secrétaire ne doit pas démarrer les soins.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        $service->changerStatut($dentiste, $rdv, StatutRdv::EnCours);
        $service->changerStatut($dentiste, $rdv, StatutRdv::Termine);
        $this->assertSame(StatutRdv::Termine, $rdv->fresh()->statut);

        $this->expectException(ValidationException::class);
        $service->changerStatut($secretaire, $rdv, StatutRdv::Annule);
    }

    public function test_un_seul_patient_au_fauteuil_par_dentiste()
    {
        $dentiste = $this->utilisateur(Role::Dentiste);
        $p1 = Patient::create(['cabinet_id' => $dentiste->cabinet_id, 'nom' => 'A']);
        $p2 = Patient::create(['cabinet_id' => $dentiste->cabinet_id, 'nom' => 'B']);
        $this->rdv($dentiste, $p1, StatutRdv::EnCours);
        $attente = $this->rdv($dentiste, $p2, StatutRdv::Arrive, '09:30:00');
        $this->actingAs($dentiste);

        $this->expectException(ValidationException::class);
        app(RendezVousService::class)->changerStatut($dentiste, $attente, StatutRdv::EnCours);
    }

    public function test_ma_journee_et_portee_cabinet_reservee_au_proprietaire()
    {
        $proprietaire = $this->utilisateur(Role::Proprietaire);
        $dentiste = $this->utilisateur(Role::Dentiste, $proprietaire->cabinet);
        $moi = Patient::create(['cabinet_id' => $proprietaire->cabinet_id, 'nom' => 'MonPatient']);
        $confrere = Patient::create(['cabinet_id' => $proprietaire->cabinet_id, 'nom' => 'PatientConfrere']);
        $this->rdv($proprietaire, $moi, StatutRdv::EnCours);
        $this->rdv($dentiste, $confrere, StatutRdv::Arrive);

        $this->actingAs($proprietaire);
        Livewire::test(MaJournee::class)
            ->assertSee('MonPatient')->assertDontSee('PatientConfrere')->assertSee('Synthèse du jour')
            ->call('setPortee', 'cabinet')
            ->assertSee('PatientConfrere');

        $this->actingAs($dentiste);
        Livewire::test(MaJournee::class)
            ->call('setPortee', 'cabinet')
            ->assertSet('portee', 'moi')
            ->assertDontSee('Synthèse du jour')
            ->assertDontSee('MonPatient');
    }

    public function test_pages_accessibles_selon_le_role()
    {
        $secretaire = $this->utilisateur(Role::Secretaire);
        $this->actingAs($secretaire);

        foreach (['/', '/patients', '/agenda', '/salle-attente'] as $url) {
            $this->get($url)->assertOk();
        }

        Livewire::test(SalleAttente::class)->assertDontSee('Faire entrer');
        Livewire::test(Agenda::class)->assertSee('Nouveau rendez-vous');
    }
}
