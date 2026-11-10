<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PlateformeLoginController;
use App\Http\Controllers\ImpressionController;
use App\Livewire\Accueil;
use App\Livewire\Agenda;
use App\Livewire\Caisse;
use App\Livewire\DossierDentaire;
use App\Livewire\Equipe;
use App\Livewire\Factures;
use App\Livewire\Patients;
use App\Livewire\SalleAttente;
use App\Livewire\Statistiques;
use App\Livewire\Stock;
use App\Livewire\Tarifs;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::redirect('/login', '/connexion');
    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware(['auth', 'compte.actif'])->group(function () {
    Route::get('/', Accueil::class)->name('accueil');
    Route::get('/patients', Patients::class)->middleware('can:patient.view')->name('patients');
    Route::get('/patients/{patient}/dossier', DossierDentaire::class)->middleware('can:dossier.view')->name('dossier');
    Route::get('/patients/{patient}/dossier/imprimer', [ImpressionController::class, 'dossier'])->middleware('can:dossier.view')->name('dossier.imprimer');
    Route::get('/fichiers/{fichier}', [ImpressionController::class, 'fichier'])->middleware('can:dossier.view')->name('fichiers.voir');
    Route::get('/agenda', Agenda::class)->middleware('can:rendez-vous.view')->name('agenda');
    Route::get('/salle-attente', SalleAttente::class)->middleware('can:salle-attente.view')->name('salle-attente');

    Route::middleware('can:facture.view')->group(function () {
        Route::get('/factures', Factures::class)->name('factures');
        Route::get('/factures/{facture}/imprimer', [ImpressionController::class, 'facture'])->name('factures.imprimer');
        Route::get('/paiements/{paiement}/recu', [ImpressionController::class, 'recu'])->name('paiements.recu');
    });
    Route::middleware('can:caisse.view')->group(function () {
        Route::get('/caisse', Caisse::class)->name('caisse');
        Route::get('/caisse/imprimer', [ImpressionController::class, 'caisse'])->name('caisse.imprimer');
    });
    Route::get('/stock', Stock::class)->middleware('can:stock.manage')->name('stock');
    Route::get('/statistiques', Statistiques::class)->middleware('can:statistiques.view')->name('statistiques');
    Route::get('/statistiques/imprimer', [ImpressionController::class, 'statistiques'])->middleware('can:statistiques.view')->name('statistiques.imprimer');
    Route::get('/parametres/tarifs', Tarifs::class)->middleware('can:tarifs.manage')->name('tarifs');
    Route::get('/parametres/tarifs/modele-actes', function (\App\Services\ActeImportService $service) {
        return response()->download($service->modele(), 'sysdental-actes-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    })->middleware('can:tarifs.manage')->name('tarifs.modele');
    Route::get('/parametres/equipe', Equipe::class)->middleware('can:user.manage')->name('equipe');
    Route::get('/parametres/export', [ImpressionController::class, 'export'])->middleware(['can:cabinet.manage', 'throttle:5,1'])->name('cabinet.export');

    Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');
});

// Administration de la plateforme (garde séparée « plateforme »).
Route::prefix('plateforme')->name('plateforme.')->group(function () {
    Route::middleware('guest:plateforme')->group(function () {
        Route::get('/connexion', [PlateformeLoginController::class, 'create'])->name('login');
        Route::post('/connexion', [PlateformeLoginController::class, 'store'])->middleware('throttle:5,1');
    });
    Route::middleware('auth:plateforme')->group(function () {
        Route::get('/', App\Livewire\Plateforme\Cabinets::class)->name('cabinets');
        Route::get('/formules', App\Livewire\Plateforme\Plans::class)->name('plans');
        Route::post('/deconnexion', [PlateformeLoginController::class, 'destroy'])->name('logout');
    });
});
