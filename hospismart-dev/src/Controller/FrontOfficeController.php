<?php

namespace App\Controller; // Déclaration du namespace du contrôleur FrontOffice

use App\Entity\Reclamation; // Import de l'entité Reclamation
use App\Form\ReclamationType; // Import du formulaire de type ReclamationType
use App\Repository\ReclamationRepository; // Import du repository pour accéder aux réclamations en base
use App\Service\NotificationService; // Import du service de notification pour alerter les admins
use App\Service\ProfanityFilterService; // Import du service de filtrage de mots inappropriés
use Doctrine\ORM\EntityManagerInterface; // Import de l'EntityManager pour persister/supprimer des entités
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Import du contrôleur de base Symfony
use Symfony\Component\HttpFoundation\Request; // Import de l'objet Request pour gérer les requêtes HTTP
use Symfony\Component\HttpFoundation\Response; // Import de l'objet Response pour renvoyer des réponses HTTP
use Symfony\Component\Routing\Annotation\Route; // Import de l'annotation Route pour définir les routes
use Symfony\Component\Validator\Validator\ValidatorInterface; // Import du validateur pour valider les entités
use Symfony\Component\Form\FormError; // Import de FormError pour ajouter des erreurs manuelles aux formulaires

#[Route('/front')] // Préfixe de route : toutes les routes de ce contrôleur commencent par /front
class FrontOfficeController extends AbstractController // Le contrôleur hérite d'AbstractController (accès aux services Symfony)
{
    #[Route('/', name: 'front_office_index', methods: ['GET'])] // Route GET pour la page d'accueil du front office
    public function index(): Response // Méthode qui affiche la page d'accueil du front office
    {
        return $this->render('front_office/index.html.twig'); // Rend le template Twig de la page d'accueil
    }

