<?php
namespace App\Controller;

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
        ]);
    }
}
