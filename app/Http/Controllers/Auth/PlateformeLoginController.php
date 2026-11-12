<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PlateformeLoginController extends Controller
{
    public function create(Request $request)
    {
        $vue = view('plateforme.login');

        // Posé par EnsureAdminPlateformeActif quand une session ouverte est fermée.
        return $request->session()->pull('plateforme_compte_desactive')
            ? $vue->withErrors(['email' => 'Ce compte administrateur est désactivé.'])
            : $vue;
    }

    public function store(Request $request)
    {
        $identifiants = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Un compte désactivé est refusé avec le même message, sans révéler son existence.
        if (!Auth::guard('plateforme')->attempt($identifiants + ['actif' => true])) {
            throw ValidationException::withMessages(['email' => 'Identifiants incorrects.']);
        }
        $request->session()->regenerate();
        Auth::guard('plateforme')->user()->forceFill(['derniere_connexion_at' => now()])->save();

        return redirect()->intended(route('plateforme.cabinets'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('plateforme')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('plateforme.login');
    }
}
