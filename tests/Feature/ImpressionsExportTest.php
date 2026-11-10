<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Cabinet;
use App\Models\Patient;
use App\Models\User;
use App\Services\CabinetExportService;
use App\Services\DossierDentaireService;
use App\Services\FichierPatientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ImpressionsExportTest extends TestCase
{
    use RefreshDatabase;

    private User $proprietaire;

    private User $secretaire;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $cabinet = Cabinet::factory()->create(['nom' => 'Cabinet Test']);
        $this->proprietaire = User::factory()->role(Role::Proprietaire)->create(['cabinet_id' => $cabinet->id]);
        $this->secretaire = User::factory()->role(Role::Secretaire)->create(['cabinet_id' => $cabinet->id]);
        $this->patient = Patient::create(['cabinet_id' => $cabinet->id, 'nom' => 'Kane', 'prenom' => 'Awa']);
        $this->actingAs($this->proprietaire);

        $dossier = app(DossierDentaireService::class);
        $dossier->enregistrerDents($this->proprietaire, $this->patient, [36], [
            'presence' => 'presente', 'restauration' => 'aucune', 'endodontie' => 'traitee',
            'faces' => ['O' => 'carie', 'M' => 'sain', 'D' => 'sain', 'V' => 'sain', 'L' => 'sain'],
            'constatations' => ['pulpite'], 'notes' => 'Sensible au froid',
        ]);
        $dossier->ajouterLigne($this->proprietaire, $this->patient, ['libelle' => 'Composite', 'quantite' => 1, 'prix_unitaire' => 3500, 'dents' => [36]]);
        app(FichierPatientService::class)->ajouter($this->proprietaire, $this->patient, UploadedFile::fake()->image('pano.jpg'), ['categorie' => 'radio', 'titre' => 'Panoramique']);
    }

    public function test_impression_du_dossier_dentaire()
    {
        $this->get(route('dossier.imprimer', $this->patient))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Dossier dentaire')
            ->assertSee('Carie O')
            ->assertSee('Pulpite')
            ->assertSee('Sensible au froid')
            ->assertSee('Composite')
            ->assertSee('3 500 MRU')
            ->assertSee('Panoramique');

        $this->actingAs($this->secretaire)->get(route('dossier.imprimer', $this->patient))->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Proprietaire)->create())->get(route('dossier.imprimer', $this->patient))->assertNotFound();
    }

    public function test_impression_du_devis()
    {
        $service = app(\App\Services\FacturationService::class);
        $devis = $service->brouillonPour($this->proprietaire, $this->patient);
        $this->actingAs($this->proprietaire)->get(route('factures.imprimer', $devis))->assertNotFound();

        $service->ajouterLigne($this->proprietaire, $devis, ['libelle' => 'Couronne', 'quantite' => 1, 'prix_unitaire' => 20000]);
        $this->get(route('factures.imprimer', $devis))->assertOk()
            ->assertSee('Devis N° D-'.$devis->id)
            ->assertSee('Valable 30 jours')
            ->assertSee('Bon pour accord')
            ->assertSee('20 000 MRU')
            ->assertDontSee('Reste à payer');
    }

    public function test_impression_des_statistiques()
    {
        $this->get(route('statistiques.imprimer', ['du' => 'nimporte', 'au' => today()->format('Y-m-d')]))->assertOk()->assertSee('Statistiques par praticien');
        $this->actingAs($this->secretaire)->get(route('statistiques.imprimer'))->assertForbidden();
    }

    public function test_export_zip_limite_au_cabinet_sans_mots_de_passe()
    {
        // Un autre cabinet avec ses données ne doit pas apparaître.
        $autre = User::factory()->role(Role::Proprietaire)->create();
        Patient::create(['cabinet_id' => $autre->cabinet_id, 'nom' => 'Etranger']);

        $chemin = app(CabinetExportService::class)->exporter($this->proprietaire);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($chemin));

        $patients = json_decode($zip->getFromName('donnees/patients.json'), true);
        $this->assertSame(['Kane'], array_column($patients, 'nom'));

        $users = json_decode($zip->getFromName('donnees/users.json'), true);
        $this->assertCount(2, $users);
        $this->assertArrayNotHasKey('password', $users[0]);
        $this->assertStringNotContainsString('"password"', $zip->getFromName('donnees/users.json'));

        $this->assertCount(1, json_decode($zip->getFromName('donnees/plan_traitement_lignes.json'), true));
        $this->assertSame('Cabinet Test', json_decode($zip->getFromName('donnees/cabinet.json'), true)['nom']);

        $fichier = json_decode($zip->getFromName('donnees/fichiers_patient.json'), true)[0];
        $this->assertNotFalse($zip->locateName('fichiers/'.$fichier['chemin']));
        $this->assertStringContainsString('fichiers physiques : 1', $zip->getFromName('LISEZMOI.txt'));
        $zip->close();
        @unlink($chemin);

        $this->get(route('cabinet.export'))->assertOk()->assertDownload();
        $this->actingAs($this->secretaire)->get(route('cabinet.export'))->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Dentiste)->create(['cabinet_id' => $this->patient->cabinet_id]))->get(route('cabinet.export'))->assertForbidden();
    }
}
