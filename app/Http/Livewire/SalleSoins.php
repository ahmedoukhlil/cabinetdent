<?php

namespace App\Http\Livewire;

use App\Traits\HasLazyLoadingPlaceholder;
use Livewire\Component;
use App\Models\Ordonnanceref;
use Illuminate\Support\Facades\Auth;

class SalleSoins extends Component
{
    use HasLazyLoadingPlaceholder;

    public $date;
    public $patientOuvert = null;

    public function mount()
    {
        $this->date = now()->format('Y-m-d');
    }

    /**
     * Charge les patients regroupés depuis les ordonnancerefs internes du jour.
     * - Infirmier : uniquement en_attente + en_cours
     * - Médecin/Propriétaire : tous (en_attente, en_cours, termine)
     */
    public function getPatientsProperty()
    {
        $user = Auth::user();
        $canCreate = $user->hasPermission('ordonnance.create');

        $query = Ordonnanceref::with(['patient', 'prescripteur', 'ordonnances' => function ($q) {
                $q->where('estInterne', true);
            }])
            ->where('fkidCabinet', $user->fkidcabinet)
            ->whereDate('dtPrescript', $this->date)
            ->whereHas('ordonnances', fn($q) => $q->where('estInterne', true));

        // Infirmier : uniquement les soins non terminés
        if (!$canCreate) {
            $query->whereIn('statutSoin', ['en_attente', 'en_cours']);
        }

        $refs = $query->orderByRaw("FIELD(statutSoin,'en_cours','en_attente','termine')")
            ->orderBy('dtPrescript', 'desc')
            ->get();

        // Grouper par patient + statut dominant
        $parPatient = [];
        foreach ($refs as $ref) {
            $pid = $ref->fkidpatient;
            if (!isset($parPatient[$pid])) {
                $parPatient[$pid] = [
                    'patient'     => $ref->patient,
                    'statut'      => $ref->statutSoin,
                    'ordonnances' => [],
                ];
            }
            // Le statut dominant : en_cours > en_attente > termine
            $priorite = ['en_cours' => 0, 'en_attente' => 1, 'termine' => 2];
            $actuel   = $priorite[$parPatient[$pid]['statut']] ?? 2;
            $nouveau  = $priorite[$ref->statutSoin] ?? 2;
            if ($nouveau < $actuel) {
                $parPatient[$pid]['statut'] = $ref->statutSoin;
            }

            $parPatient[$pid]['ordonnances'][] = [
                'id'           => $ref->id,
                'refOrd'       => $ref->refOrd,
                'dtPrescript'  => $ref->dtPrescript,
                'TypeOrd'      => $ref->TypeOrdonnance,
                'statutSoin'   => $ref->statutSoin,
                'prescripteur' => $ref->prescripteur,
                'lignes'       => $ref->ordonnances->map(fn($o) => [
                    'libelle'  => $o->Libelle,
                    'posologie'=> $o->Utilisation,
                ])->toArray(),
            ];
        }

        return collect(array_values($parPatient));
    }

    public function togglePatient($patientId)
    {
        $this->patientOuvert = ($this->patientOuvert === $patientId) ? null : $patientId;
    }

    /** Infirmier : passer toutes les ordonnances du patient à "en_cours" */
    public function demarrerSoins($patientId)
    {
        Ordonnanceref::where('fkidpatient', $patientId)
            ->where('fkidCabinet', Auth::user()->fkidcabinet)
            ->whereDate('dtPrescript', $this->date)
            ->where('statutSoin', 'en_attente')
            ->whereHas('ordonnances', fn($q) => $q->where('estInterne', true))
            ->update(['statutSoin' => 'en_cours']);
    }

    /** Infirmier : passer toutes les ordonnances du patient à "termine" */
    public function terminerSoins($patientId)
    {
        Ordonnanceref::where('fkidpatient', $patientId)
            ->where('fkidCabinet', Auth::user()->fkidcabinet)
            ->whereDate('dtPrescript', $this->date)
            ->whereHas('ordonnances', fn($q) => $q->where('estInterne', true))
            ->update(['statutSoin' => 'termine']);

        // Fermer l'accordéon si c'était ce patient
        if ($this->patientOuvert === $patientId) {
            $this->patientOuvert = null;
        }
    }

    public function selectionnerPatient($patientData)
    {
        $this->dispatch('patientSelectedFromSalle', $patientData);
    }

    public function render()
    {
        return view('livewire.salle-soins', [
            'patients'   => $this->patients,
            'canCreate'  => Auth::user()->hasPermission('ordonnance.create'),
        ]);
    }
}