    #[Route('/reclamation/nouvelle', name: 'front_office_nouvelle_reclamation', methods: ['GET', 'POST'])] // Route GET/POST pour créer une nouvelle réclamation
    public function nouvelleReclamation(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, ProfanityFilterService $profanityFilter, NotificationService $notificationService): Response // Méthode de création d'une nouvelle réclamation avec injection des dépendances
    {
        // Vérifier que l'utilisateur est authentifié
        $user = $this->getUser(); // Récupère l'utilisateur connecté
        if (!$user) { // Si aucun utilisateur n'est connecté
            return $this->redirectToRoute('app_login'); // Redirige vers la page de connexion
        }

        $reclamation = new Reclamation(); // Crée une nouvelle instance de Reclamation
        
        // Pré-remplir le nom et l'email de la réclamation avec les données de l'utilisateur
        $reclamation->setNomPatient($user->getPrenom() . ' ' . $user->getNom()); // Définit le nom complet du patient (prénom + nom)
        $reclamation->setEmail($user->getEmail()); // Définit l'email du patient depuis le profil utilisateur
        
        $form = $this->createForm(ReclamationType::class, $reclamation); // Crée le formulaire Symfony lié à l'entité Reclamation
        $form->handleRequest($request); // Traite la requête HTTP et remplit le formulaire avec les données soumises

        if ($form->isSubmitted()) { // Vérifie si le formulaire a été soumis
            // Définir la date et le statut AVANT la validation
            $reclamation->setDateCreation(new \DateTime()); // Définit la date de création à maintenant
            $reclamation->setStatut('En attente'); // Définit le statut initial à "En attente"
            
            // Valider les contraintes de l'entité Reclamation
            $errors = $validator->validate($reclamation); // Exécute les validations définies dans l'entité (Assert)

            // Vérifier le langage inapproprié dans le titre et la description
            $titreCheck = $profanityFilter->check($reclamation->getTitre() ?? ''); // Vérifie si le titre contient des mots inappropriés
            $descCheck  = $profanityFilter->check($reclamation->getDescription() ?? ''); // Vérifie si la description contient des mots inappropriés
            if (!$titreCheck['clean']) { // Si le titre contient du langage inapproprié
                $form->get('titre')->addError(new FormError('🚫 Langage inapproprié détecté dans le titre. Veuillez reformuler de manière respectueuse.')); // Ajoute une erreur au champ titre
            }
            if (!$descCheck['clean']) { // Si la description contient du langage inapproprié
                $form->get('description')->addError(new FormError('🚫 Langage inapproprié détecté dans la description. Veuillez reformuler de manière respectueuse.')); // Ajoute une erreur au champ description
            }
            
            if (count($errors) > 0 || !$form->isValid() || !$titreCheck['clean'] || !$descCheck['clean']) { // Si des erreurs de validation existent ou si le langage est inapproprié
                // Afficher les erreurs de validation Symfony
                foreach ($errors as $error) { // Parcourt chaque erreur de validation
                    // Ajouter les erreurs à la propriété correspondante
                    $propertyPath = $error->getPropertyPath(); // Récupère le nom du champ en erreur
                    if ($form->has($propertyPath)) { // Vérifie si le formulaire contient ce champ
                        $form->get($propertyPath)->addError(new \Symfony\Component\Form\FormError($error->getMessage())); // Ajoute l'erreur de validation au champ du formulaire
                    }
                }
                
                return $this->render('front_office/nouvelle_reclamation.html.twig', [ // Ré-affiche le formulaire avec les erreurs
                    'form' => $form->createView(), // Passe la vue du formulaire au template
                    'userInfo' => [ // Passe les infos utilisateur pour le pré-remplissage
                        'nom' => $reclamation->getNomPatient(), // Nom du patient
                        'email' => $reclamation->getEmail() // Email du patient
                    ],
                ]);
            }
            
            try { // Bloc try-catch pour gérer les erreurs de persistance
                // Récupérer l'état mental envoyé par le chatbot (champ caché)
                $etatMental = $request->request->get('etat_mental'); // Récupère l'état mental depuis les données POST (champ caché du formulaire)
                if ($etatMental) { // Si un état mental a été détecté par le chatbot
                    $reclamation->setEtatMental($etatMental); // Enregistre l'état mental dans la réclamation
                }
                
                $entityManager->persist($reclamation); // Prépare l'insertion de la réclamation en base de données
                $entityManager->flush(); // Exécute la requête SQL d'insertion en base de données

                // Notifier tous les admins en temps réel
                $notificationService->notifyAllAdmins( // Envoie une notification à tous les administrateurs
                    sprintf( // Formate le message de notification
                        '📩 Nouvelle réclamation de %s : "%s" (%s)', // Template du message
                        $reclamation->getNomPatient(), // Nom du patient
                        mb_substr($reclamation->getTitre(), 0, 40), // Titre tronqué à 40 caractères
                        $reclamation->getPriorite() // Niveau de priorité
                    ),
                    'reclamation', // Type de notification
                    '/admin/reclamation/' . $reclamation->getId() // Lien vers la réclamation dans l'admin
                );

                $this->addFlash('success', 'Votre réclamation a été soumise avec succès !'); // Ajoute un message flash de succès
                return $this->redirectToRoute('front_office_mes_reclamations'); // Redirige vers la liste des réclamations du patient
            } catch (\Exception $e) { // En cas d'erreur lors de la persistance
                $this->addFlash('error', 'Une erreur est survenue lors de la création de votre réclamation.'); // Ajoute un message flash d'erreur
            }
        }

        return $this->render('front_office/nouvelle_reclamation.html.twig', [ // Affiche le formulaire de création (GET ou après erreur)
            'form' => $form->createView(), // Passe la vue du formulaire au template
            'userInfo' => [ // Passe les infos utilisateur pour le pré-remplissage
                'nom' => $reclamation->getNomPatient(), // Nom du patient
                'email' => $reclamation->getEmail() // Email du patient
            ],
        ]);
    }

    #[Route('/mes-reclamations', name: 'front_office_mes_reclamations', methods: ['GET'])] // Route GET pour afficher la liste des réclamations du patient
    public function mesReclamations(ReclamationRepository $reclamationRepository): Response // Méthode qui affiche les réclamations de l'utilisateur connecté
    {
        $user = $this->getUser(); // Récupère l'utilisateur connecté
        if (!$user) { // Si aucun utilisateur n'est connecté
            return $this->redirectToRoute('app_login'); // Redirige vers la page de connexion
        }

        $reclamations = $reclamationRepository->findBy( // Recherche les réclamations en base de données
            ['email' => $user->getEmail()], // Filtre par l'email de l'utilisateur connecté
            ['dateCreation' => 'DESC'] // Tri par date de création décroissante (plus récentes en premier)
        );

        return $this->render('front_office/mes_reclamations.html.twig', [ // Rend le template avec la liste des réclamations
            'reclamations' => $reclamations, // Passe les réclamations trouvées au template
        ]);
    }

