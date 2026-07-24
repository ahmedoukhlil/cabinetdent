<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth as LaravelAuth;

class Auth extends Component
{
    public $login = '';
    public $password = '';
    public $remember = false;
    public $errorMessage = '';

    public function login()
    {
        $this->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Le login est obligatoire.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        if (LaravelAuth::attempt(['login' => $this->login, 'password' => $this->password], $this->remember)) {
            session()->regenerate();
            return redirect()->intended('/accueil-patient');
        } else {
            $this->errorMessage = 'Identifiants incorrects.';
        }
    }

    public function render()
    {
        return view('livewire.auth');
    }
}