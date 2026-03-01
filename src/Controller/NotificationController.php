<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class NotificationController extends AbstractController
{
    #[Route('/notifications/mark-read', name: 'app_notifications_mark_read', methods: ['POST'])]
    public function markAllAsRead(EntityManagerInterface $em): JsonResponse
    {
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
