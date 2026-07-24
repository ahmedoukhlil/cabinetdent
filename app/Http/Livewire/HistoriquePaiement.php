<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\CaisseOperation;
use App\Models\Patient;

class HistoriquePaiement extends Component
{
    public $patient;
    public $paymentHistory = [];
    public $totalPaiements = 0;
    public $show = false;

    protected $listeners = ['showHistoriquePaiementModal' => 'showModal'];

    public function mount($patient = null)
    {
        if ($patient) {
            if (is_array($patient)) {
                $this->patient = [
                    'ID' => $patient['ID'],
                    'Nom' => $patient['Nom'] ?? '',
                    'NomPatient' => $patient['NomPatient'] ?? '',
                    'Prenom' => $patient['Prenom'],
                    'Telephone1' => $patient['Telephone1'],
                    'IdentifiantPatient' => $patient['IdentifiantPatient'] ?? null,
                ];
            } else {
                $this->patient = [
                    'ID' => $patient->ID,
                    'Nom' => $patient->Nom ?? '',
                    'NomPatient' => $patient->Nom ?? '',
                    'Prenom' => $patient->Prenom,
                    'Telephone1' => $patient->Telephone1,
                    'IdentifiantPatient' => $patient->IdentifiantPatient ?? null,
                ];
            }
            $this->loadPaymentHistory();
        }
    }

    public function loadPaymentHistory()
    {
        if (is_array($this->patient) && isset($this->patient['ID'])) {
            $patientId = $this->patient['ID'];
        } elseif (is_object($this->patient) && isset($this->patient->ID)) {
            $patientId = $this->patient->ID;
        } else {
            $this->paymentHistory = collect();
            $this->totalPaiements = 0;
            return;
        }
        $this->paymentHistory = CaisseOperation::where('fkidTiers', $patientId)
            ->orderBy('dateoper', 'desc')
            ->get();
        $this->totalPaiements = $this->paymentHistory->sum('MontantOperation');
    }

    public function imprimerA4()
    {
        $this->dispatch('imprimer-a4');
    }

    public function showModal($patientId)
    {
        \Log::info('showModal appelé', ['patientId' => $patientId]);
        $this->loadPatient($patientId);
        $this->show = true;
    }

    public function loadPatient($patientId)
    {
        $patient = Patient::find($patientId);
        if ($patient) {
            $this->patient = [
                'ID' => $patient->ID,
                'Nom' => $patient->Nom ?? '',
                'NomPatient' => $patient->Nom ?? '',
                'Prenom' => $patient->Prenom,
                'Telephone1' => $patient->Telephone1,
                'IdentifiantPatient' => $patient->IdentifiantPatient ?? null,
            ];
        }
        $this->loadPaymentHistory();
    }

    public function imprimer()
    {
        \Log::info('Méthode imprimer appelée dans HistoriquePaiement');
        session()->flash('message', 'Méthode imprimer appelée');
        $this->dispatch('imprimer-modal');
    }

    public function fermerModal()
    {
        \Log::info('Méthode fermerModal appelée dans HistoriquePaiement');
        session()->flash('message', 'Méthode fermerModal appelée');
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.historique-paiement');
    }
} 