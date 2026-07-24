<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Rendezvou;
use App\Models\Medecin;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class RendezVousManager extends Component
{
    public $date;
    public $medecin_id = '';
    public $selectedRendezVous = [];
    public $selectAll = false;
    public $showPastRdv = false;
    public $canManageRdv = false;
    public $canViewAllRdv = false;
    public $searchPatient = '';
    public $searchBy = 'name'; // name, nni, phone

    public function mount()
    {
        $this->date = today()->toDateString();
        $this->showPastRdv = false;
        $this->initializePermissions();
    }

    protected function initializePermissions()
    {
        $user = Auth::user();
        
        // Vérifier les permissions de base
        $this->canManageRdv = $user->hasPermission('rendez-vous.create') || $user->isDocteur() || $user->isDocteurProprietaire();
        $this->canViewAllRdv = $user->hasPermission('rendez-vous.view') || $user->isDocteur() || $user->isDocteurProprietaire();
        
        // Si l'utilisateur est un docteur, filtrer par défaut sur ses rendez-vous
        if ($user->isDocteur() && !$user->isDocteurProprietaire()) {
            $this->medecin_id = $user->fkidmedecin;
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedRendezVous = $this->getRendezVousProperty()->pluck('IDRdv')->toArray();
        } else {
            $this->selectedRendezVous = [];
        }
    }

    public function getRendezVousProperty()
    {
        try {
            if (!Carbon::parse($this->date)->isValid()) {
                return collect([]);
            }

            $user = Auth::user();
            $query = Rendezvou::with(['medecin', 'patient'])
                ->whereDate('dtPrevuRDV', $this->date);

            // Filtrer par médecin si spécifié
            if ($this->medecin_id) {
                $query->where('fkidMedecin', $this->medecin_id);
            }
            // Si l'utilisateur est un docteur (non propriétaire), ne montrer que ses rendez-vous
            elseif ($user->isDocteur() && !$user->isDocteurProprietaire()) {
                $query->where('fkidMedecin', $user->fkidmedecin);
            }

            // Recherche de patient
            if ($this->searchPatient) {
                $query->whereHas('patient', function($q) {
                    switch($this->searchBy) {
                        case 'name':
                            $q->where('Nom', 'like', '%' . $this->searchPatient . '%')
                              ->orWhere('Prenom', 'like', '%' . $this->searchPatient . '%');
                            break;
                        case 'nni':
                            $q->where('NNI', 'like', '%' . $this->searchPatient . '%');
                            break;
                        case 'phone':
                            $q->where('Telephone1', 'like', '%' . $this->searchPatient . '%')
                              ->orWhere('Telephone2', 'like', '%' . $this->searchPatient . '%');
                            break;
                    }
                });
            }

            // Par défaut, n'afficher que les rendez-vous à venir
            if (!$this->showPastRdv) {
                $query->where(function($q) {
                    $q->whereDate('dtPrevuRDV', '>', now())
                      ->orWhere(function($q) {
                          $q->whereDate('dtPrevuRDV', '=', now())
                            ->whereTime('HeureRdv', '>', now()->format('H:i:s'));
                      });
                });
            }

            return $query->orderBy('HeureRdv')->get();
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    public function annulerRendezVous($id)
    {
        try {
            $user = Auth::user();
            $rdv = Rendezvou::find($id);

            if (!$this->canManageRdv) {
                session()->flash('error', 'Vous n\'avez pas la permission d\'annuler des rendez-vous.');
                return;
            }

            if ($user->isDocteur() && !$user->isDocteurProprietaire() && $rdv->fkidMedecin != $user->fkidmedecin) {
                session()->flash('error', 'Vous ne pouvez annuler que vos propres rendez-vous.');
                return;
            }

            if ($rdv) {
                $rdv->update(['rdvConfirmer' => 'annulé']);
                session()->flash('message', 'Rendez-vous annulé.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de l\'annulation du rendez-vous.');
        }
    }

    public function confirmerRendezVous($id)
    {
        try {
            $user = Auth::user();
            $rdv = Rendezvou::find($id);

            if (!$this->canManageRdv) {
                session()->flash('error', 'Vous n\'avez pas la permission de confirmer des rendez-vous.');
                return;
            }

            if ($user->isDocteur() && !$user->isDocteurProprietaire() && $rdv->fkidMedecin != $user->fkidmedecin) {
                session()->flash('error', 'Vous ne pouvez confirmer que vos propres rendez-vous.');
                return;
            }

            if ($rdv) {
                $rdv->update([
                    'rdvConfirmer' => 'confirmé',
                    'HeureConfRDV' => now()
                ]);
                session()->flash('message', 'Rendez-vous confirmé.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la confirmation du rendez-vous.');
        }
    }

    public function changerStatutRendezVous($id, $nouveauStatut)
    {
        try {
            $user = Auth::user();
            $rdv = Rendezvou::find($id);

            if (!$this->canManageRdv) {
                session()->flash('error', 'Vous n\'avez pas la permission de modifier des rendez-vous.');
                return;
            }

            if ($user->isDocteur() && !$user->isDocteurProprietaire() && $rdv->fkidMedecin != $user->fkidmedecin) {
                session()->flash('error', 'Vous ne pouvez modifier que vos propres rendez-vous.');
                return;
            }

            $statutsValides = ['En Attente', 'Confirmé', 'En cours', 'Terminé', 'Annulé'];
            
            if (!in_array($nouveauStatut, $statutsValides)) {
                session()->flash('error', 'Statut invalide.');
                return;
            }

            // Utiliser la méthode centralisée du modèle
            $result = Rendezvou::updateStatusWithConflictManagement($id, $nouveauStatut);
            
            if ($result['success']) {
                session()->flash('message', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la modification du statut: ' . $e->getMessage());
        }
    }

    public function annulerSelection()
    {
        try {
            $user = Auth::user();

            // Vérifier les permissions
            if (!$this->canManageRdv) {
                session()->flash('error', 'Vous n\'avez pas la permission d\'annuler des rendez-vous.');
                return;
            }

            // Si docteur non propriétaire, vérifier que tous les rendez-vous sélectionnés sont les siens
            if ($user->isDocteur() && !$user->isDocteurProprietaire()) {
                $invalidRdv = Rendezvou::whereIn('IDRdv', $this->selectedRendezVous)
                    ->where('fkidMedecin', '!=', $user->fkidmedecin)
                    ->exists();

                if ($invalidRdv) {
                    session()->flash('error', 'Vous ne pouvez annuler que vos propres rendez-vous.');
                    return;
                }
            }

            Rendezvou::whereIn('IDRdv', $this->selectedRendezVous)
                ->update(['rdvConfirmer' => 'annulé']);
            $this->selectedRendezVous = [];
            $this->selectAll = false;
            session()->flash('message', 'Rendez-vous sélectionnés annulés.');
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de l\'annulation des rendez-vous.');
        }
    }

    public function render()
    {
        try {
            $user = Auth::user();
            
            // Récupérer la liste des médecins selon les permissions
            if ($user->isDocteurProprietaire()) {
                $medecins = Medecin::all();
            } elseif ($user->isDocteur()) {
                $medecins = Medecin::where('idMedecin', $user->fkidmedecin)->get();
            } else {
                $medecins = Medecin::all();
            }

            $RendezVous = $this->getRendezVousProperty();
            
            return view('livewire.rendez-vous-manager', compact('medecins', 'RendezVous'));
        } catch (\Exception $e) {
            return view('livewire.rendez-vous-manager', [
                'medecins' => collect([]),
                'RendezVous' => collect([])
            ]);
        }
    }
}