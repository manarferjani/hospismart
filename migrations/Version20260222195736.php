<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222195736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_log CHANGE field_name field_name VARCHAR(255) DEFAULT NULL, CHANGE changed_by changed_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE categorie CHANGE description description VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE evenement CHANGE budget_alloue budget_alloue NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE participant_evenement CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE date_confirmation date_confirmation DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE roles roles JSON NOT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE date_naissance date_naissance DATE DEFAULT NULL, CHANGE genre genre VARCHAR(20) DEFAULT NULL, CHANGE groupe_sanguin groupe_sanguin VARCHAR(5) DEFAULT NULL, CHANGE image_patient image_patient VARCHAR(255) DEFAULT NULL, CHANGE specialite specialite VARCHAR(255) DEFAULT NULL, CHANGE matricule matricule VARCHAR(255) DEFAULT NULL, CHANGE service service VARCHAR(255) DEFAULT NULL, CHANGE image_medecin image_medecin VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_log CHANGE field_name field_name VARCHAR(255) DEFAULT \'NULL\', CHANGE changed_by changed_by VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE categorie CHANGE description description VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE evenement CHANGE budget_alloue budget_alloue NUMERIC(10, 2) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE participant_evenement CHANGE telephone telephone VARCHAR(20) DEFAULT \'NULL\', CHANGE date_confirmation date_confirmation DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE telephone telephone VARCHAR(20) DEFAULT \'NULL\', CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE date_naissance date_naissance DATE DEFAULT \'NULL\', CHANGE genre genre VARCHAR(20) DEFAULT \'NULL\', CHANGE groupe_sanguin groupe_sanguin VARCHAR(5) DEFAULT \'NULL\', CHANGE image_patient image_patient VARCHAR(255) DEFAULT \'NULL\', CHANGE specialite specialite VARCHAR(255) DEFAULT \'NULL\', CHANGE matricule matricule VARCHAR(255) DEFAULT \'NULL\', CHANGE service service VARCHAR(255) DEFAULT \'NULL\', CHANGE image_medecin image_medecin VARCHAR(255) DEFAULT \'NULL\'');
    }
}
