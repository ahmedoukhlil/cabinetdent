<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\ReglementFactureController;
use App\Http\Livewire\StatistiquesManager;
use App\Http\Controllers\CaisseController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentification
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Routes protégées par l'authentification
Route::middleware(['auth'])->group(function () {

    // ─── Accueil ────────────────────────────────────────────────────────────
    Route::get('/accueil-patient', function () {
        return view('accueil-patient-page');
    })->name('accueil.patient');

    // ─── API interne ────────────────────────────────────────────────────────
    Route::get('/api/salle-soins/count', function () {
        $user = auth()->user();
        if (!$user->hasPermission('salle-soins.view')) {
            return response()->json(['count' => 0]);
        }
        // Compter uniquement les soins en attente ou en cours (pas terminés)
        $count = \App\Models\Ordonnanceref::whereDate('dtPrescript', now()->toDateString())
            ->where('fkidCabinet', $user->fkidcabinet)
            ->whereIn('statutSoin', ['en_attente', 'en_cours'])
            ->whereHas('ordonnances', fn($q) => $q->where('estInterne', true))
            ->count();
        return response()->json(['count' => $count]);
    })->name('api.salle-soins.count');

    Route::get('/api/stock/alerts', function () {
        $user = auth()->user();
        if (!$user->hasPermission('stock.view')) {
            return response()->json(['faible' => 0, 'epuise' => 0]);
        }
        $faible = \App\Models\StockMedicament::where('fkidCabinet', $user->fkidcabinet)
            ->where('Masquer', 0)
            ->whereColumn('quantiteStock', '<=', 'quantiteMin')
            ->where('quantiteStock', '>', 0)
            ->whereHas('medicament', fn($q) => $q->where('fkidtype', 1))
            ->count();
        $epuise = \App\Models\StockMedicament::where('fkidCabinet', $user->fkidcabinet)
            ->where('Masquer', 0)
            ->where('quantiteStock', '<=', 0)
            ->whereHas('medicament', fn($q) => $q->where('fkidtype', 1))
            ->count();
        return response()->json(['faible' => $faible, 'epuise' => $epuise]);
    })->name('api.stock.alerts');

    Route::get('/api/salle-attente/count', function () {
        $user = auth()->user();
        if (!$user->hasPermission('salle-attente.view')) {
            return response()->json(['count' => 0]);
        }
        $query = \App\Models\Rendezvou::whereDate('dtPrevuRDV', now()->toDateString())
            ->whereNotIn('rdvConfirmer', ['Annulé', 'annulé', 'Terminé', 'terminé'])
            ->where('fkidcabinet', $user->fkidcabinet);

        if ($user->isDocteur() && !$user->isDocteurProprietaire()) {
            $query->where('fkidMedecin', $user->fkidmedecin);
        }

        return response()->json(['count' => $query->count()]);
    })->name('api.salle-attente.count');

    // ─── Rendez-vous ────────────────────────────────────────────────────────
    Route::middleware(['permission:rendez-vous.view'])->group(function () {
        Route::get('/rendez-vous', function () {
            return view('rendez-vous.index');
        })->name('rendez-vous');

        Route::get('/rendez-vous/print/{id}', function ($id) {
            $rendezVous = \App\Models\Rendezvou::with(['patient', 'medecin', 'cabinet'])->findOrFail($id);
            return view('rendez-vous.print', compact('rendezVous'));
        })->name('rendez-vous.print');
    });

    Route::middleware(['permission:rendez-vous.create'])->group(function () {
        Route::get('/rendez-vous/create', function () {
            return view('rendez-vous.create');
        })->name('rendez-vous.create');
    });

    // ─── Patients ────────────────────────────────────────────────────────────
    Route::middleware(['permission:patient.view'])->group(function () {
        Route::get('/patients', function () {
            return view('patients.index');
        })->name('patients.index');
    });

    Route::middleware(['permission:patient.create'])->group(function () {
        Route::get('/patients/create', function () {
            return view('patients.create');
        })->name('patients.create');
    });

    // ─── Consultations ───────────────────────────────────────────────────────
    Route::middleware(['permission:consultation.create'])->group(function () {
        Route::get('/consultations/create', [ConsultationController::class, 'create'])->name('consultations.create');
        Route::post('/consultations', [ConsultationController::class, 'store'])->name('consultations.store');
    });

    Route::middleware(['permission:consultation.view'])->group(function () {
        Route::get('/consultations/search-patient', [ConsultationController::class, 'searchPatient'])->name('consultations.search-patient');
        Route::get('/consultations/{facture}/receipt', [ConsultationController::class, 'showReceipt'])->name('consultations.receipt');
        Route::get('/consultations/{id}', [ConsultationController::class, 'show'])->name('consultations.show');
        Route::get('/facture-patient/{facture}', [ConsultationController::class, 'showFacturePatient'])->name('consultations.facture-patient');
        Route::get('/ordonnance/{consultation}', [ConsultationController::class, 'showOrdonnance'])->name('consultations.ordonnance');
    });

    // ─── Ordonnances (impression) ────────────────────────────────────────────
    Route::middleware(['permission:ordonnance.view'])->prefix('ordonnances')->name('ordonnance.')->group(function () {
        Route::get('/blank', [\App\Http\Controllers\OrdonnanceController::class, 'blank'])->name('blank');
        Route::get('/{id}/print', [\App\Http\Controllers\OrdonnanceController::class, 'print'])->name('print');
        Route::get('/{id}/download', [\App\Http\Controllers\OrdonnanceController::class, 'download'])->name('download');
    });

    // ─── Dossier médical ────────────────────────────────────────────────────
    Route::middleware(['permission:dossier.view'])->prefix('dossier-medical')->name('dossier-medical.')->group(function () {
        Route::get('/{factureId}/print', [\App\Http\Controllers\DossierMedicalController::class, 'print'])->name('print');
        Route::get('/{factureId}/download', [\App\Http\Controllers\DossierMedicalController::class, 'download'])->name('download');
        Route::get('/patient/{patientId}/print', [\App\Http\Controllers\DossierMedicalController::class, 'printPatient'])->name('patient.print');
    });

    // ─── Caisse / Finances ──────────────────────────────────────────────────
    Route::middleware(['permission:caisse-operations.view'])->group(function () {
        Route::get('/caisse-operations', function () {
            return view('caisse-operations');
        })->name('caisse-operations');
    });

    Route::middleware(['permission:finances.view'])->group(function () {
        Route::get('/caisse/etat-journalier/{date?}', [CaisseController::class, 'printEtatCaisse'])->name('caisse.etat-journalier');
    });

    // ─── Règlement facture ───────────────────────────────────────────────────
    Route::middleware(['permission:facture.view|facture.view.own'])->group(function () {
        Route::get('/reglement-facture', function () {
            return view('reglement-facture');
        })->name('reglement-facture');
        Route::get('/reglement-facture/recu/{operation}', [ReglementFactureController::class, 'showReceipt'])->name('reglement-facture.receipt');
        Route::get('/historique-paiement/print/{patient}', [PaiementController::class, 'printHistorique'])->name('paiement.print-historique');
    });

    // ─── Statistiques ───────────────────────────────────────────────────────
    Route::middleware(['permission:statistiques.view'])->group(function () {
        Route::get('/statistiques', StatistiquesManager::class)->name('statistiques');
    });

    // ─── Utilisateurs ───────────────────────────────────────────────────────
    Route::middleware(['permission:user.view'])->group(function () {
        Route::get('/users', function () {
            return view('users.index');
        })->name('users.index');

    });

    // ─── Routes de test/debug (accès libre aux authentifiés) ────────────────
    Route::get('/test-api',       fn() => view('test-api'))->name('test.api');
    Route::get('/modal-demo',     fn() => view('modal-demo'))->name('modal.demo');
    Route::get('/animation-test', fn() => view('animation-test'))->name('animation.test');
    Route::get('/test-modals',    fn() => view('test-modals'))->name('test.modals');
});

