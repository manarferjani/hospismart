<?php

namespace App\Repository;

use App\Entity\Medecin;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Medecin>
 */
class MedecinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Medecin::class);
    }

    /**
     * Recherche un médecin par nom, prénom ou service
     * @return Medecin[]
     */
    public function findBySearchTerm(string $term): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->leftJoin('m.service', 's')
            ->addSelect('u', 's') // Optimisation : évite des requêtes supplémentaires (Lazy Loading)
            ->andWhere('u.nom LIKE :term OR u.prenom LIKE :term OR s.nom LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère le profil médecin associé à un utilisateur
     */
    public function findOneByUser(User $user): ?Medecin
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}