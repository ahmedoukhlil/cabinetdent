<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\FichiersPatient;
use App\Models\Cabinet;
use App\Models\FichierPatient;
use App\Models\Patient;
use App\Models\User;
use App\Services\FichierPatientService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class FichierPatientTest extends TestCase
{
    use RefreshDatabase;

    private User $dentiste;

    private User $secretaire;

    private Patient $patient;

    private FichierPatientService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $cabinet = Cabinet::factory()->create();
        $this->dentiste = User::factory()->role(Role::Dentiste)->create(['cabinet_id' => $cabinet->id]);
        $this->secretaire = User::factory()->role(Role::Secretaire)->create(['cabinet_id' => $cabinet->id]);
        $this->patient = Patient::create(['cabinet_id' => $cabinet->id, 'nom' => 'Diallo']);
        $this->service = app(FichierPatientService::class);
        $this->actingAs($this->dentiste);
    }

    private function radio(array $donnees = []): FichierPatient
    {
        return $this->service->ajouter($this->dentiste, $this->patient, UploadedFile::fake()->image('pano.jpg', 800, 600), [
            'categorie' => 'radio', ...$donnees,
        ]);
    }

    public function test_ajout_stocke_en_prive_avec_metadonnees()
    {
        $f = $this->radio(['dents' => [36, 36, 37]]);

        Storage::disk('local')->assertExists($f->chemin);
        $this->assertStringStartsWith("cabinets/{$this->patient->cabinet_id}/patients/{$this->patient->id}/", $f->chemin);
        $this->assertSame('Radiographie', $f->titre);
        $this->assertSame([36, 37], $f->dents);
        $this->assertTrue($f->estImage());
    }

    public function test_formats_dents_et_date_controles()
    {
        foreach ([
            [UploadedFile::fake()->create('script.php', 1, 'text/x-php'), ['categorie' => 'radio']],
            [UploadedFile::fake()->create('gros.pdf', 11000, 'application/pdf'), ['categorie' => 'radio']],
            [UploadedFile::fake()->image('a.png'), ['categorie' => 'radio', 'dents' => [99]]],
            [UploadedFile::fake()->image('a.png'), ['categorie' => 'radio', 'date_examen' => now()->addDay()->format('Y-m-d')]],
            [UploadedFile::fake()->image('a.png'), ['categorie' => 'inconnue']],
        ] as [$fichier, $donnees]) {
            try {
                $this->service->ajouter($this->dentiste, $this->patient, $fichier, $donnees);
                $this->fail('Doit être refusé : '.$fichier->getClientOriginalName());
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame(0, FichierPatient::count());
    }

    public function test_secretaire_sans_acces_et_suppression_par_l_auteur()
    {
        $f = $this->radio();

        $this->actingAs($this->secretaire)->get(route('fichiers.voir', $f))->assertForbidden();
        try {
            $this->service->ajouter($this->secretaire, $this->patient, UploadedFile::fake()->image('x.jpg'), ['categorie' => 'photo']);
            $this->fail();
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        $autre = User::factory()->role(Role::Dentiste)->create(['cabinet_id' => $this->patient->cabinet_id]);
        try {
            $this->service->supprimer($autre, $f);
            $this->fail();
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        $this->service->supprimer($this->dentiste, $f);
        $this->assertSoftDeleted($f);
        Storage::disk('local')->assertExists($f->chemin);
        $this->actingAs($this->dentiste)->get(route('fichiers.voir', $f->id))->assertNotFound();
    }

    public function test_acces_authentifie_isole_et_ecran()
    {
        $f = $this->radio();

        $this->get(route('fichiers.voir', $f))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $etranger = User::factory()->role(Role::Proprietaire)->create();
        $this->actingAs($etranger)->get(route('fichiers.voir', $f))->assertNotFound();
        auth()->logout();
        $this->get(route('fichiers.voir', $f))->assertRedirect(route('login'));

        $this->actingAs($this->dentiste);
        Livewire::test(FichiersPatient::class, ['patient' => $this->patient, 'selection' => [46]])
            ->set('fichier', UploadedFile::fake()->create('cr.pdf', 50, 'application/pdf'))
            ->set('categorie', 'compte_rendu')
            ->set('titre', 'Compte rendu chirurgie')
            ->call('ajouter')
            ->assertHasNoErrors()
            ->assertSee('Compte rendu chirurgie')
            ->set('filtrerSelection', true)
            ->assertSee('Compte rendu chirurgie')
            ->assertDontSee('Radiographie</p>', false);

        $this->assertSame([46], FichierPatient::latest('id')->first()->dents);
        $this->get(route('dossier', $this->patient))->assertOk()->assertSee('Radios et documents');
    }
}
