# Configuration et Setup - HospiSmart Desktop

## Prérequis

1. **Java 21** - Télécharger et installer Java 21 depuis [oracle.com](https://www.oracle.com/java/technologies/downloads/#java21) ou utiliser [OpenJDK 21](https://jdk.java.net/21/)
2. **MySQL Server** - Avoir un serveur MySQL avec la base `hospismart`
3. **IntelliJ IDEA** - Ou tout autre IDE supportant JavaFX et Maven

## Configuration IntelliJ

### Étape 1 : Configurer le JDK du projet

1. Aller à `File → Project Structure → Project`
2. Définir le **SDK** à Java 21
3. Valider avec OK

### Étape 2 : Recharger le projet Maven

1. Ouvrir `Maven` (à droite de l'écran)
2. Cliquer sur **Reload** (icône rafraîchir)
3. Attendre la compilation

### Étape 3 : Configurer la connexion MySQL

Le fichier `src/main/resources/application.properties` contient la configuration :

```properties
db.url=jdbc:mysql://localhost:3306/hospismart?useSSL=false&serverTimezone=UTC&allowPublicKeyRetrieval=true
db.username=root
db.password=
```

Si ta configuration MySQL est différente, mets à jour ces valeurs.

**Ou utilise les variables d'environnement :**

```bash
# Windows (PowerShell)
$env:DB_URL = "jdbc:mysql://localhost:3306/hospismart?useSSL=false&serverTimezone=UTC&allowPublicKeyRetrieval=true"
$env:DB_USERNAME = "root"
$env:DB_PASSWORD = ""
```

## Lancer l'application

### Option 1 : Via Maven dans IntelliJ

1. Aller à `Maven` → `demo` → `Plugins` → `javafx` → `javafx:run`
2. Double-cliquer pour lancer

### Option 2 : Via la ligne de commande

```bash
cd C:\Users\User\IdeaProjects\demo
mvnw.cmd javafx:run
```

## Structure du projet

```
demo/
├── src/main/
│   ├── java/
│   │   ├── com/example/demo/
│   │   │   ├── domain/
│   │   │   │   └── Reclamation.java          # Modèle de données
│   │   │   ├── infrastructure/
│   │   │   │   ├── database/
│   │   │   │   │   └── DatabaseConfig.java   # Config JDBC
│   │   │   │   └── repository/
│   │   │   │       └── ReclamationRepository.java  # Accès aux données
│   │   │   ├── service/
│   │   │   │   └── DatabaseService.java      # Logique métier
│   │   │   ├── HelloApplication.java         # Point d'entrée
│   │   │   ├── ReclamationController.java    # Contrôleur UI
│   │   │   └── HelloController.java          # Contrôleur ancien (peut supprimer)
│   │   └── module-info.java                  # Configuration des modules
│   └── resources/
│       ├── application.properties              # Config MySQL
│       └── com/example/demo/
│           ├── hello-view.fxml               # Vue ancienne
│           └── reclamation-view.fxml         # Vue des réclamations
└── pom.xml                                    # Dépendances Maven
```

## Dépendances installées

- **JavaFX 21.0.6** - Framework UI
- **MySQL Connector/J 9.1.0** - Driver JDBC pour MySQL
- **JUnit 5.12.1** - Tests unitaires

## Troubleshooting

### Erreur : "Module not found: java.sql"

**Solution :** Le JDK n'est pas Java 21. Aller à `File → Project Structure` et sélectionner Java 21.

### Erreur : "Connection refused"

**Solution :** Vérifier que MySQL est en cours d'exécution et que la configuration dans `application.properties` est correcte.

### Erreur : "Table reclamation not found"

**Solution :** Vérifier que la base `hospismart` existe et contient la table `reclamation`.

## Prochaines étapes

- Ajouter d'autres tables (user, diagnostic, service, etc.)
- Créer des écrans pour CRUD (Create, Read, Update, Delete)
- Ajouter une authentification
- Améliorer l'UI avec des styles CSS JavaFX

