<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
     * Recherche d'événements avec filtres optionnels
     */
    public function search(?string $searchTerm = null, ?string $type = null, ?string $statut = null, ?string $sort = null, ?string $direction = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('e');

        if ($searchTerm) {
            $qb->andWhere('e.titre LIKE :search OR e.description LIKE :search OR e.lieu LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        if ($type) {
            $qb->andWhere('e.type_evenement = :type')
               ->setParameter('type', $type);
        }

        if ($statut) {
            $qb->andWhere('e.statut = :statut')
               ->setParameter('statut', $statut);
        }

        // Sorting
        $allowedSorts = [
            'date_debut' => 'date_debut',
            'date_fin' => 'date_fin',
            'titre' => 'titre',
            'type' => 'type_evenement',
            'lieu' => 'lieu',
        ];

        if ($sort && isset($allowedSorts[$sort])) {
            $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $qb->orderBy('e.' . $allowedSorts[$sort], $dir);
        } else {
            $qb->orderBy('e.date_debut', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les prochains événements (dates futures)
     */
    public function findProchainsEvenements(?int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.date_debut >= :now')
            ->andWhere('e.statut != :annule')
            ->setParameter('now', new \DateTime())
            ->setParameter('annule', 'annulé')
            ->orderBy('e.date_debut', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements par statut
     */
    public function findByStatut(string $statut): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('e.date_debut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
