# HospiSmart - Guide Front Office / Back Office

## Vue d'ensemble

L'application HospiSmart est maintenant un **système multiplateforme complète** avec une séparation claire entre :
- **Front Office** : Interface client pour créer et suivre les réclamations
- **Back Office** : Interface administrateur pour répondre aux réclamations
- **Dashboard** : Vue d'ensemble et statistiques

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                  HospiSmart Desktop                          │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────────┐  ┌──────────────────┐  ┌────────────┐ │
│  │  FRONT OFFICE    │  │   BACK OFFICE    │  │ DASHBOARD  │ │
│  │                  │  │                  │  │            │ │
│  │ - Créer Récl.   │  │ - Voir Récl.     │  │ - Stats    │ │
│  │ - Modifier Récl.│  │ - Répondre       │  │ - Taux     │ │
│  │ - Supprimer Récl│  │ - Suivre         │  │ - Info     │ │
│  └──────────────────┘  └──────────────────┘  └────────────┘ │
│         │                      │                     │        │
└─────────┼──────────────────────┼─────────────────────┼────────┘
          │                      │                     │
┌─────────┴──────────────────────┴─────────────────────┴────────┐
│                    DatabaseService                            │
│  (Couche métier : gestion des opérations CRUD)                │
└─────────┬──────────────────────┬─────────────────────┬────────┘
          │                      │                     │
┌─────────┴──────┐  ┌────────────┴──────────┐  ┌──────┴────────┐
│ ReclamationRepo│  │  ReponseRepository    │  │ UserRepository│
│   (CRUD)       │  │   (CRUD)              │  │   (CRUD)      │
└─────────┬──────┘  └────────────┬──────────┘  └──────┬────────┘
          │                      │                     │
└─────────┴──────────────────────┴─────────────────────┴────────┐
│                  MySQL Database - hospismart                  │
│     (Tables: reclamation, reponse, user, service, ...)        │
└─────────────────────────────────────────────────────────────┘
```

## 1. Front Office - Création de Réclamations

**Accès** : Onglet "Front Office - Créer Réclamation"

### Fonctionnalités

#### 1.1 Ajouter une nouvelle réclamation

1. Remplissez tous les champs du formulaire :
   - **Titre** : Titre concis de la réclamation
   - **Patient** : Nom du patient
   - **Email** : Email de contact
   - **Catégorie** : Service/Hygiène/Facturation/Personnel/Médicaments
   - **Priorité** : Basse/Normale/Haute/Urgente
   - **Statut** : En attente/En cours/Résolue/Fermée/Rejetée
   - **Description** : Détails complets de la réclamation

2. Cliquez sur **"Ajouter"**
3. La réclamation est immédiatement insérée dans `hospismart.reclamation`

#### 1.2 Modifier une réclamation

1. Sélectionnez une ligne du tableau
2. Cliquez **"Modifier sélection"**
3. Le formulaire se remplit avec les données
4. Apportez vos modifications
5. Cliquez **"Ajouter"** pour sauvegarder

#### 1.3 Supprimer une réclamation

1. Sélectionnez une ligne
2. Cliquez **"Supprimer sélection"**
3. Confirmez la suppression
4. La ligne est supprimée de la base

#### 1.4 Visualiser les réclamations

- Le tableau en bas affiche **toutes les réclamations existantes**
- Cliquez **"Actualiser"** pour recharger les données

## 2. Back Office - Réponses aux Réclamations

**Accès** : Onglet "Back Office - Répondre aux Réclamations"

### Fonctionnalités

#### 2.1 Afficher les réclamations en attente

- Le panneau **gauche** affiche la liste de toutes les réclamations
- La colonne **"Réponses"** indique combien de réponses chaque réclamation a reçues
- Cliquez sur une ligne pour voir ses détails

#### 2.2 Consulter les détails d'une réclamation

Le panneau **droit** affiche :
- **Titre** et **Patient** de la réclamation
- **Description complète**
- **Historique des réponses** (liste déroulante)

#### 2.3 Répondre à une réclamation

1. Sélectionnez une réclamation dans le tableau
2. Remplissez le formulaire "Ajouter une réponse" :
   - **Auteur** : Nom du personnel répondant
   - **Contenu** : Votre réponse détaillée
   - **Statut** : En attente/En cours/Résolue/Fermée/Escaladée

3. Cliquez **"Envoyer réponse"**
4. La réponse est insérée dans `hospismart.reponse`
5. L'historique se met à jour automatiquement

#### 2.4 Gérer les réponses

- Chaque réponse affiche :
  - Date et heure
  - Nom de l'auteur
  - Statut
  - Contenu de la réponse

## 3. Dashboard - Statistiques et Suivi

**Accès** : Onglet "Dashboard"

### Affichage

#### 3.1 Cartes de statistiques (en haut)

- **Total Réclamations** : Nombre total de réclamations créées
- **Total Réponses** : Nombre total de réponses envoyées
- **Taux de Réponse** : Pourcentage de réclamations ayant au moins une réponse

#### 3.2 Informations système (en bas)

Affiche :
- Date/Heure actuelle
- Base de données et serveur
- **Statistiques détaillées** :
  - Réclamations totales
  - Réclamations avec réponse
  - Réclamations sans réponse
  - Taux de réponse en %
- Architecture du système

#### 3.3 Rafraîchissement

Cliquez **"Actualiser les statistiques"** pour mettre à jour les données

## Flux de travail complet

```
1. FRONT OFFICE
   ├─ Client crée une réclamation
   │  └─ Données → hospismart.reclamation
   │
2. BACK OFFICE
   ├─ Administrateur voit la réclamation
   ├─ Administrateur consulte les détails
   └─ Administrateur répond
      └─ Données → hospismart.reponse
      
3. DASHBOARD
   └─ Suivi des métriques globales
      ├─ Nombre de réclamations
      ├─ Nombre de réponses
      └─ Taux de réponse
```

## Structure des données MySQL

### Table `reclamation`
```sql
CREATE TABLE reclamation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255),
    description TEXT,
    date_creation DATETIME,
    email VARCHAR(255),
    nom_patient VARCHAR(255),
    statut VARCHAR(50),
    categorie VARCHAR(100),
    priorite VARCHAR(50),
    etat_mental VARCHAR(100)
);
```

### Table `reponse`
```sql
CREATE TABLE reponse (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reclamation_id INT,
    contenu_reponse TEXT,
    date_reponse DATETIME,
    auteur_reponse VARCHAR(255),
    statut VARCHAR(50),
    FOREIGN KEY (reclamation_id) REFERENCES reclamation(id)
);
```

## Synchronisation en temps réel

- **Créations** : Immédiatement visibles dans tous les écrans
- **Modifications** : Mises à jour sans rechargement manuel
- **Suppressions** : Suppression physique de la base
- **Pas de cache** : Toutes les données sont fraîches

## Bonnes pratiques

1. **Front Office** : Utilisez des catégories cohérentes
2. **Back Office** : Répondez dans un délai raisonnable
3. **Dashboard** : Consultez régulièrement les statistiques
4. **Validation** : Tous les champs obligatoires
5. **Archivage** : Fermez les réclamations traitées

## Prérequis

- Java 21+
- MySQL Server (base `hospismart`)
- Connection stable à la base de données

## Lancement

```bash
cd C:\Users\User\IdeaProjects\demo
mvnw.cmd javafx:run
```

## Extensions futures

- Notifications en temps réel
- Export PDF/Excel
- Recherche et filtrage avancés
- Gestion des priorités
- Assignation aux teams
- Historique complet des modifications

