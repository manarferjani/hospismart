<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Entity\User;
use App\Form\ReponseType;
use App\Repository\ReclamationRepository;
use App\Repository\AuditLogRepository;
use App\Repository\ReponseRepository;
use App\Service\PdfExportService;
use App\Service\ExcelExportService;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\FormError;

#[Route('/admin')]
class BackOfficeController extends AbstractController
{
    #[Route('/', name: 'back_office_dashboard', methods: ['GET'])]
    public function dashboard(Request $request, ReclamationRepository $reclamationRepository): Response
    {
        // Récupérer les paramètres de tri
        $sortBy = $request->query->get('sortBy', 'date');
        $sortOrder = $request->query->get('sortOrder', 'DESC');
        
        // Valider les paramètres de tri
        if (!in_array($sortBy, ['date', 'nomPatient'])) {
            $sortBy = 'date';
        }
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        }
        
        // Mapper les noms de tri vers les colonnes de la base de données
        $orderBy = $sortBy === 'date' ? 'dateCreation' : 'nomPatient';
        
        // Récupérer toutes les réclamations pour les stats
        $allReclamations = $reclamationRepository->findBy([], [$orderBy => $sortOrder]);
        
        // Appliquer les filtres
        $reclamations = $this->getFilteredReclamations($request, $allReclamations);
        
        // Paramètres pour la vue (à renvoyer pour maintenir l'état des filtres)
        $filterStatut = $request->query->get('filterStatut', 'total');
        $searchQuery = $request->query->get('searchQuery', '');
        $filterCategorie = $request->query->get('filterCategorie', '');
        $filterPriorite = $request->query->get('filterPriorite', '');
        
        $stats = $reclamationRepository->countByStatut();
        
        $statsArray = [
            'total' => count($allReclamations),
            'en_attente' => 0,
            'en_cours' => 0,
            'traite' => 0,
            'priorite_basse' => 0,
            'priorite_normale' => 0,
            'priorite_haute' => 0,
            'priorite_urgente' => 0,
        ];
        
        foreach ($stats as $stat) {
            if ($stat['statut'] === 'En attente') $statsArray['en_attente'] = $stat['total'];
            if ($stat['statut'] === 'En cours') $statsArray['en_cours'] = $stat['total'];
            if ($stat['statut'] === 'Traité') $statsArray['traite'] = $stat['total'];
        }
        
        // Calculer les statistiques par priorité
        foreach ($allReclamations as $reclamation) {
            switch ($reclamation->getPriorite()) {
                case 'Basse':
                    $statsArray['priorite_basse']++;
                    break;
                case 'Normale':
                    $statsArray['priorite_normale']++;
                    break;
                case 'Haute':
                    $statsArray['priorite_haute']++;
                    break;
                case 'Urgente':
                    $statsArray['priorite_urgente']++;
                    break;
            }
        }
        
        // Calculer les réclamations non traitées
        $statsArray['non_traitees'] = $statsArray['en_attente'] + $statsArray['en_cours'];
        
        // Taux de réponse
        $totalWithResponse = 0;
        foreach ($allReclamations as $reclamation) {
            if ($reclamation->getReponses()->count() > 0) {
                $totalWithResponse++;
            }
        }
        $statsArray['taux_reponse'] = $statsArray['total'] > 0 ? round(($totalWithResponse / $statsArray['total']) * 100, 1) : 0;

        // Statistiques par état mental
        $etatMentalStats = [
            'Calme' => 0,
            'Frustré' => 0,
            'En colère' => 0,
            'Anxieux' => 0,
            'Triste' => 0,
            'Satisfait' => 0,
            'Non analysé' => 0,
        ];
        $alertesMentales = []; // Réclamations avec état critique
        foreach ($allReclamations as $reclamation) {
            $etat = $reclamation->getEtatMental();
            if ($etat && isset($etatMentalStats[$etat])) {
                $etatMentalStats[$etat]++;
            } else {
                $etatMentalStats['Non analysé']++;
            }
            // Alertes pour les états critiques
            if (in_array($etat, ['En colère', 'Anxieux', 'Triste']) && $reclamation->getStatut() !== 'Traité') {
                $alertesMentales[] = $reclamation;
            }
        }
        $statsArray['etat_mental'] = $etatMentalStats;
        $statsArray['alertes_mentales'] = count($alertesMentales);

        // Pagination manuelle (10 par page)
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = 10;
        $totalReclamations = count($reclamations);
        $totalPages = (int) ceil($totalReclamations / $perPage);
        $reclamationsPaginated = array_slice($reclamations, ($page - 1) * $perPage, $perPage);

