# 🏥 Aide-mémoire pour la Soutenance (Oral)

Voici les réponses aux questions techniques probables que votre professeur pourrait poser pour vérifier votre **maîtrise du sujet** (Critère 4).

---

## 1. Architecture Symfony
**Q : Pourquoi avoir mis le code dans `src/Service` au lieu du Contrôleur ?**
> **R :** Pour respecter le principe de **responsabilité unique**. Le Contrôleur ne doit que diriger les requêtes. La logique métier lourde (IA, calculs de prédiction, envoi de mails) est isolée dans des **Services** réutilisables et plus faciles à tester.

**Q : C'est quoi un "Entity" et un "Repository" ?**
> **R :** L'**Entity** (`Medicament`, `User`) représente une table en base de données sous forme d'objet PHP. Le **Repository** est la classe qui contient les requêtes SQL (ex: `findWithSearch`) pour récupérer ces objets.

---

## 2. Intelligence Artificielle (Points Forts)
**Q : Comment fonctionne votre algorithme de prédiction ?**
> **R :** Il analyse les mouvements de stock (`MouvementStock`). Il calcule la somme des sorties sur les 30 derniers jours, en déduit une **consommation quotidienne moyenne**, et divise le stock actuel par cette moyenne pour obtenir les jours restants.

**Q : Pourquoi utiliser plusieurs fournisseurs d'images IA ?**
> **R :** Pour la **robustesse**. Si Hugging Face est surchargé (timeout), le code bascule automatiquement sur Pollinations, puis sur Wikipedia Commons. Cela garantit que l'application ne plante jamais.

---

## 3. Sécurité et Mailing
**Q : Comment gérez-vous l'envoi d'emails ?**
> **R :** J'utilise un `EmailService` qui s'appuie sur la bibliothèque **PHPMailer**. On utilise le protocole **SMTP** avec une authentification sécurisée via Gmail (Port 587, TLS).

**Q : Comment protégez-vous les formulaires ?**
> **R :** On utilise des **Jetons CSRF** (Cross-Site Request Forgery) générés par Symfony pour valider que les requêtes de suppression ou de modification viennent bien de notre application.

---

## 4. Design et UX
**Q : Pourquoi utiliser Symfony UX Turbo ?**
> **R :** Pour donner une impression de SPA (Single Page Application). Turbo intercepte les clics sur les liens et ne recharge que le contenu nécessaire, ce qui rend l'interface beaucoup plus fluide et "Premium".

---

*Conseil : Montrez que vous comprenez que `App\` pointe vers le dossier `src/` grâce à l'autoloader PSR-4 spécifié dans `composer.json`.*
