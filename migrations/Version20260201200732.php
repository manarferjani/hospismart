<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 * Modifié pour utiliser IF NOT EXISTS afin de ne pas échouer si les tables existent déjà (base importée).
 */
final class Version20260201200732 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tables initiales (campagne, user, service, etc.) - skip si déjà présentes.';
    }

    public function up(Schema $schema): void
    {
        // CREATE TABLE IF NOT EXISTS : ne fait rien si la table existe déjà (base hospismart importée)
        $this->addSql('CREATE TABLE IF NOT EXISTS campagne (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, theme VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, budget DOUBLE PRECISION NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE IF NOT EXISTS diagnostic (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, probabilite_ia DOUBLE PRECISION NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE IF NOT EXISTS equipement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, reference VARCHAR(255) NOT NULL, etat VARCHAR(255) NOT NULL, relation VARCHAR(255) NOT NULL, service_id INT NOT NULL, INDEX IDX_B8B4C6F3ED5CA9E6 (service_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE IF NOT EXISTS medicament (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, quantite INT NOT NULL, seuil_alerte INT NOT NULL, prix_unitaire DOUBLE PRECISION NOT NULL, date_peremption DATE NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE IF NOT EXISTS parametre_vital (id INT AUTO_INCREMENT NOT NULL, tension VARCHAR(255) NOT NULL, temperature DOUBLE PRECISION NOT NULL, frequence_cardiaque INT NOT NULL, date_prise DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE IF NOT EXISTS service (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE IF NOT EXISTS user (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_NOM (nom), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE IF NOT EXISTS messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        // N'ajouter la FK que si elle n'existe pas encore (évite erreur sur base existante)
        $fkExists = $this->connection->executeQuery(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipement' AND CONSTRAINT_NAME = 'FK_B8B4C6F3ED5CA9E6' LIMIT 1"
        )->fetchOne();
        if ($fkExists === false) {
            $this->addSql('ALTER TABLE equipement ADD CONSTRAINT FK_B8B4C6F3ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE equipement DROP FOREIGN KEY FK_B8B4C6F3ED5CA9E6');
        $this->addSql('DROP TABLE campagne');
        $this->addSql('DROP TABLE diagnostic');
        $this->addSql('DROP TABLE equipement');
        $this->addSql('DROP TABLE medicament');
        $this->addSql('DROP TABLE parametre_vital');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
