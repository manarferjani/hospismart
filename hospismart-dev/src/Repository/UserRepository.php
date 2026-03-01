<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Liste des utilisateurs avec recherche par nom, filtre par rôle, tri par nom.
     * @param string|null $nom Recherche (LIKE sur nom, prenom, email)
     * @param string|null $role ROLE_ADMIN, ROLE_MEDECIN, ROLE_PATIENT ou null pour tous
     * @param string $sortOrder ASC ou DESC
     * @return User[]
     */
    public function findWithFilters(?string $nom, ?string $role, string $sortOrder = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.nom', $sortOrder === 'DESC' ? 'DESC' : 'ASC');

        if ($nom !== null && $nom !== '') {
            $qb->andWhere('u.nom LIKE :nom OR u.prenom LIKE :nom OR u.email LIKE :nom')
                ->setParameter('nom', '%' . $nom . '%');
        }

        $results = $qb->getQuery()->getResult();

        if ($role !== null && $role !== '') {
            $results = array_filter($results, fn (User $u) => \in_array($role, $u->getRoles(), true));
        }

        return $results;
    }

    /**
     * Trouve tous les utilisateurs ayant un rôle spécifique.
     * @return User[]
     */
    public function findByRole(string $role): array
    {
        $allUsers = $this->findAll();
        return array_filter($allUsers, fn (User $u) => \in_array($role, $u->getRoles(), true));
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
