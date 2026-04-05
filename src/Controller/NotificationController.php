<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class NotificationController extends AbstractController
{
    #[Route('/api/notifications/unread-count', name: 'app_notifications_unread_count', methods: ['GET'])]
    public function getUnreadCount(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['count' => 0]);
        }

        $count = 0;
        $notifications = [];
        foreach ($user->getNotifications() as $notification) {
            if (!$notification->isRead()) {
                $count++;
                $notifications[] = [
                    'id' => $notification->getId(),
                    'content' => $notification->getContent(),
                    'createdAt' => $notification->getCreatedAt()->format('d/m/Y H:i')
                ];
            }
        }

        return new JsonResponse([
            'count' => $count,
            'notifications' => array_slice($notifications, 0, 5) // Les 5 dernières
        ]);
    }

    #[Route('/notifications/mark-read', name: 'app_notifications_mark_read', methods: ['POST'])]
    public function markAllAsRead(EntityManagerInterface $em): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $hasChanges = false;
        foreach ($user->getNotifications() as $notification) {
            if (!$notification->isRead()) {
                $notification->setIsRead(true);
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            $em->flush();
        }

        return new JsonResponse(['success' => true]);
    }
}
