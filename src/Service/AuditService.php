<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\Reclamation;
use Doctrine\ORM\EntityManagerInterface;

class AuditService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function logReclamationCreated(Reclamation $reclamation, ?string $changedBy = null): void
    {
        $log = new AuditLog();
        $log->setEntityType('Reclamation');
        $log->setEntityId($reclamation->getId());
        $log->setAction('created');
        $log->setChangedBy($changedBy ?? 'Système');
        $log->setDescription('Nouvelle réclamation créée : ' . $reclamation->getTitre());
        
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function logReclamationUpdated(Reclamation $reclamation, array $changes, ?string $changedBy = null): void
    {
        foreach ($changes as $fieldName => $change) {
            $log = new AuditLog();
            $log->setEntityType('Reclamation');
            $log->setEntityId($reclamation->getId());
            $log->setAction('updated');
            $log->setFieldName($fieldName);
            $log->setOldValue($change['old'] ?? null);
            $log->setNewValue($change['new'] ?? null);
            $log->setChangedBy($changedBy ?? 'Système');
            $log->setDescription('Mise à jour du champ ' . $fieldName);
            
            $this->entityManager->persist($log);
        }
        
        $this->entityManager->flush();
    }

    public function logReclamationDeleted(int $reclamationId, string $titre, ?string $changedBy = null): void
    {
        $log = new AuditLog();
        $log->setEntityType('Reclamation');
        $log->setEntityId($reclamationId);
        $log->setAction('deleted');
        $log->setChangedBy($changedBy ?? 'Système');
        $log->setDescription('Réclamation supprimée : ' . $titre);
        
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function logReclamationResponded(int $reclamationId, string $responseText, ?string $adminName = null): void
    {
        $log = new AuditLog();
        $log->setEntityType('Reclamation');
        $log->setEntityId($reclamationId);
        $log->setAction('responded');
        $log->setChangedBy($adminName ?? 'Système');
        $log->setDescription('Réponse ajoutée');
        $log->setNewValue(substr($responseText, 0, 500)); // Store first 500 chars
        
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function logStatusChanged(int $reclamationId, string $oldStatus, string $newStatus, ?string $changedBy = null): void
    {
        $log = new AuditLog();
        $log->setEntityType('Reclamation');
        $log->setEntityId($reclamationId);
        $log->setAction('updated');
        $log->setFieldName('statut');
        $log->setOldValue($oldStatus);
        $log->setNewValue($newStatus);
        $log->setChangedBy($changedBy ?? 'Système');
        $log->setDescription('Statut changé de ' . $oldStatus . ' à ' . $newStatus);
        
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
