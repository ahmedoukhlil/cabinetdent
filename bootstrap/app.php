<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['compte.actif' => \App\Http\Middleware\EnsureCompteActif::class, 'plateforme.actif' => \App\Http\Middleware\EnsureAdminPlateformeActif::class]);
        $middleware->redirectGuestsTo(fn (\Illuminate\Http\Request $r) => $r->is('plateforme', 'plateforme/*') ? route('plateforme.login') : route('login'));
        $middleware->redirectUsersTo(fn (\Illuminate\Http\Request $r) => $r->is('plateforme', 'plateforme/*') ? route('plateforme.cabinets') : route('accueil'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
