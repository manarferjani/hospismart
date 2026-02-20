<?php

namespace App\Controller\Api;

use App\Repository\MedicamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/stock')]
final class StockController extends AbstractController
{
    #[Route('/faible', name: 'api_stock_faible', methods: ['GET'])]
    public function medicamentsFaibles(MedicamentRepository $medicamentRepository): JsonResponse
    {
        $medicaments = $medicamentRepository->findSousSeuilAlerte();

        $data = array_map(function ($m) {
            return [
                'id' => $m->getId(),
                'nom' => $m->getNom(),
                'quantite' => $m->getQuantite(),
                'seuil_alerte' => $m->getSeuilAlerte(),
            ];
        }, $medicaments);

        return $this->json([
            'count' => count($data),
            'medicaments' => $data,
        ]);
    }
}
