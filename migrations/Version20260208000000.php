<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour les tables Evenement et ParticipantEvenement
 */
final class Version20260208000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables evenement et participant_evenement pour le module Gestion des Événements';
    }

    public function up(Schema $schema): void
    {
        // Table evenement
        $this->addSql('CREATE TABLE evenement (
            id INT AUTO_INCREMENT NOT NULL,
            titre VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            type_evenement VARCHAR(50) NOT NULL,
            date_debut DATETIME NOT NULL,
            date_fin DATETIME NOT NULL,
            lieu VARCHAR(255) NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT "planifié",
            budget_alloue NUMERIC(10, 2) DEFAULT NULL,
            createur_id INT NOT NULL,
            INDEX IDX_B26681E73A201E5 (createur_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table participant_evenement
        $this->addSql('CREATE TABLE participant_evenement (
            id INT AUTO_INCREMENT NOT NULL,
            evenement_id INT NOT NULL,
            participant_id INT NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT "participant",
            confirme_presence TINYINT(1) NOT NULL DEFAULT 0,
            date_confirmation DATETIME DEFAULT NULL,
            INDEX IDX_8B8C3E3BFD02F13 (evenement_id),
            INDEX IDX_8B8C3E3B9D1C3019 (participant_id),
            UNIQUE INDEX unique_participant_evenement (evenement_id, participant_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Clés étrangères
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681E73A201E5 FOREIGN KEY (createur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE participant_evenement ADD CONSTRAINT FK_8B8C3E3BFD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participant_evenement ADD CONSTRAINT FK_8B8C3E3B9D1C3019 FOREIGN KEY (participant_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // Suppression des clés étrangères
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681E73A201E5');
        $this->addSql('ALTER TABLE participant_evenement DROP FOREIGN KEY FK_8B8C3E3BFD02F13');
        $this->addSql('ALTER TABLE participant_evenement DROP FOREIGN KEY FK_8B8C3E3B9D1C3019');
        
        // Suppression des tables
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE participant_evenement');
    }
}
