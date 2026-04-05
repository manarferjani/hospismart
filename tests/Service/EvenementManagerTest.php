<?php

namespace App\Tests\Service;

use App\Entity\Evenement;
use App\Service\EvenementManager;
use PHPUnit\Framework\TestCase;

class EvenementManagerTest extends TestCase
{
    /**
     * Crée un événement valide par défaut pour simplifier les tests.
     */
    private function createValidEvenement(): Evenement
    {
        $evenement = new Evenement();
        $evenement->setTitre('Conférence médicale annuelle');
        $evenement->setDescription('Une conférence sur les avancées médicales récentes');
        $evenement->setTypeEvenement('conference');
        $evenement->setLieu('Salle de conférence A');
        $evenement->setDateDebut(new \DateTime('+1 week'));
        $evenement->setDateFin(new \DateTime('+1 week +2 hours'));
        $evenement->setStatut('planifie');

        return $evenement;
    }

    /**
     * Test 1 : Un événement valide passe la validation.
     */
    public function testValidEvenement()
    {
        $manager = new EvenementManager();
        $this->assertTrue($manager->validate($this->createValidEvenement()));
    }

    /**
     * Test 2 : Le titre vide déclenche une exception.
     * Règle métier : le titre est obligatoire.
     */
    public function testEvenementSansTitre()
    {
        $this->expectException(\InvalidArgumentException::class);

        $evenement = $this->createValidEvenement();
        $evenement->setTitre('');

        $manager = new EvenementManager();
        $manager->validate($evenement);
    }

    /**
     * Test 3 : Un titre trop court déclenche une exception.
     * Règle métier : le titre doit contenir au moins 5 caractères.
     */
    public function testEvenementTitreTropCourt()
    {
        $this->expectException(\InvalidArgumentException::class);

        $evenement = $this->createValidEvenement();
        $evenement->setTitre('ABC');

        $manager = new EvenementManager();
        $manager->validate($evenement);
    }

    /**
     * Test 4 : La description vide déclenche une exception.
     * Règle métier : la description est obligatoire.
     */
    public function testEvenementSansDescription()
    {
        $this->expectException(\InvalidArgumentException::class);

        $evenement = $this->createValidEvenement();
        $evenement->setDescription('');

        $manager = new EvenementManager();
        $manager->validate($evenement);
    }

    /**
     * Test 5 : Un type d'événement invalide déclenche une exception.
     * Règle métier : le type doit être parmi (conference, formation, reunion, seminaire, atelier).
     */
    public function testEvenementTypeInvalide()
    {
        $this->expectException(\InvalidArgumentException::class);

        $evenement = $this->createValidEvenement();
        $evenement->setTypeEvenement('fete');

        $manager = new EvenementManager();
        $manager->validate($evenement);
    }

    /**
     * Test 6 : Le lieu vide déclenche une exception.
     * Règle métier : le lieu est obligatoire.
     */
    public function testEvenementSansLieu()
    {
        $this->expectException(\InvalidArgumentException::class);

        $evenement = $this->createValidEvenement();
        $evenement->setLieu('');

        $manager = new EvenementManager();
        $manager->validate($evenement);
    }

    /**
     * Test 7 : La date de fin avant la date de début déclenche une exception.
     * Règle métier : la date de fin doit être après la date de début.
     */
    public function testEvenementDateFinAvantDebut()
    {
        $this->expectException(\InvalidArgumentException::class);

        $evenement = $this->createValidEvenement();
        $evenement->setDateDebut(new \DateTime('+2 weeks'));
        $evenement->setDateFin(new \DateTime('+1 week'));

        $manager = new EvenementManager();
        $manager->validate($evenement);
    }

    /**
     * Test 8 : Un statut invalide déclenche une exception.
     * Règle métier : le statut doit être parmi (planifie, en_cours, termine, annule).
     */
    public function testEvenementStatutInvalide()
    {
        $this->expectException(\InvalidArgumentException::class);

        $evenement = $this->createValidEvenement();
        $evenement->setStatut('supprime');

        $manager = new EvenementManager();
        $manager->validate($evenement);
    }
}