    #[Route('/reclamation/{id}', name: 'front_office_detail_reclamation', methods: ['GET'])] // Route GET pour afficher le détail d'une réclamation par son ID
    public function detailReclamation(Reclamation $reclamation): Response // Méthode qui affiche le détail d'une réclamation (ParamConverter automatique)
    {
        // Vérifier que l'utilisateur est propriétaire de la réclamation
        $user = $this->getUser(); // Récupère l'utilisateur connecté
        if (!$user || $reclamation->getEmail() !== $user->getEmail()) { // Si non connecté ou si l'email ne correspond pas
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réclamation.'); // Lève une exception d'accès refusé (403)
        }

        return $this->render('front_office/detail_reclamation.html.twig', [ // Rend le template de détail
            'reclamation' => $reclamation, // Passe la réclamation au template
        ]);
    }

    #[Route('/reclamation/{id}/modifier', name: 'front_office_modifier_reclamation', methods: ['GET', 'POST'])] // Route GET/POST pour modifier une réclamation existante
    public function modifierReclamation(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager, ValidatorInterface $validator, ProfanityFilterService $profanityFilter): Response // Méthode de modification d'une réclamation avec injection des dépendances
    {
        // Vérifier que l'utilisateur est propriétaire de la réclamation
        $user = $this->getUser(); // Récupère l'utilisateur connecté
        if (!$user || $reclamation->getEmail() !== $user->getEmail()) { // Si non connecté ou si l'email ne correspond pas
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réclamation.'); // Lève une exception d'accès refusé (403)
        }

        // Vérifier que la reclamation n'est pas deja traitee
        if ($reclamation->getStatut() === 'Traité') { // Si la réclamation a déjà été traitée
            $this->addFlash('error', 'Vous ne pouvez pas modifier une réclamation déjà traitée.'); // Affiche un message d'erreur
            return $this->redirectToRoute('front_office_detail_reclamation', ['id' => $reclamation->getId()]); // Redirige vers le détail de la réclamation
        }

        // Vérifier que la réclamation n'a pas reçu de réponse
        if (count($reclamation->getReponses()) > 0) { // Si la réclamation a au moins une réponse
            $this->addFlash('error', 'Vous ne pouvez pas modifier une réclamation qui a reçu une réponse.'); // Affiche un message d'erreur
            return $this->redirectToRoute('front_office_detail_reclamation', ['id' => $reclamation->getId()]); // Redirige vers le détail
        }

        $form = $this->createForm(ReclamationType::class, $reclamation); // Crée le formulaire pré-rempli avec les données existantes
        $form->handleRequest($request); // Traite la requête HTTP et met à jour le formulaire

        if ($form->isSubmitted()) { // Vérifie si le formulaire a été soumis
            // Valider les contraintes de l'entité Reclamation
            $errors = $validator->validate($reclamation); // Exécute les validations définies dans l'entité

            // Vérifier le langage inapproprié dans le titre et la description
            $titreCheck = $profanityFilter->check($reclamation->getTitre() ?? ''); // Vérifie les mots inappropriés dans le titre
            $descCheck  = $profanityFilter->check($reclamation->getDescription() ?? ''); // Vérifie les mots inappropriés dans la description
            if (!$titreCheck['clean']) { // Si le titre contient du langage inapproprié
                $form->get('titre')->addError(new FormError('🚫 Langage inapproprié détecté dans le titre. Veuillez reformuler de manière respectueuse.')); // Ajoute une erreur au champ titre
            }
            if (!$descCheck['clean']) { // Si la description contient du langage inapproprié
                $form->get('description')->addError(new FormError('🚫 Langage inapproprié détecté dans la description. Veuillez reformuler de manière respectueuse.')); // Ajoute une erreur au champ description
            }
            
            if (count($errors) > 0 || !$form->isValid() || !$titreCheck['clean'] || !$descCheck['clean']) { // Si des erreurs existent
                // Afficher les erreurs de validation Symfony
                foreach ($errors as $error) { // Parcourt chaque erreur de validation
                    // Ajouter les erreurs à la propriété correspondante
                    $propertyPath = $error->getPropertyPath(); // Récupère le nom du champ en erreur
                    if ($form->has($propertyPath)) { // Vérifie si le formulaire contient ce champ
                        $form->get($propertyPath)->addError(new FormError($error->getMessage())); // Ajoute l'erreur au champ
                    }
                }
                
                return $this->render('front_office/modifier_reclamation.html.twig', [ // Ré-affiche le formulaire avec les erreurs
                    'reclamation' => $reclamation, // Passe la réclamation au template
                    'form' => $form->createView(), // Passe la vue du formulaire
                    'userInfo' => [ // Passe les infos utilisateur
                        'nom' => $reclamation->getNomPatient(), // Nom du patient
                        'email' => $reclamation->getEmail() // Email du patient
                    ],
                ]);
            }
            
            try { // Bloc try-catch pour gérer les erreurs de mise à jour
                $entityManager->flush(); // Exécute la requête SQL de mise à jour en base de données

                $this->addFlash('success', 'Votre réclamation a été modifiée avec succès !'); // Ajoute un message flash de succès
                return $this->redirectToRoute('front_office_detail_reclamation', ['id' => $reclamation->getId()]); // Redirige vers le détail de la réclamation
            } catch (\Exception $e) { // En cas d'erreur lors de la mise à jour
                $this->addFlash('error', 'Une erreur est survenue lors de la modification de votre réclamation.'); // Ajoute un message flash d'erreur
            }
        }

        return $this->render('front_office/modifier_reclamation.html.twig', [ // Affiche le formulaire de modification (GET ou après erreur)
            'reclamation' => $reclamation, // Passe la réclamation au template
            'form' => $form->createView(), // Passe la vue du formulaire
            'userInfo' => [ // Passe les infos utilisateur
                'nom' => $reclamation->getNomPatient(), // Nom du patient
                'email' => $reclamation->getEmail() // Email du patient
            ],
        ]);
    }

