<?php

namespace App\Controller\Api; // Déclaration du namespace pour le contrôleur API Chatbot

use App\Entity\Reclamation; // Import de l'entité Reclamation
use App\Entity\User; // Import de l'entité User pour le type-casting getUser()
use App\Repository\ReclamationRepository; // Import du repository pour accéder aux réclamations en base
use App\Service\ProfanityFilterService; // Import du service de filtrage de mots inappropriés
use Doctrine\ORM\EntityManagerInterface; // Import de l'EntityManager pour persister les entités
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Import du contrôleur de base Symfony
use Symfony\Component\HttpFoundation\JsonResponse; // Import de JsonResponse pour renvoyer des réponses JSON
use Symfony\Component\HttpFoundation\Request; // Import de l'objet Request pour gérer les requêtes HTTP
use Symfony\Component\Routing\Annotation\Route; // Import de l'annotation Route pour définir les routes
use Symfony\Contracts\HttpClient\HttpClientInterface; // Import du client HTTP pour appeler des API externes (Gemini)
use Psr\Log\LoggerInterface; // Import du logger pour journaliser les événements et erreurs

#[Route('/api/chatbot')] // Préfixe de route : toutes les routes commencent par /api/chatbot
class ChatbotController extends AbstractController // Contrôleur API du chatbot IA, hérite d'AbstractController
{
    // Constructeur avec injection de dépendances
    public function __construct(
        private HttpClientInterface $httpClient, // Client HTTP pour envoyer des requêtes à l'API Gemini
        private string $geminiApiKey, // Clé API Gemini configurée dans les paramètres Symfony
        private ProfanityFilterService $profanityFilter, // Service de filtrage de langage inapproprié
    ) {}

