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
                    'max_tokens' => 10, // Un peu plus au cas où il y a des espaces
                ],
            ]);

            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? '2';
            
            // Nettoyage rigoureux : on ne garde que le premier chiffre trouvé
            preg_match('/\d/', $content, $matches);
            $score = isset($matches[0]) ? (int)$matches[0] : 2;

            return ($score >= 1 && $score <= 5) ? $score : 2;

} catch (\Exception $e) {
    // On arrête tout pour voir l'erreur réelle
    dd($e->getMessage()); 
}
    }
}