# HospiSmart - Application Desktop Multiplateforme

## Vue d'ensemble

Application JavaFX desktop multiplateforme liée à la base de données MySQL `hospismart` existante. Elle permet de **visualiser, créer, modifier et supprimer** des données directement dans la base de données.

## Fonctionnalités principales

### 1. Gestion des Réclamations
- **Visualiser** toutes les réclamations existantes dans un tableau
- **Ajouter** une nouvelle réclamation via un formulaire
- **Modifier** une réclamation sélectionnée
- **Supprimer** une réclamation sélectionnée
- Les données sont immédiatement synchronisées avec MySQL

### 2. Opérations supportées
- **CREATE** : Ajouter de nouvelles entrées
- **READ** : Afficher les données existantes
- **UPDATE** : Modifier les données
- **DELETE** : Supprimer les données

## Architecture

```
demo/
├── src/main/java/
│   ├── com/example/demo/
│   │   ├── domain/                  # Modèles de données
│   │   │   ├── Reclamation.java
│   │   │   ├── User.java
│   │   │   ├── Service.java
│   │   │   └── ...
│   │   ├── infrastructure/
│   │   │   ├── database/
│   │   │   │   └── DatabaseConfig.java  # Gestion de la connexion MySQL
│   │   │   └── repository/
│   │   │       ├── ReclamationRepository.java  # CRUD Réclamations
│   │   │       ├── UserRepository.java
│   │   │       └── ServiceRepository.java
│   │   ├── service/
│   │   │   └── DatabaseService.java    # Logique métier
│   │   ├── HelloApplication.java       # Point d'entrée
│   │   ├── ReclamationControllerCRUD.java  # Contrôleur CRUD
│   │   └── ...
│   └── module-info.java
├── src/main/resources/
│   ├── application.properties         # Config MySQL
│   └── com/example/demo/
│       ├── reclamation-view-new.fxml  # Interface CRUD
│       └── ...
└── pom.xml
```

## Configuration MySQL

Le fichier `src/main/resources/application.properties` contient :

```properties
db.url=jdbc:mysql://localhost:3306/hospismart?useSSL=false&serverTimezone=UTC&allowPublicKeyRetrieval=true
db.username=root
db.password=
```

**À adapter selon votre configuration MySQL locale.**

## Utilisation

### Lancer l'application

```bash
cd C:\Users\User\IdeaProjects\demo
mvnw.cmd javafx:run
```

Ou via `run.bat` :
```bash
run.bat
```

### Interface utilisateur

#### Formulaire d'ajout (en haut)
1. Remplissez tous les champs : Titre, Patient, Email, etc.
2. Sélectionnez Catégorie, Priorité et Statut dans les listes
3. Cliquez sur **"Ajouter"** pour créer une nouvelle réclamation

#### Tableau des réclamations (en bas)
1. Affiche toutes les réclamations existantes
2. **Actualiser** : Recharge les données depuis la base
3. **Modifier sélection** : Charge une ligne dans le formulaire pour la modifier
4. **Supprimer sélection** : Supprime la ligne sélectionnée

## Flux de travail CRUD

### Créer
1. Remplir le formulaire en haut
2. Cliquer "Ajouter"
3. Les données sont insérées dans `hospismart.reclamation`
4. Le tableau se raffraîchit automatiquement

### Lire
- Le tableau affiche toutes les données de la table `reclamation`
- Rechargement au démarrage et après chaque opération

### Modifier
1. Cliquer sur une ligne du tableau
2. Cliquer "Modifier sélection"
3. Le formulaire se remplir avec les données
4. Changer les valeurs
5. Cliquer "Ajouter" pour sauvegarder les modifications

### Supprimer
1. Sélectionner une ligne
2. Cliquer "Supprimer sélection"
3. Confirmer la suppression
4. La ligne est supprimée de `hospismart.reclamation`

## Validation des données

- **Tous les champs sont obligatoires**
- Les données sont validées avant insertion
- Les erreurs sont affichées dans des boîtes de dialogue

## Synchronisation avec la base

- **Temps réel** : Chaque opération (CREATE, UPDATE, DELETE) est immédiate
- **Sans cache** : Les données sont toujours à jour
- **Transactions** : Chaque opération est complète ou échouée

## Gestion des erreurs

Si une erreur de connexion MySQL apparaît :

1. Vérifiez que MySQL est en cours d'exécution
2. Vérifiez les identifiants dans `application.properties`
3. Vérifiez que la base `hospismart` existe
4. Relancez l'application

## Extensions futures

- Ajouter CRUD pour User, Service, Consultation, etc.
- Créer des écrans séparés par table (tabs/onglets)
- Ajouter la pagination pour les grandes tables
- Ajouter la recherche/filtrage
- Ajouter l'export en PDF/Excel
- Ajouter l'authentification utilisateur

## Prérequis

- Java 21+
- Maven 3.8+
- MySQL Server (base `hospismart` existante)
- JavaFX 21+

## Dépendances principales

```xml
<dependency>
    <groupId>org.openjfx</groupId>
    <artifactId>javafx-controls</artifactId>
    <version>21.0.6</version>
</dependency>
<dependency>
    <groupId>com.mysql</groupId>
    <artifactId>mysql-connector-j</artifactId>
    <version>9.1.0</version>
</dependency>
```

## Support

Pour toute question ou problème :
1. Vérifiez les logs dans la console
2. Vérifiez la connexion MySQL
3. Vérifiez que les tables existent dans la base

