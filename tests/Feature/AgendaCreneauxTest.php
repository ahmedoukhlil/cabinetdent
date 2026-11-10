<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Agenda;
use App\Models\Cabinet;
use App\Models\Patient;
use App\Models\User;
use App\Services\EquipeService;
use App\Services\RendezVousService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AgendaCreneauxTest extends TestCase
{
    use RefreshDatabase;

    private User $dentiste;

    private Patient $patient;

    private RendezVousService $service;

    private string $lundi;

    protected function setUp(): void
    {
        parent::setUp();
        // Lundi 7 h : aucun créneau de la journée n'est encore passé.
        Carbon::setTestNow(Carbon::parse('2026-11-09 07:00'));
        $this->lundi = '2026-11-09';

        $cabinet = Cabinet::factory()->create([
            'duree_rdv_minutes' => 30, 'heure_ouverture' => '08:00', 'heure_fermeture' => '10:00', 'jours_ouverture' => [1, 2, 3, 4, 5],
        ]);
        $this->dentiste = User::factory()->role(Role::Dentiste)->create(['cabinet_id' => $cabinet->id]);
        $this->patient = Patient::create(['cabinet_id' => $cabinet->id, 'nom' => 'Ba']);
        $this->service = app(RendezVousService::class);
        $this->actingAs($this->dentiste);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function rdv(string $heure, string $date = null)
    {
        return $this->service->creer($this->dentiste, ['patient_id' => $this->patient->id, 'dentiste_id' => $this->dentiste->id, 'date' => $date ?? $this->lundi, 'heure' => $heure]);
    }

    public function test_creneaux_selon_horaires_duree_et_occupation()
    {
        $this->assertSame(['08:00', '08:30', '09:00', '09:30'], $this->service->creneauxLibres($this->dentiste, $this->dentiste->id, $this->lundi));

        $this->rdv('08:45');
        // 08:45 bloque 08:30 et 09:00 (chevauchement de moins de 30 min).
        $this->assertSame(['08:00', '09:30'], $this->service->creneauxLibres($this->dentiste, $this->dentiste->id, $this->lundi));

        // Un RDV annulé libère le créneau.
        $r = $this->rdv('09:30');
        $this->service->changerStatut($this->dentiste, $r, \App\Enums\StatutRdv::Annule);
        $this->assertContains('09:30', $this->service->creneauxLibres($this->dentiste, $this->dentiste->id, $this->lundi));
    }

    public function test_jour_ferme_passe_et_heures_ecoulees()
    {
        $this->assertSame([], $this->service->creneauxLibres($this->dentiste, $this->dentiste->id, '2026-11-14'), 'Samedi fermé');
        $this->assertSame([], $this->service->creneauxLibres($this->dentiste, $this->dentiste->id, '2026-11-06'), 'Date passée');

        Carbon::setTestNow(Carbon::parse('2026-11-09 08:40'));
        $this->assertSame(['09:00', '09:30'], $this->service->creneauxLibres($this->dentiste, $this->dentiste->id, $this->lundi));
    }

    public function test_reservation_refuse_les_chevauchements()
    {
        $this->rdv('09:00');
        foreach (['09:00', '09:15', '08:31'] as $h) {
            try {
                $this->rdv($h);
                $this->fail("{$h} chevauche 09:00");
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertNotNull($this->rdv('09:30')->id);
        $this->assertNotNull($this->rdv('08:30')->id);
    }

    public function test_horaires_modifiables_par_le_proprietaire()
    {
        $p = User::factory()->role(Role::Proprietaire)->create(['cabinet_id' => $this->dentiste->cabinet_id]);
        $equipe = app(EquipeService::class);
        $base = ['nom' => 'C', 'duree_rdv_minutes' => 20, 'heure_ouverture' => '09:00', 'heure_fermeture' => '10:00', 'jours_ouverture' => ['6', '1', '1']];

        $equipe->modifierCabinet($p, $base);
        $this->assertSame([1, 6], $p->cabinet->fresh()->jours_ouverture);
        $this->assertSame(['09:00', '09:20', '09:40'], $this->service->creneauxLibres($p->fresh(), $this->dentiste->id, '2026-11-14'));

        foreach ([['heure_fermeture' => '08:00'], ['jours_ouverture' => []]] as $invalide) {
            try {
                $equipe->modifierCabinet($p, [...$base, ...$invalide]);
                $this->fail();
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_ecran_agenda_creneau_cliquable_et_semaine()
    {
        $this->rdv('08:00', '2026-11-10');

        Livewire::test(Agenda::class, ['date' => $this->lundi])
            ->assertSee('Créneaux libres')
            ->assertSee('09:30')
            ->assertSee('1 RDV')
            ->assertSee('fermé')
            ->call('choisirHeure', '08:30')
            ->assertSet('heure', '08:30')
            ->call('choisirPatient', $this->patient->id)
            ->call('creer')
            ->assertHasNoErrors()
            ->assertDontSee('wire:click="choisirHeure(\'08:30\')"', false)
            ->set('heure', '08:45')
            ->call('choisirPatient', $this->patient->id)
            ->call('creer')
            ->assertHasErrors('heure');
    }
}
