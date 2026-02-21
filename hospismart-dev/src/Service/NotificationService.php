<?php
namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
    ) {}

    /**
     * Envoie une notification à un utilisateur spécifique.
     */
    public function notify(User $user, string $content, ?string $type = null, ?string $linkUrl = null): Notification
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setContent($content);
        $notification->setType($type);
        $notification->setLinkUrl($linkUrl);
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setIsRead(false);

        $this->em->persist($notification);
        $this->em->flush();

        return $notification;
    }

    /**
     * Envoie une notification à tous les utilisateurs ayant le rôle ROLE_ADMIN.
     */
    public function notifyAllAdmins(string $content, ?string $type = null, ?string $linkUrl = null): int
    {
        $admins = $this->userRepository->findByRole('ROLE_ADMIN');
        $count = 0;

        foreach ($admins as $admin) {
            $notification = new Notification();
            $notification->setUser($admin);
            $notification->setContent($content);
            $notification->setType($type);
            $notification->setLinkUrl($linkUrl);
            $notification->setCreatedAt(new \DateTimeImmutable());
            $notification->setIsRead(false);

            $this->em->persist($notification);
            $count++;
        }

        $this->em->flush();

        return $count;
    }
}