    #[Route('/reclamation', name: 'api_chatbot_reclamation', methods: ['POST'])] // Route POST pour envoyer un message au chatbot IA
    public function chat(Request $request, LoggerInterface $logger): JsonResponse // Méthode principale du chatbot : reçoit les messages et retourne la réponse de l'IA
    {
        $data      = json_decode($request->getContent(), true) ?? []; // Décode le corps JSON de la requête en tableau PHP
        $messages  = $data['messages']  ?? []; // Récupère l'historique des messages de la conversation
        $formData  = $data['formData']  ?? []; // Récupère les données actuelles du formulaire de réclamation

        // Vérifier que la clé API est configurée
        if (empty($this->geminiApiKey) // Si la clé API est vide
            || str_contains($this->geminiApiKey, 'VOTRE_CLE') // Ou contient le placeholder par défaut
            || str_contains($this->geminiApiKey, 'ICI') // Ou contient un autre placeholder
        ) {
            return $this->json([ // Retourne un message d'erreur JSON indiquant que l'API n'est pas configurée
                'message'        => '⚙️ L\'assistant IA n\'est pas encore configuré. Ajoutez votre clé API dans `.env.local` : GEMINI_API_KEY=votre_clé. Obtenez-en une gratuitement sur https://aistudio.google.com/app/apikey',
                'suggestions'    => [], // Pas de suggestions
                'not_configured' => true, // Flag indiquant que l'API n'est pas configurée
            ]);
        }

        if (empty($messages)) { // Si aucun message n'est fourni dans la requête
            return $this->json(['error' => 'Messages manquants'], 400); // Retourne une erreur 400 (Bad Request)
        }

        // Système prompt en instruction séparée (format v1beta avec systemInstruction)
        $formContext  = $this->buildFormContext($formData); // Construit le contexte du formulaire à partir des données actuelles
        $systemPrompt = $this->buildSystemPrompt($formContext); // Construit le prompt système complet pour guider l'IA

        // Construire l'historique pour Gemini
        // IMPORTANT: le tableau contents doit commencer par role=user et alterner user/model
        $contents = []; // Initialise le tableau des messages formatés pour l'API Gemini
        foreach ($messages as $msg) { // Parcourt chaque message de l'historique
            $role       = ($msg['role'] === 'user') ? 'user' : 'model'; // Convertit le rôle : 'user' reste 'user', tout le reste devient 'model'
            $content    = trim($msg['content'] ?? ''); // Récupère et nettoie le contenu du message
            if ($content === '') continue; // Ignore les messages vides
            // Sanitiser UTF-8 pour éviter les erreurs json_encode
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8'); // Convertit le contenu en UTF-8 valide
            $contents[] = [ // Ajoute le message formaté au tableau
                'role'  => $role, // Rôle du message (user ou model)
                'parts' => [['text' => $content]], // Contenu du message dans le format attendu par Gemini
            ];
        }

        // Si la liste est vide ou commence par model, ajouter une correction
        if (empty($contents)) { // Si aucun message valide après traitement
            return $this->json(['error' => 'Aucun message valide'], 400); // Retourne une erreur 400
        }
        // Assurer que le dernier message est bien de l'utilisateur
        if (end($contents)['role'] !== 'user') { // Si le dernier message n'est pas de l'utilisateur
            return $this->json(['error' => 'Dernier message doit être de l\'utilisateur'], 400); // Retourne une erreur 400 (requis par l'API Gemini)
        }

        // Payload Gemini v1beta avec systemInstruction (format officiel)
        $payload = [ // Construction du payload JSON à envoyer à l'API Gemini
            'systemInstruction' => [ // Instruction système qui guide le comportement de l'IA
                'parts' => [['text' => $systemPrompt]], // Le prompt système au format Gemini
            ],
            'contents'          => $contents, // L'historique des messages de la conversation
            'generationConfig'  => [ // Configuration de la génération de texte
                'temperature'     => 0.75, // Température : contrôle la créativité (0=déterministe, 1=créatif)
                'maxOutputTokens' => 800, // Nombre maximum de tokens dans la réponse générée
                'topP'            => 0.95, // Top-P sampling : considère les tokens les plus probables totalisant 95%
            ],
        ];

        try { // Bloc try-catch pour gérer les erreurs d'appel à l'API Gemini
            // Encoder manuellement en JSON avec gestion UTF-8 robuste
            $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); // Encode le payload en JSON avec support Unicode
            if ($jsonBody === false) { // Si l'encodage JSON échoue
                $logger->error('JSON encode failed: ' . json_last_error_msg()); // Log l'erreur d'encodage
                return $this->json([ // Retourne un message d'erreur à l'utilisateur
                    'message'     => '⚠️ Erreur d\'encodage des données. Veuillez reformuler votre message sans caractères spéciaux.',
                    'suggestions' => [], // Pas de suggestions
                ]);
            }

            $logger->info('TRACE: json_encode OK, body length=' . strlen($jsonBody)); // Log la taille du body JSON encodé

            $response = $this->httpClient->request( // Envoie la requête HTTP POST à l'API Gemini
                'POST', // Méthode HTTP
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $this->geminiApiKey, // URL de l'API Gemini avec la clé API
                [
                    'headers' => ['Content-Type' => 'application/json'], // En-tête indiquant que le corps est du JSON
                    'body'    => $jsonBody, // Le corps de la requête (payload JSON)
                    'timeout' => 30, // Timeout de 30 secondes pour la requête
                ]
            );

            $logger->info('TRACE: request sent, getting status code...'); // Log : requête envoyée, récupération du code de statut
            $statusCode = $response->getStatusCode(); // Récupère le code de statut HTTP de la réponse (200, 400, 500, etc.)
            $logger->info('TRACE: status=' . $statusCode . ', getting body...'); // Log le code de statut reçu
            
            $rawBody = $response->getContent(false); // Récupère le corps brut de la réponse (false = ne pas lancer d'exception si erreur HTTP)
            $logger->info('TRACE: raw body length=' . strlen($rawBody)); // Log la taille du corps de la réponse
            
            // Sanitiser la réponse UTF-8 avant json_decode
            $rawBody = mb_convert_encoding($rawBody, 'UTF-8', 'UTF-8'); // Convertit la réponse en UTF-8 valide
            $responseData = json_decode($rawBody, true); // Décode le JSON de la réponse en tableau PHP
            if ($responseData === null && json_last_error() !== JSON_ERROR_NONE) { // Si le décodage JSON échoue
                $logger->error('TRACE: json_decode response failed: ' . json_last_error_msg()); // Log l'erreur de décodage
                return $this->json([ // Retourne un message d'erreur
                    'message'     => '⚠️ Réponse invalide de l\'API Gemini.',
                    'suggestions' => [], // Pas de suggestions
                ]);
            }

            if ($statusCode !== 200) { // Si le code de statut HTTP n'est pas 200 (succès)
                $apiError = $responseData['error']['message'] ?? 'Erreur HTTP ' . $statusCode; // Récupère le message d'erreur de l'API
                $logger->error('Gemini API error: ' . $apiError, ['status' => $statusCode, 'payload_contents_count' => count($contents)]); // Log l'erreur Gemini avec le contexte
                return $this->json([ // Retourne l'erreur au client
                    'message'   => '⚠️ Erreur Gemini : ' . $apiError, // Message d'erreur pour l'utilisateur
                    'api_error' => $apiError, // Détail de l'erreur API
                    'suggestions' => [], // Pas de suggestions
                ]);
            }

            $finishReason = $responseData['candidates'][0]['finishReason'] ?? ''; // Récupère la raison de fin de génération (STOP, SAFETY, etc.)
            if ($finishReason === 'SAFETY') { // Si la réponse a été filtrée pour raisons de sécurité
                $text = 'Ma réponse a été filtrée pour des raisons de sécurité. Reformulez votre demande.'; // Message de remplacement
            } else { // Sinon, récupère le texte généré
                $text = $responseData['candidates'][0]['content']['parts'][0]['text'] // Extrait le texte de la réponse Gemini
                    ?? 'Désolé, je n\'ai pas pu générer de réponse.'; // Message par défaut si le texte est absent
            }

            $suggestions = $this->extractSuggestions($text); // Extrait les suggestions de formulaire depuis le texte de l'IA (titre, catégorie, etc.)
            $sentimentAnalysis = $this->extractSentiment($text); // Extrait l'analyse de sentiment/état mental depuis le texte de l'IA

            // Encoder la réponse JSON manuellement pour éviter les erreurs UTF-8
            $responseJson = json_encode([ // Encode la réponse finale en JSON
                'message'     => $text, // Le texte de la réponse du chatbot
                'suggestions' => $suggestions, // Les suggestions de remplissage du formulaire
                'sentiment'   => $sentimentAnalysis, // L'analyse du sentiment/état mental du patient
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); // Options JSON : garder les caractères Unicode et substituer les invalides

            return new JsonResponse($responseJson, 200, [], true); // Retourne la réponse JSON (true = le contenu est déjà encodé en JSON)

        } catch (\Exception $e) { // En cas d'exception (erreur réseau, timeout, etc.)
            $logger->error('ChatbotController exception: ' . $e->getMessage(), [ // Log l'exception avec le contexte
                'trace' => $e->getTraceAsString(), // Stack trace de l'erreur
                'file'  => $e->getFile(), // Fichier où l'erreur s'est produite
                'line'  => $e->getLine(), // Ligne de l'erreur
            ]);
            return $this->json([ // Retourne un message d'erreur au client
                'message'     => '⚠️ Impossible de contacter l\'API Gemini : ' . $e->getMessage(), // Message d'erreur avec détail
                'error'       => $e->getMessage(), // Détail de l'erreur
                'suggestions' => [], // Pas de suggestions
            ]);
        }
    }

