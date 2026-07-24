<?php

namespace App\Http\Livewire;

use App\Traits\HasLazyLoadingPlaceholder;
use Livewire\Component;
use App\Models\Rendezvou;
use App\Models\Medecin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SalleAttente extends Component
{
    use HasLazyLoadingPlaceholder;

    public $date;
    public $medecinFiltre = null;
    public $medecins = [];

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    public function mount()
    {
        $this->date = now()->format('Y-m-d');
        $this->loadMedecins();

        // Si c'est un médecin simple, forcer le filtre sur lui
        $user = Auth::user();
        if ($user->isDocteur() && !$user->isDocteurProprietaire()) {
            $this->medecinFiltre = $user->fkidmedecin;
        }
    }

    private function loadMedecins()
    {
        $user = Auth::user();
        $query = Medecin::orderBy('Nom');

        if ($user->isDocteur() && !$user->isDocteurProprietaire()) {
            $query->where('idMedecin', $user->fkidmedecin);
        }

        $this->medecins = $query->get();
    }

    public function selectionnerPatient($patientData)
    {
        $action = $patientData['action'] ?? null;
        $rdvId = $patientData['IDRdv'] ?? null;

        if ($rdvId && in_array($action, ['dossier', 'plan-traitement'], true)) {
            $rdv = Rendezvou::find($rdvId);
            if ($rdv && strtolower($rdv->rdvConfirmer ?? '') !== 'en cours') {
                $this->demarrerRdv($rdvId);
            }
        }

        $this->dispatch('patientSelectedFromSalle', $patientData);
    }

    public function changerStatut($rdvId, $statut)
    {
        $rdv = Rendezvou::find($rdvId);
        if ($rdv) {
            $rdv->rdvConfirmer = $statut;
            $rdv->save();
        }
    }

    // Durée maximale (en minutes) qu'un rendez-vous reste "En cours" avant
    // d'être automatiquement basculé "Terminé" par terminerRdvExpires().
    const DUREE_MAX_EN_COURS_MINUTES = 15;

    // Appeler quand on clique sur un RDV en attente/confirmé :
    // termine le RDV "En cours" du même médecin, puis passe ce RDV "En cours"
    public function demarrerRdv($rdvId)
    {
        $rdv = Rendezvou::find($rdvId);
        if (!$rdv) return;

        // Terminer tout RDV "En cours" du même médecin ce jour
        Rendezvou::where('fkidMedecin', $rdv->fkidMedecin)
            ->whereDate('dtPrevuRDV', $this->date)
            ->where('rdvConfirmer', 'En cours')
            ->where('IDRdv', '!=', $rdvId)
            ->update(['rdvConfirmer' => 'Terminé']);

        $rdv->rdvConfirmer = 'En cours';
        $rdv->debut_en_cours = now();
        $rdv->save();
    }

    public function terminerRdv($rdvId)
    {
        $rdv = Rendezvou::find($rdvId);
        if ($rdv) {
            $rdv->rdvConfirmer = 'Terminé';
            $rdv->save();
        }
    }

    // Bascule automatiquement "Terminé" tout RDV "En cours" depuis plus de
    // DUREE_MAX_EN_COURS_MINUTES — appelée à chaque cycle de wire:poll.
    private function terminerRdvExpires()
    {
        $seuil = Carbon::now()->subMinutes(self::DUREE_MAX_EN_COURS_MINUTES);

        Rendezvou::where('rdvConfirmer', 'En cours')
            ->where('fkidcabinet', Auth::user()->fkidcabinet)
            ->whereNotNull('debut_en_cours')
            ->where('debut_en_cours', '<=', $seuil)
            ->update(['rdvConfirmer' => 'Terminé']);
    }

    public function getRendezVousProperty()
    {
        $this->terminerRdvExpires();

        $query = Rendezvou::with(['patient', 'medecin'])
            ->whereDate('dtPrevuRDV', $this->date)
            ->whereNotIn('rdvConfirmer', ['Annulé', 'annulé', 'Terminé', 'terminé'])
            ->where('fkidcabinet', Auth::user()->fkidcabinet)
            ->orderBy('HeureRdv');

        if ($this->medecinFiltre) {
            $query->where('fkidMedecin', $this->medecinFiltre);
        }

        return $query->get()->groupBy('fkidMedecin');
    }

    public function render()
    {
        return view('livewire.salle-attente', [
            'rdvParMedecin' => $this->rendezVous,
        ]);
    }
}
