<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIService
{
    private $client;
    private $apiKey;

    public function __construct(HttpClientInterface $client, string $openAiKey)
    {
        $this->client = $client;
        $this->apiKey = $openAiKey;
    }

    public function calculerPriorite(string $motif): int
    {
        if (empty(trim($motif))) {
            return 2;
        }

        // Si la clé API n'est pas configurée, on utilise la détection locale
        if (empty($this->apiKey) || $this->apiKey === 'REMOVED') {
            return $this->calculerPrioriteLocale($motif);
        }

        try {
            $response = $this->client->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'HTTP-Referer' => 'http://localhost:8000', 
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'meta-llama/llama-3.1-8b-instruct',
                    'messages' => [
                        [
                            'role' => 'system', 
                            'content' => 'Tu es un algorithme de triage médical de haute précision. 
                            Ton unique but est de classer le motif de consultation selon l échelle de priorité suivante :

                            5 (CRITIQUE) : Pronostic vital engagé (arrêt cardiaque, hémorragie massive, inconscience, détresse respiratoire sévère, douleur thoracique typique).
                            4 (URGENT) : Risque d aggravation rapide (fracture ouverte, douleur intense, fièvre très élevée chez l enfant, brûlure grave).
                            3 (MOYEN) : Pathologie aiguë stable mais nécessitant des soins (forte fièvre, vomissements persistants, plaie à suturer).
                            2 (STANDARD) : Consultation de routine, suivi de maladie chronique, petite blessure.
                            1 (NON-URGENT) : Administratif, renouvellement d ordonnance, certificat médical.

                            RÈGLES DE MODÉRATION : 
                            1. N attribue le score 4 ou 5 que si des signes vitaux sont en danger immédiat. 
                            2. Une forte fièvre sans détresse respiratoire ou hémorragie doit rester en score 3.

                            RÈGLE DE SORTIE : Réponds UNIQUEMENT par le chiffre correspondant (1, 2, 3, 4 ou 5). 
                            Interdiction de parler. Si le motif est flou, choisis 2.'
                        ],
                        ['role' => 'user', 'content' => "Analyse ce motif médical : $motif"]
                    ],
                    'temperature' => 0,
                    'max_tokens' => 10,
                ],
            ]);

            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? '2';
            
            // Nettoyage rigoureux : on ne garde que le premier chiffre trouvé
            preg_match('/\d/', $content, $matches);
            $score = isset($matches[0]) ? (int)$matches[0] : 2;

            return ($score >= 1 && $score <= 5) ? $score : 2;

        } catch (\Exception $e) {
            // En cas d'erreur API (401, timeout, etc.), on utilise la détection locale
            return $this->calculerPrioriteLocale($motif);
        }
    }

    /**
     * Calcul de priorité local basé sur des mots-clés (sans IA)
     */
    private function calculerPrioriteLocale(string $motif): int
    {
        $motifLower = mb_strtolower($motif, 'UTF-8');
        
        // Priorité 5 - CRITIQUE (pronostic vital engagé)
        $critique = [
            'crise cardiaque', 'infarctus', 'arrêt cardiaque', 'avc', 'accident vasculaire',
            'hémorragie', 'saignement abondant', 'inconscience', 'inconscient', 'coma',
            'détresse respiratoire', 'ne respire plus', 'asphyxie', 'étouffement',
            'convulsion', 'crise épileptique', 'paralysie', 'douleur thoracique intense',
            'accident grave', 'polytraumatisme', 'chute grave', 'trauma crânien',
            'tentative de suicide', 'overdose', 'empoisonnement', 'intoxication grave'
        ];
        
        foreach ($critique as $keyword) {
            if (strpos($motifLower, $keyword) !== false) {
                return 5;
            }
        }
        
        // Priorité 4 - URGENT (risque d'aggravation rapide)
        $urgent = [
            'fracture', 'os cassé', 'luxation', 'entorse grave',
            'douleur intense', 'douleur insupportable', 'forte douleur',
            'brûlure', 'brûlure grave', 'ébouillantement',
            'fièvre élevée', 'fièvre très forte', '40°', '41°',
            'vomissement sang', 'sang dans', 'hémoptysie',
            'œdème', 'gonflement important', 'enflure',
            'plaie profonde', 'coupure profonde', 'lacération',
            'réaction allergique', 'allergie sévère', 'urticaire généralisé',
            'accouchement', 'contractions', 'perte des eaux'
        ];
        
        foreach ($urgent as $keyword) {
            if (strpos($motifLower, $keyword) !== false) {
                return 4;
            }
        }
        
        // Priorité 3 - MOYEN (pathologie aiguë mais stable)
        $moyen = [
            'fièvre', 'température', 'grippe',
            'vomissement', 'diarrhée', 'gastro',
            'plaie', 'coupure', 'suture', 'points de suture',
            'migraine', 'mal de tête fort',
            'infection', 'abcès',
            'toux persistante', 'essoufflement',
            'vertige', 'étourdissement',
            'douleur abdominale', 'mal au ventre',
            'entorse', 'foulure'
        ];
        
        foreach ($moyen as $keyword) {
            if (strpos($motifLower, $keyword) !== false) {
                return 3;
            }
        }
        
        // Priorité 1 - NON-URGENT (administratif)
        $nonUrgent = [
            'certificat', 'ordonnance', 'renouvellement',
            'arrêt de travail', 'attestation', 'document',
            'administratif', 'paperasse', 'formulaire',
            'vaccination', 'vaccin', 'rappel'
        ];
        
        foreach ($nonUrgent as $keyword) {
            if (strpos($motifLower, $keyword) !== false) {
                return 1;
            }
        }
        
        // Priorité 2 - STANDARD (par défaut)
        return 2;
    }
}