    // Construit le contexte du formulaire à partir des données saisies par le patient
    private function buildFormContext(array $formData): string
    {
        $context = []; // Initialise un tableau pour stocker les lignes de contexte
        if (!empty($formData['titre']))      $context[] = 'Titre actuel : ' . $formData['titre']; // Ajoute le titre si renseigné
        if (!empty($formData['categorie']))  $context[] = 'Catégorie actuelle : ' . $formData['categorie']; // Ajoute la catégorie si renseignée
        if (!empty($formData['priorite']))   $context[] = 'Priorité actuelle : ' . $formData['priorite']; // Ajoute la priorité si renseignée
        if (!empty($formData['description'])) $context[] = 'Description actuelle : ' . $formData['description']; // Ajoute la description si renseignée
        return empty($context) ? 'Formulaire vide (aucun champ rempli pour l\'instant)' : implode("\n", $context); // Retourne le contexte formaté ou un message indiquant que le formulaire est vide
    }

    // Construit le prompt système complet qui guide le comportement de l'IA Gemini
    private function buildSystemPrompt(string $formContext): string
    {
        return <<<PROMPT
Tu es un assistant virtuel médical bienveillant d'HospiSmart, un système de gestion hospitalière.
Ton rôle est d'aider les patients à remplir leur formulaire de réclamation de manière claire et complète.

CONTEXTE DU FORMULAIRE ACTUEL:
{$formContext}

CHAMPS DU FORMULAIRE (que tu dois aider à remplir):
1. **Titre** : Un titre court et descriptif (5 à 255 caractères)
2. **Catégorie** : Choisir EXACTEMENT parmi ces valeurs (copie exacte obligatoire) :
   - "Service médical"
   - "Accueil"
   - "Facturation"
   - "Hygiène"
   - "Autre"
3. **Priorité** : Choisir EXACTEMENT parmi : "Basse", "Normale", "Haute", "Urgente"
4. **Description** : Explication détaillée (minimum 10 caractères)

MODES D'ASSISTANCE DISPONIBLES:
Le patient peut choisir parmi trois modes d'interaction:

1. **MODE GUIDÉ** : Tu poses des questions UNE PAR UNE pour remplir chaque champ
   - Commence par demander son nom/prénom
   - Puis demande de décrire brièvement sa situation
   - Ensuite aide à choisir la catégorie appropriée
   - Puis la priorité
   - Enfin aide à formuler le titre et la description détaillée
   - Attends TOUJOURS la réponse avant la question suivante
   
2. **MODE LIBRE** : Le patient décrit TOUT en une fois, puis tu génères TOUTES les suggestions
   - Encourage le patient à décrire sa situation complètement
   - Une fois la description reçue, analyse et propose tous les marqueurs d'un coup
   
3. **MODE CONVERSATIONNEL** : Discussion ouverte, le patient peut parler librement
   - Réponds naturellement à ses questions ou préoccupations
   - Offre du soutien et des conseils généraux (non médicaux)
   - Propose de basculer vers un mode guidé/libre si approprié

TES INSTRUCTIONS:
- Réponds TOUJOURS en français, de manière empathique et professionnelle
- Adapte ton comportement selon le mode choisi par le patient
- Sois concis (2-3 phrases max par réponse)
- Si le patient a fourni assez d'informations, propose des suggestions avec ces marqueurs EXACTS sur des lignes séparées :
  TITRE_SUGGERE: "le titre ici"
  CATEGORIE_SUGGEREE: "Service médical" (ou une des valeurs exactes listées)
  PRIORITE_SUGGEREE: "Normale" (ou une des valeurs exactes listées)
  DESCRIPTION_SUGGEREE: "la description ici"
- Montre de l'empathie
- NE fournis PAS de conseils médicaux

ANALYSE DE L'ÉTAT MENTAL:
À la FIN de chaque réponse, sur une ligne séparée, ajoute OBLIGATOIREMENT une analyse de l'état mental du patient en te basant sur le ton, les mots utilisés et le contexte de ses messages. Utilise CE FORMAT EXACT:
ETAT_MENTAL: "[état]" — [courte explication]

Les états possibles sont EXACTEMENT:
- "Calme" — Le patient est posé et factuel
- "Frustré" — Le patient montre de l'agacement ou de l'impatience  
- "En colère" — Le patient est très mécontent, utilise un ton agressif
- "Anxieux" — Le patient est inquiet, stressé ou effrayé
- "Triste" — Le patient exprime de la tristesse ou du découragement
- "Satisfait" — Le patient semble content malgré la réclamation
PROMPT;
    }

