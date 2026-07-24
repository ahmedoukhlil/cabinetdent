# Tables de Stock - Documentation

## Vue d'ensemble

L'application utilise **3 tables principales** pour la gestion du stock de médicaments :

---

## 1. Table `stock_medicaments`

**Modèle :** `StockMedicament`  
**Clé primaire :** `idStock`

### Colonnes principales :

| Colonne | Type | Description |
|---------|------|-------------|
| `idStock` | INT | Identifiant unique du stock |
| `fkidMedicament` | INT | Référence au médicament (FK vers `medicaments.IDMedic`) |
| `fkidCabinet` | INT | Référence au cabinet (FK vers `cabinets.idCabinet`) |
| `quantiteStock` | FLOAT | Quantité totale en stock |
| `quantiteMin` | FLOAT | Seuil minimum d'alerte |
| `prixAchat` | FLOAT | Prix d'achat moyen pondéré |
| `prixVente` | FLOAT | Prix de vente |
| `dateDerniereEntree` | DATETIME | Date de la dernière entrée de stock |
| `dateDerniereSortie` | DATETIME | Date de la dernière sortie de stock |
| `Masquer` | INT | Indicateur de suppression logique (0 = actif, 1 = masqué) |

### Relations :
- **Belongs To :** `Medicament`, `Cabinet`
- **Has Many :** `LotMedicament`, `MouvementStock`

### Fonctionnalités :
- Gestion du stock global par médicament et par cabinet
- Calcul automatique du prix d'achat moyen pondéré
- Alertes de stock faible
- Calcul de la valeur du stock

---

## 2. Table `lots_medicaments`

**Modèle :** `LotMedicament`  
**Clé primaire :** `idLot`

### Colonnes principales :

| Colonne | Type | Description |
|---------|------|-------------|
| `idLot` | INT | Identifiant unique du lot |
| `fkidStock` | INT | Référence au stock (FK vers `stock_medicaments.idStock`) |
| `fkidMedicament` | INT | Référence au médicament (FK vers `medicaments.IDMedic`) |
| `numeroLot` | VARCHAR | Numéro de lot (optionnel) |
| `quantiteInitiale` | FLOAT | Quantité initiale du lot |
| `quantiteRestante` | FLOAT | Quantité restante dans le lot |
| `dateExpiration` | DATE | Date d'expiration du lot (optionnel) |
| `dateEntree` | DATETIME | Date d'entrée du lot en stock |
| `prixAchatUnitaire` | FLOAT | Prix d'achat unitaire du lot |
| `fournisseur` | VARCHAR | Nom du fournisseur (optionnel) |
| `referenceFacture` | VARCHAR | Référence de la facture d'achat (optionnel) |
| `fkidUser` | INT | Utilisateur qui a créé le lot |
| `Masquer` | INT | Indicateur de suppression logique (0 = actif, 1 = masqué) |

### Relations :
- **Belongs To :** `StockMedicament`, `Medicament`, `User`
- **Has Many :** `MouvementStock`

### Fonctionnalités :
- Gestion des lots avec dates d'expiration
- Traçabilité FIFO (First In First Out)
- Gestion des fournisseurs et références
- Alertes d'expiration (expirés et expirant bientôt)

---

## 3. Table `mouvements_stock`

**Modèle :** `MouvementStock`  
**Clé primaire :** `idMouvement`

### Colonnes principales :

| Colonne | Type | Description |
|---------|------|-------------|
| `idMouvement` | INT | Identifiant unique du mouvement |
| `fkidStock` | INT | Référence au stock (FK vers `stock_medicaments.idStock`) |
| `fkidMedicament` | INT | Référence au médicament (FK vers `medicaments.IDMedic`) |
| `fkidLot` | INT | Référence au lot (FK vers `lots_medicaments.idLot`, optionnel) |
| `typeMouvement` | VARCHAR | Type : `ENTREE`, `SORTIE`, `AJUSTEMENT` |
| `quantite` | FLOAT | Quantité du mouvement (positive ou négative) |
| `prixUnitaire` | FLOAT | Prix unitaire au moment du mouvement |
| `montantTotal` | FLOAT | Montant total (quantité × prix unitaire) |
| `motif` | VARCHAR | Motif du mouvement |
| `fkidFacture` | INT | Référence à la facture (FK vers `facture.Idfacture`, optionnel) |
| `fkidDetailFacture` | INT | Référence au détail de facture (FK vers `detailfacturepatient.idDetfacture`, optionnel) |
| `fkidPatient` | INT | Référence au patient (FK vers `patients.ID`, optionnel) |
| `fkidUser` | INT | Utilisateur qui a effectué le mouvement |
| `dateMouvement` | DATETIME | Date et heure du mouvement |
| `reference` | VARCHAR | Référence externe (ex: numéro de facture) |
| `notes` | TEXT | Notes supplémentaires |

### Relations :
- **Belongs To :** `StockMedicament`, `Medicament`, `LotMedicament`, `Facture`, `Detailfacturepatient`, `Patient`, `User`

### Types de mouvements :
- **ENTREE** : Ajout de stock (achat, réception)
- **SORTIE** : Retrait de stock (vente, facturation)
- **AJUSTEMENT** : Correction d'inventaire

### Fonctionnalités :
- Traçabilité complète de tous les mouvements
- Lien avec les factures et patients
- Historique des entrées/sorties
- Calcul des montants

---

## Relations entre les tables

```
medicaments (IDMedic)
    ↓
stock_medicaments (fkidMedicament)
    ↓
    ├── lots_medicaments (fkidStock)
    │       ↓
    │       └── mouvements_stock (fkidLot)
    │
    └── mouvements_stock (fkidStock)
            ↓
            ├── facture (fkidFacture)
            ├── detailfacturepatient (fkidDetailFacture)
            └── patients (fkidPatient)
```

---

## Flux de données

### Entrée de stock :
1. Création/mise à jour de `stock_medicaments`
2. Création d'un `lots_medicaments` (si date d'expiration)
3. Création d'un `mouvements_stock` (type: ENTREE)

### Sortie de stock (vente) :
1. Déduction dans `lots_medicaments` (FIFO)
2. Déduction dans `stock_medicaments`
3. Création d'un `mouvements_stock` (type: SORTIE) lié à la facture

### Suppression de facture :
1. Restauration des quantités dans `lots_medicaments` et `stock_medicaments`
2. Suppression des `mouvements_stock` liés

---

## Utilisation dans l'application

### Composants Livewire utilisant ces tables :
- `PharmacieManager` : Dashboard et gestion du stock
- `MedicamentManager` : Liste des médicaments avec stock
- `ReglementFacture` : Déduction du stock lors de la facturation

### Fonctionnalités principales :
- ✅ Dashboard de suivi de stock
- ✅ Gestion des entrées de stock
- ✅ Déduction automatique lors de la facturation
- ✅ Gestion des lots avec dates d'expiration
- ✅ Alertes (stock faible, lots expirés)
- ✅ Historique des mouvements
- ✅ Traçabilité complète

---

## Notes importantes

1. **FIFO (First In First Out)** : Les sorties de stock utilisent toujours les lots les plus anciens en premier
2. **Suppression logique** : Les enregistrements sont marqués comme masqués (`Masquer = 1`) plutôt que supprimés physiquement
3. **Intégrité référentielle** : Tous les mouvements sont liés à un stock, un médicament et un utilisateur
4. **Traçabilité** : Chaque mouvement peut être lié à une facture, un patient et un détail de facture

