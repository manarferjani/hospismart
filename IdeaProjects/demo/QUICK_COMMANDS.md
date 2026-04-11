# 🚀 Commandes rapides - Compilation et déploiement

**Date**: 11 Avril 2026  
**Projet**: HospiSmart v2.0  

---

## 🔧 Compilations

### Compilation complète
```bash
mvn clean package
```
**Résultat**: Compile l'application complète et crée un JAR

### Compilation rapide (sans tests)
```bash
mvn clean compile
```
**Résultat**: Compile uniquement le code Java

### Compilation avec tests
```bash
mvn clean package -DskipTests=false
```
**Résultat**: Compile et exécute les tests unitaires

---

## 🧪 Exécution

### Lancer l'application
```bash
mvn javafx:run
```
**Résultat**: Lance l'application en mode développement

### Lancer avec debug
```bash
mvn javafx:run -X
```
**Résultat**: Lance avec logs détaillés

### Lancer le JAR généré
```bash
java -jar target/demo-1.0-SNAPSHOT.jar
```
**Résultat**: Lance le JAR packaging final

---

## 📦 Packaging

### Créer un JAR exécutable
```bash
mvn clean package
```
**Résultat**: Crée `target/demo-1.0-SNAPSHOT.jar`

### Créer un JAR sans tests
```bash
mvn clean package -DskipTests
```
**Résultat**: Plus rapide, ommet les tests

### Créer un JAR avec optimisation
```bash
mvn clean package -DskipTests -Djavafx.maven.plugin.version=0.0.8
```
**Résultat**: JAR optimisé pour la production

---

## 🔍 Vérification

### Vérifier les dépendances
```bash
mvn dependency:tree
```
**Résultat**: Affiche l'arbre des dépendances

### Vérifier la compilation
```bash
mvn clean compile
```
**Résultat**: Si succès, pas d'erreurs

### Vérifier le JAR
```bash
jar -tf target/demo-1.0-SNAPSHOT.jar | grep -E "\.fxml|\.css|\.class"
```
**Résultat**: Vérifie que les fichiers sont bien inclus

---

## 📊 Rapport

### Générer un rapport de dépendances
```bash
mvn site
```
**Résultat**: Crée un rapport HTML dans `target/site`

### Afficher les plugins
```bash
mvn help:describe
```
**Résultat**: Liste les plugins disponibles

---

## 🧹 Nettoyage

### Supprimer le répertoire target
```bash
mvn clean
```
**Résultat**: Efface tous les artefacts compilés

### Nettoyer les caches locaux
```bash
mvn clean && rm -rf ~/.m2/repository/com/example/demo
```
**Résultat**: Réinitialise complètement le projet

---

## 📝 Workflow complet

### Développement itératif
```bash
mvn clean compile && mvn javafx:run
```

### Avant commit
```bash
mvn clean package -DskipTests && mvn site
```

### Avant déploiement
```bash
mvn clean package -DskipTests=false
```

---

## 🔗 Chemins importants

| Chemin | Contenu |
|--------|---------|
| `target/demo-1.0-SNAPSHOT.jar` | JAR exécutable |
| `target/classes` | Classes compilées |
| `target/site/index.html` | Rapport du projet |
| `pom.xml` | Configuration Maven |

---

## 📋 Fichiers de configuration

### Maven
- `pom.xml` - Configuration principale

### Java
- `src/main/java` - Code source
- `src/main/resources` - Ressources (FXML, CSS)

### Tests
- `src/test/java` - Tests unitaires

---

## ✅ Checklist pré-déploiement

```bash
# 1. Nettoyer
mvn clean

# 2. Vérifier les dépendances CVE
# (Consult DEPLOYMENT_CHECKLIST.md)

# 3. Compiler
mvn compile

# 4. Tester
mvn test

# 5. Packager
mvn package

# 6. Vérifier le JAR
jar -tf target/demo-1.0-SNAPSHOT.jar

# 7. Tester en local
mvn javafx:run

# 8. Vérifier les logs
# Pas d'erreurs = OK ✅
```

---

## 🚨 Troubleshooting

### Erreur: "JAVA_HOME not found"
```bash
# Windows
set JAVA_HOME=C:\Program Files\Java\jdk21
mvn clean compile

# Linux/Mac
export JAVA_HOME=/usr/libexec/java_home -v 21
mvn clean compile
```

### Erreur: "Maven not found"
```bash
# Installer Maven ou utiliser le wrapper
./mvnw clean compile  # Linux/Mac
mvnw.cmd clean compile  # Windows
```

### Erreur: "Port 8080 already in use"
```bash
# Utiliser un port différent (si applicable)
mvn javafx:run -Dport=8081
```

### Erreur de compilation FXML
```bash
# Vérifier la syntaxe FXML
mvn clean compile -X | grep FXML
```

---

## 📚 Ressources

### Documentation Maven
https://maven.apache.org/

### Documentation JavaFX Maven Plugin
https://openjfx.io/openjfx-docs/

### Documentation JavaFX
https://openjfx.io/

---

## 💾 Sauvegarde du projet

```bash
# Créer une archive du projet
tar -czf hospismart-v2.0-backup.tar.gz demo/

# Ou en ZIP
zip -r hospismart-v2.0-backup.zip demo/
```

---

## 🎯 Commandes par cas d'usage

### Je veux juste compiler
```bash
mvn clean compile
```

### Je veux tester l'app
```bash
mvn clean javafx:run
```

### Je veux créer un JAR
```bash
mvn clean package
```

### Je veux tout vérifier avant déployer
```bash
mvn clean package && mvn site
```

### Je veux nettoyer et recommencer
```bash
mvn clean
```

---

## ✨ Astuces avancées

### Compiler rapidement (offline mode)
```bash
mvn clean compile -o
```

### Compiler en parallèle
```bash
mvn clean compile -T 4
```

### Voir les détails de compilation
```bash
mvn clean compile -X
```

### Ignorer les tests
```bash
mvn clean package -DskipTests
```

---

## 📊 Résumé des commandes principales

| Commande | Utilité | Temps |
|----------|---------|-------|
| `mvn clean` | Nettoie | 1s |
| `mvn compile` | Compile | 30s |
| `mvn test` | Tests | 2-5s |
| `mvn package` | Package | 30s |
| `mvn javafx:run` | Exécute | 5s + app |
| `mvn site` | Rapport | 10s |

---

## 🔐 Sécurité

### Vérifier les vulnérabilités
```bash
mvn dependency:check
```
(Nécessite le plugin OWASP)

### Voir les dépendances outdated
```bash
mvn versions:display-dependency-updates
```

---

## 📞 Support

Si vous avez des problèmes:

1. Consultez `DEPLOYMENT_CHECKLIST.md`
2. Vérifiez les logs de compilation
3. Consultez `DOCUMENTATION_INDEX.md`
4. Contactez l'équipe DevOps

---

**Dernière mise à jour**: 11 Avril 2026  
**Version**: 2.0  
**Status**: ✅ Prêt à l'emploi

