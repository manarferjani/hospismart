<?php

namespace App\Controller\Api;

use App\Service\AiDescriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AiDescriptionController extends AbstractController
{
    #[Route('/api/evenement/generate-description', name: 'api_generate_description', methods: ['POST'])]
    public function generateDescription(Request $request, AiDescriptionService $aiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['titre'])) {
            return $this->json(['error' => 'Le titre est requis pour générer une description.'], 400);
        }

        $description = $aiService->generateDescription([
            'titre'     => $data['titre'] ?? '',
            'type'      => $data['type'] ?? '',
            'lieu'      => $data['lieu'] ?? '',
            'dateDebut' => $data['dateDebut'] ?? '',
            'dateFin'   => $data['dateFin'] ?? '',
            'budget'    => $data['budget'] ?? '',
        ]);

        if ($description === null) {
            return $this->json([
                'error' => 'Impossible de générer la description. Vérifiez que la clé API Gemini est configurée dans .env.'
            ], 503);
        }

        return $this->json(['description' => $description]);
    }
}