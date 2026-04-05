# Audit de Validation Symfony - 100% Côté Serveur

**Date**: 6 février 2026
**Statut**: ✅ VALIDÉ - Toute validation est effectuée côté serveur avec Symfony

---

## 1. Configuration des Entités (Validation au niveau du modèle)

### ✅ `src/Entity/Reclamation.php`
Contraintes Symfony définies pour TOUS les champs:
- **titre**: `@Assert\NotBlank` + `@Assert\Length(min: 5, max: 255)`
- **description**: `@Assert\NotBlank` + `@Assert\Length(min: 10, max: 5000)`
- **email**: `@Assert\NotBlank` + `@Assert\Email`
- **nomPatient**: `@Assert\NotBlank` + `@Assert\Length(min: 2, max: 255)`
- **statut**: `@Assert\NotBlank` + `@Assert\Choice(['En attente', 'En cours', 'Traité'])`
- **categorie**: `@Assert\Length(max: 100)` - Optionnel

### ✅ `src/Entity/Reponse.php`
Contraintes Symfony définies pour TOUS les champs:
- **contenu**: `@Assert\NotBlank` + `@Assert\Length(min: 10, max: 5000)`
- **adminNom**: `@Assert\NotBlank` + `@Assert\Length(min: 2, max: 255)`
- **adminEmail**: `@Assert\NotBlank` + `@Assert\Email`
- **reclamation**: `@Assert\NotNull` (relation obligatoire)

---

## 2. Configuration des Formulaires (Pas de validation HTML)

### ✅ `src/Form/ReclamationType.php`
- **Email**: Utilise `TextType::class` (pas `EmailType::class`)
  - `type="email"` ajouté uniquement dans `attr` pour l'affichage
  - Pas de validation HTML5 car le `type` dans `attr` n'est pas traité comme validation
- **Validation HTML5 DÉSACTIVÉE**: Aucun attribut `minlength`, `maxlength`, `required`, `pattern`
- Tous les champs utilisent le type de base: `TextType`, `TextareaType`, `ChoiceType`

### ✅ `src/Form/ReponseType.php`
- **Email**: Utilise `TextType::class` (pas `EmailType::class`)
  - `type="email"` ajouté uniquement dans `attr` pour l'affichage
- **Validation HTML5 DÉSACTIVÉE**: Aucun attribut `minlength`, `maxlength`, `required`, `pattern`

---

## 3. Templates Front Office - Désactivation de la Validation HTML5

### ✅ `templates/front_office/nouvelle_reclamation.html.twig`
```twig
{{ form_start(form, {'attr': {'novalidate': 'novalidate'}}) }}
```
- ✅ `novalidate` présent
- ✅ Affichage des erreurs Symfony avec boucle sur `form.*.vars.errors`
- ✅ Pas d'attributs HTML5 de validation

### ✅ `templates/front_office/modifier_reclamation.html.twig`
```twig
{{ form_start(form, {'attr': {'novalidate': 'novalidate'}}) }}
```
- ✅ `novalidate` présent
- ✅ Affichage des erreurs Symfony détaillé
- ✅ Classes `is-invalid` appliquées dynamiquement

---

## 4. Templates Back Office - Désactivation de la Validation HTML5

### ✅ `templates/back_office/repondre_reclamation.html.twig`
```twig
{{ form_start(form, {'attr': {'novalidate': 'novalidate'}}) }}
```
- ✅ `novalidate` présent
- ✅ Affichage complet des erreurs Symfony
- ✅ Gestion des erreurs au niveau du formulaire et des champs

### ✅ `templates/reponse/new.html.twig`
```twig
{{ form_start(form, {'attr': {'novalidate': 'novalidate'}}) }}
```
- ✅ `novalidate` présent
- ✅ Affichage des erreurs globales du formulaire
- ✅ Affichage des erreurs par champ avec classe `is-invalid`

### ✅ `templates/reponse/edit.html.twig`
```twig
{{ form_start(form, {'attr': {'novalidate': 'novalidate'}}) }}
```
- ✅ `novalidate` présent
- ✅ Affichage expert des erreurs Symfony
- ✅ Style de feedback invalid appliqué

---

## 5. Flux de Validation Complet

```
1. Utilisateur soumet le formulaire
          ↓
2. Attribut novalidate bloque TOUTE validation HTML5
          ↓
3. Requête HTTP est envoyée au serveur (PHP/Symfony)
          ↓
4. Symfony valide les données avec les @Assert constraints
          ↓
5. Si erreurs = Re-rendu la page avec les messages Symfony
   Si succès = Traitement des données
```

---

## 6. Checklist de Sécurité - 100% Serveur

- ✅ **Aucun `required` attribute** sur les inputs HTML
- ✅ **Aucun `minlength` attribute** sur les inputs HTML
- ✅ **Aucun `maxlength` attribute** sur les inputs HTML
- ✅ **Aucun `pattern` attribute** sur les inputs HTML
- ✅ **Aucun validation HTML5** (type="email", type="number", etc. comme validation)
- ✅ **Tous les formulaires ont `novalidate`**
- ✅ **Toutes les contraintes sont dans les @Assert de l'entité**
- ✅ **Les erreurs sont affichées uniquement par Symfony**
- ✅ **Pas de JavaScript de validation**
- ✅ **La validation email est gérée par @Assert\Email Symfony**
- ✅ **Les champs de longueur sont gérés par @Assert\Length Symfony**

---

## 7. Messages d'Erreur Symfony

Tous les messages d'erreur sont définis dans les contraintes:
```php
#[Assert\NotBlank(message: 'Le titre ne peut pas être vide')]
#[Assert\Email(message: 'L\'adresse email "{{ value }}" est invalide')]
#[Assert\Length(
    min: 5,
    minMessage: 'Le titre doit contenir au moins {{ limit }} caractères',
    maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères'
)]
```

Ces messages s'affichent **UNIQUEMENT** après validation serveur avec Symfony.

---

## 8. Affichage des Erreurs dans les Templates

Tous les templates utilisent:
```twig
{% if form.nomChamp.vars.errors|length > 0 %}
    <div class="invalid-feedback d-block">
        {% for error in form.nomChamp.vars.errors %}
            {{ error.message }}
        {% endfor %}
    </div>
{% endif %}
```

Cela garantit que seules les erreurs **validées par Symfony** sont affichées.

---

## 9. Conclusion

✅ **GARANTIE 100% SYMFONY**:
- Zéro validation HTML5
- Zéro validation JavaScript
- Zéro contournement possible (la validation HTML5 a un `novalidate` global)
- Toute validation est effectuée côté serveur avec les @Assert constraints Symfony
- Tous les messages d'erreur proviennent de Symfony uniquement

**Impossible de soumettre un formulaire valide sans passer par Symfony!**

