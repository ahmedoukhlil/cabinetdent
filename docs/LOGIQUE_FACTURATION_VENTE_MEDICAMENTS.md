# Logique de Facturation et Vente de Médicaments

## Vue d'ensemble

Ce document explique la logique complète de facturation et de vente de médicaments dans le système CabinetSavwa.

---

## 1. STRUCTURE DES DONNÉES

### 1.1 Modèle Facture (`app/Models/Facture.php`)

La facture est l'entité centrale qui regroupe tous les actes, médicaments, analyses et radios facturés à un patient.

**Champs clés :**
- `Idfacture` : Identifiant unique
- `Nfacture` : Numéro de facture (format: `{numero}-{annee}`)
- `IDPatient` : Patient concerné
- `TotFacture` : Montant total de la facture
- `TotalPEC` : Montant pris en charge par l'assurance
- `TotalfactPatient` : Montant à payer par le patient (`TotFacture - TotalPEC`)
- `TotReglPatient` : Montant déjà réglé par le patient
- `ReglementPEC` : Montant déjà réglé par l'assurance
- `TXPEC` : Taux de prise en charge (0-1)
- `ISTP` : Indicateur si le patient est assuré (1 = assuré, 0 = non assuré)
- `FkidMedecinInitiateur` : Médecin qui a créé la facture

**Génération du numéro de facture :**
- Méthode `generateUniqueFactureNumber()` : Génère un numéro unique par cabinet et par année
- Utilise des transactions avec verrouillage pour éviter les conflits
- Format : `{numero}-{annee}` (ex: `1-2025`)

### 1.2 Modèle Detailfacturepatient (`app/Models/Detailfacturepatient.php`)

Représente chaque ligne d'une facture (acte, médicament, analyse ou radio).

**Champs clés :**
- `idDetfacture` : Identifiant unique
- `fkidfacture` : Facture parente
- `fkidacte` : ID de l'acte (si `IsAct = 1`)
- `fkidmedicament` : ID du médicament/analyse/radio (si `IsAct = 2, 3 ou 4`)
- `IsAct` : Type d'item
  - `1` = Acte médical
  - `2` = Médicament (`fkidtype = 1`)
  - `3` = Analyse (`fkidtype = 2`)
  - `4` = Radio (`fkidtype = 3`)
- `PrixRef` : Prix de référence
- `PrixFacture` : Prix facturé (peut différer du prix de référence)
- `Quantite` : Quantité
- `Actes` : Libellé de l'item

### 1.3 Modèle Medicament (`app/Models/Medicament.php`)

Catalogue des médicaments, analyses et radios.

**Champs clés :**
- `IDMedic` : Identifiant unique
- `LibelleMedic` : Nom du médicament/analyse/radio
- `fkidtype` : Type
  - `1` = Médicament
  - `2` = Analyse
  - `3` = Radio
- `PrixRef` : Prix de référence

### 1.4 Modèle StockMedicament (`app/Models/StockMedicament.php`)

Gère le stock des médicaments par cabinet.

**Champs clés :**
- `idStock` : Identifiant unique
- `fkidMedicament` : Médicament concerné
- `fkidCabinet` : Cabinet concerné
- `quantiteStock` : Quantité en stock
- `quantiteMin` : Seuil minimum d'alerte
- `prixAchat` : Prix d'achat moyen
- `prixVente` : Prix de vente

**Note importante :** Seuls les médicaments (`fkidtype = 1`) ont un stock. Les analyses et radios n'ont pas de stock.

### 1.5 Modèle LotMedicament (`app/Models/LotMedicament.php`)

Gère les lots de médicaments avec dates d'expiration (méthode FIFO).

**Champs clés :**
- `idLot` : Identifiant unique
- `fkidStock` : Stock parent
- `numeroLot` : Numéro de lot
- `quantiteInitiale` : Quantité initiale du lot
- `quantiteRestante` : Quantité restante
- `dateExpiration` : Date d'expiration
- `prixAchatUnitaire` : Prix d'achat unitaire

### 1.6 Modèle MouvementStock (`app/Models/MouvementStock.php`)

Historique des mouvements de stock (entrées, sorties, ajustements).

