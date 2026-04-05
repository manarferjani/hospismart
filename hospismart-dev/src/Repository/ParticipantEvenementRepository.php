<?php

namespace App\Repository;

use App\Entity\ParticipantEvenement;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParticipantEvenement>
 */
class ParticipantEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipantEvenement::class);
    }

    /**
     * Réservations du user connecté (par compte lié ou par email)
     */
    public function findByUser(User $user): array
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.evenement', 'e')
            ->addOrderBy('e.date_debut', 'DESC');

        $qb->where($qb->expr()->orX(
            'p.participant = :user',
            'p.email = :email'
        ))
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail());

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve tous les participants d'un événement
     */
    public function findByEvenement(int $evenementId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.evenement = :evenementId')
            ->setParameter('evenementId', $evenementId)
            ->orderBy('p.role', 'ASC')
            ->addOrderBy('p.participant', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les événements d'un participant
     */
    public function findByParticipant(int $participantId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.participant = :participantId')
            ->setParameter('participantId', $participantId)
            ->orderBy('p.evenement', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les participants confirmés d'un événement
     */
    public function findConfirmesByEvenement(int $evenementId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.evenement = :evenementId')
            ->andWhere('p.confirme_presence = :confirme')
            ->setParameter('evenementId', $evenementId)
            ->setParameter('confirme', true)
            ->getQuery()
            ->getResult();
    }
}
