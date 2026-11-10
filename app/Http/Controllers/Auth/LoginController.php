<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $identifiants = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($identifiants + ['actif' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Identifiants incorrects ou compte désactivé.']);
        }

        $cabinet = Auth::user()->cabinet;
        if (!$cabinet || !\App\Services\AbonnementService::accesAutorise($cabinet)) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => "L'accès de ce cabinet est suspendu. Contactez Sysdental pour régulariser l'abonnement."]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('accueil'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
