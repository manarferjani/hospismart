<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications', name: 'app_notification')]
#[IsGranted('ROLE_USER')]
final class NotificationController extends AbstractController
{
    #[Route('', name: '_index', methods: ['GET'])]
    public function index(NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        $notifications = $notificationRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        $stats = [
            'total'  => count($notifications),
            'unread' => count(array_filter($notifications, fn($n) => !$n->isRead())),
            'read'   => count(array_filter($notifications, fn($n) => $n->isRead())),
        ];

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
            'stats'         => $stats,
        ]);
    }

    #[Route('/{id}/mark-read', name: '_mark_read', methods: ['POST'])]
    public function markRead(
        Notification $notification,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if ($notification->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('mark_read', $request->getPayload()->getString('_token'))) {
            $notification->setIsRead(true);
            $entityManager->flush();
            $this->addFlash('success', 'Notification marquée comme lue.');
        }

        return $this->redirectToRoute('app_notification_index');
    }

    #[Route('/{id}/delete', name: '_delete', methods: ['POST'])]
    public function delete(
        Notification $notification,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if ($notification->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete', $request->getPayload()->getString('_token'))) {
            $entityManager->remove($notification);
            $entityManager->flush();
            $this->addFlash('success', 'Notification supprimée.');
        }

        return $this->redirectToRoute('app_notification_index');
    }

    #[Route('/mark-all-read', name: '_mark_all_read', methods: ['POST'])]
    public function markAllRead(
        NotificationRepository $notificationRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        if ($this->isCsrfTokenValid('mark_all_read', $request->getPayload()->getString('_token'))) {
            $user = $this->getUser();
            $notifications = $notificationRepository->findBy(['user' => $user, 'isRead' => false]);

            foreach ($notifications as $notification) {
                $notification->setIsRead(true);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Toutes les notifications sont marquées comme lues.');
        }

        return $this->redirectToRoute('app_notification_index');
    }
}
