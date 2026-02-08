<?php

namespace App\Repository;

use App\Entity\Medecin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MedecinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Medecin::class);
    }

    /**
     * Permet de rechercher un médecin par son nom (partiel)
     */
public function findByNomLike(string $nom): array
{
    return $this->createQueryBuilder('m')
        ->leftJoin('m.user', 'u')    // On joint la table User
        ->leftJoin('m.service', 's') // On joint la table Service
        ->andWhere('u.nom LIKE :term OR u.prenom LIKE :term OR s.nom LIKE :term')
        ->setParameter('term', '%' . $nom . '%')
        ->getQuery()
        ->getResult();
}
}