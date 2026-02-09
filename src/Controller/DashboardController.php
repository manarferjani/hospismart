<?php

namespace App\Controller;

<<<<<<< HEAD
use App\Repository\ConsultationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function index(ConsultationRepository $consultationRepository): Response
    {
        $total = $consultationRepository->count([]);
        $enAttente = $consultationRepository->count(['statut' => \App\Enum\ConsultationStatus::EN_ATTENTE]);
        $enCours = $consultationRepository->count(['statut' => \App\Enum\ConsultationStatus::EN_COURS]);
        $traites = $consultationRepository->count(['statut' => \App\Enum\ConsultationStatus::TERMINEE]);
        $tauxReponse = $total > 0 ? round(($traites / $total) * 100) : 100;

        return $this->render('back/dashboard/index.html.twig', [
            'total_reclamations' => $total,
            'en_attente' => $enAttente,
            'en_cours' => $enCours,
            'traites' => $traites,
            'taux_reponse' => $tauxReponse,
            'urgent' => 0,
            'haute' => 0,
            'non_traitees' => $enAttente + $enCours,
        ]);
    }

    #[Route('/dashboard/reclamations', name: 'app_dashboard_reclamations', methods: ['GET'])]
    public function reclamations(Request $request, ConsultationRepository $consultationRepository): Response
    {
        $consultations = $consultationRepository->findBy([], ['date_heure' => 'DESC'], 50);
        return $this->render('back/dashboard/reclamations.html.twig', [
            'consultations' => $consultations,
=======
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
>>>>>>> origin/Stock-mahmoud
        ]);
    }
}
