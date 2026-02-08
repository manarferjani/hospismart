# Guide de Validation des Formulaires avec Symfony Validator

## Vue d'ensemble
Tous les formulaires de l'application utilisent maintenant le système de validation Symfony via les contraintes (Constraints) définies dans les entités Doctrine.

## Entités avec validation

### User (Utilisateur)
- **nom**: Non vide, 3-180 caractères
- **prenom**: Non vide, 2-255 caractères  
- **email**: Non vide, format email valide
- **password**: Non vide, minimum 6 caractères
- **telephone**: Format téléphone valide (optionnel)

### Patient
- **date_naissance**: Non vide, pas dans le futur
- **genre**: Non vide, choix: Masculin/Féminin/Autre
- **groupe_sanguin**: Choix: O+/O-/A+/A-/B+/B-/AB+/AB- (optionnel)
- **adresse**: Maximum 1000 caractères (optionnel)
- **user**: Obligation d'associer un utilisateur

### Medecin
- **specialite**: Non vide, 2-255 caractères
- **matricule**: Non vide, minimum 3 caractères
- **telephone**: Non vide, format téléphone valide
- **user**: Obligation d'associer un utilisateur
- **service**: Obligation d'associer un service

### Consultation
- **date_heure**: Non vide, format date-heure valide
- **motif**: Non vide, 5-1000 caractères
- **observations**: Maximum 2000 caractères (optionnel)
- **statut**: Enum valide (EN_ATTENTE, EN_COURS, COMPLETEE, ANNULEE)
- **patient**: Obligation d'associer un patient
- **medecin**: Obligation d'associer un médecin

### Diagnostic
- **contenu**: Non vide, 10-5000 caractères
- **probabilite_ia**: Non vide, entre 0 et 100

### Medicament
- **nom**: Non vide, 2-255 caractères
- **quantite**: Non vide, >= 0
- **seuil_alerte**: Non vide, >= 0
- **prix_unitaire**: Non vide, > 0
- **date_peremption**: Non vide, date future

### Equipement
- **nom**: Non vide, 2-255 caractères
- **reference**: Non vide, 2-255 caractères
- **etat**: Non vide, choix: Bon/Moyen/Mauvais/Défaillant
- **relation**: Non vide, 2-255 caractères
- **service**: Obligation d'associer un service

### Service
- **nom**: Non vide, 2-255 caractères
- **description**: Non vide, 5-255 caractères

### Campagne
- **titre**: Non vide, 3-255 caractères
- **theme**: Non vide, 2-255 caractères
- **description**: Non vide, 10-5000 caractères
- **date_debut**: Non vide, date future
- **date_fin**: Non vide, après date_debut
- **budget**: Non vide, > 0

### Disponibilite
- **date_debut**: Non vide
- **date_fin**: Non vide, après date_debut
- **est_reserve**: Non vide
- **medecin**: Obligation d'associer un médecin

### ParametreVital
- **tension**: Non vide, format tensio (ex: 120/80)
- **temperature**: Non vide, entre 35°C et 45°C
- **frequence_cardiaque**: Non vide, entre 40 et 200 bpm
- **date_prise**: Non vide, pas dans le futur

## Comment utiliser la validation

### 1. Dans un contrôleur (exemple)
```php
use Symfony\Component\Validator\Validator\ValidatorInterface;

public function create(Request $request, ValidatorInterface $validator, EntityManagerInterface $em)
{
    $entity = new MyEntity();
    $entity->setName($request->request->get('name'));
    
    // Valider l'entité
    $errors = $validator->validate($entity);
    
    if (count($errors) > 0) {
        // Afficher les erreurs
        foreach ($errors as $error) {
            echo $error->getMessage();
        }
    } else {
        // Sauvegarder
        $em->persist($entity);
        $em->flush();
    }
}
```

### 2. Dans un template Twig
```twig
{% if errors %}
    <div class="alert alert-danger">
        <ul>
            {% for error in errors %}
                <li>{{ error }}</li>
            {% endfor %}
        </ul>
    </div>
{% endif %}
```

## Comment ajouter une nouvelle validation

### 1. Éditer l'entité
Ajoutez les contraintes (Constraints) au-dessus de la propriété:
```php
use Symfony\Component\Validator\Constraints as Assert;

class MyEntity
{
    #[Assert\NotBlank(message: 'Ce champ est obligatoire')]
    #[Assert\Length(min: 3, max: 50, minMessage: 'Minimum 3 caractères')]
    private ?string $name = null;
}
```

### 2. Contraintes disponibles
- `#[Assert\NotBlank]` - Champ non vide
- `#[Assert\Length(min: X, max: Y)]` - Taille de texte
- `#[Assert\Email]` - Format email
- `#[Assert\Range(min: X, max: Y)]` - Plage de nombres
- `#[Assert\Choice(choices: [...])]` - Valeur dans une liste
- `#[Assert\Regex(pattern: '...')]` - Format regex
- `#[Assert\GreaterThan(value: X)]` - Plus grand que
- `#[Assert\LessThanOrEqual(value: X)]` - Plus petit ou égal
- `#[Assert\Unique]` - Valeur unique en base de données
- `#[Assert\DateTime]` - Format date-heure valide

### 3. Personnaliser les messages
Utilisez le paramètre `message` dans la contrainte:
```php
#[Assert\NotBlank(message: 'Le {{ label }} est obligatoire')]
```

## Validation à la saisie (JavaScript)
Le système utilise aussi une validation client HTML5 dans les formulaires:
- `required` pour les champs obligatoires
- `type="email"` pour les emails
- `min`, `max` pour les nombres
- `pattern` pour les expressions régulières

## Notes importantes
- La validation est automatique lors de la sauvegarde en base de données
- Les messages d'erreur sont en français pour meilleure expérience utilisateur
- Les validations côté client ne remplacent pas la validation serveur
- Testez toujours les validations en envoyant des données invalides
