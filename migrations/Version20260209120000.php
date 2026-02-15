<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create mouvement_stock table for stock movements.
 */
final class Version20260209120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create mouvement_stock table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE mouvement_stock (
            id INT AUTO_INCREMENT NOT NULL,
            medicament_id INT NOT NULL,
            type VARCHAR(10) NOT NULL,
            quantite INT NOT NULL,
            date_mouvement DATETIME NOT NULL,
            commentaire LONGTEXT DEFAULT NULL,
            INDEX IDX_mouvement_stock_medicament (medicament_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE mouvement_stock ADD CONSTRAINT FK_mouvement_stock_medicament FOREIGN KEY (medicament_id) REFERENCES medicament (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY FK_mouvement_stock_medicament');
        $this->addSql('DROP TABLE mouvement_stock');
    }
}
