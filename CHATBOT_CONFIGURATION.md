# Configuration du Chatbot IA Gemini

## 🔑 Obtenir une clé API Gemini (GRATUIT)

Le chatbot utilise l'API Google Gemini pour aider les patients à rédiger leurs réclamations.

### Étapes pour obtenir votre clé API gratuite :

1. **Allez sur Google AI Studio** : https://aistudio.google.com/app/apikey

2. **Connectez-vous** avec votre compte Google

3. **Cliquez sur "Create API Key"** (Créer une clé API)

4. **Copiez** la clé générée (elle ressemble à `AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXX`)

5. **Créez le fichier `.env.local`** dans le dossier du projet :
   ```bash
   cd c:\Users\User\Downloads\hospismart-integrationFinale\hospismart-integrationFinale
   ```

6. **Ajoutez votre clé** dans `.env.local` :
   ```env
   GEMINI_API_KEY=VOTRE_VRAIE_CLE_ICI
   ```

7. **Videz le cache Symfony** :
   ```bash
   php bin/console cache:clear
   ```

8. **Rechargez la page** et testez le chatbot !

## ✨ Fonctionnalités du Chatbot

### 3 Modes d'Assistance

1. **Mode Guidé** 🎯
   - Questions une par une
   - Parfait pour les patients qui préfèrent être guidés

2. **Mode Libre** 💬
   - Description complète en une fois
   - Génération automatique de toutes les suggestions

3. **Discussion Libre** 🗨️
   - Conversation ouverte
   - Support émotionnel et conseils

### Détection de l'État Mental

Le chatbot analyse automatiquement l'état émotionnel du patient :
- 😌 **Calme** : Patient posé et factuel
- 😤 **Frustré** : Agacement ou impatience
- 😠 **En colère** : Mécontentement, ton agressif
- 😰 **Anxieux** : Inquiétude, stress
- 😢 **Triste** : Tristesse, découragement
- 😊 **Satisfait** : Patient content

**L'état mental est sauvegardé avec la réclamation** pour que l'administrateur puisse mieux répondre aux besoins du patient.

### Auto-remplissage Intelligent

Après la conversation, le chatbot peut :
- Suggérer un titre approprié
- Recommander une catégorie
- Estimer la priorité
- Rédiger une description structurée

### Détection de Langage Inapproprié

- Validation en temps réel
- Blocage de la soumission si mots inappropriés détectés
- Liste complète français/anglais

## 🚀 Test de l'API Gemini

Pour vérifier que l'API fonctionne :

```bash
php bin/console debug:container gemini_api_key
```

## 📖 Documentation API

- **Google Gemini Documentation** : https://ai.google.dev/docs
- **API Pricing** : Gratuit jusqu'à 60 requêtes/minute
- **Modèle utilisé** : `gemini-2.0-flash-exp` (rapide et performant)

## ⚠️ Important

- Ne commitez JAMAIS votre clé API dans Git
- `.env.local` est dans `.gitignore` par défaut
- La clé gratuite est suffisante pour le développement et tests

## 🎓 Présentation de Soutenance

Fonctionnalités à démontrer :
1. Sélection du mode d'assistance
2. Interaction avec le chatbot
3. Détection de l'état mental (badge coloré)
4. Auto-remplissage des champs
5. Validation des bad words
6. Sauvegarde de l'état mental en base de données
7. Visualisation par l'admin de l'état mental du patient
