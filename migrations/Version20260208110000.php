<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Suppression des champs createur_nom, createur_prenom, createur_email
 */
final class Version20260208110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime createur_nom, createur_prenom, createur_email de evenement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evenement DROP createur_nom, DROP createur_prenom, DROP createur_email');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evenement ADD createur_nom VARCHAR(255) DEFAULT NULL, ADD createur_prenom VARCHAR(255) DEFAULT NULL, ADD createur_email VARCHAR(255) DEFAULT NULL');
    }
}
