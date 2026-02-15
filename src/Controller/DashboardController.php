<?php

namespace App\Controller;

use App\Repository\ConsultationRepository;
use App\Repository\MedicamentRepository;
use App\Repository\MouvementStockRepository;
use App\Enum\ConsultationStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard', methods: ['GET'])]
    public function index(
        ConsultationRepository $consultationRepository,
        MedicamentRepository $medicamentRepository,
        MouvementStockRepository $mouvementRepository
    ): Response {
        // --- SECTION CONSULTATIONS (HEAD) ---
        $totalConsultations = $consultationRepository->count([]);
        $enAttente = $consultationRepository->count(['statut' => ConsultationStatus::EN_ATTENTE]);
        $enCours = $consultationRepository->count(['statut' => ConsultationStatus::EN_COURS]);
        $traites = $consultationRepository->count(['statut' => ConsultationStatus::TERMINEE]);
        $tauxReponse = $totalConsultations > 0 ? round(($traites / $totalConsultations) * 100) : 100;

        // --- SECTION STOCKS (Stock-mahmoud) ---
        $medicaments = $medicamentRepository->findAll();
        $mouvements = $mouvementRepository->findAll();

        $totalMedicaments = count($medicaments);
        $stockFaible = count($medicamentRepository->findSousSeuilAlerte());
        $valeurStockTotal = array_sum(
            array_map(fn($m) => $m->getQuantite() * $m->getPrixUnitaire(), $medicaments)
        );

        // Top 10 médicaments pour le graphique
        $topMedicaments = $medicaments;
        usort($topMedicaments, fn($a, $b) => $b->getQuantite() - $a->getQuantite());
        $topMedicaments = array_slice($topMedicaments, 0, 10);

        $chartData = [
            'labels' => array_map(fn($m) => substr($m->getNom(), 0, 15), $topMedicaments),
            'quantities' => array_map(fn($m) => $m->getQuantite(), $topMedicaments),
        ];

        // Mouvements récents
        usort($mouvements, fn($a, $b) => $b->getDateMouvement() <=> $a->getDateMouvement());
        $recentMouvements = array_slice($mouvements, 0, 30);
        $entrees = count(array_filter($recentMouvements, fn($m) => $m->getType() === 'ENTREE'));
        $sorties = count(array_filter($recentMouvements, fn($m) => $m->getType() === 'SORTIE'));

        return $this->render('back/dashboard/index.html.twig', [
            // Data Consultations
            'total_consultations' => $totalConsultations,
            'en_attente' => $enAttente,
            'en_cours' => $enCours,
            'traites' => $traites,
            'taux_reponse' => $tauxReponse,
            'non_traitees' => $enAttente + $enCours,
            
            // Data Stocks
            'totalMedicaments' => $totalMedicaments,
            'stockFaible' => $stockFaible,
            'valeurStockTotal' => $valeurStockTotal,
            'chartData' => $chartData,
            'entrees' => $entrees,
            'sorties' => $sorties,
            'recentMouvements' => array_slice($mouvements, 0, 5),
        ]);
    }

    #[Route('/reclamations', name: 'app_dashboard_reclamations', methods: ['GET'])]
    public function reclamations(ConsultationRepository $consultationRepository): Response
    {
        $consultations = $consultationRepository->findBy([], ['date_heure' => 'DESC'], 50);
        return $this->render('back/dashboard/reclamations.html.twig', [
            'consultations' => $consultations,
        ]);
    }
}