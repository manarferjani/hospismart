<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Compte les notifications non lues pour un utilisateur.
     */
    public function countUnreadByUser(int $userId): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user = :userId')
            ->andWhere('n.isRead = false')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les dernières notifications pour un utilisateur.
     * @return Notification[]
     */
    public function findLatestByUser(int $userId, int $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les notifications non lues depuis un certain ID.
     * @return Notification[]
     */
    public function findUnreadSince(int $userId, int $sinceId = 0): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :userId')
            ->andWhere('n.isRead = false')
            ->andWhere('n.id > :sinceId')
            ->setParameter('userId', $userId)
            ->setParameter('sinceId', $sinceId)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Marque toutes les notifications d'un utilisateur comme lues.
     */
    public function markAllReadByUser(int $userId): int
    {
        return $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', 'true')
            ->where('n.user = :userId')
            ->andWhere('n.isRead = false')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    //    /**
    //     * @return Notification[] Returns an array of Notification objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('n.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Notification
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
