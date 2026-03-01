<?php

namespace App\Controller;

use App\Repository\ConsultationRepository;
use App\Repository\MedicamentRepository;
use App\Repository\MouvementStockRepository;

use App\Enum\ConsultationStatus;

use App\Service\StockPredictionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;
use App\Repository\EvenementRepository;
use App\Repository\ReclamationRepository;
use App\Repository\EquipementRepository;

#[Route('/dashboard')]
class DashboardController extends AbstractController
{
#[Route('', name: 'app_dashboard', methods: ['GET'])]
    public function index(
        ConsultationRepository $consultationRepository,
        MedicamentRepository $medicamentRepository,
        MouvementStockRepository $mouvementRepository,
        UserRepository $userRepository,
        EvenementRepository $evenementRepository,
        ReclamationRepository $reclamationRepository,
        EquipementRepository $equipementRepository,
        StockPredictionService $predictionService
    ): Response {
        // --- DATA GLOBALES (Ton travail) ---
        $totalUsers = $userRepository->count([]);
        $totalEvenements = $evenementRepository->count([]);
        $totalReclamations = $reclamationRepository->count([]);
        $reclamationsAttente = $reclamationRepository->count(['statut' => 'En attente']);
        $totalEquipements = $equipementRepository->count([]);

        // --- DATA CONSULTATIONS ---
        $totalConsultations = $consultationRepository->count([]);
        $traites = $consultationRepository->count(['statut' => ConsultationStatus::TERMINEE]);
        $tauxReponse = $totalConsultations > 0 ? round(($traites / $totalConsultations) * 100) : 100;

        // --- DATA STOCKS (Mahmoud) ---
        $medicaments = $medicamentRepository->findAll();
        $mouvements = $mouvementRepository->findAll();
        $totalMedicaments = count($medicaments);
        $stockFaible = count($medicamentRepository->findSousSeuilAlerte());
        $valeurStockTotal = array_sum(array_map(fn($m) => $m->getQuantite() * $m->getPrixUnitaire(), $medicaments));

        // Prédictions IA Mahmoud
        $predictionSummary = $predictionService->getSummary();
        $allPredictions = $predictionService->predictAll();
        $criticalPredictions = array_slice(array_filter($allPredictions, fn($p) => $p['niveauRisque'] !== 'ok'), 0, 5);

        // Graphique Top 10 Médicaments
        $topMedicaments = $medicaments;
        usort($topMedicaments, fn($a, $b) => $b->getQuantite() - $a->getQuantite());
        $topMedicaments = array_slice($topMedicaments, 0, 10);
        $chartData = [
            'labels' => array_map(fn($m) => substr($m->getNom(), 0, 15), $topMedicaments),
            'quantities' => array_map(fn($m) => $m->getQuantite(), $topMedicaments),
        ];

        return $this->render('back/dashboard/index.html.twig', [
            'total_consultations' => $totalConsultations,
            'taux_reponse' => $tauxReponse,
            'totalMedicaments' => $totalMedicaments,
            'stockFaible' => $stockFaible,
            'valeurStockTotal' => $valeurStockTotal,
            'predictionSummary' => $predictionSummary,
            'criticalPredictions' => $criticalPredictions,
            'chartData' => $chartData,
            'totalUsers' => $totalUsers,
            'totalEvenements' => $totalEvenements,
            'totalReclamations' => $totalReclamations,
            'reclamationsAttente' => $reclamationsAttente,
            'totalEquipements' => $totalEquipements,
            'recentMouvements' => array_slice($mouvements, 0, 5),
        ]);
    }


    #[Route('/reclamations', name: 'app_dashboard_reclamations', methods: ['GET'])]
    public function reclamations(Request $request, \App\Repository\ReclamationRepository $reclamationRepository): Response
    {
        $sort = $request->query->get('sort', 'dateCreation');
        $direction = $request->query->get('direction', 'DESC');
        
        // Sécuriser les champs de tri
        $allowedSorts = ['dateCreation', 'nomPatient', 'priorite', 'statut'];
        if (!in_array($sort, $allowedSorts)) $sort = 'dateCreation';
        
        $reclamations = $reclamationRepository->findBy([], [$sort => $direction]);
        
        return $this->render('back/dashboard/reclamations.html.twig', [
            'reclamations' => $reclamations,
            'currentSort' => $sort,
            'currentDirection' => $direction
        ]);
    }

    #[Route('/reclamations/{id}', name: 'app_dashboard_reclamations_show', methods: ['GET'])]
    public function showReclamation(\App\Entity\Reclamation $reclamation): Response
    {
        return $this->render('back/dashboard/reclamation_show.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/reclamations/{id}/edit', name: 'app_dashboard_reclamations_edit', methods: ['GET', 'POST'])]
    public function editReclamation(Request $request, \App\Entity\Reclamation $reclamation, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $statut = $request->request->get('statut');
            $priorite = $request->request->get('priorite');
            
            if ($statut) $reclamation->setStatut($statut);
            if ($priorite) $reclamation->setPriorite($priorite);
            
            $entityManager->flush();
            $this->addFlash('success', 'Réclamation mise à jour avec succès.');
            return $this->redirectToRoute('app_dashboard_reclamations');
        }

        return $this->render('back/dashboard/reclamation_edit.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/reclamations/{id}/delete', name: 'app_dashboard_reclamations_delete', methods: ['POST'])]
    public function deleteReclamation(Request $request, \App\Entity\Reclamation $reclamation, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
            $this->addFlash('success', 'Réclamation supprimée avec succès.');
        }

        return $this->redirectToRoute('app_dashboard_reclamations');
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
