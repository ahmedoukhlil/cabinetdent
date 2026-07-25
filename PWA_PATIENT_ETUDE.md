# Étude — PWA Patient SysMedical

## 1. Objectif

Permettre à chaque patient d'accéder, via une Progressive Web App installable sur son téléphone, à :
- son plan de traitement dentaire
- son historique de paiements
- ses factures
- des notifications de rappel de rendez-vous
- la file d'attente en temps quasi-réel le jour de son RDV

## 2. Existant à connaître

- Un accès patient **par token signé** existe déjà (`PatientInterfaceController`), généré par QR code, valable uniquement le jour d'un RDV précis (HMAC-SHA256, payload `patientId|date|medecinId`). Il n'y a **aucun compte patient permanent**.
- La salle d'attente côté cabinet (`SalleAttente.php`) utilise `wire:poll.15s` — le même principe de polling sera réutilisé côté patient.
- Le modèle `Patient` existe déjà avec toutes les données nécessaires (ID, nom, téléphone...), mais n'a pas de mécanisme d'authentification propre (pas de colonne mot de passe).

## 3. Décisions actées avec l'utilisateur

| Sujet | Décision |
|---|---|
| Authentification | Compte permanent identifiant + mot de passe (séparé du système token actuel) |
| Notifications de rappel | Web Push (navigateur/PWA), pas de SMS pour l'instant |
| File d'attente patient | Polling simple (15-30s), pas de WebSocket |

## 4. Architecture proposée

### 4.1 Authentification patient

- Nouvelle table `patient_users` (ou colonnes ajoutées à `patients` : `password`, `email_verified_at`) — **séparée** de `t_user` (comptes staff), pour ne jamais mélanger les permissions.
- Un **guard Laravel dédié** (`auth:patient`) avec son propre `Patient` model implémentant `Authenticatable`, distinct du guard web existant utilisé par le staff.
- Création de compte : soit auto-inscription (le patient choisit un mot de passe à partir de son numéro de téléphone/dossier, avec vérification), soit compte pré-créé par le secrétariat lors de la première visite (recommandé pour un cabinet — évite les doublons/erreurs de saisie).
- Récupération de mot de passe oublié : par SMS (nécessite un fournisseur, coût) ou par email si le patient en a un enregistré.

### 4.2 Structure applicative

- Nouvel espace de routes `patient.*` sous un préfixe dédié (ex: `/espace-patient`), avec son propre layout, complètement séparé de `accueil-patient` (interface staff).
- Composants Livewire dédiés (`PatientDashboard`, `PatientPlanTraitement`, `PatientFactures`, `PatientFileAttente`) réutilisant les modèles existants en lecture seule — **aucune capacité d'écriture** pour le patient sur ses propres données médicales/financières.
- Manifest PWA (`manifest.json`) + Service Worker (`sw.js`) pour l'installabilité (icône sur écran d'accueil, mode standalone) et le cache offline des pages statiques (mentions, coordonnées du cabinet).

### 4.3 Notifications Web Push

- Librairie `laravel-notification-channels/webpush` (VAPID keys) — standard, gratuit, pas de service tiers payant.
- Un job planifié (`php artisan schedule`) vérifie chaque jour les RDV du lendemain et pousse une notification aux patients ayant souscrit.
- Limite à anticiper : le patient doit avoir ouvert la PWA au moins une fois et accepté la permission navigateur — taux de couverture non garanti à 100%, contrairement à un SMS.

### 4.4 File d'attente patient

- Réutilisation de la logique de `SalleAttente.php` (position dans la file, temps estimé), déjà largement présente dans `PatientInterfaceController::showRendezVous()` — cette page existe quasiment déjà pour le flux token, il faut la reprendre pour le flux compte permanent.
- Un composant Livewire avec `wire:poll.20s`, visible uniquement le jour du RDV du patient (même contrainte temporelle que le système token actuel).

## 5. Ce qui change pour le staff (cabinet)

Rien côté fonctionnement existant — c'est un espace totalement séparé. Seul ajout : un écran d'administration pour créer/lier un compte patient à une fiche `Patient` existante.

## 6. Risques et points d'attention

- **RGPD / consentement** : les patients accèdent à des données médicales sensibles depuis leur propre téléphone — s'assurer que le mot de passe est fort, que les sessions expirent, et qu'il n'y a pas de fuite de données entre patients (toujours vérifier `patient_id` dans chaque requête).
- **Charge serveur** : le polling de la file d'attente par plusieurs patients simultanés ajoute des requêtes récurrentes — acceptable au volume actuel du cabinet, à surveiller si le nombre de patients simultanés grandit fortement.
- **Notifications Web Push non garanties** : bien communiquer aux patients que ce n'est pas un SMS, sous peine de RDV manqués si mal comprises.
- **Volume de travail** : ce projet est significativement plus gros que les fixes ponctuels traités jusqu'ici — probablement plusieurs jours de développement (auth, 4-5 vues, service worker, notifications, tests).

## 7. Découpage proposé en étapes livrables

1. **Socle auth patient** : migration, guard, page de connexion, création de compte côté staff.
2. **Dashboard + Plan de traitement** (lecture seule des données existantes).
3. **Paiements + Factures** (lecture seule).
4. **PWA installable** (manifest + service worker, sans notifications).
5. **File d'attente patient** (polling).
6. **Notifications Web Push** (dernière étape, la plus optionnelle si le budget/temps est limité).

Chaque étape est livrable et testable indépendamment — on peut s'arrêter après l'étape 4 et ajouter la suite plus tard.

## 8. Déploiement — notifications Web Push (étape 6, terminée)

- **Clés VAPID** : générées une fois, stockées dans `.env` (`VAPID_PUBLIC_KEY`,
  `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`) — jamais commitées. Sur le VPS, les
  ajouter manuellement au `.env` de production avec les mêmes valeurs que
  celles générées en local (ou en générer de nouvelles dédiées à la prod).
- **Cron obligatoire** : Laravel ne déclenche jamais seul les tâches planifiées
  (`routes/console.php` → `Schedule::command('app:envoyer-rappels-rdv')`).
  Il faut une entrée crontab sur le VPS :
  ```
  * * * * * cd /var/www/cabinetdent && php artisan schedule:run >> /dev/null 2>&1
  ```
  Sans cette ligne, aucun rappel de RDV ne sera jamais envoyé automatiquement.
- **HTTPS obligatoire** : les Service Workers et Web Push exigent HTTPS (déjà
  le cas pour `cabinetdentaire.syslog-apps.online`, à vérifier si ce n'est pas
  encore actif).
- Le patient doit explicitement cliquer sur "Activer" (bouton sur le
  dashboard) et accepter la permission navigateur — rien n'est automatique
  ni implicite, conformément aux règles des navigateurs sur les notifications.
