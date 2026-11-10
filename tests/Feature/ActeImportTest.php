<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Tarifs;
use App\Models\Acte;
use App\Models\ActeTarif;
use App\Models\Assureur;
use App\Models\Cabinet;
use App\Models\Patient;
use App\Models\User;
use App\Services\ActeImportService;
use App\Services\FacturationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ActeImportTest extends TestCase
{
    use RefreshDatabase;

    private User $proprietaire;

    private Assureur $cnam;

    protected function setUp(): void
    {
        parent::setUp();
        $cabinet = Cabinet::factory()->create();
        $this->proprietaire = User::factory()->role(Role::Proprietaire)->create(['cabinet_id' => $cabinet->id]);
        $this->actingAs($this->proprietaire);
        $this->cnam = Assureur::create(['nom' => 'CNAM', 'taux_pec' => 0.8]);
    }

    private function xlsx(array $lignes): string
    {
        $chemin = tempnam(sys_get_temp_dir(), 't').'.xlsx';
        $w = new Writer();
        $w->openToFile($chemin);
        foreach ($lignes as $l) {
            $w->addRow(Row::fromValues($l));
        }
        $w->close();

        return $chemin;
    }

    public function test_import_cree_met_a_jour_et_gere_les_prix_par_assureur()
    {
        $existant = Acte::create(['code' => 'D01', 'libelle' => 'Consultation', 'prix' => 800]);
        ActeTarif::create(['acte_id' => $existant->id, 'assureur_id' => $this->cnam->id, 'prix' => 500]);

        $r = app(ActeImportService::class)->importer($this->proprietaire, $this->xlsx([
            ['Code', 'Libellé', 'Prix sans assurance', 'cnam', 'Mutuelle X'],
            ['d01', 'Consultation', '1 000', '', '300'],
            ['', 'Détartrage', '1500,50', 1200, ''],
            [null, null, null, null, null],
        ]));

        $this->assertSame(['crees' => 1, 'modifies' => 1, 'tarifs' => 1], array_slice($r, 0, 3));
        $this->assertCount(1, $r['avertissements'], 'Colonne Mutuelle X inconnue.');
        $this->assertSame(1000.0, $existant->fresh()->prix);
        $this->assertSame(1000.0, $existant->fresh()->prixPour($this->cnam->id), 'Case vide : le tarif CNAM est retiré.');
        $detartrage = Acte::where('libelle', 'Détartrage')->first();
        $this->assertSame(1500.5, $detartrage->prix);
        $this->assertSame(1200.0, $detartrage->prixPour($this->cnam->id));
    }

    public function test_import_tout_ou_rien_avec_erreurs_par_ligne()
    {
        try {
            app(ActeImportService::class)->importer($this->proprietaire, $this->xlsx([
                ['Code', 'Libellé', 'Prix sans assurance', 'CNAM'],
                ['A1', 'Bon acte', 100, ''],
                ['A2', '', 100, ''],
                ['A3', 'Prix faux', 'abc', '-5'],
                ['A1', 'Doublon', 100, ''],
            ]));
            $this->fail('Erreurs attendues.');
        } catch (ValidationException $e) {
            $messages = implode(' | ', $e->errors()['fichier']);
            $this->assertStringContainsString('Ligne 3 : libellé manquant', $messages);
            $this->assertStringContainsString('Ligne 4 : prix sans assurance', $messages);
            $this->assertStringContainsString('Ligne 4 : prix « -5 »', $messages);
            $this->assertStringContainsString('Ligne 5 : acte en double', $messages);
        }
        $this->assertSame(0, Acte::count());
    }

    public function test_modele_contient_le_catalogue_et_les_assureurs()
    {
        $a = Acte::create(['code' => 'D01', 'libelle' => 'Consultation', 'prix' => 800]);
        ActeTarif::create(['acte_id' => $a->id, 'assureur_id' => $this->cnam->id, 'prix' => 500]);

        $reponse = $this->get(route('tarifs.modele'))->assertOk();
        $reader = new Reader();
        $reader->open($reponse->baseResponse->getFile()->getPathname());
        $lignes = [];
        foreach ($reader->getSheetIterator() as $f) {
            foreach ($f->getRowIterator() as $row) {
                $lignes[] = $row->toArray();
            }
            break;
        }
        $reader->close();
        $this->assertSame(['Code', 'Libellé', 'Type', 'Prix sans assurance', 'CNAM'], $lignes[0]);
        $this->assertEquals(['D01', 'Consultation', 'Soin', 800, 500], $lignes[1]);

        $secretaire = User::factory()->role(Role::Secretaire)->create(['cabinet_id' => $this->proprietaire->cabinet_id]);
        $this->actingAs($secretaire)->get(route('tarifs.modele'))->assertForbidden();
    }

    public function test_prix_selon_assurance_du_patient_et_ecran()
    {
        $a = Acte::create(['libelle' => 'Détartrage', 'prix' => 1500]);
        Livewire::test(Tarifs::class)
            ->call('modifierActe', $a->id)
            ->set('acte.tarifs.'.$this->cnam->id, '1000')
            ->call('enregistrerActe')->assertHasNoErrors()
            ->assertSee('1 000');

        $patient = Patient::create(['nom' => 'Assuré', 'assureur_id' => $this->cnam->id]);
        $service = app(FacturationService::class);
        $facture = $service->brouillonPour($this->proprietaire, $patient);
        $this->assertSame(1000.0, $service->ajouterLigne($this->proprietaire, $facture, ['acte_id' => $a->id, 'quantite' => 1])->prix_unitaire);

        $sans = Patient::create(['nom' => 'Sans']);
        $f2 = $service->brouillonPour($this->proprietaire, $sans);
        $this->assertSame(1500.0, $service->ajouterLigne($this->proprietaire, $f2, ['acte_id' => $a->id, 'quantite' => 1])->prix_unitaire);

        Livewire::test(Tarifs::class)->call('ouvrirImport')
            ->set('fichier', UploadedFile::fake()->createWithContent('a.xlsx', file_get_contents($this->xlsx([['Libellé', 'Prix'], ['Couronne', 20000]]))))
            ->call('importer')->assertHasNoErrors()->assertSee('1 acte(s) créé(s)');
    }
}
