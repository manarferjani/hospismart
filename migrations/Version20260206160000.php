<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add priorite field to reclamation table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reclamation ADD priorite VARCHAR(50) NOT NULL DEFAULT "Normale"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reclamation DROP COLUMN IF EXISTS priorite');
    }
}
