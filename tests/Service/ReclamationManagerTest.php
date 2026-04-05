<?php

namespace App\Tests\Service;

use App\Entity\Reclamation;
use App\Service\ReclamationManager;
use PHPUnit\Framework\TestCase;

class ReclamationManagerTest extends TestCase
{
    /**
     * Crée une réclamation valide par défaut pour simplifier les tests.
     */
    private function createValidReclamation(): Reclamation
    {
        $reclamation = new Reclamation();
        $reclamation->setTitre('Problème de facturation');
        $reclamation->setEmail('patient@gmail.com');
        $reclamation->setPriorite('Haute');
        $reclamation->setDescription('Description détaillée du problème rencontré');
        $reclamation->setCategorie('Facturation');

        return $reclamation;
    }

    /**
     * Test 1 : Une réclamation valide passe la validation.
     */
    public function testValidReclamation()
    {
        $manager = new ReclamationManager();
        $this->assertTrue($manager->validate($this->createValidReclamation()));
    }

    /**
     * Test 2 : Le titre vide déclenche une exception.
     * Règle métier : le titre est obligatoire.
     */
    public function testReclamationSansTitre()
    {
        $this->expectException(\InvalidArgumentException::class);

        $reclamation = $this->createValidReclamation();
        $reclamation->setTitre('');

        $manager = new ReclamationManager();
        $manager->validate($reclamation);
    }

    /**
     * Test 3 : Un titre trop court déclenche une exception.
     * Règle métier : le titre doit contenir au moins 5 caractères.
     */
    public function testReclamationTitreTropCourt()
    {
        $this->expectException(\InvalidArgumentException::class);

        $reclamation = $this->createValidReclamation();
        $reclamation->setTitre('AB');

        $manager = new ReclamationManager();
        $manager->validate($reclamation);
    }

    /**
     * Test 4 : Un email invalide déclenche une exception.
     * Règle métier : l'email doit être valide.
     */
    public function testReclamationEmailInvalide()
    {
        $this->expectException(\InvalidArgumentException::class);

        $reclamation = $this->createValidReclamation();
        $reclamation->setEmail('email_invalide');

        $manager = new ReclamationManager();
        $manager->validate($reclamation);
    }

    /**
     * Test 5 : Une priorité invalide déclenche une exception.
     * Règle métier : la priorité doit être parmi (Basse, Normale, Haute, Urgente).
     */
    public function testReclamationPrioriteInvalide()
    {
        $this->expectException(\InvalidArgumentException::class);

        $reclamation = $this->createValidReclamation();
        $reclamation->setPriorite('Critique');

        $manager = new ReclamationManager();
        $manager->validate($reclamation);
    }

    /**
     * Test 6 : Une description vide déclenche une exception.
     * Règle métier : la description est obligatoire.
     */
    public function testReclamationSansDescription()
    {
        $this->expectException(\InvalidArgumentException::class);

        $reclamation = $this->createValidReclamation();
        $reclamation->setDescription('');

        $manager = new ReclamationManager();
        $manager->validate($reclamation);
    }

    /**
     * Test 7 : Une description trop courte déclenche une exception.
     * Règle métier : la description doit contenir au moins 10 caractères.
     */
    public function testReclamationDescriptionTropCourte()
    {
        $this->expectException(\InvalidArgumentException::class);

        $reclamation = $this->createValidReclamation();
        $reclamation->setDescription('Court');

        $manager = new ReclamationManager();
        $manager->validate($reclamation);
    }

    /**
     * Test 8 : Une catégorie invalide déclenche une exception.
     * Règle métier : la catégorie doit être parmi les valeurs autorisées.
     */
    public function testReclamationCategorieInvalide()
    {
        $this->expectException(\InvalidArgumentException::class);

        $reclamation = $this->createValidReclamation();
        $reclamation->setCategorie('Invalide');

        $manager = new ReclamationManager();
        $manager->validate($reclamation);
    }
}