        return $this->render('back_office/dashboard.html.twig', [
            'reclamations' => $reclamationsPaginated,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalReclamations' => $totalReclamations,
            'stats' => $statsArray,
            'alertesMentales' => $alertesMentales,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'filterStatut' => $filterStatut,
            'searchQuery' => $searchQuery,
            'filterCategorie' => $filterCategorie,
            'filterPriorite' => $filterPriorite,
        ]);
    }

    #[Route('/reclamation/{id}', name: 'back_office_voir_reclamation', methods: ['GET', 'POST'])]
    public function voirReclamation(Request $request, Reclamation $reclamation, AuditLogRepository $auditLogRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator, AuditService $auditService): Response
    {
        // Traitement du formulaire de réponse (fusionné pour une meilleure expérience UX)
        $reponse = new Reponse();
        $reponse->setReclamation($reclamation);
        
        $form = $this->createForm(ReponseType::class, $reponse, [
            'include_reclamation' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            /** @var User $user */
            $user = $this->getUser();
            $reponse->setAdminNom($user->getPrenom() . ' ' . $user->getNom());
            $reponse->setAdminEmail($user->getEmail());
            $reponse->setAdminAdresse($user->getAdresse());
            
            // Valider les contraintes de l'entité Reponse
            $errors = $validator->validate($reponse);
            
            if (count($errors) > 0 || !$form->isValid()) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                $reponse->setDateReponse(new \DateTime());
                
                // Marquer automatiquement la réclamation comme traitée
                $oldStatus = $reclamation->getStatut();
                if ($oldStatus !== 'Traité') {
                    $reclamation->setStatut('Traité');
                    $auditService->logStatusChanged($reclamation->getId(), $oldStatus, 'Traité', $reponse->getAdminNom());
                }
                
                $entityManager->persist($reponse);
                $entityManager->persist($reclamation);
                $entityManager->flush();

                // Log the response action
                $auditService->logReclamationResponded($reclamation->getId(), $reponse->getContenu(), $reponse->getAdminNom());

                $this->addFlash('success', 'Réponse envoyée avec succès !');
                return $this->redirectToRoute('back_office_voir_reclamation', ['id' => $reclamation->getId()]);
            }
        }

        $auditLogs = $auditLogRepository->findByReclamation($reclamation->getId());
        
        return $this->render('back_office/voir_reclamation.html.twig', [
            'reclamation' => $reclamation,
            'auditLogs' => $auditLogs,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reclamation/{id}/repondre', name: 'back_office_repondre_reclamation', methods: ['GET', 'POST'])]
    public function repondreReclamation(Reclamation $reclamation): Response
    {
        // Redirection vers la vue unifiée
        return $this->redirectToRoute('back_office_voir_reclamation', ['id' => $reclamation->getId()]);
    }

    #[Route('/reclamation/{id}/supprimer', name: 'back_office_supprimer_reclamation', methods: ['POST'])]
    public function supprimerReclamation(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager, AuditService $auditService): Response
    {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide. Suppression impossible.');
            return $this->redirectToRoute('back_office_dashboard');
        }
        
        // Vérifier que la réclamation existe
        if (!$reclamation) {
            $this->addFlash('error', 'La réclamation n\'existe pas.');
            return $this->redirectToRoute('back_office_dashboard');
        }
        
        try {
            // Log the deletion before removing
            $reclamationId = $reclamation->getId();
            $reclamationTitre = $reclamation->getTitre();
            
            $entityManager->remove($reclamation);
            $entityManager->flush();
            
            // Log the deletion action
            $auditService->logReclamationDeleted($reclamationId, $reclamationTitre);
            
            $this->addFlash('success', 'Réclamation supprimée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression de la réclamation.');
        }

        return $this->redirectToRoute('back_office_dashboard');
    }

    #[Route('/export/pdf', name: 'back_office_export_pdf', methods: ['GET'])]
    public function exportReclamationsPdf(Request $request, ReclamationRepository $reclamationRepository, PdfExportService $pdfService): Response
    {
        $allReclamations = $reclamationRepository->findBy([], ['dateCreation' => 'DESC']);
        $reclamations = $this->getFilteredReclamations($request, $allReclamations);
        
        $pdfContent = $pdfService->generateReclamationsPdf($reclamations);

        return new Response($pdfContent, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reclamations_' . date('Y-m-d_H-i-s') . '.pdf"',
        ]);
    }

    #[Route('/reclamation/{id}/export-pdf', name: 'back_office_export_reclamation_pdf', methods: ['GET'])]
    public function exportReclamationDetailPdf(Reclamation $reclamation, PdfExportService $pdfService): Response
    {
        $pdfContent = $pdfService->generateReclamationDetailPdf($reclamation);

        return new Response($pdfContent, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reclamation_' . $reclamation->getId() . '_' . date('Y-m-d') . '.pdf"',
        ]);
    }

    #[Route('/export/excel', name: 'back_office_export_excel', methods: ['GET'])]
    public function exportReclamationsExcel(Request $request, ReclamationRepository $reclamationRepository, ExcelExportService $excelService)
    {
        $allReclamations = $reclamationRepository->findBy([], ['dateCreation' => 'DESC']);
        $reclamations = $this->getFilteredReclamations($request, $allReclamations);
        
        return $excelService->generateReclamationsExcel($reclamations);
    }

    #[Route('/reclamation/{id}/export-excel', name: 'back_office_export_reclamation_excel', methods: ['GET'])]
    public function exportReclamationDetailExcel(Reclamation $reclamation, ExcelExportService $excelService)
    {
        return $excelService->generateReclamationDetailExcel($reclamation);
    }

    // =============================================
    // GESTION DES RÉPONSES
    // =============================================

    #[Route('/reponses', name: 'back_office_reponses_liste', methods: ['GET'])]
    public function listeReponses(ReponseRepository $reponseRepository): Response
    {
        $reponses = $reponseRepository->findBy([], ['dateReponse' => 'DESC']);

        return $this->render('back_office/reponses_liste.html.twig', [
            'reponses' => $reponses,
        ]);
    }

    #[Route('/reponse/{id}/modifier', name: 'back_office_modifier_reponse', methods: ['GET', 'POST'])]
    public function modifierReponse(Request $request, Reponse $reponse, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $form = $this->createForm(ReponseType::class, $reponse, [
            'include_reclamation' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $validator->validate($reponse);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                $entityManager->flush();
                $this->addFlash('success', 'Réponse modifiée avec succès.');
                return $this->redirectToRoute('back_office_reponses_liste');
            }
        }

        return $this->render('back_office/modifier_reponse.html.twig', [
            'reponse' => $reponse,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reponse/{id}/supprimer', name: 'back_office_supprimer_reponse', methods: ['POST'])]
    public function supprimerReponse(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_reponse_' . $reponse->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('back_office_reponses_liste');
        }

        $reclamationId = $reponse->getReclamation()?->getId();
        $entityManager->remove($reponse);
        $entityManager->flush();

        $this->addFlash('success', 'Réponse supprimée avec succès.');
        return $this->redirectToRoute('back_office_reponses_liste');
    }

    private function getFilteredReclamations(Request $request, array $allReclamations): array
    {
        $filterStatut = $request->query->get('filterStatut', 'total');
        $searchQuery = $request->query->get('searchQuery', '');
        $filterCategorie = $request->query->get('filterCategorie', '');
        $filterPriorite = $request->query->get('filterPriorite', '');

        $filtered = [];
        foreach ($allReclamations as $reclamation) {
            $matches = true;
            
            // Filtre statut/type card
            if (in_array($filterStatut, ['En attente', 'En cours', 'Traité'])) {
                if ($reclamation->getStatut() !== $filterStatut) $matches = false;
            } elseif ($filterStatut === 'non_traitees') {
                if ($reclamation->getStatut() === 'Traité') $matches = false;
            } elseif ($filterStatut === 'urgences') {
                if ($reclamation->getPriorite() !== 'Urgente') $matches = false;
            }

            // Filtre catégorie
            if (!empty($filterCategorie) && $reclamation->getCategorie() !== $filterCategorie) $matches = false;
            
            // Filtre priorité
            if (!empty($filterPriorite) && $reclamation->getPriorite() !== $filterPriorite) $matches = false;

            // Filtre recherche texte
            if (!empty($searchQuery)) {
                $q = mb_strtolower($searchQuery);
                if (mb_strpos(mb_strtolower($reclamation->getTitre()), $q) === false &&
                    mb_strpos(mb_strtolower($reclamation->getNomPatient()), $q) === false &&
                    mb_strpos(mb_strtolower($reclamation->getEmail()), $q) === false) {
                    $matches = false;
                }
            }

            if ($matches) $filtered[] = $reclamation;
        }

        return $filtered;
    }
}