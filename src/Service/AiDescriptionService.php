<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiDescriptionService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $apiKey;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $geminiApiKey
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $geminiApiKey;
    }

    public function generateDescription(array $eventInfo): ?string
    {
        // Try Gemini API first if key is configured
        if (!empty($this->apiKey) && $this->apiKey !== 'YOUR_GEMINI_API_KEY_HERE') {
            $result = $this->callGeminiApi($eventInfo);
            if ($result !== null) {
                return $result;
            }
        }

        // Fallback: local smart template-based generation (always works)
        return $this->generateLocalDescription($eventInfo);
    }

    private function callGeminiApi(array $eventInfo): ?string
    {
        $prompt = $this->buildPrompt($eventInfo);

        try {
            $response = $this->httpClient->request('POST',
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->apiKey,
                [
                    'json' => [
                        'contents' => [
                            ['parts' => [['text' => $prompt]]]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.7,
                            'maxOutputTokens' => 300,
                        ]
                    ],
                    'timeout' => 5,
                ]
            );

            $data = $response->toArray(false);

            if (isset($data['error'])) {
                $this->logger->warning('Gemini API error: ' . ($data['error']['message'] ?? 'unknown'));
                return null;
            }

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $text = trim($data['candidates'][0]['content']['parts'][0]['text']);
                $text = preg_replace('/^#+\s*/m', '', $text);
                return trim($text);
            }

            return null;

        } catch (\Throwable $e) {
            $this->logger->warning('Gemini API unavailable, using local AI: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Local AI: generates a professional description using smart templates
     * and NLP-inspired text construction. No external API needed.
     */
    private function generateLocalDescription(array $info): string
    {
        $titre = $info['titre'] ?? 'événement';
        $type = $info['type'] ?? '';
        $lieu = $info['lieu'] ?? '';
        $dateDebut = $info['dateDebut'] ?? '';
        $dateFin = $info['dateFin'] ?? '';
        $budget = $info['budget'] ?? '';

        $typeTemplates = [
            'réunion' => [
                "ouverture" => [
                    "Cette réunion professionnelle intitulée \"{titre}\" est organisée dans le cadre de la coordination des activités de notre établissement de santé.",
                    "L'établissement organise la réunion \"{titre}\" afin de renforcer la collaboration entre les équipes médicales et administratives.",
                    "Dans le cadre de l'amélioration continue de nos services de santé, la réunion \"{titre}\" rassemblera les acteurs clés de l'établissement.",
                ],
                "objectif" => [
                    "Cette rencontre vise à favoriser les échanges entre les différents services et à coordonner les actions prioritaires.",
                    "L'objectif principal est de discuter des enjeux stratégiques et d'aligner les efforts des équipes sur les priorités de l'établissement.",
                    "Elle permettra de faire le point sur les avancées récentes et de définir les orientations pour la période à venir.",
                ],
            ],
            'formation' => [
                "ouverture" => [
                    "La formation \"{titre}\" est organisée pour renforcer les compétences des professionnels de santé de notre établissement.",
                    "Dans le cadre du développement professionnel continu, notre établissement propose la formation \"{titre}\".",
                    "Cette session de formation intitulée \"{titre}\" s'inscrit dans la démarche qualité et le perfectionnement des pratiques médicales.",
                ],
                "objectif" => [
                    "Les participants auront l'opportunité d'acquérir de nouvelles connaissances et de mettre à jour leurs pratiques professionnelles.",
                    "Cette formation interactive combine apports théoriques et mises en situation pratiques pour un apprentissage optimal.",
                    "L'accent sera mis sur les dernières avancées dans le domaine et leur application concrète au quotidien.",
                ],
            ],
            'visite' => [
                "ouverture" => [
                    "La visite \"{titre}\" est programmée dans le cadre de l'évaluation et de l'amélioration de nos installations hospitalières.",
                    "Notre établissement accueille la visite \"{titre}\" pour permettre un échange constructif sur nos pratiques et infrastructures.",
                ],
                "objectif" => [
                    "Cette visite permettra d'évaluer les installations, d'identifier les points d'amélioration et de partager les bonnes pratiques.",
                    "L'objectif est de garantir la conformité de nos installations aux normes en vigueur et d'optimiser la qualité des soins.",
                ],
            ],
            'maintenance' => [
                "ouverture" => [
                    "L'opération de maintenance \"{titre}\" est planifiée pour assurer le bon fonctionnement des équipements de notre établissement.",
                    "Dans le cadre de la maintenance préventive, l'intervention \"{titre}\" est programmée pour garantir la fiabilité de nos installations.",
                ],
                "objectif" => [
                    "Cette intervention vise à prévenir les pannes, optimiser les performances des équipements et garantir la sécurité des patients et du personnel.",
                    "Les travaux porteront sur la vérification, le calibrage et la mise à jour des équipements concernés.",
                ],
            ],
        ];

        $defaultTemplate = [
            "ouverture" => [
                "L'événement \"{titre}\" est organisé par notre établissement de santé dans le cadre de ses activités institutionnelles.",
                "Notre établissement hospitalier a le plaisir d'organiser \"{titre}\", un événement dédié à l'amélioration de nos services.",
            ],
            "objectif" => [
                "Cet événement s'inscrit dans la démarche d'excellence de notre établissement et vise à renforcer la qualité des services proposés.",
                "Il contribuera au rayonnement de notre établissement et au renforcement des liens entre les différents acteurs de la santé.",
            ],
        ];

        $typeKey = mb_strtolower(trim($type));
        $templates = $typeTemplates[$typeKey] ?? $defaultTemplate;

        $parts = [];

        // Opening sentence
        $ouverture = $templates['ouverture'][array_rand($templates['ouverture'])];
        $parts[] = str_replace('{titre}', $titre, $ouverture);

        // Objective sentence
        $objectif = $templates['objectif'][array_rand($templates['objectif'])];
        $parts[] = $objectif;

        // Location & date details
        $details = [];
        if (!empty($lieu)) {
            $details[] = "au " . $lieu;
        }
        if (!empty($dateDebut)) {
            $formattedDate = $this->formatDate($dateDebut);
            if ($formattedDate) {
                $details[] = "le " . $formattedDate;
            }
        }
        if (!empty($details)) {
            $parts[] = "L'événement se tiendra " . implode(', ', $details) . ".";
        }

        // Budget mention
        if (!empty($budget) && (float)$budget > 0) {
            $parts[] = "Un budget de " . number_format((float)$budget, 2, ',', ' ') . " TND a été alloué pour assurer le bon déroulement de cette initiative.";
        }

        // Closing
        $closings = [
            "La participation de l'ensemble des collaborateurs concernés est vivement encouragée.",
            "Tous les professionnels de l'établissement sont invités à y participer activement.",
            "Nous comptons sur la mobilisation de chacun pour faire de cet événement une réussite.",
        ];
        $parts[] = $closings[array_rand($closings)];

        return implode(' ', $parts);
    }

    private function formatDate(string $dateStr): ?string
    {
        try {
            $date = new \DateTime($dateStr);
            $months = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
                       'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
            $day = (int)$date->format('d');
            $month = $months[(int)$date->format('n')];
            $year = $date->format('Y');
            $hour = $date->format('H:i');
            return "$day $month $year à $hour";
        } catch (\Exception $e) {
            return null;
        }
    }

    private function buildPrompt(array $info): string
    {
        $parts = [];
        $parts[] = "Génère une description professionnelle et concise (3-5 phrases) en français pour un événement hospitalier :";

        if (!empty($info['titre'])) $parts[] = "- Titre : " . $info['titre'];
        if (!empty($info['type'])) $parts[] = "- Type : " . $info['type'];
        if (!empty($info['lieu'])) $parts[] = "- Lieu : " . $info['lieu'];
        if (!empty($info['dateDebut'])) $parts[] = "- Date de début : " . $info['dateDebut'];
        if (!empty($info['dateFin'])) $parts[] = "- Date de fin : " . $info['dateFin'];
        if (!empty($info['budget'])) $parts[] = "- Budget : " . $info['budget'] . " TND";

        $parts[] = "";
        $parts[] = "Pas de titre ni de markdown. Texte direct uniquement.";

        return implode("\n", $parts);
    }
}