<?php

namespace App\Controller;

use App\Repository\MedicamentRepository;
use App\Repository\MouvementStockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard')]
final class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard', methods: ['GET'])]
    public function index(
        MedicamentRepository $medicamentRepository,
        MouvementStockRepository $mouvementRepository
    ): Response {
        $medicaments = $medicamentRepository->findAll();
        $mouvements = $mouvementRepository->findAll();

        // Statistiques
        $totalMedicaments = count($medicaments);
        $stockFaible = count($medicamentRepository->findSousSeuilAlerte());
        $valeurStockTotal = array_sum(
            array_map(fn($m) => $m->getQuantite() * $m->getPrixUnitaire(), $medicaments)
        );
        $totalMouvements = count($mouvements);

        // Top 10 médicaments par quantité
        $topMedicaments = $medicaments;
        usort($topMedicaments, fn($a, $b) => $b->getQuantite() - $a->getQuantite());
        $topMedicaments = array_slice($topMedicaments, 0, 10);

        $chartData = [
            'labels' => array_map(fn($m) => substr($m->getNom(), 0, 15), $topMedicaments),
            'quantities' => array_map(fn($m) => $m->getQuantite(), $topMedicaments),
        ];

        // Mouvements récents (derniers 30)
        usort($mouvements, fn($a, $b) => $b->getDateMouvement() <=> $a->getDateMouvement());
        $recentMouvements = array_slice($mouvements, 0, 30);

        $entrees = count(array_filter($recentMouvements, fn($m) => $m->getType() === 'ENTREE'));
        $sorties = count(array_filter($recentMouvements, fn($m) => $m->getType() === 'SORTIE'));

        return $this->render('dashboard/index.html.twig', [
            'totalMedicaments' => $totalMedicaments,
            'stockFaible' => $stockFaible,
            'valeurStockTotal' => $valeurStockTotal,
            'totalMouvements' => $totalMouvements,
            'chartData' => $chartData,
            'entrees' => $entrees,
            'sorties' => $sorties,
            'recentMouvements' => array_slice($recentMouvements, 0, 5),
        ]);
    }
}
