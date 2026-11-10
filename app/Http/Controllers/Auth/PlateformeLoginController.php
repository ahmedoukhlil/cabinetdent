<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PlateformeLoginController extends Controller
{
    public function create()
    {
        return view('plateforme.login');
    }

    public function store(Request $request)
    {
        $identifiants = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::guard('plateforme')->attempt($identifiants)) {
            throw ValidationException::withMessages(['email' => 'Identifiants incorrects.']);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('plateforme.cabinets'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('plateforme')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('plateforme.login');
    }
}