**Champs clés :**
- `typeMouvement` : `ENTREE`, `SORTIE`, `AJUSTEMENT`
- `quantite` : Quantité du mouvement
- `fkidFacture` : Facture liée (pour les sorties)
- `fkidDetailFacture` : Détail de facture lié

---

## 2. PROCESSUS DE FACTURATION

### 2.1 Création d'une facture vide

**Fichier :** `app/Http/Livewire/ReglementFacture.php`

**Méthode :** `createFactureVide()`

**Processus :**
1. Génération d'un numéro de facture unique via `Facture::generateUniqueFactureNumber()`
2. Création de la facture avec montants à 0
3. Association du patient et du médecin
4. Récupération de l'assureur du patient si disponible

### 2.2 Ajout d'un acte à la facture

**Méthode :** `saveActeToFacture()`

**Processus :**
1. Validation des données (acte, prix, quantité)
2. Création d'un `Detailfacturepatient` avec `IsAct = 1`
3. Mise à jour des totaux de la facture :
   ```php
   $prixFactureActe = $prixFacture * $quantite;
   $nouveauTotFacture = $facture->TotFacture + $prixFactureActe;
   $montantPEC = $prixFactureActe * $txpec;
   $totalPEC = $facture->TotalPEC + $montantPEC;
   $totalfactPatient = $nouveauTotFacture - $totalPEC;
   ```

### 2.3 Ajout d'un médicament/analyse/radio à la facture

**Méthode :** `saveMedicamentToFacture()`

**Processus :**
1. Validation des données (médicament, type, prix, quantité)
2. Détermination de `IsAct` selon le type :
   - Médicament (`fkidtype = 1`) → `IsAct = 2`
   - Analyse (`fkidtype = 2`) → `IsAct = 3`
   - Radio (`fkidtype = 3`) → `IsAct = 4`
3. Création d'un `Detailfacturepatient` avec le bon `IsAct`
4. Mise à jour des totaux de la facture (même logique que pour les actes)

**Note :** Pour les médicaments, le stock n'est **PAS** déduit à ce stade. La déduction se fait lors du paiement (voir section 3.3).

### 2.4 Calcul des totaux de facture

**Logique de calcul :**
```php
// Pour chaque item ajouté :
$prixItem = $prixFacture * $quantite;
$nouveauTotFacture = $facture->TotFacture + $prixItem;

// Si patient assuré (ISTP = 1) :
$montantPEC = $prixItem * $txpec; // TXPEC = taux de prise en charge (0-1)
$totalPEC = $facture->TotalPEC + $montantPEC;
$totalfactPatient = $nouveauTotFacture - $totalPEC;

// Si patient non assuré (ISTP = 0) :
$totalfactPatient = $nouveauTotFacture;
$totalPEC = 0;
```

### 2.5 Groupement des détails par type

**Méthode :** `Facture::getDetailsGroupesParType()`

Groupe les détails de facture en sections :
- **Actes médicaux** : `IsAct = 1`
- **Médicaments** : `IsAct = 2`
- **Analyses** : `IsAct = 3`
- **Radios** : `IsAct = 4`

---

## 3. PROCESSUS DE VENTE DE MÉDICAMENTS

### 3.1 Gestion du stock

**Fichier :** `app/Http/Livewire/PharmacieManager.php`

#### 3.1.1 Entrée de stock

**Méthode :** `enregistrerEntree()`

**Processus :**
1. Création ou récupération du `StockMedicament`
2. Calcul du nouveau prix d'achat moyen :
   ```php
   $nouveauPrixAchat = (($stock->prixAchat * $stock->quantiteStock) + ($prixAchat * $quantite)) 
                       / ($stock->quantiteStock + $quantite);
   ```
3. Création d'un `LotMedicament` si date d'expiration renseignée
4. Mise à jour du stock :
   ```php
   $stock->quantiteStock = $stock->quantiteStock + $quantite;
   ```
5. Création d'un `MouvementStock` de type `ENTREE`

#### 3.1.2 Vérification du stock disponible

**Méthode :** `creerFacture()` dans `PharmacieManager.php`

