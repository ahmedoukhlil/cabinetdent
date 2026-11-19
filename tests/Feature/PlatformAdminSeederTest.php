<?php

namespace Tests\Feature;

use App\Models\AdminPlateforme;
use Database\Seeders\PlatformAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_cree_et_reinitialise_un_administrateur_depuis_la_configuration(): void
    {
        config()->set('abonnement.admin_plateforme', [
            'name' => 'Administrateur Sysdental',
            'email' => 'admin@sysdental.test',
            'password' => 'mot-de-passe-initial',
        ]);

        $this->seed(PlatformAdminSeeder::class);

        $admin = AdminPlateforme::where('email', 'admin@sysdental.test')->firstOrFail();
        $this->assertSame('Administrateur Sysdental', $admin->name);
        $this->assertTrue(Hash::check('mot-de-passe-initial', $admin->password));

        config()->set('abonnement.admin_plateforme.password', 'nouveau-mot-de-passe');
        $this->seed(PlatformAdminSeeder::class);

        $this->assertSame(1, AdminPlateforme::where('email', 'admin@sysdental.test')->count());
        $this->assertTrue(Hash::check(
            'nouveau-mot-de-passe',
            AdminPlateforme::where('email', 'admin@sysdental.test')->value('password'),
        ));
    }
}
