<?php

namespace App\Controller;

use App\Repository\ConsultationRepository;
use App\Repository\MedicamentRepository;
use App\Repository\MouvementStockRepository;
use App\Repository\UserRepository;
use App\Repository\RendezVousRepository;
use App\Repository\ReclamationRepository;
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
        MouvementStockRepository $mouvementRepository,
        UserRepository $userRepository,
        RendezVousRepository $rdvRepository,
        ReclamationRepository $reclamationRepository
    ): Response {
        // --- SECTION CONSULTATIONS ---
        $totalConsultations = $consultationRepository->count([]);
        $enAttente = $consultationRepository->count(['statut' => ConsultationStatus::EN_ATTENTE]);
        $enCours   = $consultationRepository->count(['statut' => ConsultationStatus::EN_COURS]);
        $traites   = $consultationRepository->count(['statut' => ConsultationStatus::TERMINEE]);
        $tauxReponse = $totalConsultations > 0 ? round(($traites / $totalConsultations) * 100) : 100;

        // --- SECTION STOCKS ---
        $medicaments = $medicamentRepository->findAll();
        $mouvements  = $mouvementRepository->findAll();

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
            'labels'     => array_map(fn($m) => substr($m->getNom(), 0, 15), $topMedicaments),
            'quantities' => array_map(fn($m) => $m->getQuantite(), $topMedicaments),
        ];

        // Mouvements récents
        usort($mouvements, fn($a, $b) => $b->getDateMouvement() <=> $a->getDateMouvement());
        $recentMouvements = array_slice($mouvements, 0, 5);

        // --- SECTION UTILISATEURS ---
        $totalPatients  = $userRepository->count(['type' => 'PATIENT']);
        $totalMedecins  = $userRepository->count(['type' => 'MEDECIN']);
        $totalUsers     = $userRepository->count([]);

        // --- SECTION RENDEZ-VOUS ---
        $aujourdhuiDebut = new \DateTime('today 00:00:00');
        $aujourdhuiFin   = new \DateTime('today 23:59:59');

        $rdvAujourdhui = $rdvRepository->createQueryBuilder('r')
            ->select('count(r.id)')
            ->where('r.datetime BETWEEN :debut AND :fin')
            ->setParameter('debut', $aujourdhuiDebut)
            ->setParameter('fin', $aujourdhuiFin)
            ->getQuery()
            ->getSingleScalarResult();

        $rdvEnAttente = $rdvRepository->count(['statut' => 'EN_ATTENTE']);
        $rdvTotal     = $rdvRepository->count([]);

        // --- SECTION RÉCLAMATIONS ---
        $reclamationsEnAttente = $reclamationRepository->count(['statut' => 'En attente']);
        $reclamationsTotal     = $reclamationRepository->count([]);

        return $this->render('back/dashboard/index.html.twig', [
            // Consultations
            'total_consultations' => $totalConsultations,
            'en_attente'          => $enAttente,
            'en_cours'            => $enCours,
            'traites'             => $traites,
            'taux_reponse'        => $tauxReponse,
            'non_traitees'        => $enAttente + $enCours,

            // Stocks
            'totalMedicaments'    => $totalMedicaments,
            'stockFaible'         => $stockFaible,
            'valeurStockTotal'    => $valeurStockTotal,
            'chartData'           => $chartData,
            'recentMouvements'    => $recentMouvements,

            // Utilisateurs
            'totalPatients'       => $totalPatients,
            'totalMedecins'       => $totalMedecins,
            'totalUsers'          => $totalUsers,

            // Rendez-vous
            'rdvAujourdhui'       => $rdvAujourdhui,
            'rdvEnAttente'        => $rdvEnAttente,
            'rdvTotal'            => $rdvTotal,

            // Réclamations
            'reclamationsEnAttente' => $reclamationsEnAttente,
            'reclamationsTotal'     => $reclamationsTotal,
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