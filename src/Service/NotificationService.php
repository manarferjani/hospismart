<?php
namespace App\Service;

use App\Entity\Notification;
use App\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function notifyPatient(Patient $patient, string $message): void
    {
        $notification = new Notification();
        $notification->setPatient($patient);
        $notification->setContent($message);
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setIsRead(false);

        $this->em->persist($notification);
        $this->em->flush();
    }
}