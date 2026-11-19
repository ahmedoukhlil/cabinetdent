<?php

namespace Database\Seeders;

use App\Models\AdminPlateforme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = config('abonnement.admin_plateforme');

        $validator = Validator::make($admin, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:12'],
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException(
                'Configuration administrateur invalide : '.$validator->errors()->first()
            );
        }

        AdminPlateforme::updateOrCreate(
            ['email' => $admin['email']],
            ['name' => $admin['name'], 'password' => $admin['password']],
        );

        $this->command?->info("Administrateur plateforme {$admin['email']} enregistré.");
    }
}
