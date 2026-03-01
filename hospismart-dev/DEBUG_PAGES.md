# Debug - Pages qui chargent indéfiniment

## Tests à effectuer

### 1. Test simple (sans template)
Accédez à : **http://localhost:9000/test**

Si cette page fonctionne → Le problème vient des templates Twig ou des CDN.

Si cette page ne fonctionne pas → Le problème vient de Symfony ou du serveur.

### 2. Vérifier la console du navigateur
1. Ouvrez les outils de développement (F12)
2. Onglet **Console** → Vérifiez les erreurs JavaScript
3. Onglet **Network** → Vérifiez si les CDN se chargent (AdminLTE, jQuery, Bootstrap)

### 3. Vérifier les logs Symfony
```bash
# Voir les logs en temps réel
tail -f var/log/dev.log

# Ou sur Windows PowerShell
Get-Content var/log/dev.log -Wait
```

### 4. Vérifier qu'il y a des utilisateurs dans la base
```bash
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM user"
```

Si le résultat est 0, créez au moins un utilisateur avant de créer un événement.

### 5. Test avec template simplifié
Si `/test` fonctionne mais `/evenement` ne fonctionne pas, le problème vient probablement :
- Des CDN AdminLTE qui ne se chargent pas
- D'une erreur dans les templates Twig
- D'une erreur dans le contrôleur (ex: pas d'utilisateurs)

## Solutions possibles

### Solution 1 : Utiliser le template simplifié temporairement
Modifiez `templates/evenement/index.html.twig` :
```twig
{% extends 'base_simple.html.twig' %}
```

### Solution 2 : Vérifier la connexion internet
Les CDN nécessitent une connexion internet. Si vous êtes hors ligne, utilisez le template simplifié.

### Solution 3 : Créer un utilisateur de test
```bash
php bin/console doctrine:query:sql "INSERT INTO user (nom, prenom, email, password, roles) VALUES ('Admin', 'Test', 'admin@test.com', '\$2y\$13\$...', '[]')"
```

## Prochaines étapes

1. Testez `/test` d'abord
2. Vérifiez la console du navigateur
3. Vérifiez les logs Symfony
4. Dites-moi ce que vous trouvez !
