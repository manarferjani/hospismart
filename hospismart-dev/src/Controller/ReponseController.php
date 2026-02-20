<?php

namespace App\Controller;

use App\Entity\Reponse;
use App\Form\ReponseType;
use App\Repository\ReponseRepository;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\FormError;

#[Route('/admin/reponse')]
class ReponseController extends AbstractController
{
    #[Route('/', name: 'reponse_index', methods: ['GET'])]
    public function index(Request $request, ReponseRepository $reponseRepository): Response
    {
        // Récupérer le paramètre de filtre par statut
        $filterStatut = $request->query->get('filterStatut', 'total'); // 'total', 'En attente', 'En cours', 'Traité'
        
        // Récupérer toutes les réponses
        $allReponses = $reponseRepository->findBy([], ['dateReponse' => 'DESC']);
        
        // Filtrer les réponses selon le statut de la réclamation associée
        $reponses = [];
        if ($filterStatut === 'total') {
            $reponses = $allReponses;
        } else {
            foreach ($allReponses as $reponse) {
                if ($reponse->getReclamation() && $reponse->getReclamation()->getStatut() === $filterStatut) {
                    $reponses[] = $reponse;
                }
            }
        }
        
        return $this->render('reponse/index.html.twig', [
            'reponses' => $reponses,
            'filterStatut' => $filterStatut,
        ]);
    }

    #[Route('/new', name: 'reponse_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ReclamationRepository $reclamationRepository, ValidatorInterface $validator): Response
    {
        $reponse = new Reponse();
        
        $reclamationId = $request->query->get('reclamation');
        if ($reclamationId) {
            // Valider que l'ID est un entier
            if (!ctype_digit((string)$reclamationId)) {
                $this->addFlash('error', 'L\'ID de la réclamation est invalide.');
                return $this->redirectToRoute('reponse_index');
            }
            
            $reclamation = $reclamationRepository->find($reclamationId);
            if (!$reclamation) {
                $this->addFlash('error', 'La réclamation n\'existe pas.');
                return $this->redirectToRoute('reponse_index');
            }
            
            $reponse->setReclamation($reclamation);
        }
        
        $form = $this->createForm(ReponseType::class, $reponse, [
            'include_reclamation' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Valider les contraintes de l'entité Reponse
            $errors = $validator->validate($reponse);
            
            if (count($errors) > 0 || !$form->isValid()) {
                // Afficher les erreurs de validation Symfony
                foreach ($errors as $error) {
                    // Ajouter les erreurs à la propriété correspondante
                    $propertyPath = $error->getPropertyPath();
                    if ($form->has($propertyPath)) {
                        $form->get($propertyPath)->addError(new FormError($error->getMessage()));
                    }
                }
                
                return $this->render('reponse/new.html.twig', [
                    'reponse' => $reponse,
                    'form' => $form->createView(),
                ]);
            }
            
            // Vérifier qu'une réclamation est associée
            if (!$reponse->getReclamation()) {
                $form->get('reclamation')->addError(new FormError('Vous devez sélectionner une réclamation.'));
                return $this->render('reponse/new.html.twig', [
                    'reponse' => $reponse,
                    'form' => $form->createView(),
                ]);
            }
            
            try {
                $reponse->setDateReponse(new \DateTime());
                
                // Ne pas changer automatiquement le statut de la réclamation
                // L'administrateur peut le faire manuellement s'il le souhaite
                
                $entityManager->persist($reponse);
                $entityManager->flush();

                $this->addFlash('success', 'La réponse a été créée avec succès.');
                return $this->redirectToRoute('reponse_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la création de la réponse.');
            }
        }

        return $this->render('reponse/new.html.twig', [
            'reponse' => $reponse,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'reponse_show', methods: ['GET'])]
    public function show(Reponse $reponse): Response
    {
        return $this->render('reponse/show.html.twig', [
            'reponse' => $reponse,
        ]);
    }

    #[Route('/{id}/edit', name: 'reponse_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reponse $reponse, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $form = $this->createForm(ReponseType::class, $reponse, [
            'include_reclamation' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Valider les contraintes de l'entité Reponse
            $errors = $validator->validate($reponse);
            
            if (count($errors) > 0 || !$form->isValid()) {
                // Afficher les erreurs de validation Symfony
                foreach ($errors as $error) {
                    // Ajouter les erreurs à la propriété correspondante
                    $propertyPath = $error->getPropertyPath();
                    if ($form->has($propertyPath)) {
                        $form->get($propertyPath)->addError(new FormError($error->getMessage()));
                    }
                }
                
                return $this->render('reponse/edit.html.twig', [
                    'reponse' => $reponse,
                    'form' => $form->createView(),
                ]);
            }
            
            try {
                $entityManager->flush();

                $this->addFlash('success', 'La réponse a été modifiée avec succès.');
                return $this->redirectToRoute('reponse_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la modification de la réponse.');
            }
        }

        return $this->render('reponse/edit.html.twig', [
            'reponse' => $reponse,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'reponse_delete', methods: ['POST'])]
    public function delete(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('delete'.$reponse->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide. Suppression impossible.');
            return $this->redirectToRoute('reponse_index');
        }
        
        // Vérifier que la réponse existe
        if (!$reponse) {
            $this->addFlash('error', 'La réponse n\'existe pas.');
            return $this->redirectToRoute('reponse_index');
        }
        
        try {
            $reclamation = $reponse->getReclamation();
            
            $entityManager->remove($reponse);
            $entityManager->flush();
            
            // Si la réclamation n'a plus de réponses, la remettre en "En attente"
            if ($reclamation && $reclamation->getReponses()->isEmpty()) {
                $reclamation->setStatut('En attente');
                $entityManager->flush();
            }

            $this->addFlash('success', 'La réponse a été supprimée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression de la réponse.');
        }

        return $this->redirectToRoute('reponse_index');
    }
}