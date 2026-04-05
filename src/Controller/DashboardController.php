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
        $nbMedecins = $userRepository->count(['type' => 'medecin']);
        $nbPatients = $userRepository->count(['type' => 'patient']);
        $totalEvenements = $evenementRepository->count([]);
        $totalReclamations = $reclamationRepository->count([]);
        $reclamationsAttente = $reclamationRepository->count(['statut' => 'En attente']);
        $totalEquipements = $equipementRepository->count([]);

        // --- DATA CONSULTATIONS ---
        $totalConsultations = $consultationRepository->count([]);
        $traites = $consultationRepository->count(['statut' => ConsultationStatus::TERMINEE]);
        $enAttente = $consultationRepository->count(['statut' => ConsultationStatus::EN_ATTENTE]);
        $enCours = $consultationRepository->count(['statut' => ConsultationStatus::EN_COURS]);
        $nonTraitees = $totalConsultations - $traites;
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

        // Activités (Entrées/Sorties)
        $entrees = array_filter($mouvements, fn($m) => $m->getType() === 'entree');
        $sorties = array_filter($mouvements, fn($m) => $m->getType() === 'sortie');
        $activitesData = [
            'entrees' => count($entrees),
            'sorties' => count($sorties),
        ];

        // Mouvements récents triés
        usort($mouvements, fn($a, $b) => $b->getDateMouvement() <=> $a->getDateMouvement());
        $recentMouvements = array_slice($mouvements, 0, 10);

        return $this->render('back/dashboard/index.html.twig', [
            'total_consultations' => $totalConsultations,
            'taux_reponse' => $tauxReponse,
            'en_attente' => $enAttente,
            'en_cours' => $enCours,
            'traites' => $traites,
            'non_traitees' => $nonTraitees,
            'nbMedecins' => $nbMedecins,
            'nbPatients' => $nbPatients,
            'totalMedicaments' => $totalMedicaments,
            'stockFaible' => $stockFaible,
            'valeurStockTotal' => $valeurStockTotal,
            'predictionSummary' => $predictionSummary,
            'criticalPredictions' => $criticalPredictions,
            'chartData' => $chartData,
            'activitesData' => $activitesData,
            'totalUsers' => $totalUsers,
            'totalEvenements' => $totalEvenements,
            'totalReclamations' => $totalReclamations,
            'reclamationsAttente' => $reclamationsAttente,
            'totalEquipements' => $totalEquipements,
            'recentMouvements' => $recentMouvements,
        ]);
    }


    #[Route('/reclamations', name: 'app_dashboard_reclamations', methods: ['GET'])]
    public function reclamations(Request $request, \App\Repository\ReclamationRepository $reclamationRepository): Response
    {
        $sort = $request->query->get('sort', 'dateCreation');
        $direction = $request->query->get('direction', 'DESC');
        $filterStatut = $request->query->get('statut');
        $filterPriorite = $request->query->get('priorite');

        // Sécuriser les champs de tri
        $allowedSorts = ['dateCreation', 'nomPatient', 'priorite', 'statut'];
        if (!in_array($sort, $allowedSorts)) $sort = 'dateCreation';

        // Construire les critères de filtre
        $criteria = [];
        if ($filterStatut && in_array($filterStatut, ['En attente', 'En cours', 'Traité'])) {
            $criteria['statut'] = $filterStatut;
        }
        if ($filterPriorite && in_array($filterPriorite, ['Basse', 'Normale', 'Haute', 'Urgente'])) {
            $criteria['priorite'] = $filterPriorite;
        }

        $reclamations = $reclamationRepository->findBy($criteria, [$sort => $direction]);

        // Statistiques
        $totalReclamations = $reclamationRepository->count([]);
        $nbAttente = $reclamationRepository->count(['statut' => 'En attente']);
        $nbEnCours = $reclamationRepository->count(['statut' => 'En cours']);
        $nbTraite = $reclamationRepository->count(['statut' => 'Traité']);
        $nbUrgente = $reclamationRepository->count(['priorite' => 'Urgente']);
        $nbHaute = $reclamationRepository->count(['priorite' => 'Haute']);
        $tauxTraitement = $totalReclamations > 0 ? round(($nbTraite / $totalReclamations) * 100) : 0;

        return $this->render('back/dashboard/reclamations.html.twig', [
            'reclamations' => $reclamations,
            'currentSort' => $sort,
            'currentDirection' => $direction,
            'filterStatut' => $filterStatut,
            'filterPriorite' => $filterPriorite,
            'totalReclamations' => $totalReclamations,
            'nbAttente' => $nbAttente,
            'nbEnCours' => $nbEnCours,
            'nbTraite' => $nbTraite,
            'nbUrgente' => $nbUrgente,
            'nbHaute' => $nbHaute,
            'tauxTraitement' => $tauxTraitement,
        ]);
    }

    #[Route('/reclamations/export/pdf', name: 'app_dashboard_reclamations_export_pdf', methods: ['GET'])]
    public function exportReclamationsPdf(\App\Repository\ReclamationRepository $reclamationRepository, \App\Service\PdfExportService $pdfExportService): Response
    {
        $reclamations = $reclamationRepository->findBy([], ['dateCreation' => 'DESC']);
        $pdfContent = $pdfExportService->generateReclamationsPdf($reclamations);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reclamations_' . date('Y-m-d_H-i-s') . '.pdf"',
        ]);
    }

    #[Route('/reclamations/export/excel', name: 'app_dashboard_reclamations_export_excel', methods: ['GET'])]
    public function exportReclamationsExcel(\App\Repository\ReclamationRepository $reclamationRepository, \App\Service\ExcelExportService $excelExportService): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $reclamations = $reclamationRepository->findBy([], ['dateCreation' => 'DESC']);
        return $excelExportService->generateReclamationsExcel($reclamations);
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
            $action = $request->request->get('action', 'update_status');

            if ($action === 'repondre') {
                // Créer une nouvelle réponse
                $contenu = $request->request->get('contenu');
                $adminNom = $request->request->get('admin_nom');
                $adminEmail = $request->request->get('admin_email');

                if ($contenu && $adminNom && $adminEmail) {
                    $reponse = new \App\Entity\Reponse();
                    $reponse->setContenu($contenu);
                    $reponse->setAdminNom($adminNom);
                    $reponse->setAdminEmail($adminEmail);
                    $reponse->setReclamation($reclamation);

                    // Passer le statut à "Traité" automatiquement
                    $reclamation->setStatut('Traité');

                    $entityManager->persist($reponse);
                    $entityManager->flush();
                    $this->addFlash('success', 'Réponse envoyée avec succès. La réclamation est maintenant traitée.');
                } else {
                    $this->addFlash('error', 'Veuillez remplir tous les champs de la réponse.');
                }
            } else {
                // Mise à jour statut + priorité
                $statut = $request->request->get('statut');
                $priorite = $request->request->get('priorite');

                if ($statut) $reclamation->setStatut($statut);
                if ($priorite) $reclamation->setPriorite($priorite);

                $entityManager->flush();
                $this->addFlash('success', 'Réclamation mise à jour avec succès.');
            }

            return $this->redirectToRoute('app_dashboard_reclamations_edit', ['id' => $reclamation->getId()]);
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

    // ===================== RÉPONSES =====================

    #[Route('/reponses', name: 'app_dashboard_reponses', methods: ['GET'])]
    public function listReponses(Request $request, \App\Repository\ReponseRepository $reponseRepository): Response
    {
        $sort = $request->query->get('sort', 'dateReponse');
        $direction = $request->query->get('direction', 'DESC');

        $allowedSorts = ['dateReponse', 'adminNom', 'adminEmail'];
        if (!in_array($sort, $allowedSorts)) $sort = 'dateReponse';
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $reponses = $reponseRepository->findBy([], [$sort => $direction]);

        return $this->render('back/dashboard/reponses.html.twig', [
            'reponses' => $reponses,
            'currentSort' => $sort,
            'currentDirection' => $direction,
        ]);
    }

    #[Route('/reponses/{id}/edit', name: 'app_dashboard_reponses_edit', methods: ['GET', 'POST'])]
    public function editReponse(Request $request, \App\Entity\Reponse $reponse, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $contenu = $request->request->get('contenu');
            $adminNom = $request->request->get('admin_nom');
            $adminEmail = $request->request->get('admin_email');

            if ($contenu && strlen($contenu) >= 10 && $adminNom && $adminEmail) {
                $reponse->setContenu($contenu);
                $reponse->setAdminNom($adminNom);
                $reponse->setAdminEmail($adminEmail);
                $entityManager->flush();
                $this->addFlash('success', 'Réponse modifiée avec succès.');
                return $this->redirectToRoute('app_dashboard_reponses');
            } else {
                $this->addFlash('error', 'Veuillez remplir tous les champs correctement (contenu min. 10 caractères).');
            }
        }

        return $this->render('back/dashboard/reponse_edit.html.twig', [
            'reponse' => $reponse,
        ]);
    }

    #[Route('/reponses/{id}/delete', name: 'app_dashboard_reponses_delete', methods: ['POST'])]
    public function deleteReponse(Request $request, \App\Entity\Reponse $reponse, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_reponse'.$reponse->getId(), $request->request->get('_token'))) {
            $reclamation = $reponse->getReclamation();
            $entityManager->remove($reponse);
            $entityManager->flush();

            // Si plus de réponses, remettre le statut à "En attente"
            if ($reclamation && $reclamation->getReponses()->count() === 0) {
                $reclamation->setStatut('En attente');
                $entityManager->flush();
            }

            $this->addFlash('success', 'Réponse supprimée avec succès.');
        }

        return $this->redirectToRoute('app_dashboard_reponses');
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

