# demo JavaFX + MySQL

Ce projet contient maintenant une base de connexion MySQL réutilisable pour l'application desktop JavaFX.

## Configuration

Par défaut, la configuration est lue depuis `src/main/resources/application.properties`.

Tu peux aussi surcharger les valeurs via des variables d'environnement :

- `DB_URL`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_DRIVER`
- `DB_LOGIN_TIMEOUT_SECONDS`

## Exemple

```properties
db.url=jdbc:mysql://localhost:3306/your_database?useSSL=false&serverTimezone=UTC&allowPublicKeyRetrieval=true
db.username=root
db.password=
db.driver=com.mysql.cj.jdbc.Driver
db.loginTimeoutSeconds=5
```

## Lancer les tests

```powershell
mvn test
```

## Étape suivante

La prochaine étape sera de créer un repository/service métier pour lire les tables MySQL de ton projet Symfony depuis JavaFX.

