<?php

namespace App\Repository;

use App\Entity\MouvementStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MouvementStock>
 */
class MouvementStockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MouvementStock::class);
    }

    /**
     * Sorties d'un médicament sur les N derniers jours
     * @return MouvementStock[]
     */
    public function findSortiesByMedicament(int $medicamentId, int $days = 90): array
    {
        $since = new \DateTime("-{$days} days");

        return $this->createQueryBuilder('m')
            ->andWhere('m.medicament = :medId')
            ->andWhere('m.type = :type')
            ->andWhere('m.date_mouvement >= :since')
            ->setParameter('medId', $medicamentId)
            ->setParameter('type', 'SORTIE')
            ->setParameter('since', $since)
            ->orderBy('m.date_mouvement', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Consommation totale par médicament sur les N derniers jours
     * Retourne un tableau [medicament_id => total_sorties]
     */
    public function getTotalSortiesParMedicament(int $days = 30): array
    {
        $since = new \DateTime("-{$days} days");

        $results = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.medicament) as med_id, SUM(m.quantite) as total')
            ->andWhere('m.type = :type')
            ->andWhere('m.date_mouvement >= :since')
            ->setParameter('type', 'SORTIE')
            ->setParameter('since', $since)
            ->groupBy('m.medicament')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($results as $row) {
            $map[(int)$row['med_id']] = (int)$row['total'];
        }
        return $map;
    }

    /**
     * Consommation quotidienne regroupée par jour pour un médicament
     * Retourne [['date' => 'Y-m-d', 'total' => int], ...]
     */
    public function getDailyConsumption(int $medicamentId, int $days = 90): array
    {
        $since = new \DateTime("-{$days} days");

        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT DATE(m.date_mouvement) as date_jour, SUM(m.quantite) as total
            FROM mouvement_stock m
            WHERE m.medicament_id = :medId
              AND m.type = :type
              AND m.date_mouvement >= :since
            GROUP BY date_jour
            ORDER BY date_jour ASC
        ';

        return $conn->executeQuery($sql, [
            'medId' => $medicamentId,
            'type' => 'SORTIE',
            'since' => $since->format('Y-m-d'),
        ])->fetchAllAssociative();
    }
}
