<?php

namespace App\Tests\Service;

use App\Entity\Medicament;
use App\Service\MedicamentManager;
use PHPUnit\Framework\TestCase;

class MedicamentManagerTest extends TestCase
{
    /**
     * Crée un médicament valide par défaut pour simplifier les tests.
     */
    private function createValidMedicament(): Medicament
    {
        $medicament = new Medicament();
        $medicament->setNom('Paracétamol');
        $medicament->setQuantite(100);
        $medicament->setSeuilAlerte(10);
        $medicament->setPrixUnitaire(5.50);
        $medicament->setDatePeremption(new \DateTime('+1 year'));

        return $medicament;
    }

    /**
     * Test 1 : Un médicament valide passe la validation.
     */
    public function testValidMedicament()
    {
        $manager = new MedicamentManager();
        $this->assertTrue($manager->validate($this->createValidMedicament()));
    }

    /**
     * Test 2 : Le nom vide déclenche une exception.
     * Règle métier : le nom est obligatoire.
     */
    public function testMedicamentSansNom()
    {
        $this->expectException(\InvalidArgumentException::class);

        $medicament = $this->createValidMedicament();
        $medicament->setNom('');

        $manager = new MedicamentManager();
        $manager->validate($medicament);
    }

    /**
     * Test 3 : Un nom trop court déclenche une exception.
     * Règle métier : le nom doit contenir au moins 3 caractères.
     */
    public function testMedicamentNomTropCourt()
    {
        $this->expectException(\InvalidArgumentException::class);

        $medicament = $this->createValidMedicament();
        $medicament->setNom('AB');

        $manager = new MedicamentManager();
        $manager->validate($medicament);
    }

    /**
     * Test 4 : Une quantité négative déclenche une exception.
     * Règle métier : la quantité doit être positive ou nulle.
     */
    public function testMedicamentQuantiteNegative()
    {
        $this->expectException(\InvalidArgumentException::class);

        $medicament = $this->createValidMedicament();
        $medicament->setQuantite(-5);

        $manager = new MedicamentManager();
        $manager->validate($medicament);
    }

    /**
     * Test 5 : Un seuil d'alerte nul déclenche une exception.
     * Règle métier : le seuil d'alerte doit être strictement positif.
     */
    public function testMedicamentSeuilAlerteNul()
    {
        $this->expectException(\InvalidArgumentException::class);

        $medicament = $this->createValidMedicament();
        $medicament->setSeuilAlerte(0);

        $manager = new MedicamentManager();
        $manager->validate($medicament);
    }

    /**
     * Test 6 : Un seuil d'alerte négatif déclenche une exception.
     * Règle métier : le seuil d'alerte doit être strictement positif.
     */
    public function testMedicamentSeuilAlerteNegatif()
    {
        $this->expectException(\InvalidArgumentException::class);

        $medicament = $this->createValidMedicament();
        $medicament->setSeuilAlerte(-3);

        $manager = new MedicamentManager();
        $manager->validate($medicament);
    }

    /**
     * Test 7 : Un prix unitaire négatif déclenche une exception.
     * Règle métier : le prix unitaire doit être positif ou nul.
     */
    public function testMedicamentPrixNegatif()
    {
        $this->expectException(\InvalidArgumentException::class);

        $medicament = $this->createValidMedicament();
        $medicament->setPrixUnitaire(-2.50);

        $manager = new MedicamentManager();
        $manager->validate($medicament);
    }

    /**
     * Test 8 : Une date de péremption passée déclenche une exception.
     * Règle métier : la date de péremption doit être dans le futur.
     */
    public function testMedicamentDatePeremptionPassee()
    {
        $this->expectException(\InvalidArgumentException::class);

        $medicament = $this->createValidMedicament();
        $medicament->setDatePeremption(new \DateTime('-1 month'));

        $manager = new MedicamentManager();
        $manager->validate($medicament);
    }
}
