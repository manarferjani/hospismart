<?php

namespace App\Controller; // Déclaration du namespace du contrôleur Reponse

use App\Entity\Reponse; // Import de l'entité Reponse
use App\Form\ReponseType; // Import du formulaire de type ReponseType
use App\Repository\ReponseRepository; // Import du repository pour accéder aux réponses en base
use App\Repository\ReclamationRepository; // Import du repository pour accéder aux réclamations en base
use Doctrine\ORM\EntityManagerInterface; // Import de l'EntityManager pour persister/supprimer des entités
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Import du contrôleur de base Symfony
use Symfony\Component\HttpFoundation\Request; // Import de l'objet Request pour gérer les requêtes HTTP
use Symfony\Component\HttpFoundation\Response; // Import de l'objet Response pour renvoyer des réponses HTTP
use Symfony\Component\Routing\Annotation\Route; // Import de l'annotation Route pour définir les routes
use Symfony\Component\Validator\Validator\ValidatorInterface; // Import du validateur pour valider les entités
use Symfony\Component\Form\FormError; // Import de FormError pour ajouter des erreurs manuelles aux formulaires

#[Route('/admin/reponse')] // Préfixe de route : toutes les routes de ce contrôleur commencent par /admin/reponse (espace admin)
class ReponseController extends AbstractController // Le contrôleur hérite d'AbstractController (accès aux services Symfony)
{
    #[Route('/', name: 'reponse_index', methods: ['GET'])] // Route GET pour afficher la liste de toutes les réponses
    public function index(Request $request, ReponseRepository $reponseRepository): Response // Méthode qui affiche la liste des réponses avec filtrage possible
    {
        // Récupérer le paramètre de filtre par statut
        $filterStatut = $request->query->get('filterStatut', 'total'); // Récupère le filtre depuis l'URL (par défaut 'total' = toutes les réponses)
        
        // Récupérer toutes les réponses
        $allReponses = $reponseRepository->findBy([], ['dateReponse' => 'DESC']); // Récupère toutes les réponses triées par date décroissante
        
        // Filtrer les réponses selon le statut de la réclamation associée
        $reponses = []; // Initialise un tableau vide pour les réponses filtrées
        if ($filterStatut === 'total') { // Si le filtre est 'total', on affiche toutes les réponses
            $reponses = $allReponses; // Pas de filtrage
        } else { // Sinon, on filtre par statut de réclamation
            foreach ($allReponses as $reponse) { // Parcourt chaque réponse
                if ($reponse->getReclamation() && $reponse->getReclamation()->getStatut() === $filterStatut) { // Vérifie si la réclamation associée a le statut demandé
                    $reponses[] = $reponse; // Ajoute la réponse au tableau filtré
                }
            }
        }
        
        return $this->render('reponse/index.html.twig', [ // Rend le template avec la liste des réponses
            'reponses' => $reponses, // Passe les réponses filtrées au template
            'filterStatut' => $filterStatut, // Passe le filtre actuel au template (pour l'affichage du filtre actif)
        ]);
    }