**Logique :**
```php
// Calculer la quantité déjà facturée mais non payée
$quantiteDejaFacturee = Detailfacturepatient::join('facture', ...)
    ->where('fkidmedicament', $medicamentId)
    ->where('IsAct', 2) // Uniquement les médicaments
    ->whereRaw('(CASE WHEN facture.ISTP > 0 THEN facture.TotalfactPatient ELSE facture.TotFacture END) > (facture.TotReglPatient + COALESCE(facture.ReglementPEC, 0))')
    ->sum('Quantite');

// Stock disponible = Stock total - Quantité déjà facturée non payée
$stockDisponible = $stock->quantiteStock - $quantiteDejaFacturee;
```

**Important :** Le système considère qu'un médicament est "réservé" dès qu'il est facturé, même si la facture n'est pas encore payée. Cela évite les surventes.

### 3.2 Création d'une facture de vente

**Méthode :** `creerFacture()` dans `PharmacieManager.php`

**Processus :**
1. Vérification que le panier n'est pas vide
2. Vérification du stock disponible pour tous les médicaments du panier
3. Création d'une facture vide
4. Pour chaque médicament du panier :
   - Création d'un `Detailfacturepatient` avec `IsAct = 2`
   - Mise à jour des totaux de la facture
5. **Le stock n'est PAS déduit à ce stade**

**Note :** Le message affiché indique : *"Le stock sera déduit lors du paiement complet de la facture."*

### 3.3 Déduction du stock lors du paiement

**✅ IMPLÉMENTÉ :** 

Le stock est maintenant **automatiquement déduit** lors du paiement complet de la facture. 

**Fichier concerné :** `app/Http/Livewire/ReglementFacture.php`
**Méthode :** `enregistrerReglement()`

**Processus :**
1. Création d'une `CaisseOperation` (enregistrement du paiement)
2. Mise à jour de `TotReglPatient` ou `ReglementPEC` selon le type de règlement
3. Vérification si la facture est complètement payée via `Facture::estCompletementPayee()`
4. Si oui, appel de `deduireStockFacture()` qui :
   - Pour chaque médicament (`IsAct = 2`) de la facture :
     - Vérifie si le stock n'a pas déjà été déduit (évite les doubles déductions)
     - Déduit selon la méthode FIFO (First In First Out) :
       - Récupère les lots actifs triés par date d'expiration (plus ancien d'abord)
       - Déduit la quantité des lots dans l'ordre
       - Met à jour `quantiteRestante` de chaque lot
       - Crée un `MouvementStock` de type `SORTIE` pour chaque lot utilisé
     - Si stock insuffisant dans les lots, déduit du stock général
     - Met à jour `quantiteStock` du stock
     - Met à jour `dateDerniereSortie` du stock

