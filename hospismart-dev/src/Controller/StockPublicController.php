<?php

namespace App\Controller;

use App\Repository\MedicamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Front Office : page publique de consultation des stocks.
 */
final class StockPublicController extends AbstractController
{
    #[Route('/stock', name: 'app_stock_public', methods: ['GET'])]
    public function index(MedicamentRepository $medicamentRepository): Response
    {
        $medicaments = $medicamentRepository->findBy([], ['nom' => 'ASC']);

        return $this->render('stock_public/index.html.twig', [
            'medicaments' => $medicaments,
        ]);
    }
}
