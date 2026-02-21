<?php

namespace App\Controller\Api;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/notifications')]
class NotificationApiController extends AbstractController
{
    #[Route('/poll', name: 'api_notifications_poll', methods: ['GET'])]
    public function poll(Request $request, NotificationRepository $notifRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $sinceId = (int) $request->query->get('since', 0);
        $unreadCount = $notifRepo->countUnreadByUser($user->getId());

        $newNotifs = $notifRepo->findUnreadSince($user->getId(), $sinceId);

        $notifsData = [];
        foreach ($newNotifs as $notif) {
            $notifsData[] = [
                'id'        => $notif->getId(),
                'content'   => $notif->getContent(),
                'type'      => $notif->getType(),
                'linkUrl'   => $notif->getLinkUrl(),
                'createdAt' => $notif->getCreatedAt()->format('d/m/Y H:i'),
                'isRead'    => $notif->isRead(),
            ];
        }

        return $this->json([
            'unreadCount' => $unreadCount,
            'notifications' => $notifsData,
        ]);
    }

    #[Route('/latest', name: 'api_notifications_latest', methods: ['GET'])]
    public function latest(NotificationRepository $notifRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $notifications = $notifRepo->findLatestByUser($user->getId(), 15);
        $unreadCount = $notifRepo->countUnreadByUser($user->getId());

        $notifsData = [];
        foreach ($notifications as $notif) {
            $notifsData[] = [
                'id'        => $notif->getId(),
                'content'   => $notif->getContent(),
                'type'      => $notif->getType(),
                'linkUrl'   => $notif->getLinkUrl(),
                'createdAt' => $notif->getCreatedAt()->format('d/m/Y H:i'),
                'isRead'    => $notif->isRead(),
            ];
        }

        return $this->json([
            'unreadCount' => $unreadCount,
            'notifications' => $notifsData,
        ]);
    }

    #[Route('/mark-read', name: 'api_notifications_mark_read', methods: ['POST'])]
    public function markRead(NotificationRepository $notifRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $count = $notifRepo->markAllReadByUser($user->getId());

        return $this->json([
            'success' => true,
            'marked'  => $count,
        ]);
    }
}
