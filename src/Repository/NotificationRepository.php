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
     * Count unread notifications for a given user.
     */
    public function countUnreadByUser(int $userId): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.user = :userId')
            ->andWhere('n.isRead = false')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find unread notifications for a user since a given notification ID.
     *
     * @return Notification[]
     */
    public function findUnreadSince(int $userId, int $sinceId = 0): array
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.user = :userId')
            ->andWhere('n.isRead = false')
            ->setParameter('userId', $userId)
            ->orderBy('n.createdAt', 'DESC');

        if ($sinceId > 0) {
            $qb->andWhere('n.id > :sinceId')
               ->setParameter('sinceId', $sinceId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find the latest notifications for a user.
     *
     * @return Notification[]
     */
    public function findLatestByUser(int $userId, int $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Mark all notifications as read for a given user.
     *
     * @return int The number of notifications marked as read
     */
    public function markAllReadByUser(int $userId): int
    {
        return (int) $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', 'true')
            ->andWhere('n.user = :userId')
            ->andWhere('n.isRead = false')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }
}
