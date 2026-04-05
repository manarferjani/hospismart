<?php

namespace App\Tests\Service;

use App\Entity\Service;
use App\Service\ServiceManager;
use PHPUnit\Framework\TestCase;

class ServiceManagerTest extends TestCase
{
    /**
     * Crée un service valide par défaut pour simplifier les tests.
     */
    private function createValidService(): Service
    {
        $service = new Service();
        $service->setNom('Cardiologie');
        $service->setDescription('Service de cardiologie et maladies cardiovasculaires');

        return $service;
    }

    /**
     * Test 1 : Un service valide passe la validation.
     */
    public function testValidService()
    {
        $manager = new ServiceManager();
        $this->assertTrue($manager->validate($this->createValidService()));
    }

    /**
     * Test 2 : Le nom vide déclenche une exception.
     * Règle métier : le nom est obligatoire.
     */
    public function testServiceSansNom()
    {
        $this->expectException(\InvalidArgumentException::class);

        $service = $this->createValidService();
        $service->setNom('');

        $manager = new ServiceManager();
        $manager->validate($service);
    }

    /**
     * Test 3 : Un nom trop court déclenche une exception.
     * Règle métier : le nom doit contenir au moins 3 caractères.
     */
    public function testServiceNomTropCourt()
    {
        $this->expectException(\InvalidArgumentException::class);

        $service = $this->createValidService();
        $service->setNom('AB');

        $manager = new ServiceManager();
        $manager->validate($service);
    }

    /**
     * Test 4 : La description vide déclenche une exception.
     * Règle métier : la description est obligatoire.
     */
    public function testServiceSansDescription()
    {
        $this->expectException(\InvalidArgumentException::class);

        $service = $this->createValidService();
        $service->setDescription('');

        $manager = new ServiceManager();
        $manager->validate($service);
    }

    /**
     * Test 5 : Une description trop courte déclenche une exception.
     * Règle métier : la description doit contenir au moins 10 caractères.
     */
    public function testServiceDescriptionTropCourte()
    {
        $this->expectException(\InvalidArgumentException::class);

        $service = $this->createValidService();
        $service->setDescription('Court');

        $manager = new ServiceManager();
        $manager->validate($service);
    }

    /**
     * Test 6 : Un nom trop long déclenche une exception.
     * Règle métier : le nom ne doit pas dépasser 255 caractères.
     */
    public function testServiceNomTropLong()
    {
        $this->expectException(\InvalidArgumentException::class);

        $service = $this->createValidService();
        $service->setNom(str_repeat('A', 256));

        $manager = new ServiceManager();
        $manager->validate($service);
    }

    /**
     * Test 7 : Une description trop longue déclenche une exception.
     * Règle métier : la description ne doit pas dépasser 255 caractères.
     */
    public function testServiceDescriptionTropLongue()
    {
        $this->expectException(\InvalidArgumentException::class);

        $service = $this->createValidService();
        $service->setDescription(str_repeat('A', 256));

        $manager = new ServiceManager();
        $manager->validate($service);
    }

    /**
     * Test 8 : Un nom avec des caractères spéciaux déclenche une exception.
     * Règle métier : le nom ne doit contenir que des lettres, chiffres, espaces, tirets et apostrophes.
     */
    public function testServiceNomCaracteresSpeciaux()
    {
        $this->expectException(\InvalidArgumentException::class);

        $service = $this->createValidService();
        $service->setNom('Service @#$%');

        $manager = new ServiceManager();
        $manager->validate($service);
    }
}
