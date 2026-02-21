<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use App\Service\NotificationService;
use App\Service\ProfanityFilterService;
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
    public function nouvelleReclamation(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, ProfanityFilterService $profanityFilter, NotificationService $notificationService): Response
    {
        // Vérifier que l'utilisateur est authentifié
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $reclamation = new Reclamation();
        
        // Pré-remplir le nom et l'email de la réclamation avec les données de l'utilisateur
        $reclamation->setNomPatient($user->getPrenom() . ' ' . $user->getNom());
        $reclamation->setEmail($user->getEmail());
        
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Définir la date et le statut AVANT la validation
            $reclamation->setDateCreation(new \DateTime());
            $reclamation->setStatut('En attente');
            
            // Valider les contraintes de l'entité Reclamation
            $errors = $validator->validate($reclamation);

            // Vérifier le langage inapproprié dans le titre et la description
            $titreCheck = $profanityFilter->check($reclamation->getTitre() ?? '');
            $descCheck  = $profanityFilter->check($reclamation->getDescription() ?? '');
            if (!$titreCheck['clean']) {
                $form->get('titre')->addError(new FormError('🚫 Langage inapproprié détecté dans le titre. Veuillez reformuler de manière respectueuse.'));
            }
            if (!$descCheck['clean']) {
                $form->get('description')->addError(new FormError('🚫 Langage inapproprié détecté dans la description. Veuillez reformuler de manière respectueuse.'));
            }
            
            if (count($errors) > 0 || !$form->isValid() || !$titreCheck['clean'] || !$descCheck['clean']) {
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
                    'userInfo' => [
                        'nom' => $reclamation->getNomPatient(),
                        'email' => $reclamation->getEmail()
                    ],
                ]);
            }
            
            try {
                // Récupérer l'état mental envoyé par le chatbot (champ caché)
                $etatMental = $request->request->get('etat_mental');
                if ($etatMental) {
                    $reclamation->setEtatMental($etatMental);
                }
                
                $entityManager->persist($reclamation);
                $entityManager->flush();

                // Notifier tous les admins en temps réel
                $notificationService->notifyAllAdmins(
                    sprintf(
                        '📩 Nouvelle réclamation de %s : "%s" (%s)',
                        $reclamation->getNomPatient(),
                        mb_substr($reclamation->getTitre(), 0, 40),
                        $reclamation->getPriorite()
                    ),
                    'reclamation',
                    '/admin/reclamation/' . $reclamation->getId()
                );

                $this->addFlash('success', 'Votre réclamation a été soumise avec succès !');
                return $this->redirectToRoute('front_office_mes_reclamations');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la création de votre réclamation.');
            }
        }

        return $this->render('front_office/nouvelle_reclamation.html.twig', [
            'form' => $form->createView(),
            'userInfo' => [
                'nom' => $reclamation->getNomPatient(),
                'email' => $reclamation->getEmail()
            ],
        ]);
    }

    #[Route('/mes-reclamations', name: 'front_office_mes_reclamations', methods: ['GET'])]
    public function mesReclamations(ReclamationRepository $reclamationRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $reclamations = $reclamationRepository->findBy(
            ['email' => $user->getEmail()],
            ['dateCreation' => 'DESC']
        );

        return $this->render('front_office/mes_reclamations.html.twig', [
            'reclamations' => $reclamations,
        ]);
    }

    #[Route('/reclamation/{id}', name: 'front_office_detail_reclamation', methods: ['GET'])]
    public function detailReclamation(Reclamation $reclamation): Response
    {
        // Vérifier que l'utilisateur est propriétaire de la réclamation
        $user = $this->getUser();
        if (!$user || $reclamation->getEmail() !== $user->getEmail()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réclamation.');
        }

        return $this->render('front_office/detail_reclamation.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/reclamation/{id}/modifier', name: 'front_office_modifier_reclamation', methods: ['GET', 'POST'])]
    public function modifierReclamation(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager, ValidatorInterface $validator, ProfanityFilterService $profanityFilter): Response
    {
        // Vérifier que l'utilisateur est propriétaire de la réclamation
        $user = $this->getUser();
        if (!$user || $reclamation->getEmail() !== $user->getEmail()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réclamation.');
        }

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

            // Vérifier le langage inapproprié dans le titre et la description
            $titreCheck = $profanityFilter->check($reclamation->getTitre() ?? '');
            $descCheck  = $profanityFilter->check($reclamation->getDescription() ?? '');
            if (!$titreCheck['clean']) {
                $form->get('titre')->addError(new FormError('🚫 Langage inapproprié détecté dans le titre. Veuillez reformuler de manière respectueuse.'));
            }
            if (!$descCheck['clean']) {
                $form->get('description')->addError(new FormError('🚫 Langage inapproprié détecté dans la description. Veuillez reformuler de manière respectueuse.'));
            }
            
            if (count($errors) > 0 || !$form->isValid() || !$titreCheck['clean'] || !$descCheck['clean']) {
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
                    'userInfo' => [
                        'nom' => $reclamation->getNomPatient(),
                        'email' => $reclamation->getEmail()
                    ],
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
            'userInfo' => [
                'nom' => $reclamation->getNomPatient(),
                'email' => $reclamation->getEmail()
            ],
        ]);
    }

    #[Route('/reclamation/{id}/supprimer', name: 'front_office_supprimer_reclamation', methods: ['POST'])]
    public function supprimerReclamation(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est propriétaire de la réclamation
        $user = $this->getUser();
        if (!$user || $reclamation->getEmail() !== $user->getEmail()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réclamation.');
        }

        $email = $reclamation->getEmail();
        
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide. Suppression impossible.');
            return $this->redirectToRoute('front_office_mes_reclamations');
        }
        
        // Vérifier que la réclamation n'est pas déjà traitée
        if ($reclamation->getStatut() === 'Traité') {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer une réclamation déjà traitée.');
            return $this->redirectToRoute('front_office_detail_reclamation', ['id' => $reclamation->getId()]);
        }
        
        // Vérifier que la réclamation existe
        if (!$reclamation) {
            $this->addFlash('error', 'La réclamation n\'existe pas.');
            return $this->redirectToRoute('front_office_mes_reclamations');
        }
        
        try {
            $entityManager->remove($reclamation);
            $entityManager->flush();
            $this->addFlash('success', 'Votre réclamation a été supprimée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression de votre réclamation.');
        }

        return $this->redirectToRoute('front_office_mes_reclamations');
    }
}