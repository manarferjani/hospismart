<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create audit_log table for tracking reclamation changes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(255) NOT NULL,
            entity_id INT NOT NULL,
            action VARCHAR(255) NOT NULL,
            field_name VARCHAR(255),
            old_value LONGTEXT,
            new_value LONGTEXT,
            changed_by VARCHAR(255),
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            description LONGTEXT,
            KEY entity_lookup (entity_type, entity_id),
            KEY created_date (created_at)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS audit_log');
    }
}
