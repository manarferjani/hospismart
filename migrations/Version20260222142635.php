<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222142635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rendez_vous DROP INDEX UNIQ_65E8AA0A2B9D6493, ADD INDEX IDX_65E8AA0A2B9D6493 (disponibilite_id)');
        $this->addSql('ALTER TABLE rendez_vous CHANGE disponibilite_id disponibilite_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rendez_vous DROP INDEX IDX_65E8AA0A2B9D6493, ADD UNIQUE INDEX UNIQ_65E8AA0A2B9D6493 (disponibilite_id)');
        $this->addSql('ALTER TABLE rendez_vous CHANGE disponibilite_id disponibilite_id INT DEFAULT NULL');
    }
}
