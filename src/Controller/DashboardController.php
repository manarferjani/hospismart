<?php

namespace App\Controller;

use App\Repository\MedicamentRepository;
use App\Repository\MouvementStockRepository;
use App\Service\StockPredictionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard')]
final class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard', methods: ['GET'])]
    public function index(
        MedicamentRepository $medicamentRepository,
        MouvementStockRepository $mouvementRepository,
        StockPredictionService $predictionService
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

        // Prédictions IA
        $predictionSummary = $predictionService->getSummary();
        $allPredictions = $predictionService->predictAll();
        $criticalPredictions = array_slice(
            array_filter($allPredictions, fn($p) => $p['niveauRisque'] !== 'ok'),
            0,
            5
        );
 
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
            'predictionSummary' => $predictionSummary,
            'criticalPredictions' => $criticalPredictions,
            'chartData' => $chartData,
            'entrees' => $entrees,
            'sorties' => $sorties,
            'recentMouvements' => array_slice($recentMouvements, 0, 5),
        ]);
    }

    #[Route('/predictions', name: 'app_predictions', methods: ['GET'])]
    public function predictions(StockPredictionService $predictionService): Response
    {
        $predictions = $predictionService->predictAll();

        // Données pour le graphique
        $chartPredictions = array_filter($predictions, fn($p) => $p['joursRestants'] !== null);
        $chartPredictions = array_slice($chartPredictions, 0, 15);

        $chartData = [
            'labels' => array_map(fn($p) => substr($p['medicament']->getNom(), 0, 15), $chartPredictions),
            'joursRestants' => array_map(fn($p) => $p['joursRestants'], $chartPredictions),
            'colors' => array_map(function ($p) {
                return match ($p['niveauRisque']) {
                    'critique' => 'rgba(220, 53, 69, 0.8)',
                    'eleve' => 'rgba(255, 152, 0, 0.8)',
                    'moyen' => 'rgba(255, 193, 7, 0.8)',
                    default => 'rgba(67, 233, 123, 0.8)',
                };
            }, $chartPredictions),
        ];

        return $this->render('dashboard/predictions.html.twig', [
            'predictions' => $predictions,
            'chartData' => $chartData,
        ]);
    }
}

