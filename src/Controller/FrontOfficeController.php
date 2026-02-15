<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\FormError;

#[Route('/front')]
class FrontOfficeController extends AbstractController
{
    #[Route('/', name: 'front_office_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('front_office/index.html.twig');
    }

    #[Route('/reclamation/nouvelle', name: 'front_office_nouvelle_reclamation', methods: ['GET', 'POST'])]
    public function nouvelleReclamation(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Valider les contraintes de l'entité Reclamation
            $errors = $validator->validate($reclamation);
            
            if (count($errors) > 0 || !$form->isValid()) {
                // Afficher les erreurs de validation Symfony
                foreach ($errors as $error) {
                    // Ajouter les erreurs à la propriété correspondante
                    $propertyPath = $error->getPropertyPath();
                    if ($form->has($propertyPath)) {
                        $form->get($propertyPath)->addError(new \Symfony\Component\Form\FormError($error->getMessage()));
                    }
                }
                
                return $this->render('front_office/nouvelle_reclamation.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            
            $reclamation->setDateCreation(new \DateTime());
            $reclamation->setStatut('En attente');
            
            try {
                $entityManager->persist($reclamation);
                $entityManager->flush();

                $this->addFlash('success', 'Votre réclamation a été soumise avec succès !');
                return $this->redirectToRoute('front_office_mes_reclamations', ['email' => $reclamation->getEmail()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la création de votre réclamation.');
            }
        }

        return $this->render('front_office/nouvelle_reclamation.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mes-reclamations', name: 'front_office_mes_reclamations', methods: ['GET', 'POST'])]
    public function mesReclamations(Request $request, ReclamationRepository $reclamationRepository): Response
    {
        $email = $request->query->get('email') ?? $request->request->get('email');
        $reclamations = [];
        $emailError = null;

        if ($email) {
            // Valider le format de l'email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailError = 'Le format de l\'adresse email est invalide.';
                $this->addFlash('error', $emailError);
            } else {
                // Sécuriser l'email pour éviter les injections
                $email = filter_var($email, FILTER_SANITIZE_EMAIL);
                $reclamations = $reclamationRepository->findByEmail($email);
                
                if (empty($reclamations)) {
                    $this->addFlash('info', 'Aucune réclamation trouvée pour cet email.');
                }
            }
        }

        return $this->render('front_office/mes_reclamations.html.twig', [
            'reclamations' => $reclamations,
            'email' => $email,
        ]);
    }

    #[Route('/reclamation/{id}', name: 'front_office_detail_reclamation', methods: ['GET'])]
    public function detailReclamation(Reclamation $reclamation): Response
    {
        return $this->render('front_office/detail_reclamation.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/reclamation/{id}/modifier', name: 'front_office_modifier_reclamation', methods: ['GET', 'POST'])]
    public function modifierReclamation(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Vérifier que la reclamation n'est pas deja traitee
        if ($reclamation->getStatut() === 'Traité') {
            $this->addFlash('error', 'Vous ne pouvez pas modifier une réclamation déjà traitée.');
            return $this->redirectToRoute('front_office_detail_reclamation', ['id' => $reclamation->getId()]);
        }

        // Vérifier que la réclamation n'a pas reçu de réponse
        if (count($reclamation->getReponses()) > 0) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier une réclamation qui a reçu une réponse.');
            return $this->redirectToRoute('front_office_detail_reclamation', ['id' => $reclamation->getId()]);
        }

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Valider les contraintes de l'entité Reclamation
            $errors = $validator->validate($reclamation);
            
            if (count($errors) > 0 || !$form->isValid()) {
                // Afficher les erreurs de validation Symfony
                foreach ($errors as $error) {
                    // Ajouter les erreurs à la propriété correspondante
                    $propertyPath = $error->getPropertyPath();
                    if ($form->has($propertyPath)) {
                        $form->get($propertyPath)->addError(new FormError($error->getMessage()));
                    }
                }
                
                return $this->render('front_office/modifier_reclamation.html.twig', [
                    'reclamation' => $reclamation,
                    'form' => $form->createView(),
                ]);
            }
            
            try {
                $entityManager->flush();

                $this->addFlash('success', 'Votre réclamation a été modifiée avec succès !');
                return $this->redirectToRoute('front_office_detail_reclamation', ['id' => $reclamation->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la modification de votre réclamation.');
            }
        }

        return $this->render('front_office/modifier_reclamation.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reclamation/{id}/supprimer', name: 'front_office_supprimer_reclamation', methods: ['POST'])]
    public function supprimerReclamation(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $email = $reclamation->getEmail();
        
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide. Suppression impossible.');
            return $this->redirectToRoute('front_office_mes_reclamations', ['email' => $email]);
        }
        
        // Vérifier que la réclamation n'est pas déjà traitée
        if ($reclamation->getStatut() === 'Traité') {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer une réclamation déjà traitée.');
            return $this->redirectToRoute('front_office_detail_reclamation', ['id' => $reclamation->getId()]);
        }
        
        // Vérifier que la réclamation existe
        if (!$reclamation) {
            $this->addFlash('error', 'La réclamation n\'existe pas.');
            return $this->redirectToRoute('front_office_mes_reclamations', ['email' => $email]);
        }
        
        try {
            $entityManager->remove($reclamation);
            $entityManager->flush();
            $this->addFlash('success', 'Votre réclamation a été supprimée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression de votre réclamation.');
        }

        return $this->redirectToRoute('front_office_mes_reclamations', ['email' => $email]);
    }
}