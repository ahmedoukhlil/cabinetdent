<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\ModesPaiement;
use App\Models\Acte;
use App\Models\Cabinet;
use App\Models\Facture;
use App\Models\ModePaiement;
use App\Models\Patient;
use App\Models\User;
use App\Services\FacturationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ModesPaiementTest extends TestCase
{
    use RefreshDatabase;

    private User $proprietaire;

    private Cabinet $cabinet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cabinet = Cabinet::factory()->create();
        $this->proprietaire = User::factory()->role(Role::Proprietaire)->create(['cabinet_id' => $this->cabinet->id]);
        $this->actingAs($this->proprietaire);
    }

    private function factureValidee(): Facture
    {
        $service = app(FacturationService::class);
        $patient = Patient::create(['cabinet_id' => $this->cabinet->id, 'nom' => 'Ba']);
        $f = $service->brouillonPour($this->proprietaire, $patient);
        $service->ajouterLigne($this->proprietaire, $f, ['libelle' => 'Soin', 'quantite' => 1, 'prix_unitaire' => 5000]);

        return $service->valider($this->proprietaire, $f);
    }

    public function test_nouveau_cabinet_recoit_les_modes_par_defaut()
    {
        $this->assertSame(array_values(ModePaiement::DEFAUTS), array_values(ModePaiement::actifs($this->cabinet->id)));
        $this->assertSame('especes', ModePaiement::parDefaut($this->cabinet->id));
    }

    public function test_ajouter_modifier_et_ordonner()
    {
        Livewire::test(ModesPaiement::class)
            ->set('form.libelle', 'Bankily')
            ->call('enregistrer')
            ->assertHasNoErrors()
            ->set('form.libelle', 'bankily')
            ->call('enregistrer')
            ->assertHasErrors('form.libelle');

        $mode = ModePaiement::where('libelle', 'Bankily')->firstOrFail();
        $this->assertSame('bankily', $mode->code);
        $this->assertSame('Bankily', array_values(ModePaiement::actifs())[5]);

        Livewire::test(ModesPaiement::class)
            ->call('modifier', $mode->id)
            ->set('form.libelle', 'Bankily (BMCI)')
            ->call('enregistrer')
            ->call('deplacer', $mode->id, -1);

        $mode->refresh();
        $this->assertSame('bankily', $mode->code, 'Le code ne change pas avec le libellé.');
        $this->assertSame('Bankily (BMCI)', array_values(ModePaiement::actifs())[4]);
    }

    public function test_mode_utilise_non_supprimable_et_mode_desactive_refuse()
    {
        $especes = ModePaiement::where('code', 'especes')->firstOrFail();
        $f = $this->factureValidee();
        app(FacturationService::class)->enregistrerPaiement($this->proprietaire, $f, ['payeur' => 'patient', 'type' => 'encaissement', 'mode' => 'especes', 'montant' => 1000]);

        Livewire::test(ModesPaiement::class)->call('supprimer', $especes->id);
        $this->assertModelExists($especes);

        Livewire::test(ModesPaiement::class)->call('basculer', $especes->id);
        $this->assertFalse($especes->fresh()->actif);
        $this->assertSame('Espèces', ModePaiement::libelles()['especes'], 'Le libellé reste pour l\'historique.');

        $this->expectException(ValidationException::class);
        app(FacturationService::class)->enregistrerPaiement($this->proprietaire, $f->fresh(), ['payeur' => 'patient', 'type' => 'encaissement', 'mode' => 'especes', 'montant' => 1000]);
    }

    public function test_mode_inutilise_supprimable_et_dernier_actif_protege()
    {
        $cheque = ModePaiement::where('code', 'cheque')->firstOrFail();
        Livewire::test(ModesPaiement::class)->call('supprimer', $cheque->id);
        $this->assertModelMissing($cheque);

        ModePaiement::where('code', '!=', 'especes')->update(['actif' => false]);
        $especes = ModePaiement::where('code', 'especes')->firstOrFail();
        Livewire::test(ModesPaiement::class)->call('basculer', $especes->id);
        $this->assertTrue($especes->fresh()->actif);
    }

    public function test_isolation_entre_cabinets_et_droits()
    {
        $autre = Cabinet::factory()->create();
        ModePaiement::withoutGlobalScopes()->where('cabinet_id', $autre->id)->where('code', 'mobile')->update(['libelle' => 'Masrvi']);
        $this->assertSame('Paiement mobile', ModePaiement::libelles()['mobile']);

        $secretaire = User::factory()->role(Role::Secretaire)->create(['cabinet_id' => $this->cabinet->id]);
        $this->actingAs($secretaire)->get(route('modes-paiement'))->assertForbidden();
        $this->actingAs($this->proprietaire)->get(route('modes-paiement'))->assertOk()->assertSee('Modes de paiement');
    }
}