    #[Route('/new', name: 'reponse_new', methods: ['GET', 'POST'])] // Route GET/POST pour créer une nouvelle réponse
    public function new(Request $request, EntityManagerInterface $entityManager, ReclamationRepository $reclamationRepository, ValidatorInterface $validator): Response // Méthode de création d'une nouvelle réponse avec injection des dépendances
    {
        $reponse = new Reponse(); // Crée une nouvelle instance de Reponse
        
        $reclamationId = $request->query->get('reclamation'); // Récupère l'ID de la réclamation depuis les paramètres GET (pré-sélection)
        if ($reclamationId) { // Si un ID de réclamation est fourni dans l'URL
            // Valider que l'ID est un entier
            if (!ctype_digit((string)$reclamationId)) { // Vérifie que l'ID est bien un nombre entier
                $this->addFlash('error', 'L\'ID de la réclamation est invalide.'); // Message d'erreur
                return $this->redirectToRoute('reponse_index'); // Redirige vers la liste des réponses
            }
            
            $reclamation = $reclamationRepository->find($reclamationId); // Cherche la réclamation par son ID en base de données
            if (!$reclamation) { // Si la réclamation n'existe pas
                $this->addFlash('error', 'La réclamation n\'existe pas.'); // Message d'erreur
                return $this->redirectToRoute('reponse_index'); // Redirige vers la liste
            }
            
            $reponse->setReclamation($reclamation); // Associe la réclamation trouvée à la nouvelle réponse
        }
        
        $form = $this->createForm(ReponseType::class, $reponse, [ // Crée le formulaire Symfony lié à l'entité Reponse
            'include_reclamation' => true, // Option : inclure le champ de sélection de réclamation dans le formulaire
        ]);
        $form->handleRequest($request); // Traite la requête HTTP et remplit le formulaire avec les données soumises

        if ($form->isSubmitted()) { // Vérifie si le formulaire a été soumis
            // Valider les contraintes de l'entité Reponse
            $errors = $validator->validate($reponse); // Exécute les validations définies dans l'entité (Assert)
            
            if (count($errors) > 0 || !$form->isValid()) { // Si des erreurs de validation existent
                // Afficher les erreurs de validation Symfony
                foreach ($errors as $error) { // Parcourt chaque erreur de validation
                    // Ajouter les erreurs à la propriété correspondante
                    $propertyPath = $error->getPropertyPath(); // Récupère le nom du champ en erreur
                    if ($form->has($propertyPath)) { // Vérifie si le formulaire contient ce champ
                        $form->get($propertyPath)->addError(new FormError($error->getMessage())); // Ajoute l'erreur au champ du formulaire
                    }
                }
                
                return $this->render('reponse/new.html.twig', [ // Ré-affiche le formulaire avec les erreurs
                    'reponse' => $reponse, // Passe la réponse au template
                    'form' => $form->createView(), // Passe la vue du formulaire
                ]);
            }
            
            // Vérifier qu'une réclamation est associée
            if (!$reponse->getReclamation()) { // Si aucune réclamation n'est sélectionnée
                $form->get('reclamation')->addError(new FormError('Vous devez sélectionner une réclamation.')); // Ajoute une erreur au champ réclamation
                return $this->render('reponse/new.html.twig', [ // Ré-affiche le formulaire avec l'erreur
                    'reponse' => $reponse, // Passe la réponse au template
                    'form' => $form->createView(), // Passe la vue du formulaire
                ]);
            }
            
            try { // Bloc try-catch pour gérer les erreurs de persistance
                $reponse->setDateReponse(new \DateTime()); // Définit la date de réponse à maintenant
                
                // Ne pas changer automatiquement le statut de la réclamation
                // L'administrateur peut le faire manuellement s'il le souhaite
                
                $entityManager->persist($reponse); // Prépare l'insertion de la réponse en base de données
                $entityManager->flush(); // Exécute la requête SQL d'insertion en base de données

                $this->addFlash('success', 'La réponse a été créée avec succès.'); // Ajoute un message flash de succès
                return $this->redirectToRoute('reponse_index'); // Redirige vers la liste des réponses
            } catch (\Exception $e) { // En cas d'erreur lors de la persistance
                $this->addFlash('error', 'Une erreur est survenue lors de la création de la réponse.'); // Message flash d'erreur
            }
        }

        return $this->render('reponse/new.html.twig', [ // Affiche le formulaire de création (GET ou après erreur)
            'reponse' => $reponse, // Passe la réponse au template
            'form' => $form->createView(), // Passe la vue du formulaire
        ]);
    }

    #[Route('/{id}', name: 'reponse_show', methods: ['GET'])] // Route GET pour afficher le détail d'une réponse par son ID
    public function show(Reponse $reponse): Response // Méthode qui affiche le détail d'une réponse (ParamConverter automatique)
    {
        return $this->render('reponse/show.html.twig', [ // Rend le template de détail de la réponse
            'reponse' => $reponse, // Passe la réponse au template
        ]);
    }

