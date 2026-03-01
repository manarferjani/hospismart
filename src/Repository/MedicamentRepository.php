<?php

namespace App\Repository;

use App\Entity\Medicament;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Medicament>
 */
class MedicamentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Medicament::class);
    }

    /**
     * @return Medicament[]
     */
    public function findWithSearch(?string $search, ?string $sortBy = 'nom', ?string $order = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.mouvements', 'mv')
            ->addSelect('mv'); // Eager loading

        if ($search !== null && $search !== '') {
            $qb->andWhere('m.nom LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $allowedSort = ['nom', 'quantite', 'seuilAlerte', 'datePeremption', 'id'];
        $sortBy = in_array($sortBy, $allowedSort) ? $sortBy : 'nom';
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy('m.' . $sortBy, $order);
        $qb->distinct();

        return $qb->getQuery()->getResult();
    }

    /**
     * Médicaments dont la quantité est en dessous du seuil d'alerte.
     *
     * @return Medicament[]
     */
    public function findSousSeuilAlerte(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.quantite <= m.seuil_alerte')
            ->orderBy('m.quantite', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les médicaments avec leur catégorie pour l'affichage public
     * @return Medicament[]
     */
    public function findForPublic(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.categorie', 'c')
            ->addSelect('c')
            ->orderBy('m.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Medicament[] Returns an array of Medicament objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Medicament
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
