<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TUser extends Authenticatable
{
    use Notifiable;

    /**
     * Nom de la table dans la base de données
     */
    protected $table = 't_user';

    /**
     * Clé primaire
     */
    protected $primaryKey = 'Iduser';

    /**
     * Désactiver les timestamps de Laravel
     */
    public $timestamps = false;

    /**
     * Champs assignables en masse
     */
    protected $fillable = [
        'login',
        'password',
        'NomComplet',
        'fonction',
        'IdClasseUser',
        'fkidmedecin',
        'fkidcabinet',
        'ismasquer',
        'DtCr'
    ];

    /**
     * Champs cachés dans les sérialisations
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Conversion automatique de type
     */
    protected $casts = [
        'ismasquer' => 'int',
        'IdClasseUser' => 'int',
        'fkidmedecin' => 'int',
        'DtCr' => 'datetime',
        'fkidcabinet' => 'int'
    ];

    /**
     * Méthode pour hasher automatiquement le mot de passe lors de la mise à jour
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    /**
     * Méthode pour vérifier si le mot de passe correspond
     */
    public function checkPassword($password)
    {
        return Hash::check($password, $this->password);
    }

    /**
     * Accesseur pour obtenir le rôle de l'utilisateur sous forme de chaîne
     */
    public function getRoleNameAttribute()
    {
        $roles = [
            1 => 'Secrétaire',
            2 => 'Docteur',
            3 => 'Docteur propriétaire'
        ];

        return $roles[$this->IdClasseUser] ?? 'Inconnu';
    }

    /**
     * Récupère le type d'utilisateur associé
     */
    public function typeUser()
    {
        return $this->belongsTo(TypeUser::class, 'IdClasseUser', 'IdClasseUser0');
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     * 
     * @param int|string|array $role Rôle(s) à vérifier
     * @return bool
     */
    public function hasRole($role)
    {
        if (is_numeric($role)) {
            return $this->IdClasseUser == $role;
        } else if (is_string($role)) {
            return $this->typeUser && $this->typeUser->Libelle === $role;
        } else if (is_array($role)) {
            return in_array($this->IdClasseUser, $role);
        }
        
        return false;
    }

    /**
     * Vérifie si l'utilisateur est un secrétaire
     */
    public function isSecretaire()
    {
        return $this->IdClasseUser == 1;
    }

    /**
     * Vérifie si l'utilisateur est un docteur
     */
    public function isDocteur()
    {
        return $this->IdClasseUser == 2;
    }

    /**
     * Vérifie si l'utilisateur est un docteur propriétaire
     */
    public function isDocteurProprietaire()
    {
        return $this->IdClasseUser == 3;
    }

    /**
     * Vérifie si l'utilisateur a une permission spécifique
     * 
     * @param string $permission Permission à vérifier
     * @return bool
     */
    public function hasPermission($permission)
    {
        $rolePermissions = $this->getRolePermissions();
        
        return in_array($permission, $rolePermissions) || in_array('*', $rolePermissions);
    }

    /**
     * Vérifie si l'utilisateur a l'une des permissions spécifiées
     * 
     * @param array $permissions Tableau de permissions à vérifier
     * @return bool
     */
    public function hasAnyPermission(array $permissions)
    {
        $rolePermissions = $this->getRolePermissions();
        
        foreach ($permissions as $permission) {
            if (in_array($permission, $rolePermissions)) {
                return true;
            }
        }
        
        return in_array('*', $rolePermissions); // Accès complet
    }

    /**
     * Récupère toutes les permissions du rôle depuis la DB (avec cache 1h).
     * Fallback sur le tableau statique si la table n'existe pas encore.
     */
    public function getRolePermissions(): array
    {
        $roleId = $this->IdClasseUser;

        try {
            return Cache::remember("role_permissions_{$roleId}", 3600, function () use ($roleId) {
                return DB::table('role_permissions')
                    ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                    ->where('role_permissions.role_id', $roleId)
                    ->pluck('permissions.name')
                    ->toArray();
            });
        } catch (\Exception $e) {
            // Fallback statique si la table permissions n'existe pas encore
            $fallback = [
                1 => ['rendez-vous.create','rendez-vous.view','patient.create','patient.view','patient.edit','caisse-operations.view'],
                2 => ['rendez-vous.view','rendez-vous.own','patient.view','patient.edit','facture.view.own','finances.own','act.create','act.view'],
                3 => ['rendez-vous.view','rendez-vous.create','rendez-vous.edit','rendez-vous.delete','patient.view','patient.create','patient.edit','patient.delete','facture.view','facture.create','facture.edit','facture.delete','finances.view','finances.create','finances.edit','finances.delete','act.view','act.create','act.edit','act.delete','user.view','user.create','user.edit','user.delete','caisse-operations.view'],
            ];
            return $fallback[$roleId] ?? [];
        }
    }

    /**
     * Invalide le cache des permissions pour un rôle donné.
     * À appeler après toute modification des permissions.
     */
    public static function clearPermissionsCache(int $roleId): void
    {
        Cache::forget("role_permissions_{$roleId}");
    }

    /**
     * Obtenez le médecin associé si l'utilisateur est un médecin
     */
    public function medecin()
    {
        return $this->belongsTo(Medecin::class, 'fkidmedecin', 'idMedecin');
    }

    /**
     * Obtenez le cabinet associé
     */
    public function cabinet()
    {
        return $this->belongsTo(Infocabinet::class, 'fkidcabinet', 'idEntete');
    }

    /**
     * Récupère les rendez-vous associés à ce médecin
     */
    public function rendezVous()
    {
        if (!$this->fkidmedecin) {
            return collect([]);
        }
        
        return Rendezvou::where('fkidMedecin', $this->fkidmedecin)->get();
    }

    /**
     * Récupère les rendez-vous du jour pour ce médecin
     */
    public function rendezVousDuJour()
    {
        if (!$this->fkidmedecin) {
            return collect([]);
        }
        
        return Rendezvou::where('fkidMedecin', $this->fkidmedecin)
            ->whereDate('dtPrevuRDV', Carbon::today())
            ->orderBy('HeureRdv')
            ->get();
    }

    /**
     * Récupère les factures associées à ce médecin
     */
    public function factures()
    {
        if (!$this->fkidmedecin) {
            return collect([]);
        }
        
        return Facture::whereHas('detailFactures', function($query) {
            $query->where('fkidMedecin', $this->fkidmedecin);
        })->get();
    }

    /**
     * Récupère les opérations de caisse associées à cet utilisateur
     */
    public function caisseOperations()
    {
        return CaisseOperation::where('fkiduser', $this->Iduser)->get();
    }

    /**
     * Méthode pour vérifier si l'utilisateur a accès à des données financières spécifiques
     */
    public function canAccessFinancesOf($medecinId)
    {
        // Docteur propriétaire peut voir tout
        if ($this->IdClasseUser == 3) {
            return true;
        }
        
        // Docteur peut voir ses propres finances
        if ($this->IdClasseUser == 2 && $this->fkidmedecin == $medecinId) {
            return true;
        }
        
        return false;
    }

    /**
     * Méthode pour vérifier si l'utilisateur peut voir les dépenses
     */
    public function canViewExpenses()
    {
        // Seul le docteur propriétaire peut voir les dépenses
        return $this->IdClasseUser == 3;
    }

    /**
     * Pour compatibilité avec Laravel Auth
     */
    public function getAuthIdentifierName()
    {
        return 'Iduser';
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getRememberTokenName()
    {
        return null; // Si vous n'utilisez pas remember_token
    }

    public function getEmailForPasswordReset()
    {
        return $this->login; // Ou un champ email si disponible
    }

    /**
     * Vérifie si l'utilisateur peut accéder aux données d'un médecin spécifique
     */
    public function canAccessMedecinData($medecinId)
    {
        // Le docteur propriétaire peut voir toutes les données
        if ($this->IdClasseUser == 3) {
            return true;
        }
        
        // Le docteur ne peut voir que ses propres données
        if ($this->IdClasseUser == 2) {
            return $this->fkidmedecin == $medecinId;
        }
        
        // La secrétaire ne peut pas voir les données des médecins
        return false;
    }

    /**
     * Vérifie si l'utilisateur peut modifier un utilisateur
     */
    public function canModifyUser()
    {
        // Seul le docteur propriétaire peut modifier les utilisateurs
        return $this->IdClasseUser == 3;
    }

    public function canSecretairePerform($action)
    {
        if (!$this->isSecretaire()) {
            return false;
        }

        $secretairePermissions = [
            'manage_rdv' => ['rendez-vous.create', 'rendez-vous.view', 'rendez-vous.edit'],
            'manage_patient' => ['patient.create', 'patient.view', 'patient.edit'],
            'manage_caisse' => ['caisse-operations.view', 'caisse-operations.create'],
            'view_actes' => ['act.view'],
            'view_assureurs' => ['assureur.view']
        ];

        if (!isset($secretairePermissions[$action])) {
            return false;
        }

        foreach ($secretairePermissions[$action] as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }
}