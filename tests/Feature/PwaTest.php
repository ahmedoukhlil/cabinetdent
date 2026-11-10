<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_valide_et_icones_presentes()
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/', $manifest['start_url']);
        foreach ($manifest['icons'] as $icone) {
            $this->assertFileExists(public_path(ltrim($icone['src'], '/')));
            [$largeur] = getimagesize(public_path(ltrim($icone['src'], '/')));
            $this->assertSame((int) explode('x', $icone['sizes'])[0], $largeur);
        }
        $this->assertFileExists(public_path('offline.html'));
    }

    public function test_le_service_worker_ne_met_en_cache_que_la_coque_statique()
    {
        $sw = file_get_contents(public_path('sw.js'));

        // Les navigations passent par le réseau, avec la page hors ligne en secours.
        $this->assertStringContainsString("fetch(requete).catch(() => caches.match('/offline.html'))", $sw);
        // Seuls /build/ et /icons/ sont mis en cache à l'exécution.
        $this->assertSame(1, substr_count($sw, 'cache.put('));
        $this->assertStringContainsString("url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')", $sw);
        $this->assertStringNotContainsString('/fichiers', $sw);
        $this->assertStringNotContainsString('/livewire', $sw);
    }

    public function test_pages_declarent_le_manifest()
    {
        $this->get(route('login'))->assertOk()->assertSee('rel="manifest"', false);

        $user = User::factory()->role(Role::Secretaire)->create();
        $this->actingAs($user)->get(route('accueil'))->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('sysdental-mise-a-jour', false);
    }
}
