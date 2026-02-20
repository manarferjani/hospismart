<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Form\ReponseType;
use App\Repository\ReclamationRepository;
use App\Repository\AuditLogRepository;
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
        // Récupérer les paramètres de tri et filtre
        $sortBy = $request->query->get('sortBy', 'date');
        $sortOrder = $request->query->get('sortOrder', 'DESC');
        $filterStatut = $request->query->get('filterStatut', 'total');
        
        // Nouveaux paramètres de recherche avancée
        $searchQuery = $request->query->get('searchQuery', '');
        $filterCategorie = $request->query->get('filterCategorie', '');
        $filterPriorite = $request->query->get('filterPriorite', '');
        $filterStatutSearch = $request->query->get('filterStatutSearch', 'total');
        
        // Valider les paramètres
        if (!in_array($sortBy, ['date', 'nomPatient'])) {
            $sortBy = 'date';
        }
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        }
        if (!in_array($filterStatut, ['total', 'En attente', 'En cours', 'Traité'])) {
            $filterStatut = 'total';
        }
        if (!in_array($filterStatutSearch, ['total', 'En attente', 'En cours', 'Traité'])) {
            $filterStatutSearch = 'total';
        }
        
        // Mapper les noms de tri vers les colonnes de la base de données
        $orderBy = $sortBy === 'date' ? 'dateCreation' : 'nomPatient';
        
        // Récupérer toutes les réclamations avec le tri spécifié
        $allReclamations = $reclamationRepository->findBy([], [$orderBy => $sortOrder]);
        
        // Appliquer les filtres
        $reclamations = [];
        foreach ($allReclamations as $reclamation) {
            $matches = true;
            
            // Filtre par statut (si filterStatutSearch est utilisé)
            if ($filterStatutSearch !== 'total') {
                if ($reclamation->getStatut() !== $filterStatutSearch) {
                    $matches = false;
                }
            }
            
            // Filtre par catégorie
            if (!empty($filterCategorie)) {
                if ($reclamation->getCategorie() !== $filterCategorie) {
                    $matches = false;
                }
            }
            
            // Filtre par priorité
            if (!empty($filterPriorite)) {
                if ($reclamation->getPriorite() !== $filterPriorite) {
                    $matches = false;
                }
            }
            
            // Recherche par texte (titre, patient, email)
            if (!empty($searchQuery)) {
                $lowerSearchQuery = strtolower($searchQuery);
                $titre = strtolower($reclamation->getTitre());
                $patient = strtolower($reclamation->getNomPatient());
                $email = strtolower($reclamation->getEmail());
                
                if (strpos($titre, $lowerSearchQuery) === false &&
                    strpos($patient, $lowerSearchQuery) === false &&
                    strpos($email, $lowerSearchQuery) === false) {
                    $matches = false;
                }
            }
            
            if ($matches) {
                $reclamations[] = $reclamation;
            }
        }
        
        // Appliquer aussi le filtre statut pour les cards de statistiques
        $filteredReclamations = [];
        if ($filterStatut === 'total') {
            $filteredReclamations = $allReclamations;
        } else {
            foreach ($allReclamations as $reclamation) {
                if ($reclamation->getStatut() === $filterStatut) {
                    $filteredReclamations[] = $reclamation;
                }
            }
        }
        
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
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'filterStatut' => $filterStatut,
            'searchQuery' => $searchQuery,
            'filterCategorie' => $filterCategorie,
            'filterPriorite' => $filterPriorite,
            'filterStatutSearch' => $filterStatutSearch,
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
    public function exportReclamationsPdf(ReclamationRepository $reclamationRepository, PdfExportService $pdfService): Response
    {
        $reclamations = $reclamationRepository->findAll();
        
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
    public function exportReclamationsExcel(ReclamationRepository $reclamationRepository, ExcelExportService $excelService)
    {
        $reclamations = $reclamationRepository->findAll();
        
        return $excelService->generateReclamationsExcel($reclamations);
    }

    #[Route('/reclamation/{id}/export-excel', name: 'back_office_export_reclamation_excel', methods: ['GET'])]
    public function exportReclamationDetailExcel(Reclamation $reclamation, ExcelExportService $excelService)
    {
        return $excelService->generateReclamationDetailExcel($reclamation);
    }
}