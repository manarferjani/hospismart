<?php

namespace App\Repository;

use App\Entity\Reclamation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclamation::class);
    }

    public function findByEmail(string $email): array
    {
        // Valider et nettoyer l'email
        $email = trim($email);
        
        // Vérifier que l'email n'est pas vide
        if (empty($email)) {
            throw new \InvalidArgumentException('L\'email ne peut pas être vide.');
        }
        
        // Valider le format de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('L\'adresse email "' . $email . '" est invalide.');
        }
        
        // Nettoyer l'email
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        // Convertir en minuscules pour une recherche insensible à la casse
        $email = strtolower($email);
        
        return $this->createQueryBuilder('r')
            ->andWhere('LOWER(r.email) = :email')
            ->setParameter('email', $email)
            ->orderBy('r.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatut(string $statut): array
    {
        // Valider et nettoyer le statut
        $statut = trim($statut);
        
        // Vérifier que le statut n'est pas vide
        if (empty($statut)) {
            throw new \InvalidArgumentException('Le statut ne peut pas être vide.');
        }
        
        // Valider que le statut fait partie des valeurs autorisées
        $statutsAutorises = ['En attente', 'En cours', 'Traité'];
        if (!in_array($statut, $statutsAutorises, true)) {
            throw new \InvalidArgumentException(
                'Le statut "' . $statut . '" est invalide. Valeurs autorisées: ' . 
                implode(', ', $statutsAutorises)
            );
        }
        
        return $this->createQueryBuilder('r')
            ->andWhere('r.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('r.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatut(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.statut, COUNT(r.id) as total')
            ->groupBy('r.statut')
            ->getQuery()
            ->getResult();
    }
}