    // Extrait les suggestions de remplissage du formulaire depuis le texte de réponse de l'IA
    private function extractSuggestions(string $text): array
    {
        $suggestions = []; // Initialise un tableau vide pour stocker les suggestions extraites

        if (preg_match('/TITRE_SUGGERE:\s*"?([^"\n]+)"?/i', $text, $m)) { // Recherche le marqueur TITRE_SUGGERE dans le texte avec une regex
            $suggestions['titre'] = trim($m[1]); // Extrait et nettoie le titre suggéré
        }

        // Valeurs EXACTES du ReclamationType
        $categories = ['Service médical', 'Accueil', 'Facturation', 'Hygiène', 'Autre']; // Liste des catégories valides du formulaire
        if (preg_match('/CATEGORIE_SUGGEREE:\s*"?([^"\n]+)"?/i', $text, $m)) { // Recherche le marqueur CATEGORIE_SUGGEREE dans le texte
            $cat = trim($m[1]); // Extrait et nettoie la catégorie suggérée
            // Correspondance exacte
            if (in_array($cat, $categories)) { // Si la catégorie correspond exactement à une valeur autorisée
                $suggestions['categorie'] = $cat; // Utilise la valeur telle quelle
            } else { // Sinon, tente une correspondance souple par mots-clés
                // Correspondance souple
                $catLower = mb_strtolower($cat); // Convertit en minuscules pour la comparaison
                $map = [ // Table de correspondance mot-clé => catégorie valide
                    'médical'   => 'Service médical', // "médical" correspond à "Service médical"
                    'soins'     => 'Service médical', // "soins" correspond à "Service médical"
                    'accueil'   => 'Accueil', // "accueil" correspond à "Accueil"
                    'personnel' => 'Accueil', // "personnel" correspond à "Accueil"
                    'attente'   => 'Accueil', // "attente" correspond à "Accueil"
                    'factura'   => 'Facturation', // "factura" correspond à "Facturation"
                    'administ'  => 'Facturation', // "administ" correspond à "Facturation"
                    'hygiène'   => 'Hygiène', // "hygiène" correspond à "Hygiène"
                    'propreté'  => 'Hygiène', // "propreté" correspond à "Hygiène"
                ];
                $matched = false; // Flag pour savoir si une correspondance a été trouvée
                foreach ($map as $keyword => $value) { // Parcourt chaque mot-clé de la table de correspondance
                    if (str_contains($catLower, $keyword)) { // Si la catégorie suggérée contient le mot-clé
                        $suggestions['categorie'] = $value; // Utilise la catégorie valide correspondante
                        $matched = true; // Marque comme trouvé
                        break; // Arrête la boucle (première correspondance)
                    }
                }
                if (!$matched) { // Si aucune correspondance n'a été trouvée
                    $suggestions['categorie'] = 'Autre'; // Par défaut, catégorie "Autre"
                }
            }
        }

        $priorites = ['Basse', 'Normale', 'Haute', 'Urgente']; // Liste des priorités valides du formulaire
        if (preg_match('/PRIORITE_SUGGEREE:\s*"?([^"\n]+)"?/i', $text, $m)) { // Recherche le marqueur PRIORITE_SUGGEREE dans le texte
            $prio = trim($m[1]); // Extrait et nettoie la priorité suggérée
            if (in_array($prio, $priorites)) { // Si la priorité correspond exactement à une valeur autorisée
                $suggestions['priorite'] = $prio; // Utilise la valeur telle quelle
            } else { // Sinon, tente une correspondance souple
                $prioLower = mb_strtolower($prio); // Convertit en minuscules pour la comparaison
                if (str_contains($prioLower, 'urgent') || str_contains($prioLower, 'critique')) { // Si contient "urgent" ou "critique"
                    $suggestions['priorite'] = 'Urgente'; // Correspond à la priorité "Urgente"
                } elseif (str_contains($prioLower, 'haut') || str_contains($prioLower, 'import')) { // Si contient "haut" ou "import"
                    $suggestions['priorite'] = 'Haute'; // Correspond à la priorité "Haute"
                } elseif (str_contains($prioLower, 'bass') || str_contains($prioLower, 'mineur')) { // Si contient "bass" ou "mineur"
                    $suggestions['priorite'] = 'Basse'; // Correspond à la priorité "Basse"
                } else { // Si aucune correspondance spécifique
                    $suggestions['priorite'] = 'Normale'; // Par défaut, priorité "Normale"
                }
            }
        }

        if (preg_match('/DESCRIPTION_SUGGEREE:\s*"?(.+?)(?:"|$)/is', $text, $m)) { // Recherche le marqueur DESCRIPTION_SUGGEREE dans le texte (multi-lignes)
            $suggestions['description'] = trim($m[1]); // Extrait et nettoie la description suggérée
        }

        return $suggestions; // Retourne le tableau de toutes les suggestions extraites
    }

