# Module Gestion des Événements - OASIS

## 📋 Description

Module complet de gestion des événements hospitaliers pour le projet OASIS (Outil d'Assistance et de Suivi Intelligent de Santé).

## ✅ Fonctionnalités Implémentées

### 1. Entités
- **Evenement** : Gestion des événements avec toutes les propriétés requises
- **ParticipantEvenement** : Gestion des participants aux événements

### 2. CRUD Complet
- ✅ Création, lecture, mise à jour, suppression pour les deux entités
- ✅ Formulaires Symfony avec validation
- ✅ Templates AdminLTE intégrés

### 3. Validations Symfony
- ✅ Validation des champs obligatoires (Assert\NotBlank)
- ✅ Validation des longueurs (Assert\Length)
- ✅ Validation des choix (Assert\Choice)
- ✅ Validation des dates (Assert\GreaterThan)
- ✅ Validation des expressions (Assert\Expression pour date_fin > date_debut)
- ✅ Validation des montants (Assert\GreaterThanOrEqual)

### 4. Fonctionnalités Avancées
- ✅ Recherche par terme (titre, description, lieu)
- ✅ Filtres par type d'événement
- ✅ Filtres par statut
- ✅ Tri par date de début
- ✅ API REST pour les événements

### 5. Templates
- ✅ Back Office avec AdminLTE
- ✅ Front Office public pour affichage des événements
- ✅ Navigation fonctionnelle entre les pages

## 🗂️ Structure des Fichiers

```
src/
├── Entity/
│   ├── Evenement.php                    ✅ Créé
│   └── ParticipantEvenement.php         ✅ Créé
├── Repository/
│   ├── EvenementRepository.php          ✅ Créé (avec recherche)
│   └── ParticipantEvenementRepository.php ✅ Créé
├── Controller/
│   ├── EvenementController.php         ✅ Créé
│   ├── ParticipantEvenementController.php ✅ Créé
│   └── Api/
│       └── EvenementApiController.php   ✅ Créé
└── Form/
    ├── EvenementType.php                ✅ Créé
    └── ParticipantEvenementType.php     ✅ Créé

templates/
├── base.html.twig                       ✅ Modifié (AdminLTE)
├── evenement/
│   ├── index.html.twig                  ✅ Créé
│   ├── new.html.twig                    ✅ Créé
│   ├── show.html.twig                   ✅ Créé
│   └── edit.html.twig                   ✅ Créé
├── participant_evenement/
│   ├── index.html.twig                  ✅ Créé
│   ├── new.html.twig                    ✅ Créé
│   ├── show.html.twig                   ✅ Créé
│   └── edit.html.twig                   ✅ Créé
└── front/
    └── evenements.html.twig             ✅ Créé

migrations/
└── Version20260208000000.php            ✅ Créé
```

## 🚀 Installation et Configuration

### 1. Exécuter la Migration

```bash
php bin/console doctrine:migrations:migrate
```

### 2. Vérifier les Routes

```bash
php bin/console debug:router | grep evenement
```

### 3. Accéder aux Pages

- **Back Office - Liste des événements** : `/evenement`
- **Back Office - Nouvel événement** : `/evenement/new`
- **Back Office - Participants** : `/participant/evenement`
- **Front Office - Événements publics** : `/evenement/public`
- **API - Prochains événements** : `/api/evenements/prochains`
- **API - Détails événement** : `/api/evenements/{id}`

## 📊 Structure de la Base de Données

### Table `evenement`
- `id` (INT, PRIMARY KEY)
- `titre` (VARCHAR 255)
- `description` (TEXT)
- `type_evenement` (VARCHAR 50) : réunion, formation, visite, maintenance, autre
- `date_debut` (DATETIME)
- `date_fin` (DATETIME)
- `lieu` (VARCHAR 255)
- `statut` (VARCHAR 50) : planifié, en_cours, terminé, annulé
- `budget_alloue` (DECIMAL 10,2, nullable)
- `createur_id` (INT, FOREIGN KEY vers `user`)

### Table `participant_evenement`
- `id` (INT, PRIMARY KEY)
- `evenement_id` (INT, FOREIGN KEY vers `evenement`)
- `participant_id` (INT, FOREIGN KEY vers `user`)
- `role` (VARCHAR 50) : organisateur, intervenant, participant, observateur
- `confirme_presence` (BOOLEAN, default: false)
- `date_confirmation` (DATETIME, nullable)
- **Contrainte unique** : (evenement_id, participant_id)

## 🔗 Relations

- `Evenement` (1) ↔ (Many) `ParticipantEvenement`
- `User` (1) ↔ (Many) `ParticipantEvenement`
- `User` (1) ↔ (Many) `Evenement` (comme créateur)

## 🧪 Scénario de Test

1. **Créer un événement** :
   - Aller sur `/evenement/new`
   - Remplir le formulaire avec :
     - Titre : "Formation RCP"
     - Type : "formation"
     - Dates futures
     - Lieu : "Amphithéâtre A"
   - Valider

2. **Ajouter des participants** :
   - Aller sur `/participant/evenement/new`
   - Sélectionner l'événement créé
   - Sélectionner un médecin de la liste
   - Choisir un rôle
   - Valider

3. **Tester la validation** :
   - Essayer de créer un événement avec une date passée
   - Vérifier que Symfony affiche une erreur

4. **Tester la recherche** :
   - Aller sur `/evenement`
   - Utiliser le formulaire de recherche
   - Filtrer par type ou statut

5. **Tester le Front Office** :
   - Aller sur `/evenement/public`
   - Vérifier l'affichage des événements

6. **Tester l'API** :
   - Accéder à `/api/evenements/prochains`
   - Vérifier le retour JSON

## 📝 Notes Importantes

1. **Utilisateurs** : Le système utilise la table `user` existante. Assurez-vous d'avoir au moins un utilisateur dans la base de données.

2. **Créateur par défaut** : Dans le contrôleur `EvenementController`, le premier utilisateur de la base est utilisé par défaut comme créateur. À adapter selon votre système d'authentification.

3. **Validation des dates** : La validation `GreaterThan('today')` sur `date_debut` peut être trop stricte selon vos besoins. Vous pouvez la modifier dans `src/Entity/Evenement.php`.

4. **AdminLTE** : Le template utilise AdminLTE 3.2 via CDN. Pour une utilisation en production, considérez télécharger les fichiers localement.

## 🎯 Points de Validation du Professeur

✅ **Template Front Office & Back Office** : Implémenté avec AdminLTE
✅ **CRUD avec 2 entités** : Evenement et ParticipantEvenement
✅ **1 relation** : Evenement ↔ ParticipantEvenement
✅ **Validation Symfony** : Toutes les validations utilisent Assert (pas de HTML/JS)
✅ **Fonctionnalités avancées** : Recherche, tri, API REST
✅ **Intégration sur une machine** : Prêt pour déploiement
✅ **GitHub** : Code prêt à être commité

## 🔧 Commandes Utiles

```bash
# Créer la base de données (si nécessaire)
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Vider le cache
php bin/console cache:clear

# Vérifier les routes
php bin/console debug:router

# Vérifier la configuration Doctrine
php bin/console doctrine:schema:validate
```

## 📞 Support

Pour toute question ou problème, vérifier :
1. Les logs Symfony : `var/log/dev.log`
2. La configuration Doctrine : `config/packages/doctrine.yaml`
3. Les routes disponibles : `php bin/console debug:router`

---

**Module créé le** : 08/02/2026  
**Version** : 1.0.0  
**Auteur** : Module OASIS - Gestion des Événements
