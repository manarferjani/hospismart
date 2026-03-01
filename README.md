# 🏥 HospiSmart OASIS

**HospiSmart OASIS** est une solution de gestion hospitalière intelligente spécialisée dans l'optimisation des stocks de pharmacie. L'application combine la puissance de **Symfony 7** avec l'intelligence artificielle pour garantir qu'aucun médicament critique ne vienne à manquer.

---

## 🚀 Fonctionnalités Avancées

### 🤖 Intelligence Artificielle Prédictive
- **Analyse de Consommation** : Calcul en temps réel de la vitesse moyenne de sortie des médicaments sur les 30 derniers jours.
- **Prédiction de Rupture** : Algorithme prédisant le nombre de jours restants avant la rupture de stock et la date exacte estimée.
- **Alertes Prioritaires** : Dashboard intelligent isolant les risques critiques (Critique, Élevé, Moyen).

### 🖼️ Génération d'Images IA
- **Système Multi-Source** : Utilisation de modèles Generative AI (Hugging Face / Pollinations) pour remplir automatiquement les fiches médicaments avec des visuels pertinents.
- **Fallback Automatique** : Système de secours vers Wikipedia Commons en cas d'indisponibilité des serveurs d'IA.

### 📊 Expérience Premium
- **Interface Glassmorphism** : Design moderne avec effets de transparence et micro-animations.
- **Export PDF Officiel** : Génération de rapports d'état des stocks prêts à l'impression.
- **Système de Notification** : Envoi automatique d'emails d'alerte SMTP dès qu'un seuil critique est atteint.

---

## 🛠️ Stack Technique

- **Framework** : Symfony 7.4 + PHP 8.2
- **Base de données** : PostgreSQL / MySQL (Doctrine ORM)
- **Frontend** : Twig, Symfony UX Turbo, Vanilla CSS (Premium Design System)
- **Mailing** : PHPMailer via STMP (Gmail)
- **APIs** : Hugging Face Inference, Pollinations.ai, Wikipedia Commons

---

## 📖 Installation

1. Cloner le projet
2. `composer install`
3. Configurer le `.env.local` (BDD et `HF_API_TOKEN`)
4. `php bin/console doctrine:migrations:migrate`
5. `php -S 127.0.0.1:9000 -t public`

---

*Développé dans le cadre d'un projet universitaire de gestion avancée avec Symfony.*