    // Extrait l'analyse de sentiment/état mental du patient depuis le texte de réponse de l'IA
    private function extractSentiment(string $text): ?array
    {
        $validStates = ['Calme', 'Frustré', 'En colère', 'Anxieux', 'Triste', 'Satisfait']; // Liste des états mentaux valides
        
        if (preg_match('/ETAT_MENTAL:\s*"?([^"—\n]+)"?\s*[—\-]\s*(.+?)(?:\n|$)/i', $text, $m)) { // Recherche le marqueur ETAT_MENTAL dans le texte avec une regex
            $etat = trim($m[1]); // Extrait l'état mental détecté
            $explication = trim($m[2]); // Extrait l'explication associée
            
            // Correspondance exacte
            foreach ($validStates as $valid) { // Parcourt les états valides
                if (mb_strtolower($etat) === mb_strtolower($valid)) { // Compare en minuscules pour ignorer la casse
                    return ['etat' => $valid, 'explication' => $explication]; // Retourne l'état trouvé avec son explication
                }
            }
            
            // Correspondance souple par mots-clés si la correspondance exacte échoue
            $etatLower = mb_strtolower($etat); // Convertit en minuscules pour la comparaison
            if (str_contains($etatLower, 'col')) return ['etat' => 'En colère', 'explication' => $explication]; // "col" => En colère
            if (str_contains($etatLower, 'frustr')) return ['etat' => 'Frustré', 'explication' => $explication]; // "frustr" => Frustré
            if (str_contains($etatLower, 'anxi') || str_contains($etatLower, 'stress')) return ['etat' => 'Anxieux', 'explication' => $explication]; // "anxi" ou "stress" => Anxieux
            if (str_contains($etatLower, 'trist') || str_contains($etatLower, 'décou')) return ['etat' => 'Triste', 'explication' => $explication]; // "trist" ou "décou" => Triste
            if (str_contains($etatLower, 'satisf') || str_contains($etatLower, 'content')) return ['etat' => 'Satisfait', 'explication' => $explication]; // "satisf" ou "content" => Satisfait
            
            return ['etat' => 'Calme', 'explication' => $explication]; // Par défaut, état "Calme"
        }
        
        return null; // Retourne null si aucun marqueur ETAT_MENTAL n'est trouvé dans le texte
    }

