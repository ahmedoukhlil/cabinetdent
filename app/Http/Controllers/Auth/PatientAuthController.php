<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PatientAuthController extends Controller
{
    /**
     * Affiche le formulaire de connexion patient.
     */
    public function showLoginForm()
    {
        return view('patient-auth.login');
    }

    /**
     * Étape 1 : le patient saisit son numéro de téléphone.
     * - Si un seul patient correspond et n'a jamais défini de mot de passe :
     *   connexion immédiate, puis redirection vers la création du mot de passe.
     * - Si un seul patient correspond et a déjà un mot de passe : demander le mot de passe.
     * - Si plusieurs patients partagent ce numéro : demander de choisir lequel.
     * - Si aucun patient ne correspond : erreur.
     */
    public function rechercherParTelephone(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string',
        ]);

        $telephone = trim($request->input('telephone'));

        $patients = Patient::where('Telephone1', $telephone)
            ->orWhere('Telephone2', $telephone)
            ->get();

        if ($patients->isEmpty()) {
            throw ValidationException::withMessages([
                'telephone' => "Aucun patient n'est enregistré avec ce numéro de téléphone. Contactez le cabinet.",
            ]);
        }

        if ($patients->count() > 1) {
            // Numéro partagé entre plusieurs patients (ex: famille) : laisser
            // choisir explicitement lequel, avant de statuer sur le mot de
            // passe — évite qu'un tiers accède au dossier d'un autre patient
            // simplement parce qu'il connaît le même numéro de téléphone.
            return view('patient-auth.choisir-patient', [
                'telephone' => $telephone,
                'patients' => $patients,
            ]);
        }

        return $this->traiterPatientUnique($patients->first(), $telephone);
    }

    /**
     * Le patient a choisi lequel des profils partageant le numéro est le sien.
     */
    public function selectionnerPatient(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string',
            'patient_id' => 'required|integer',
        ]);

        $patient = Patient::where('ID', $request->input('patient_id'))
            ->where(function ($q) use ($request) {
                $q->where('Telephone1', $request->input('telephone'))
                    ->orWhere('Telephone2', $request->input('telephone'));
            })
            ->firstOrFail();

        return $this->traiterPatientUnique($patient, $request->input('telephone'));
    }

    private function traiterPatientUnique(Patient $patient, string $telephone)
    {
        if (!$patient->password) {
            // Première connexion : aucun mot de passe défini, le numéro de
            // téléphone seul suffit. On connecte immédiatement puis on force
            // la création d'un mot de passe avant tout accès à l'espace patient.
            Auth::guard('patient')->login($patient);
            request()->session()->regenerate();
            return redirect()->route('patient.definir-mot-de-passe');
        }

        // Mot de passe déjà défini : le numéro seul ne suffit plus, il faut
        // le mot de passe (sécurité — voir décision actée avec l'utilisateur).
        return view('patient-auth.mot-de-passe', [
            'telephone' => $telephone,
            'patient_id' => $patient->ID,
            'nom' => $patient->NomContact ?? trim(($patient->Prenom ?? '') . ' ' . ($patient->Nom ?? '')),
        ]);
    }

    /**
     * Étape 2 (si mot de passe déjà défini) : vérifie le mot de passe.
     */
    public function verifierMotDePasse(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|integer',
            'password' => 'required|string',
        ]);

        $patient = Patient::findOrFail($request->input('patient_id'));

        if (!$patient->password || !Hash::check($request->input('password'), $patient->password)) {
            throw ValidationException::withMessages([
                'password' => 'Mot de passe incorrect.',
            ]);
        }

        Auth::guard('patient')->login($patient);
        $request->session()->regenerate();

        return redirect()->intended(route('patient.dashboard'));
    }

    /**
     * Formulaire de création de mot de passe, affiché obligatoirement après
     * la toute première connexion (par téléphone seul).
     */
    public function showDefinirMotDePasse()
    {
        $patient = Auth::guard('patient')->user();

        // Un patient ayant déjà un mot de passe ne doit pas repasser par cet
        // écran (accès direct via URL par exemple) — redirection normale.
        if ($patient->password) {
            return redirect()->route('patient.dashboard');
        }

        return view('patient-auth.definir-mot-de-passe');
    }

    public function definirMotDePasse(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $patient = Auth::guard('patient')->user();

        $patient->update([
            'password' => Hash::make($request->input('password')),
            'mdp_defini_le' => now(),
        ]);

        return redirect()->route('patient.dashboard')
            ->with('message', 'Mot de passe créé avec succès.');
    }

    public function logout(Request $request)
    {
        Auth::guard('patient')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('patient.login');
    }
}
