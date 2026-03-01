<?php

namespace App\Controller\Api;

use App\Service\SmartSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SmartSearchController extends AbstractController
{
    #[Route('/api/evenement/smart-search', name: 'api_smart_search', methods: ['GET'])]
    public function search(Request $request, SmartSearchService $searchService): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen(trim($query)) < 2) {
            return $this->json([]);
        }

        $results = $searchService->search($query);

        $data = array_map(fn($r) => [
            'id' => $r['event']->getId(),
            'titre' => $r['event']->getTitre(),
            'type' => $r['event']->getTypeEvenement(),
            'lieu' => $r['event']->getLieu(),
            'statut' => $r['event']->getStatut(),
            'date' => $r['event']->getDateDebut() ? $r['event']->getDateDebut()->format('d/m/Y H:i') : null,
            'score' => $r['score'],
            'relevance' => $r['relevance'],
        ], $results);

        return $this->json($data);
    }
}