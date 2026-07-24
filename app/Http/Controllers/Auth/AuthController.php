<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Afficher le formulaire de connexion
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Traiter la tentative de connexion
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required',
            'password' => 'required',
        ]);

        // Vérifier d'abord si l'utilisateur existe
        $user = TUser::where('login', $credentials['login'])
                    ->where('ismasquer', 0)
                    ->first();

        if ($user && $this->checkPassword($credentials['password'], $user->password)) {
            // Authentification réussie
            Auth::login($user);
            return redirect()->route('accueil.patient');
        }

        // Authentification échouée
        return back()->withErrors([
            'login' => 'Les identifiants fournis ne correspondent pas à nos enregistrements.',
        ]);
    }

    /**
     * Vérifier le mot de passe (à adapter selon votre méthode de hachage)
     */
    private function checkPassword($inputPassword, $hashedPassword)
    {
        // Si vous utilisez le hachage Laravel
        if (strlen($hashedPassword) === 60 && strpos($hashedPassword, '$2y$') === 0) {
            return Hash::check($inputPassword, $hashedPassword);
        }
        
        // Si vous utilisez un hachage personnalisé ou stockez en clair (non recommandé)
        return $inputPassword === $hashedPassword;
    }

    /**
     * Déconnexion de l'utilisateur
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}