    #[Route('/{id}/edit', name: 'reponse_edit', methods: ['GET', 'POST'])] // Route GET/POST pour modifier une réponse existante
    public function edit(Request $request, Reponse $reponse, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response // Méthode de modification d'une réponse avec injection des dépendances
    {
        $form = $this->createForm(ReponseType::class, $reponse, [ // Crée le formulaire pré-rempli avec les données existantes
            'include_reclamation' => false, // Option : ne pas inclure le champ réclamation (on ne change pas la réclamation en édition)
        ]);
        $form->handleRequest($request); // Traite la requête HTTP et met à jour le formulaire

        if ($form->isSubmitted()) { // Vérifie si le formulaire a été soumis
            // Valider les contraintes de l'entité Reponse
            $errors = $validator->validate($reponse); // Exécute les validations définies dans l'entité
            
            if (count($errors) > 0 || !$form->isValid()) { // Si des erreurs de validation existent
                // Afficher les erreurs de validation Symfony
                foreach ($errors as $error) { // Parcourt chaque erreur de validation
                    // Ajouter les erreurs à la propriété correspondante
                    $propertyPath = $error->getPropertyPath(); // Récupère le nom du champ en erreur
                    if ($form->has($propertyPath)) { // Vérifie si le formulaire contient ce champ
                        $form->get($propertyPath)->addError(new FormError($error->getMessage())); // Ajoute l'erreur au champ
                    }
                }
                
                return $this->render('reponse/edit.html.twig', [ // Ré-affiche le formulaire avec les erreurs
                    'reponse' => $reponse, // Passe la réponse au template
                    'form' => $form->createView(), // Passe la vue du formulaire
                ]);
            }
            
            try { // Bloc try-catch pour gérer les erreurs de mise à jour
                $entityManager->flush(); // Exécute la requête SQL de mise à jour en base de données

                $this->addFlash('success', 'La réponse a été modifiée avec succès.'); // Message flash de succès
                return $this->redirectToRoute('reponse_index'); // Redirige vers la liste des réponses
            } catch (\Exception $e) { // En cas d'erreur lors de la mise à jour
                $this->addFlash('error', 'Une erreur est survenue lors de la modification de la réponse.'); // Message flash d'erreur
            }
        }

        return $this->render('reponse/edit.html.twig', [ // Affiche le formulaire de modification (GET ou après erreur)
            'reponse' => $reponse, // Passe la réponse au template
            'form' => $form->createView(), // Passe la vue du formulaire
        ]);
    }

    #[Route('/{id}', name: 'reponse_delete', methods: ['POST'])] // Route POST pour supprimer une réponse
    public function delete(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response // Méthode de suppression d'une réponse
    {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('delete'.$reponse->getId(), $request->request->get('_token'))) { // Vérifie la validité du token CSRF pour sécuriser la suppression
            $this->addFlash('error', 'Token de sécurité invalide. Suppression impossible.'); // Message d'erreur si token invalide
            return $this->redirectToRoute('reponse_index'); // Redirige vers la liste
        }
        
        // Vérifier que la réponse existe
        if (!$reponse) { // Vérification de sécurité (normalement toujours vrai grâce au ParamConverter)
            $this->addFlash('error', 'La réponse n\'existe pas.'); // Message d'erreur
            return $this->redirectToRoute('reponse_index'); // Redirige vers la liste
        }
        
        try { // Bloc try-catch pour gérer les erreurs de suppression
            $reclamation = $reponse->getReclamation(); // Récupère la réclamation associée à la réponse (avant suppression)
            
            $entityManager->remove($reponse); // Prépare la suppression de la réponse
            $entityManager->flush(); // Exécute la requête SQL de suppression en base de données
            
            // Si la réclamation n'a plus de réponses, la remettre en "En attente"
            if ($reclamation && $reclamation->getReponses()->isEmpty()) { // Vérifie si la réclamation n'a plus aucune réponse
                $reclamation->setStatut('En attente'); // Remet le statut à "En attente"
                $entityManager->flush(); // Sauvegarde le changement de statut en base
            }

            $this->addFlash('success', 'La réponse a été supprimée avec succès.'); // Message flash de succès
        } catch (\Exception $e) { // En cas d'erreur lors de la suppression
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression de la réponse.'); // Message flash d'erreur
        }

        return $this->redirectToRoute('reponse_index'); // Redirige vers la liste des réponses
    }
}