    #[Route('/reclamation/{id}/supprimer', name: 'front_office_supprimer_reclamation', methods: ['POST'])] // Route POST pour supprimer une réclamation
    public function supprimerReclamation(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response // Méthode de suppression d'une réclamation
    {
        // Vérifier que l'utilisateur est propriétaire de la réclamation
        $user = $this->getUser(); // Récupère l'utilisateur connecté
        if (!$user || $reclamation->getEmail() !== $user->getEmail()) { // Si non connecté ou si l'email ne correspond pas
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réclamation.'); // Lève une exception d'accès refusé (403)
        }

        $email = $reclamation->getEmail(); // Sauvegarde l'email avant la suppression
        
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->request->get('_token'))) { // Vérifie la validité du token CSRF pour sécuriser la suppression
            $this->addFlash('error', 'Token de sécurité invalide. Suppression impossible.'); // Message d'erreur si token invalide
            return $this->redirectToRoute('front_office_mes_reclamations'); // Redirige vers la liste
        }
        
        // Vérifier que la réclamation n'est pas déjà traitée
        if ($reclamation->getStatut() === 'Traité') { // Si la réclamation a déjà été traitée
            $this->addFlash('error', 'Vous ne pouvez pas supprimer une réclamation déjà traitée.'); // Message d'erreur
            return $this->redirectToRoute('front_office_detail_reclamation', ['id' => $reclamation->getId()]); // Redirige vers le détail
        }
        
        // Vérifier que la réclamation existe
        if (!$reclamation) { // Vérification de sécurité (normalement toujours vrai grâce au ParamConverter)
            $this->addFlash('error', 'La réclamation n\'existe pas.'); // Message d'erreur si réclamation inexistante
            return $this->redirectToRoute('front_office_mes_reclamations'); // Redirige vers la liste
        }
        
        try { // Bloc try-catch pour gérer les erreurs de suppression
            $entityManager->remove($reclamation); // Prépare la suppression de la réclamation
            $entityManager->flush(); // Exécute la requête SQL de suppression en base de données
            $this->addFlash('success', 'Votre réclamation a été supprimée avec succès.'); // Message flash de succès
        } catch (\Exception $e) { // En cas d'erreur lors de la suppression
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression de votre réclamation.'); // Message flash d'erreur
        }

        return $this->redirectToRoute('front_office_mes_reclamations'); // Redirige vers la liste des réclamations
    }
}