    #[Route('/reclamation/save-sentiment', name: 'api_chatbot_save_sentiment', methods: ['POST'])] // Route POST pour sauvegarder l'état mental détecté par le chatbot sur une réclamation
    public function saveSentiment(Request $request, ReclamationRepository $reclamationRepository, EntityManagerInterface $em): JsonResponse // Méthode pour sauvegarder le sentiment en base de données
    {
        $data = json_decode($request->getContent(), true) ?? []; // Décode le corps JSON de la requête
        $reclamationId = $data['reclamationId'] ?? null; // Récupère l'ID de la réclamation depuis les données JSON
        $etatMental = $data['etatMental'] ?? null; // Récupère l'état mental détecté depuis les données JSON
        
        if (!$reclamationId || !$etatMental) { // Si l'ID ou l'état mental est manquant
            return $this->json(['error' => 'Données manquantes'], 400); // Retourne une erreur 400 (Bad Request)
        }
        
        /** @var Reclamation|null $reclamation */
        $reclamation = $reclamationRepository->find($reclamationId); // Cherche la réclamation par son ID en base de données
        if (!$reclamation) { // Si la réclamation n'existe pas
            return $this->json(['error' => 'Réclamation non trouvée'], 404); // Retourne une erreur 404 (Not Found)
        }
        
        // Vérifier que l'utilisateur est propriétaire
        /** @var User|null $user */
        $user = $this->getUser(); // Récupère l'utilisateur connecté
        if (!$user || $reclamation->getEmail() !== $user->getEmail()) { // Si non connecté ou si l'email ne correspond pas
            return $this->json(['error' => 'Accès refusé'], 403); // Retourne une erreur 403 (Forbidden)
        }
        
        $reclamation->setEtatMental($etatMental); // Met à jour l'état mental de la réclamation
        $em->flush(); // Exécute la requête SQL de mise à jour en base de données
        
        return $this->json(['success' => true]); // Retourne une réponse JSON de succès
    }
}
