<?php

namespace App\Repository;

use App\Entity\Consultation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Consultation>
 */
class ConsultationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Consultation::class);
    }

    /**
     * Récupère les consultations triées par priorité (Urgence IA) puis par heure
     * @return Consultation[] 
     */
    public function findAllPrioritized(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.priorite', 'DESC') // Le score 5 (Urgent) apparaît en haut
            ->addOrderBy('c.date_heure', 'ASC') // À priorité égale, le premier arrivé est premier
            ->getQuery()
            ->getResult();
    }
}