<?php

namespace App\Http\Livewire;
use App\Traits\HasLazyLoadingPlaceholder;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Infocabinet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ParametresCabinet extends Component
{
    use HasLazyLoadingPlaceholder;

    use WithFileUploads;

    // Français
    public $NomCabFr;
    public $Specialite1Fr;
    public $Specialite2fr;
    public $Specialite3Fr;
    public $AdresseFr1;
    public $AdresseFr2;
    public $ContactFR;
    public $DrFr;

    // Arabe
    public $NomCabAr;
    public $Specialite1Ar;
    public $Specialite2Ar;
    public $Specialite3Ar;
    public $AdresseL1AR;
    public $AdresseL2AR;
    public $ContactAR;
    public $DrAr;

    // Commun
    public $AdresseMail;
    public $TelephonePublic;
    public $piedPage;

    // Logo
    public $logoFile;
    public $logoActuel;

    public function mount()
    {
        $cabinet = Infocabinet::where('idEntete', Auth::user()->fkidcabinet)->first();
        if ($cabinet) {
            $this->NomCabFr     = $cabinet->NomCabFr;
            $this->Specialite1Fr = $cabinet->Specialite1Fr;
            $this->Specialite2fr = $cabinet->Specialite2fr;
            $this->Specialite3Fr = $cabinet->Specialite3Fr;
            $this->AdresseFr1   = $cabinet->AdresseFr1;
            $this->AdresseFr2   = $cabinet->AdresseFr2;
            $this->ContactFR    = $cabinet->ContactFR;
            $this->DrFr         = $cabinet->DrFr;

            $this->NomCabAr     = $cabinet->NomCabAr;
            $this->Specialite1Ar = $cabinet->Specialite1Ar;
            $this->Specialite2Ar = $cabinet->Specialite2Ar;
            $this->Specialite3Ar = $cabinet->Specialite3Ar;
            $this->AdresseL1AR  = $cabinet->AdresseL1AR;
            $this->AdresseL2AR  = $cabinet->AdresseL2AR;
            $this->ContactAR    = $cabinet->ContactAR;
            $this->DrAr         = $cabinet->DrAr;

            $this->AdresseMail  = $cabinet->AdresseMail;
            $this->TelephonePublic = $cabinet->TelephonePublic;
            $this->piedPage     = $cabinet->piedPage ?? '';
            $this->logoActuel   = $cabinet->logo ?? null;
        }
    }

    public function save()
    {
        $cabinet = Infocabinet::where('idEntete', Auth::user()->fkidcabinet)->first();
        if (!$cabinet) {
            session()->flash('error', 'Cabinet introuvable.');
            return;
        }

        $data = [
            'NomCabFr'     => $this->NomCabFr,
            'Specialite1Fr' => $this->Specialite1Fr,
            'Specialite2fr' => $this->Specialite2fr,
            'Specialite3Fr' => $this->Specialite3Fr,
            'AdresseFr1'   => $this->AdresseFr1,
            'AdresseFr2'   => $this->AdresseFr2,
            'ContactFR'    => $this->ContactFR,
            'DrFr'         => $this->DrFr,
            'NomCabAr'     => $this->NomCabAr,
            'Specialite1Ar' => $this->Specialite1Ar,
            'Specialite2Ar' => $this->Specialite2Ar,
            'Specialite3Ar' => $this->Specialite3Ar,
            'AdresseL1AR'  => $this->AdresseL1AR,
            'AdresseL2AR'  => $this->AdresseL2AR,
            'ContactAR'    => $this->ContactAR,
            'DrAr'         => $this->DrAr,
            'AdresseMail'  => $this->AdresseMail,
            'TelephonePublic' => $this->TelephonePublic,
            'piedPage'     => $this->piedPage,
        ];

        // Gestion logo
        if ($this->logoFile) {
            // Supprimer l'ancien logo
            if ($this->logoActuel && file_exists(public_path($this->logoActuel))) {
                @unlink(public_path($this->logoActuel));
            }
            $ext = $this->logoFile->getClientOriginalExtension();
            $filename = 'logo_' . Auth::user()->fkidcabinet . '_' . time() . '.' . $ext;
            $destDir = public_path('uploads' . DIRECTORY_SEPARATOR . 'logos');
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            // Livewire TemporaryUploadedFile: read content and write manually
            $content = file_get_contents($this->logoFile->getRealPath());
            file_put_contents($destDir . DIRECTORY_SEPARATOR . $filename, $content);
            $data['logo'] = 'uploads/logos/' . $filename;
            $this->logoActuel = $data['logo'];
            $this->logoFile = null;
        }

        $cabinet->fill($data)->save();
        session()->flash('success', 'Paramètres enregistrés avec succès.');
    }

    public function supprimerLogo()
    {
        $cabinet = Infocabinet::where('idEntete', Auth::user()->fkidcabinet)->first();
        if ($cabinet && $cabinet->logo) {
            if (file_exists(public_path($cabinet->logo))) {
                @unlink(public_path($cabinet->logo));
            }
            $cabinet->logo = null;
            $cabinet->save();
            $this->logoActuel = null;
        }
    }

    public function render()
    {
        return view('livewire.parametres-cabinet');
    }
}
