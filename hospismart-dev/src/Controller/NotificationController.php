<?php

namespace App\Controller; // Déclaration du namespace du contrôleur Notification

use App\Repository\NotificationRepository; // Import du repository pour accéder aux notifications en base
use App\Entity\Notification; // Import de l'entité Notification
use Doctrine\ORM\EntityManagerInterface; // Import de l'EntityManager pour persister/supprimer des entités
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Import du contrôleur de base Symfony
use Symfony\Component\HttpFoundation\Request; // Import de l'objet Request pour gérer les requêtes HTTP
use Symfony\Component\HttpFoundation\Response; // Import de l'objet Response pour renvoyer des réponses HTTP
use Symfony\Component\Routing\Attribute\Route; // Import de l'attribut Route pour définir les routes
use Symfony\Component\Security\Http\Attribute\IsGranted; // Import de l'attribut IsGranted pour restreindre l'accès

#[Route('/notifications', name: 'app_notification')] // Préfixe de route : toutes les routes commencent par /notifications
#[IsGranted('ROLE_USER')] // Restriction d'accès : seuls les utilisateurs authentifiés (ROLE_USER) peuvent accéder à ce contrôleur
final class NotificationController extends AbstractController // Contrôleur pour la gestion des notifications côté interface web (pages HTML)
{
    #[Route('', name: 'app_notification_index', methods: ['GET'])] // Route GET pour afficher la liste de toutes les notifications de l'utilisateur
    public function index(NotificationRepository $notificationRepository): Response // Méthode qui affiche la page des notifications
    {
        $user = $this->getUser(); // Récupère l'utilisateur connecté
        $notifications = $notificationRepository->findBy( // Recherche toutes les notifications de l'utilisateur en base
            ['user' => $user], // Filtre par l'utilisateur connecté
            ['createdAt' => 'DESC'] // Tri par date de création décroissante (plus récentes en premier)
        );

        $stats = [ // Calcule les statistiques des notifications
            'total'  => count($notifications), // Nombre total de notifications
            'unread' => count(array_filter($notifications, fn($n) => !$n->isRead())), // Nombre de notifications non lues (filtre les non lues)
            'read'   => count(array_filter($notifications, fn($n) => $n->isRead())), // Nombre de notifications lues (filtre les lues)
        ];

        return $this->render('notification/index.html.twig', [ // Rend le template avec la liste des notifications
            'notifications' => $notifications, // Passe les notifications au template
            'stats'         => $stats, // Passe les statistiques au template (total, non lues, lues)
        ]);
    }

    #[Route('/{id}/mark-read', name: '_mark_read', methods: ['POST'])] // Route POST pour marquer une notification spécifique comme lue
    public function markRead(
        Notification $notification, // La notification à marquer (récupérée automatiquement par ParamConverter grâce à l'ID dans l'URL)
        EntityManagerInterface $entityManager, // L'EntityManager pour sauvegarder les modifications
        Request $request // La requête HTTP (contient le token CSRF)
    ): Response { // Méthode pour marquer une notification individuelle comme lue
        if ($notification->getUser() !== $this->getUser()) { // Vérifie que la notification appartient à l'utilisateur connecté
            throw $this->createAccessDeniedException(); // Lève une exception 403 si l'utilisateur n'est pas le propriétaire
        }

        if ($this->isCsrfTokenValid('mark_read', $request->getPayload()->getString('_token'))) { // Vérifie la validité du token CSRF pour sécuriser l'action
            $notification->setIsRead(true); // Marque la notification comme lue
            $entityManager->flush(); // Exécute la requête SQL de mise à jour en base
            $this->addFlash('success', 'Notification marquée comme lue.'); // Ajoute un message flash de succès
        }

        return $this->redirectToRoute('app_notification_index'); // Redirige vers la liste des notifications
    }

    #[Route('/{id}/delete', name: '_delete', methods: ['POST'])] // Route POST pour supprimer une notification spécifique
    public function delete(
        Notification $notification, // La notification à supprimer (récupérée automatiquement par ParamConverter)
        EntityManagerInterface $entityManager, // L'EntityManager pour exécuter la suppression
        Request $request // La requête HTTP (contient le token CSRF)
    ): Response { // Méthode pour supprimer une notification individuelle
        if ($notification->getUser() !== $this->getUser()) { // Vérifie que la notification appartient à l'utilisateur connecté
            throw $this->createAccessDeniedException(); // Lève une exception 403 si l'utilisateur n'est pas le propriétaire
        }

        if ($this->isCsrfTokenValid('delete', $request->getPayload()->getString('_token'))) { // Vérifie la validité du token CSRF pour sécuriser la suppression
            $entityManager->remove($notification); // Prépare la suppression de la notification
            $entityManager->flush(); // Exécute la requête SQL de suppression en base
            $this->addFlash('success', 'Notification supprimée.'); // Ajoute un message flash de succès
        }

        return $this->redirectToRoute('app_notification_index'); // Redirige vers la liste des notifications
    }

    #[Route('/mark-all-read', name: '_mark_all_read', methods: ['POST'])] // Route POST pour marquer toutes les notifications comme lues
    public function markAllRead(
        NotificationRepository $notificationRepository, // Repository pour récupérer les notifications non lues
        EntityManagerInterface $entityManager, // L'EntityManager pour sauvegarder les modifications
        Request $request // La requête HTTP (contient le token CSRF)
    ): Response { // Méthode pour marquer toutes les notifications de l'utilisateur comme lues
        if ($this->isCsrfTokenValid('mark_all_read', $request->getPayload()->getString('_token'))) { // Vérifie la validité du token CSRF
            $user = $this->getUser(); // Récupère l'utilisateur connecté
            $notifications = $notificationRepository->findBy(['user' => $user, 'isRead' => false]); // Récupère toutes les notifications non lues de l'utilisateur

            foreach ($notifications as $notification) { // Parcourt chaque notification non lue
                $notification->setIsRead(true); // Marque la notification comme lue
            }

            $entityManager->flush(); // Exécute toutes les requêtes SQL de mise à jour en une seule transaction
            $this->addFlash('success', 'Toutes les notifications sont marquées comme lues.'); // Ajoute un message flash de succès
        }

        return $this->redirectToRoute('app_notification_index'); // Redirige vers la liste des notifications
    }
}
