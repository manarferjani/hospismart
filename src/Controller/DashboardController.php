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
        EquipementRepository $equipementRepository
    ): Response {
        // --- SECTION STATISTIQUES GLOBALES ---
        $totalUsers = $userRepository->count([]);
        $nbMedecins = $userRepository->count(['type' => 'MEDECIN']);
        $nbPatients = $userRepository->count(['type' => 'PATIENT']);
        $totalEvenements = $evenementRepository->count([]);
        $totalReclamations = $reclamationRepository->count([]);
        $reclamationsAttente = $reclamationRepository->count(['statut' => 'En attente']);
        $totalEquipements = $equipementRepository->count([]);

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
            'entrees' => $entrees,
            'sorties' => $sorties,
            'recentMouvements' => array_slice($mouvements, 0, 5),

            // Data Globales
            'totalUsers' => $totalUsers,
            'nbMedecins' => $nbMedecins,
            'nbPatients' => $nbPatients,
            'totalEvenements' => $totalEvenements,
            'totalReclamations' => $totalReclamations,
            'reclamationsAttente' => $reclamationsAttente,
            'totalEquipements' => $totalEquipements,
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
}