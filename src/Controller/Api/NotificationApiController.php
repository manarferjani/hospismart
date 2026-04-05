<?php

namespace App\Controller\Api; // Déclaration du namespace pour le contrôleur API de notifications

use App\Entity\User; // Import de l'entité User pour le typage getUser()
use App\Repository\NotificationRepository; // Import du repository pour accéder aux notifications en base
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Import du contrôleur de base Symfony
use Symfony\Component\HttpFoundation\JsonResponse; // Import de JsonResponse pour renvoyer des réponses JSON
use Symfony\Component\HttpFoundation\Request; // Import de l'objet Request pour gérer les requêtes HTTP
use Symfony\Component\Routing\Annotation\Route; // Import de l'annotation Route pour définir les routes

#[Route('/api/notifications')] // Préfixe de route : toutes les routes commencent par /api/notifications
class NotificationApiController extends AbstractController // Contrôleur API REST pour les notifications en temps réel
{
    #[Route('/poll', name: 'api_notifications_poll', methods: ['GET'])] // Route GET pour le polling : vérifier les nouvelles notifications (appelée périodiquement par le front-end)
    public function poll(Request $request, NotificationRepository $notifRepo): JsonResponse // Méthode de polling des notifications avec injection du repository
    {
        /** @var User|null $user */
        $user = $this->getUser(); // Récupère l'utilisateur connecté
        if (!$user) { // Si aucun utilisateur n'est connecté
            return $this->json(['error' => 'Non authentifié'], 401); // Retourne une erreur 401 (Unauthorized)
        }

        $sinceId = (int) $request->query->get('since', 0); // Récupère l'ID de la dernière notification vue (paramètre GET 'since', par défaut 0)
        $unreadCount = $notifRepo->countUnreadByUser($user->getId()); // Compte le nombre total de notifications non lues pour cet utilisateur

        $newNotifs = $notifRepo->findUnreadSince($user->getId(), $sinceId); // Récupère les nouvelles notifications non lues depuis le dernier ID vu

        $notifsData = []; // Initialise un tableau vide pour formater les notifications
        foreach ($newNotifs as $notif) { // Parcourt chaque nouvelle notification
            $notifsData[] = [ // Formate la notification en tableau associatif pour la réponse JSON
                'id'        => $notif->getId(), // Identifiant unique de la notification
                'content'   => $notif->getContent(), // Contenu textuel de la notification
                'type'      => $notif->getType(), // Type de notification (reclamation, info, etc.)
                'linkUrl'   => $notif->getLinkUrl(), // URL de redirection au clic
                'createdAt' => $notif->getCreatedAt()->format('d/m/Y H:i'), // Date de création formatée en français (jour/mois/année heure:minute)
                'isRead'    => $notif->isRead(), // État de lecture de la notification (true/false)
            ];
        }

        return $this->json([ // Retourne la réponse JSON avec les données de polling
            'unreadCount' => $unreadCount, // Nombre total de notifications non lues
            'notifications' => $notifsData, // Liste des nouvelles notifications depuis le dernier polling
        ]);
    }

    #[Route('/latest', name: 'api_notifications_latest', methods: ['GET'])] // Route GET pour récupérer les 15 dernières notifications de l'utilisateur
    public function latest(NotificationRepository $notifRepo): JsonResponse // Méthode qui retourne les notifications les plus récentes
    {
        /** @var User|null $user */
        $user = $this->getUser(); // Récupère l'utilisateur connecté
        if (!$user) { // Si aucun utilisateur n'est connecté
            return $this->json(['error' => 'Non authentifié'], 401); // Retourne une erreur 401 (Unauthorized)
        }

        $notifications = $notifRepo->findLatestByUser($user->getId(), 15); // Récupère les 15 dernières notifications de l'utilisateur
        $unreadCount = $notifRepo->countUnreadByUser($user->getId()); // Compte le nombre de notifications non lues

        $notifsData = []; // Initialise un tableau vide pour formater les notifications
        foreach ($notifications as $notif) { // Parcourt chaque notification
            $notifsData[] = [ // Formate la notification en tableau associatif pour la réponse JSON
                'id'        => $notif->getId(), // Identifiant unique de la notification
                'content'   => $notif->getContent(), // Contenu textuel de la notification
                'type'      => $notif->getType(), // Type de notification
                'linkUrl'   => $notif->getLinkUrl(), // URL de redirection au clic
                'createdAt' => $notif->getCreatedAt()->format('d/m/Y H:i'), // Date de création formatée
                'isRead'    => $notif->isRead(), // État de lecture
            ];
        }

        return $this->json([ // Retourne la réponse JSON avec les dernières notifications
            'unreadCount' => $unreadCount, // Nombre total de notifications non lues
            'notifications' => $notifsData, // Liste des 15 dernières notifications
        ]);
    }

    #[Route('/mark-read', name: 'api_notifications_mark_read', methods: ['POST'])] // Route POST pour marquer toutes les notifications comme lues
    public function markRead(NotificationRepository $notifRepo): JsonResponse // Méthode qui marque toutes les notifications de l'utilisateur comme lues
    {
        /** @var User|null $user */
        $user = $this->getUser(); // Récupère l'utilisateur connecté
        if (!$user) { // Si aucun utilisateur n'est connecté
            return $this->json(['error' => 'Non authentifié'], 401); // Retourne une erreur 401 (Unauthorized)
        }

        $count = $notifRepo->markAllReadByUser($user->getId()); // Marque toutes les notifications non lues comme lues et retourne le nombre de notifications modifiées

        return $this->json([ // Retourne la réponse JSON de confirmation
            'success' => true, // Indique que l'opération a réussi
            'marked'  => $count, // Nombre de notifications marquées comme lues
        ]);
    }
}
