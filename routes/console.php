<?php

use App\Models\AdminPlateforme;
use App\Services\AbonnementService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;

Artisan::command('abonnements:verifier', function (AbonnementService $service) {
    $n = $service->verifierTous();
    $this->info("{$n} abonnement(s) mis à jour.");
})->purpose('Applique les échéances des abonnements (impayé, suspension)');

// Nécessite la tâche planifiée système : php artisan schedule:run chaque minute.
Schedule::command('abonnements:verifier')->dailyAt('00:10');

Artisan::command('plateforme:admin {email} {--name=Administrateur}', function (string $email) {
    $motDePasse = $this->secret('Mot de passe (12 caractères minimum)');
    $v = Validator::make(['email' => $email, 'password' => $motDePasse], [
        'email' => ['required', 'email'],
        'password' => ['required', 'string', 'min:12'],
    ]);
    if ($v->fails()) {
        $this->error($v->errors()->first());

        return 1;
    }
    AdminPlateforme::updateOrCreate(['email' => $email], ['name' => $this->option('name'), 'password' => $motDePasse]);
    $this->info("Administrateur plateforme {$email} enregistré.");
})->purpose('Crée ou réinitialise un administrateur de la plateforme');
