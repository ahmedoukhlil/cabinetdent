# Interface Patient via QR Code

## Vue d'ensemble

Cette fonctionnalité permet aux patients d'accéder à leurs informations de rendez-vous et de consultations via un QR code apposé sur les reçus. L'interface est sécurisée et accessible sans authentification grâce à un système de tokens temporaires.

## Fonctionnalités

### Interface Patient
- **Vue des rendez-vous** : Affichage de tous les rendez-vous du patient avec leurs statuts
- **Vue des consultations** : Historique des consultations avec détails
- **Auto-refresh** : Mise à jour automatique toutes les 30 secondes
- **Interface responsive** : Optimisée pour mobile et desktop
- **Sécurité** : Accès via tokens temporaires (7 jours)

### Gestion des Statuts
- **Sélecteur de statut** : Les secrétaires et médecins peuvent changer le statut directement dans la liste
- **Statuts disponibles** :
  - En attente
  - Confirmé
  - Annulé
  - Terminé

## Routes

### Routes Publiques (Patient)
```
GET /patient/rendez-vous/{token}    # Interface des rendez-vous
GET /patient/consultation/{token}    # Interface des consultations
```

### Routes Protégées (Staff)
```
GET /rendez-vous                    # Gestion des rendez-vous
GET /rendez-vous/create             # Création de rendez-vous
```

## Structure des Fichiers

```
app/
├── Http/
│   └── Controllers/
│       └── PatientInterfaceController.php    # Contrôleur interface patient
├── Helpers/
│   └── QrCodeHelper.php                      # Helper génération QR codes
└── Http/Livewire/
    └── RendezVousManager.php                 # Gestionnaire rendez-vous

resources/views/
├── patient/
│   ├── rendez-vous.blade.php                 # Vue rendez-vous patient
│   ├── consultation.blade.php                # Vue consultations patient
│   └── error.blade.php                       # Vue d'erreur
├── rendez-vous/
│   └── print.blade.php                       # Impression rendez-vous (avec QR)
└── consultations/
    └── receipt.blade.php                     # Impression consultation (avec QR)
```

## Utilisation

### Pour les Patients
1. Scannez le QR code sur votre reçu de rendez-vous ou de consultation
2. L'interface s'ouvre automatiquement dans votre navigateur
3. Consultez vos informations en temps réel
4. La page se met à jour automatiquement

### Pour le Staff
1. **Gestion des statuts** : Utilisez le sélecteur dans la colonne "Statut" de la liste des rendez-vous
2. **Impression** : Les QR codes sont automatiquement générés sur les reçus
3. **Permissions** : Seuls les secrétaires et médecins peuvent modifier les statuts

## Sécurité

### Système de Tokens
- **Génération** : `PatientInterfaceController::generateToken($patientId)`
- **Format** : `base64_encode($patientId . '|' . time())`
- **Expiration** : 7 jours après génération
- **Validation** : Vérification automatique de l'expiration

### Permissions
- **Lecture** : Accès public via token valide
- **Modification** : Seuls les utilisateurs authentifiés avec permissions
- **Génération QR** : Seuls les utilisateurs authentifiés

## Configuration

### Variables d'Environnement
```env
QR_CODE_FORMAT=png
QR_CODE_SIZE=200
QR_CODE_MARGIN=0
QR_CODE_ERROR_CORRECTION=M
QR_CODE_ENCODING=UTF-8
```

### Dépendances
```bash
composer require simplesoftwareio/simple-qrcode
```

## Personnalisation

### Styles CSS
Les interfaces patient utilisent Tailwind CSS pour un design moderne et responsive.

### Statuts Personnalisés
Pour ajouter de nouveaux statuts :
1. Modifier `$statutsValides` dans `RendezVousManager.php`
2. Ajouter les styles correspondants dans les vues
3. Mettre à jour les options du sélecteur

### QR Codes Personnalisés
Utiliser `QrCodeHelper::generateCustomQrCode($data, $size, $format)` pour des QR codes personnalisés.

## Maintenance

### Nettoyage des Tokens Expirés
Les tokens expirés sont automatiquement rejetés. Aucun nettoyage manuel n'est nécessaire.

### Logs
Les erreurs d'accès sont loggées automatiquement par Laravel.

### Performance
- Les QR codes sont générés à la demande
- L'interface patient utilise un auto-refresh de 30 secondes
- Les données sont optimisées pour le mobile

## Support

Pour toute question ou problème :
1. Vérifier les logs Laravel
2. Tester la validité du token
3. Vérifier les permissions utilisateur
4. Contacter l'équipe technique 