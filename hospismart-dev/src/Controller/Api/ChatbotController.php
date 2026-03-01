<?php

namespace App\Controller\Api;

use App\Entity\Reclamation;
use App\Repository\ReclamationRepository;
use App\Service\ProfanityFilterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

#[Route('/api/chatbot')]
class ChatbotController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $geminiApiKey,
        private ProfanityFilterService $profanityFilter,
    ) {}

    #[Route('/reclamation', name: 'api_chatbot_reclamation', methods: ['POST'])]
    public function chat(Request $request, LoggerInterface $logger): JsonResponse
    {
        $data      = json_decode($request->getContent(), true) ?? [];
        $messages  = $data['messages']  ?? [];
        $formData  = $data['formData']  ?? [];

        // Vérifier que la clé API est configurée
        if (empty($this->geminiApiKey)
            || str_contains($this->geminiApiKey, 'VOTRE_CLE')
            || str_contains($this->geminiApiKey, 'ICI')
        ) {
            return $this->json([
                'message'        => '⚙️ L\'assistant IA n\'est pas encore configuré. Ajoutez votre clé API dans `.env.local` : GEMINI_API_KEY=votre_clé. Obtenez-en une gratuitement sur https://aistudio.google.com/app/apikey',
                'suggestions'    => [],
                'not_configured' => true,
            ]);
        }

        if (empty($messages)) {
            return $this->json(['error' => 'Messages manquants'], 400);
        }

        // Système prompt en instruction séparée (format v1beta avec systemInstruction)
        $formContext  = $this->buildFormContext($formData);
        $systemPrompt = $this->buildSystemPrompt($formContext);

        // Construire l'historique pour Gemini
        // IMPORTANT: le tableau contents doit commencer par role=user et alterner user/model
        $contents = [];
        foreach ($messages as $msg) {
            $role       = ($msg['role'] === 'user') ? 'user' : 'model';
            $content    = trim($msg['content'] ?? '');
            if ($content === '') continue;
            // Sanitiser UTF-8 pour éviter les erreurs json_encode
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
            $contents[] = [
                'role'  => $role,
                'parts' => [['text' => $content]],
            ];
        }

        // Si la liste est vide ou commence par model, ajouter une correction
        if (empty($contents)) {
            return $this->json(['error' => 'Aucun message valide'], 400);
        }
        // Assurer que le dernier message est bien de l'utilisateur
        if (end($contents)['role'] !== 'user') {
            return $this->json(['error' => 'Dernier message doit être de l\'utilisateur'], 400);
        }

        // Payload Gemini v1beta avec systemInstruction (format officiel)
        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents'          => $contents,
            'generationConfig'  => [
                'temperature'     => 0.75,
                'maxOutputTokens' => 800,
                'topP'            => 0.95,
            ],
        ];

        try {
            // Encoder manuellement en JSON avec gestion UTF-8 robuste
            $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if ($jsonBody === false) {
                $logger->error('JSON encode failed: ' . json_last_error_msg());
                return $this->json([
                    'message'     => '⚠️ Erreur d\'encodage des données. Veuillez reformuler votre message sans caractères spéciaux.',
                    'suggestions' => [],
                ]);
            }

            $logger->info('TRACE: json_encode OK, body length=' . strlen($jsonBody));

            $response = $this->httpClient->request(
                'POST',
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $this->geminiApiKey,
                [
                    'headers' => ['Content-Type' => 'application/json'],
                    'body'    => $jsonBody,
                    'timeout' => 30,
                ]
            );

            $logger->info('TRACE: request sent, getting status code...');
            $statusCode = $response->getStatusCode();
            $logger->info('TRACE: status=' . $statusCode . ', getting body...');
            
            $rawBody = $response->getContent(false);
            $logger->info('TRACE: raw body length=' . strlen($rawBody));
            
            // Sanitiser la réponse UTF-8 avant json_decode
            $rawBody = mb_convert_encoding($rawBody, 'UTF-8', 'UTF-8');
            $responseData = json_decode($rawBody, true);
            if ($responseData === null && json_last_error() !== JSON_ERROR_NONE) {
                $logger->error('TRACE: json_decode response failed: ' . json_last_error_msg());
                return $this->json([
                    'message'     => '⚠️ Réponse invalide de l\'API Gemini.',
                    'suggestions' => [],
                ]);
            }

            if ($statusCode !== 200) {
                $apiError = $responseData['error']['message'] ?? 'Erreur HTTP ' . $statusCode;
                $logger->error('Gemini API error: ' . $apiError, ['status' => $statusCode, 'payload_contents_count' => count($contents)]);
                return $this->json([
                    'message'   => '⚠️ Erreur Gemini : ' . $apiError,
                    'api_error' => $apiError,
                    'suggestions' => [],
                ]);
            }

            $finishReason = $responseData['candidates'][0]['finishReason'] ?? '';
            if ($finishReason === 'SAFETY') {
                $text = 'Ma réponse a été filtrée pour des raisons de sécurité. Reformulez votre demande.';
            } else {
                $text = $responseData['candidates'][0]['content']['parts'][0]['text']
                    ?? 'Désolé, je n\'ai pas pu générer de réponse.';
            }

            $suggestions = $this->extractSuggestions($text);
            $sentimentAnalysis = $this->extractSentiment($text);

            // Encoder la réponse JSON manuellement pour éviter les erreurs UTF-8
            $responseJson = json_encode([
                'message'     => $text,
                'suggestions' => $suggestions,
                'sentiment'   => $sentimentAnalysis,
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

            return new JsonResponse($responseJson, 200, [], true);

        } catch (\Exception $e) {
            $logger->error('ChatbotController exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
            return $this->json([
                'message'     => '⚠️ Impossible de contacter l\'API Gemini : ' . $e->getMessage(),
                'error'       => $e->getMessage(),
                'suggestions' => [],
            ]);
        }
    }

    private function buildFormContext(array $formData): string
    {
        $context = [];
        if (!empty($formData['titre']))      $context[] = 'Titre actuel : ' . $formData['titre'];
        if (!empty($formData['categorie']))  $context[] = 'Catégorie actuelle : ' . $formData['categorie'];
        if (!empty($formData['priorite']))   $context[] = 'Priorité actuelle : ' . $formData['priorite'];
        if (!empty($formData['description'])) $context[] = 'Description actuelle : ' . $formData['description'];
        return empty($context) ? 'Formulaire vide (aucun champ rempli pour l\'instant)' : implode("\n", $context);
    }

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

TES INSTRUCTIONS:
- Réponds TOUJOURS en français, de manière empathique et professionnelle
- Sois concis (2-3 phrases max par réponse)
- Pose des questions pour comprendre le problème du patient
- Si le patient décrit son problème, propose des suggestions avec ces marqueurs EXACTS sur des lignes séparées :
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

    private function extractSuggestions(string $text): array
    {
        $suggestions = [];

        if (preg_match('/TITRE_SUGGERE:\s*"?([^"\n]+)"?/i', $text, $m)) {
            $suggestions['titre'] = trim($m[1]);
        }

        // Valeurs EXACTES du ReclamationType
        $categories = ['Service médical', 'Accueil', 'Facturation', 'Hygiène', 'Autre'];
        if (preg_match('/CATEGORIE_SUGGEREE:\s*"?([^"\n]+)"?/i', $text, $m)) {
            $cat = trim($m[1]);
            // Correspondance exacte
            if (in_array($cat, $categories)) {
                $suggestions['categorie'] = $cat;
            } else {
                // Correspondance souple
                $catLower = mb_strtolower($cat);
                $map = [
                    'médical'   => 'Service médical',
                    'soins'     => 'Service médical',
                    'accueil'   => 'Accueil',
                    'personnel' => 'Accueil',
                    'attente'   => 'Accueil',
                    'factura'   => 'Facturation',
                    'administ'  => 'Facturation',
                    'hygiène'   => 'Hygiène',
                    'propreté'  => 'Hygiène',
                ];
                $matched = false;
                foreach ($map as $keyword => $value) {
                    if (str_contains($catLower, $keyword)) {
                        $suggestions['categorie'] = $value;
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    $suggestions['categorie'] = 'Autre';
                }
            }
        }

        $priorites = ['Basse', 'Normale', 'Haute', 'Urgente'];
        if (preg_match('/PRIORITE_SUGGEREE:\s*"?([^"\n]+)"?/i', $text, $m)) {
            $prio = trim($m[1]);
            if (in_array($prio, $priorites)) {
                $suggestions['priorite'] = $prio;
            } else {
                $prioLower = mb_strtolower($prio);
                if (str_contains($prioLower, 'urgent') || str_contains($prioLower, 'critique')) {
                    $suggestions['priorite'] = 'Urgente';
                } elseif (str_contains($prioLower, 'haut') || str_contains($prioLower, 'import')) {
                    $suggestions['priorite'] = 'Haute';
                } elseif (str_contains($prioLower, 'bass') || str_contains($prioLower, 'mineur')) {
                    $suggestions['priorite'] = 'Basse';
                } else {
                    $suggestions['priorite'] = 'Normale';
                }
            }
        }

        if (preg_match('/DESCRIPTION_SUGGEREE:\s*"?(.+?)(?:"|$)/is', $text, $m)) {
            $suggestions['description'] = trim($m[1]);
        }

        return $suggestions;
    }

    private function extractSentiment(string $text): ?array
    {
        $validStates = ['Calme', 'Frustré', 'En colère', 'Anxieux', 'Triste', 'Satisfait'];
        
        if (preg_match('/ETAT_MENTAL:\s*"?([^"—\n]+)"?\s*[—\-]\s*(.+?)(?:\n|$)/i', $text, $m)) {
            $etat = trim($m[1]);
            $explication = trim($m[2]);
            
            // Correspondance exacte
            foreach ($validStates as $valid) {
                if (mb_strtolower($etat) === mb_strtolower($valid)) {
                    return ['etat' => $valid, 'explication' => $explication];
                }
            }
            
            // Correspondance souple
            $etatLower = mb_strtolower($etat);
            if (str_contains($etatLower, 'col')) return ['etat' => 'En colère', 'explication' => $explication];
            if (str_contains($etatLower, 'frustr')) return ['etat' => 'Frustré', 'explication' => $explication];
            if (str_contains($etatLower, 'anxi') || str_contains($etatLower, 'stress')) return ['etat' => 'Anxieux', 'explication' => $explication];
            if (str_contains($etatLower, 'trist') || str_contains($etatLower, 'décou')) return ['etat' => 'Triste', 'explication' => $explication];
            if (str_contains($etatLower, 'satisf') || str_contains($etatLower, 'content')) return ['etat' => 'Satisfait', 'explication' => $explication];
            
            return ['etat' => 'Calme', 'explication' => $explication];
        }
        
        return null;
    }

    #[Route('/reclamation/save-sentiment', name: 'api_chatbot_save_sentiment', methods: ['POST'])]
    public function saveSentiment(Request $request, ReclamationRepository $reclamationRepository, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $reclamationId = $data['reclamationId'] ?? null;
        $etatMental = $data['etatMental'] ?? null;
        
        if (!$reclamationId || !$etatMental) {
            return $this->json(['error' => 'Données manquantes'], 400);
        }
        
        $reclamation = $reclamationRepository->find($reclamationId);
        if (!$reclamation) {
            return $this->json(['error' => 'Réclamation non trouvée'], 404);
        }
        
        // Vérifier que l'utilisateur est propriétaire
        $user = $this->getUser();
        if (!$user || $reclamation->getEmail() !== $user->getEmail()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }
        
        $reclamation->setEtatMental($etatMental);
        $em->flush();
        
        return $this->json(['success' => true]);
    }
}
