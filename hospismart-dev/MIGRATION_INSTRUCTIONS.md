# Instructions pour les migrations (base hospismart existante)

Votre base **hospismart** a été créée à partir d’un export SQL (ou d’une ancienne migration). Les tables `campagne`, `user`, etc. existent déjà.

Doctrine essaie de réexécuter la migration `Version20260201200732` qui recrée ces tables → erreur **"Table already exists"**.

## Solution en 3 étapes

Exécutez les commandes **dans l’ordre** dans le terminal (à la racine du projet).

### 1. Synchroniser le stockage des migrations

```bash
php bin/console doctrine:migrations:sync-metadata-storage
```

Répondez `yes` si demandé.

### 2. Marquer l’ancienne migration comme déjà exécutée

Cela indique à Doctrine de ne pas réexécuter la migration qui crée les tables déjà présentes :

```bash
php bin/console doctrine:migrations:version "DoctrineMigrations\Version20260201200732" --add
```

Répondez `yes` si demandé.

### 3. Exécuter la migration des événements

Seule la migration qui crée les tables **evenement** et **participant_evenement** sera exécutée :

```bash
php bin/console doctrine:migrations:migrate
```

Répondez `yes` si demandé.

---

## Résumé des commandes (dans l’ordre)

| Étape | Commande |
|--------|----------|
| 1 | `php bin/console doctrine:migrations:sync-metadata-storage` |
| 2 | `php bin/console doctrine:migrations:version "DoctrineMigrations\Version20260201200732" --add` |
| 3 | `php bin/console doctrine:migrations:migrate` |

Après cela, les tables `evenement` et `participant_evenement` seront créées sans toucher aux tables existantes.
