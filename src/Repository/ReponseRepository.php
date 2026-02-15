<?php

namespace App\Repository;

use App\Entity\Reponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reponse::class);
    }

    public function findByReclamation(int $reclamationId): array
    {
        // Valider que l'ID est un entier positif
        if ($reclamationId <= 0) {
            throw new \InvalidArgumentException(
                'L\'ID de la réclamation doit être un nombre positif. Valeur reçue: ' . $reclamationId
            );
        }
        
        // Vérifier que l'ID est bien un entier
        if (!is_int($reclamationId)) {
            throw new \InvalidArgumentException(
                'L\'ID de la réclamation doit être un entier.'
            );
        }
        
        return $this->createQueryBuilder('r')
            ->andWhere('r.reclamation = :reclamationId')
            ->setParameter('reclamationId', $reclamationId)
            ->orderBy('r.dateReponse', 'DESC')
            ->getQuery()
            ->getResult();
    }
}