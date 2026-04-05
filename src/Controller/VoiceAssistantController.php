<?php

namespace App\Controller;

use App\Entity\FicheMedicale;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/patient/assistant-vocal')]
#[IsGranted('ROLE_PATIENT')]
class VoiceAssistantController extends AbstractController
{
    #[Route('', name: 'app_voice_assistant', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('front/patient/voice_assistant.html.twig');
    }

    #[Route('/analyze', name: 'app_voice_analyze', methods: ['POST'])]
    public function analyze(Request $request, HttpClientInterface $httpClient): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $speech = $data['speech'] ?? '';

        if (empty($speech)) {
            return new JsonResponse(['error' => 'Veuillez décrire vos symptômes.'], 400);
        }

        $apiKey = $_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? null;
        $structuredData = null;

        if ($apiKey && $apiKey !== 'VOTRE_CLE_GEMINI') {
            try {
                // Call Gemini API (gemini-1.5-flash)
                $prompt = "Tu es un agent médical IA expert. Analyse le discours du patient suivant et extrait les informations au format JSON strict. Champs requis : 'mainSymptom', 'location', 'duration', 'intensity', 'triggers', 'secondarySymptoms', 'alertLevel' (faible, moyen, eleve, urgent), 'summary' (un résumé concis de la situation). Si une info est manquante, met '\u00e0 preciser'. NE RETOURNE RIEN D'AUTRE QUE LE JSON PUR SANS BALISES MARKDOWN.\n\nDiscours : \"$speech\"";
                
                $response = $httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey, [
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        'contents' => [
                            ['parts' => [['text' => $prompt]]]
                        ],
                        'generationConfig' => [
                            'response_mime_type' => 'application/json'
                        ]
                    ]
                ]);

                $content = $response->toArray();
                $geminiText = $content['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
                
                // Nettoyage des éventuelles balises markdown autour du json
                $geminiText = preg_replace('/```json|```/', '', $geminiText);
                $geminiText = trim($geminiText);

                $structuredData = json_decode($geminiText, true);

                // Si le décodage échoue ou si le format n'est pas bon, on force le fallback
                if (!$structuredData || !isset($structuredData['mainSymptom'])) {
                    $structuredData = null;
                }
            } catch (\Exception $e) {
                // Fallback to rules if API fails
                error_log("Gemini API Error: " . $e->getMessage());
            }
        }

        if (!$structuredData) {
            // Intelligent Fallback with Regex / Keywords if Gemini isn't configured
            $structuredData = $this->fallbackAnalysis($speech);
        }

        // Generate the summary sentence like requested
        $summary = sprintf(
            "Patient rapporte %s%s, intensité %s, aggravé/déclenché par %s, associé avec %s.",
            $structuredData['mainSymptom'],
            isset($structuredData['duration']) && $this->isValidInfo($structuredData['duration']) ? " apparu depuis " . $structuredData['duration'] : "",
            $structuredData['intensity'],
            $structuredData['triggers'],
            $structuredData['secondarySymptoms']
        );

        return new JsonResponse([
            'success' => true,
            'analysis' => $structuredData,
            'summary' => $structuredData['summary'] ?? $summary
        ]);
    }

    #[Route('/save', name: 'app_voice_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        /** @var \App\Entity\User|null $patient */
        $patient = $this->getUser();

        $fiche = new FicheMedicale();
        $fiche->setPatient($patient);
        $fiche->setRawSpeech($data['rawSpeech'] ?? '');
        $fiche->setMainSymptom($data['mainSymptom'] ?? '');
        $fiche->setLocation($data['location'] ?? '');
        $fiche->setDuration($data['duration'] ?? '');
        $fiche->setIntensity($data['intensity'] ?? '');
        $fiche->setTriggers($data['triggers'] ?? '');
        $fiche->setSecondarySymptoms($data['secondarySymptoms'] ?? '');
        $fiche->setAlertLevel($data['alertLevel'] ?? 'faible');
        $fiche->setSummary($data['summary'] ?? '');
        $fiche->setConfirmed(true);

        $em->persist($fiche);
        $em->flush();

        return new JsonResponse(['success' => true, 'redirect' => $this->generateUrl('app_patient_coordonnees')]);
    }

    private function isValidInfo($value): bool
    {
        return !empty($value) && strtolower($value) !== 'à préciser';
    }

    private function fallbackAnalysis(string $text): array
    {
        $textLower = strtolower($text);
        
        // 1. Détection du symptôme principal améliorée
        $symptomes = [
            'migraine' => ['mal de tête', 'mal a la tete', 'migraine', 'crâne'],
            'douleur' => ['mal', 'douleur', 'ça pique', 'ça brûle', 'brûlure', 'souffre'],
            'toux' => ['toux', 'tousse', 'crache'],
            'fièvre' => ['fièvre', 'chaud', 'frissons', 'température'],
            'nausée / vomissement' => ['nausée', 'vomir', 'vomissement', 'mal au coeur', 'mal au cœur'],
            'fatigue' => ['fatigué', 'fatigue', 'épuisé', 'ko', 'k.o', 'somnolence'],
            'difficulté respiratoire' => ['respire', 'souffle', 'étouffe', 'respiration'],
            'vertige' => ['vertige', 'tourne', 'tête qui tourne', 'déséquilibre']
        ];

        $mainMatch = 'à préciser';
        foreach ($symptomes as $sym => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($textLower, $kw)) {
                    $mainMatch = ucfirst($sym);
                    break 2;
                }
            }
        }

        // 2. Détection de la localisation
        $localisations = [
            'tête / crâne' => ['tête', 'crâne', 'cerveau', 'front', 'tempes'],
            'poitrine / thorax' => ['poitrine', 'coeur', 'cœur', 'thorax', 'poumons'],
            'ventre / abdomen' => ['ventre', 'estomac', 'intestin', 'abdomen', 'digestion'],
            'dos' => ['dos', 'colonne', 'lombaire', 'reins', 'nuque'],
            'membres' => ['bras', 'jambe', 'genou', 'pied', 'main', 'épaule', 'muscle', 'articulation'],
            'gorge' => ['gorge', 'cou', 'déglutir']
        ];

        $location = 'à préciser';
        foreach ($localisations as $loc => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($textLower, $kw)) {
                    $location = ucfirst($loc);
                    break 2;
                }
            }
        }

        // 3. Détection de l'intensité
        $intensity = 'moyenne';
        if (preg_match('/(très mal|insupportable|fort|horrible|atroce|extrême|10 sur 10|énormément)/i', $textLower)) {
            $intensity = 'forte';
        } elseif (preg_match('/(un peu|léger|légèrement|soutenable|faible)/i', $textLower)) {
            $intensity = 'légère';
        }

        // 4. Détection de la durée (regex plus performante)
        $duration = 'à préciser';
        if (preg_match('/(depuis|ça fait|il y a) (environ )?(\d+|un|une|deux|trois|quatre|cinq|six|sept|huit|neuf|dix) (jours?|semaines?|mois|heures?|ans?|années?)/i', $textLower, $matches)) {
            $duration = $matches[3] . ' ' . $matches[4];
        } elseif (preg_match('/(ce matin|hier|avant-hier|cette nuit)/i', $textLower, $matches)) {
            $duration = $matches[1];
        }

        // 5. Facteurs déclenchants
        $triggers = 'aucun rapporté';
        if (preg_match('/(après avoir mangé|après le repas|en mangeant)/i', $textLower)) $triggers = 'Post-prandial (après repas)';
        if (preg_match('/(après le sport|pendant l\'effort|en courant|en marchant)/i', $textLower)) $triggers = 'A l\'effort';
        if (preg_match('/(au réveil|le matin|la nuit|le soir)/i', $textLower, $matches)) $triggers = $matches[1];
        if (preg_match('/(quand je respire|en respirant|quand je tousse)/i', $textLower)) $triggers = 'A la respiration/toux';

        // 6. Symptômes secondaires (tout ce qui n'est pas le symptôme principal)
        $secondary = [];
        foreach ($symptomes as $sym => $keywords) {
            if (ucfirst($sym) === $mainMatch) continue;
            foreach ($keywords as $kw) {
                if (str_contains($textLower, $kw)) {
                    $secondary[] = ucfirst($sym);
                    break;
                }
            }
        }
        $secondarySymptoms = empty($secondary) ? 'aucun autre rapporté' : implode(', ', $secondary);

        // 7. Niveau d'Alerte
        $alertLevel = 'faible';
        $urgentKeywords = ['sang', 'saigne', 'coeur', 'cœur', 'poitrine', 'respire', 'étouffe', 'insupportable', 'tomber', 'evanoui', 'évanoui', 'conscience'];
        
        foreach ($urgentKeywords as $kw) {
            if (str_contains($textLower, $kw)) {
                $alertLevel = 'urgent';
                break;
            }
        }
        if ($alertLevel !== 'urgent' && ($intensity === 'forte' || $duration === 'plusieurs mois')) {
            $alertLevel = 'eleve';
        }

        // 8. Résumé propre
        $durationText = $duration !== 'à préciser' ? " depuis $duration" : "";
        $locText = $location !== 'à préciser' ? " au niveau: $location" : "";
        
        return [
            'mainSymptom' => $mainMatch,
            'location' => $location,
            'duration' => $duration,
            'intensity' => $intensity,
            'triggers' => $triggers,
            'secondarySymptoms' => $secondarySymptoms,
            'alertLevel' => $alertLevel,
            'summary' => "Patient signale : $mainMatch$locText$durationText. Intensité $intensity. Aggravé par : $triggers. Symptômes secondaires : $secondarySymptoms."
        ];
    }
}
