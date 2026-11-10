<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Equipe;
use App\Models\Cabinet;
use App\Models\User;
use App\Services\EquipeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class EquipeTest extends TestCase
{
    use RefreshDatabase;

    private User $proprietaire;

    private EquipeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $cabinet = Cabinet::factory()->create();
        $this->proprietaire = User::factory()->role(Role::Proprietaire)->create(['cabinet_id' => $cabinet->id]);
        $this->service = app(EquipeService::class);
        $this->actingAs($this->proprietaire);
    }

    private function nouveau(string $role = 'secretaire', string $email = 'sec@exemple.test'): User
    {
        return $this->service->creer($this->proprietaire, ['name' => 'Fatou', 'email' => $email, 'role' => $role, 'password' => 'motdepasse1']);
    }

    public function test_creation_dans_le_cabinet_et_email_unique()
    {
        $u = $this->nouveau();
        $this->assertSame($this->proprietaire->cabinet_id, $u->cabinet_id);
        $this->assertTrue(Hash::check('motdepasse1', $u->password));

        $this->expectException(ValidationException::class);
        $this->nouveau();
    }

    public function test_seul_le_proprietaire_gere_l_equipe()
    {
        $sec = $this->nouveau();
        $this->actingAs($sec)->get(route('equipe'))->assertForbidden();

        $this->expectException(AuthorizationException::class);
        $this->service->creer($sec, ['name' => 'X', 'email' => 'x@exemple.test', 'role' => 'dentiste', 'password' => 'motdepasse1']);
    }

    public function test_pas_d_auto_desactivation_ni_cabinet_sans_proprietaire()
    {
        try {
            $this->service->basculerActif($this->proprietaire, $this->proprietaire);
            $this->fail();
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
        try {
            $this->service->modifier($this->proprietaire, $this->proprietaire, ['name' => 'P', 'email' => $this->proprietaire->email, 'role' => 'dentiste']);
            $this->fail();
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $second = $this->nouveau('proprietaire', 'p2@exemple.test');
        $this->service->basculerActif($this->proprietaire, $second);
        $this->assertFalse($second->fresh()->actif);

        // Le compte désactivé ne se connecte plus.
        $this->actingAs($second->fresh())->get(route('accueil'))->assertRedirect(route('login'));
    }

    public function test_isolation_et_modification_du_cabinet()
    {
        $etranger = User::factory()->role(Role::Dentiste)->create();
        try {
            $this->service->basculerActif($this->proprietaire, $etranger);
            $this->fail();
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        $this->get(route('equipe'))->assertOk()->assertDontSee($etranger->email);

        Livewire::test(Equipe::class)
            ->set('cabinet.nom', 'Clinique du Sourire')
            ->set('cabinet.duree_rdv_minutes', '45')
            ->call('enregistrerCabinet')
            ->assertHasNoErrors()
            ->set('compte', ['id' => null, 'name' => 'Dr Sy', 'email' => 'sy@exemple.test', 'role' => 'dentiste', 'password' => 'court'])
            ->call('enregistrer')
            ->assertHasErrors('compte.password');

        $this->assertSame('Clinique du Sourire', $this->proprietaire->cabinet->fresh()->nom);
        $this->assertSame(45, $this->proprietaire->cabinet->fresh()->duree_rdv_minutes);
    }
}
