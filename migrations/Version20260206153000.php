<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mettre à jour les statuts des réclamations vers les nouvelles valeurs standardisées';
    }

    public function up(Schema $schema): void
    {
        // Remplacer les anciens statuts par les nouveaux
        $this->addSql("UPDATE reclamation SET statut = 'Traité' WHERE statut = 'Résolue'");
        $this->addSql("UPDATE reclamation SET statut = 'Traité' WHERE statut = 'Rejetée'");
    }

    public function down(Schema $schema): void
    {
        // Restaurer les anciens statuts en cas de rollback
        $this->addSql('UPDATE reclamation SET statut = ? WHERE statut = ?', ['Résolue', 'Traité']);
    }
}
