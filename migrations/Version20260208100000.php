<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajout des champs nom, prénom, email pour participants et créateur
 */
final class Version20260208100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Participant et créateur : champs texte (nom, prénom, email) au lieu de lien User';
    }

    public function up(Schema $schema): void
    {
        // Supprimer la contrainte unique (evenement, participant) car participant devient optionnel
        $this->addSql('ALTER TABLE participant_evenement DROP INDEX unique_participant_evenement');

        // ParticipantEvenement : ajouter nom, prenom, email, telephone
        $this->addSql('ALTER TABLE participant_evenement ADD nom VARCHAR(255) DEFAULT NULL, ADD prenom VARCHAR(255) DEFAULT NULL, ADD email VARCHAR(255) DEFAULT NULL, ADD telephone VARCHAR(20) DEFAULT NULL');
        $this->addSql("UPDATE participant_evenement p LEFT JOIN user u ON p.participant_id = u.id SET p.nom = COALESCE(u.nom, 'Inconnu'), p.prenom = COALESCE(u.prenom, ''), p.email = COALESCE(u.email, 'inconnu@email.com')");
        $this->addSql("UPDATE participant_evenement SET nom = 'Inconnu', prenom = '', email = 'inconnu@email.com' WHERE nom IS NULL OR email IS NULL");
        $this->addSql('ALTER TABLE participant_evenement CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE prenom prenom VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE participant_evenement MODIFY participant_id INT DEFAULT NULL');

        // Evenement : ajouter createur_nom, createur_prenom, createur_email
        $this->addSql('ALTER TABLE evenement ADD createur_nom VARCHAR(255) DEFAULT NULL, ADD createur_prenom VARCHAR(255) DEFAULT NULL, ADD createur_email VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE evenement e INNER JOIN user u ON e.createur_id = u.id SET e.createur_nom = u.nom, e.createur_prenom = u.prenom, e.createur_email = u.email');
        $this->addSql('ALTER TABLE evenement MODIFY createur_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participant_evenement DROP nom, DROP prenom, DROP email, DROP telephone');
        $this->addSql('ALTER TABLE participant_evenement MODIFY participant_id INT NOT NULL');
        $this->addSql('ALTER TABLE evenement DROP createur_nom, DROP createur_prenom, DROP createur_email');
        $this->addSql('ALTER TABLE evenement MODIFY createur_id INT NOT NULL');
    }
}
