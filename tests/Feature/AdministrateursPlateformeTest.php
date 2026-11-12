<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Plateforme\Administrateurs;
use App\Livewire\Plateforme\Plans;
use App\Models\AdminPlateforme;
use App\Models\Cabinet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdministrateursPlateformeTest extends TestCase
{
    use RefreshDatabase;

    private AdminPlateforme $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminPlateforme::create(['name' => 'Admin', 'email' => 'admin@exemple.test', 'password' => 'motdepasse-long']);
    }

    public function test_page_reservee_a_la_plateforme()
    {
        $this->get(route('plateforme.administrateurs'))->assertRedirect(route('plateforme.login'));
        $proprietaire = User::factory()->role(Role::Proprietaire)->create(['cabinet_id' => Cabinet::factory()->create()->id]);
        $this->actingAs($proprietaire)->get(route('plateforme.administrateurs'))->assertRedirect(route('plateforme.login'));

        $this->actingAs($this->admin, 'plateforme')->get(route('plateforme.administrateurs'))
            ->assertOk()->assertSee('admin@exemple.test')->assertSee('Vous');
    }

    public function test_creer_et_modifier_un_administrateur()
    {
        $c = Livewire::actingAs($this->admin, 'plateforme')->test(Administrateurs::class)
            ->call('nouveau')
            ->set('form', ['id' => null, 'name' => 'Fatou', 'email' => 'Fatou@Exemple.test', 'password' => 'court'])
            ->call('enregistrer')
            ->assertHasErrors(['form.password'])
            ->set('form.password', 'un-mot-de-passe-solide')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $fatou = AdminPlateforme::where('email', 'fatou@exemple.test')->firstOrFail();
        $this->assertTrue($fatou->actif);
        $this->assertTrue(Hash::check('un-mot-de-passe-solide', $fatou->password));

        // E-mail déjà pris.
        $c->call('nouveau')->set('form', ['id' => null, 'name' => 'Doublon', 'email' => 'admin@exemple.test', 'password' => 'un-mot-de-passe-solide'])
            ->call('enregistrer')->assertHasErrors(['form.email']);

        // Modification sans mot de passe : inchangé.
        $c->call('modifier', $fatou->id)->set('form.name', 'Fatou Ba')->set('form.password', '')->call('enregistrer')->assertHasNoErrors();
        $this->assertSame('Fatou Ba', $fatou->fresh()->name);
        $this->assertTrue(Hash::check('un-mot-de-passe-solide', $fatou->fresh()->password));

        // Réinitialisation du mot de passe.
        $c->call('modifier', $fatou->id)->set('form.password', 'nouveau-mot-de-passe')->call('enregistrer')->assertHasNoErrors();
        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $fatou->fresh()->password));
    }

    public function test_desactiver_depuis_la_page()
    {
        $autre = AdminPlateforme::create(['name' => 'Autre', 'email' => 'autre@exemple.test', 'password' => 'motdepasse-long']);

        Livewire::actingAs($this->admin, 'plateforme')->test(Administrateurs::class)->call('basculer', $autre->id)->assertHasNoErrors();
        $this->assertFalse($autre->fresh()->actif);
        Livewire::actingAs($autre->fresh(), 'plateforme')->test(Administrateurs::class)->assertForbidden();

        Livewire::actingAs($this->admin, 'plateforme')->test(Administrateurs::class)->call('basculer', $autre->id);
        $this->assertTrue($autre->fresh()->actif);
    }

    public function test_compte_desactive_sessions_fermees_et_connexion_refusee()
    {
        $autre = AdminPlateforme::create(['name' => 'Autre', 'email' => 'autre@exemple.test', 'password' => 'motdepasse-long']);
        $this->actingAs($autre, 'plateforme')->get(route('plateforme.cabinets'))->assertOk();

        $autre->update(['actif' => false]);

        // Session déjà ouverte : fermée au prochain chargement.
        $this->actingAs($autre->fresh(), 'plateforme')->get(route('plateforme.cabinets'))
            ->assertRedirect(route('plateforme.login'));
        $this->assertGuest('plateforme');
        $this->get(route('plateforme.login'))->assertOk()->assertSee('Ce compte administrateur est désactivé.');
        $this->get(route('plateforme.login'))->assertOk()->assertDontSee('Ce compte administrateur est désactivé.');
        // Requête Livewire (clic, formulaire) : 403.
        $this->actingAs($autre->fresh(), 'plateforme')->withHeader('X-Livewire', '1')->get(route('plateforme.cabinets'))->assertForbidden();
        auth('plateforme')->logout();

        // Connexion refusée, même message que des identifiants faux.
        $this->post(route('plateforme.login'), ['email' => 'autre@exemple.test', 'password' => 'motdepasse-long'])
            ->assertSessionHasErrors(['email' => 'Identifiants incorrects.']);
        $this->assertGuest('plateforme');

        $autre->update(['actif' => true]);
        $this->post(route('plateforme.login'), ['email' => 'autre@exemple.test', 'password' => 'motdepasse-long'])->assertRedirect(route('plateforme.cabinets'));
        $this->assertNotNull($autre->fresh()->derniere_connexion_at);
    }

    public function test_impossible_de_se_desactiver_ou_de_desactiver_le_dernier()
    {
        Livewire::actingAs($this->admin, 'plateforme')->test(Administrateurs::class)
            ->call('basculer', $this->admin->id)->assertHasErrors(['liste']);
        $this->assertTrue($this->admin->fresh()->actif);

        // Le seul autre actif ne peut pas désactiver le dernier administrateur actif restant.
        $autre = AdminPlateforme::create(['name' => 'Autre', 'email' => 'autre@exemple.test', 'password' => 'motdepasse-long']);
        $this->admin->update(['actif' => false]);
        Livewire::actingAs($autre, 'plateforme')->test(Administrateurs::class)
            ->call('basculer', $this->admin->id)->assertHasNoErrors(); // réactive : autorisé
        $this->assertTrue($this->admin->fresh()->actif);
    }

    public function test_autres_pages_refusees_a_un_compte_desactive()
    {
        $this->admin->update(['actif' => false]);
        $this->actingAs($this->admin, 'plateforme')->get(route('plateforme.plans'))->assertRedirect(route('plateforme.login'));
    }
}