**Méthode FIFO :**
- Les lots sont triés par date d'expiration (plus ancien d'abord)
- En cas d'égalité, tri par date d'entrée (plus ancien d'abord)
- La quantité est déduite des lots dans l'ordre jusqu'à épuisement
- Chaque déduction crée un mouvement de stock traçable

---

## 4. PROCESSUS DE RÈGLEMENT

### 4.1 Enregistrement d'un règlement

**Fichier :** `app/Http/Livewire/ReglementFacture.php`
**Méthode :** `enregistrerReglement()`

**Processus :**
1. Validation des données (montant, mode de paiement)
2. Si patient assuré, déterminer si le règlement est pour le patient ou la PEC
3. Création d'une `CaisseOperation` :
   - `MontantOperation` : Montant du règlement (peut être négatif pour remboursement)
   - `entreEspece` : Montant si entrée
   - `retraitEspece` : Montant si sortie (remboursement)
   - `fkidfacturebord` : ID de la facture
4. Mise à jour de la facture :
   ```php
   if ($facture->ISTP == 1 && $pourQui === 'pec') {
       $facture->ReglementPEC += $montantOperation;
   } else {
       $facture->TotReglPatient += $montantOperation;
   }
   ```

### 4.2 Calcul du reste à payer

**Logique :**
```php
// Pour un patient non assuré :
$resteAPayer = $facture->TotFacture - $facture->TotReglPatient;

// Pour un patient assuré :
$resteAPayerPatient = $facture->TotalfactPatient - $facture->TotReglPatient;
$resteAPayerPEC = $facture->TotalPEC - $facture->ReglementPEC;
```

### 4.3 Facture réglée

Une facture est considérée comme réglée si :
- Patient non assuré : `TotFacture <= TotReglPatient`
- Patient assuré : `TotalfactPatient <= TotReglPatient` ET `TotalPEC <= ReglementPEC`

---

## 5. POINTS IMPORTANTS À RETENIR

### 5.1 Différence entre médicaments, analyses et radios

- **Médicaments** (`fkidtype = 1`, `IsAct = 2`) : Ont un stock, peuvent être vendus depuis l'onglet Pharmacie
- **Analyses** (`fkidtype = 2`, `IsAct = 3`) : Pas de stock, facturées comme services
- **Radios** (`fkidtype = 3`, `IsAct = 4`) : Pas de stock, facturées comme services

### 5.2 Gestion du stock

- Le stock est vérifié lors de l'ajout au panier et lors de la création de la facture
- Le stock disponible = Stock total - Quantité déjà facturée non payée
- **✅ Le stock est automatiquement déduit lors du paiement complet de la facture**
- La déduction utilise la méthode FIFO (First In First Out) pour gérer les lots
- Chaque déduction crée un mouvement de stock traçable

### 5.3 Calcul des montants

- `TotFacture` : Montant total de tous les items
- `TotalPEC` : Montant pris en charge par l'assurance (calculé avec `TXPEC`)
- `TotalfactPatient` : Montant à payer par le patient (`TotFacture - TotalPEC`)
- `TotReglPatient` : Montant déjà payé par le patient
- `ReglementPEC` : Montant déjà payé par l'assurance

### 5.4 Numérotation des factures

- Format : `{numero}-{annee}` (ex: `1-2025`)
- Unique par cabinet et par année
- Génération avec verrouillage pour éviter les conflits

---

## 6. FONCTIONNALITÉS IMPLÉMENTÉES

### 6.1 Déduction automatique du stock lors du paiement

**✅ Implémenté dans :** `ReglementFacture::enregistrerReglement()`

**Méthode :** `deduireStockFacture(Facture $facture, CaisseOperation $operation)`

**Fonctionnalités :**
- Vérification automatique si la facture est complètement payée
- Déduction du stock uniquement pour les médicaments (`IsAct = 2`)
- Protection contre les doubles déductions (vérification des mouvements existants)
- Gestion des lots selon la méthode FIFO
- Création de mouvements de stock traçables
- Gestion du stock général si les lots sont insuffisants

### 6.2 Gestion des lots (FIFO)

**✅ Implémenté :** Méthode pour déduire le stock en respectant la méthode FIFO (First In First Out)

**Logique implémentée :**
1. Récupération des lots actifs triés par date d'expiration (plus ancien d'abord)
2. En cas d'égalité, tri par date d'entrée (plus ancien d'abord)
3. Déduction de la quantité des lots dans l'ordre jusqu'à épuisement
4. Mise à jour de `quantiteRestante` de chaque lot
5. Création d'un `MouvementStock` de type `SORTIE` pour chaque lot utilisé
6. Mise à jour de `quantiteStock` du stock basé sur les lots restants
7. Si stock insuffisant dans les lots, déduction du stock général avec création d'un mouvement

---

## 7. FICHIERS CLÉS

- **Facturation :**
  - `app/Models/Facture.php`
  - `app/Models/Detailfacturepatient.php`
  - `app/Http/Livewire/ReglementFacture.php`

- **Médicaments :**
  - `app/Models/Medicament.php`
  - `app/Http/Livewire/MedicamentManager.php`
  - `app/Http/Livewire/MedicamentSearch.php`

- **Stock :**
  - `app/Models/StockMedicament.php`
  - `app/Models/LotMedicament.php`
  - `app/Models/MouvementStock.php`
  - `app/Http/Livewire/PharmacieManager.php`

---

## 8. CONCLUSION

Le système de facturation et de vente de médicaments est maintenant complet avec :
- ✅ Gestion complète de la facturation (actes, médicaments, analyses, radios)
- ✅ Gestion du stock avec lots et dates d'expiration
- ✅ Déduction automatique du stock lors du paiement complet
- ✅ Méthode FIFO pour la gestion des lots
- ✅ Traçabilité complète via les mouvements de stock
- ✅ Protection contre les doubles déductions

Le système est prêt pour une utilisation en production avec une gestion complète du stock.