// Routes publiques pour l'interface patient (accessible via QR code)
Route::prefix('patient')->group(function () {
    Route::get('/rendez-vous/{token}', [App\Http\Controllers\PatientInterfaceController::class, 'showRendezVous'])->name('patient.rendez-vous');
    Route::get('/consultation/{token}', [App\Http\Controllers\PatientInterfaceController::class, 'showConsultation'])->name('patient.consultation');
});

// ─── Espace patient (compte permanent, PWA) ────────────────────────────────
Route::prefix('espace-patient')->name('patient.')->group(function () {
    Route::middleware('guest:patient')->group(function () {
        Route::get('/connexion', [App\Http\Controllers\Auth\PatientAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/connexion/telephone', [App\Http\Controllers\Auth\PatientAuthController::class, 'rechercherParTelephone'])->name('login.telephone');
        Route::post('/connexion/choisir', [App\Http\Controllers\Auth\PatientAuthController::class, 'selectionnerPatient'])->name('login.choisir');
        Route::post('/connexion/mot-de-passe', [App\Http\Controllers\Auth\PatientAuthController::class, 'verifierMotDePasse'])->name('login.mot-de-passe');
    });

    Route::middleware('auth:patient')->group(function () {
        Route::get('/definir-mot-de-passe', [App\Http\Controllers\Auth\PatientAuthController::class, 'showDefinirMotDePasse'])->name('definir-mot-de-passe');
        Route::post('/definir-mot-de-passe', [App\Http\Controllers\Auth\PatientAuthController::class, 'definirMotDePasse'])->name('definir-mot-de-passe.save');
        Route::post('/deconnexion', [App\Http\Controllers\Auth\PatientAuthController::class, 'logout'])->name('logout');

        Route::get('/', function () {
            return view('patient-auth.dashboard');
        })->name('dashboard');
    });
});
