<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Make medecin.telephone nullable
 */
final class Version20260208180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make medecin.telephone nullable to allow optional telephone';
    }

    public function up(Schema $schema): void
    {
        // Make telephone nullable
        $this->addSql('ALTER TABLE medecin CHANGE telephone telephone VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Reverse: make telephone NOT NULL
        $this->addSql('ALTER TABLE medecin CHANGE telephone telephone VARCHAR(255) NOT NULL');
    }
}
