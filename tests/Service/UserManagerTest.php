<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    /**
     * Crée un utilisateur valide par défaut pour simplifier les tests.
     */
    private function createValidUser(): User
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('dupont@gmail.com');
        $user->setPassword('motdepasse123');
        $user->setType('patient');

        return $user;
    }

    /**
     * Test 1 : Un utilisateur valide passe la validation.
     */
    public function testValidUser()
    {
        $manager = new UserManager();
        $this->assertTrue($manager->validate($this->createValidUser()));
    }

    /**
     * Test 2 : Le nom vide déclenche une exception.
     * Règle métier : le nom est obligatoire.
     */
    public function testUserSansNom()
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = $this->createValidUser();
        $user->setNom('');

        $manager = new UserManager();
        $manager->validate($user);
    }

    /**
     * Test 3 : Le prénom vide déclenche une exception.
     * Règle métier : le prénom est obligatoire.
     */
    public function testUserSansPrenom()
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = $this->createValidUser();
        $user->setPrenom('');

        $manager = new UserManager();
        $manager->validate($user);
    }

    /**
     * Test 4 : Un email invalide déclenche une exception.
     * Règle métier : l'email doit être valide.
     */
    public function testUserEmailInvalide()
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = $this->createValidUser();
        $user->setEmail('email_invalide');

        $manager = new UserManager();
        $manager->validate($user);
    }

    /**
     * Test 5 : Un mot de passe trop court déclenche une exception.
     * Règle métier : le mot de passe doit contenir au moins 8 caractères.
     */
    public function testUserMotDePasseTropCourt()
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = $this->createValidUser();
        $user->setPassword('abc');

        $manager = new UserManager();
        $manager->validate($user);
    }

    /**
     * Test 6 : Un mot de passe vide déclenche une exception.
     * Règle métier : le mot de passe est obligatoire.
     */
    public function testUserSansMotDePasse()
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = $this->createValidUser();
        $user->setPassword('');

        $manager = new UserManager();
        $manager->validate($user);
    }

    /**
     * Test 7 : Un type d'utilisateur invalide déclenche une exception.
     * Règle métier : le type doit être parmi (admin, patient, medecin).
     */
    public function testUserTypeInvalide()
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = $this->createValidUser();
        $user->setType('inconnu');

        $manager = new UserManager();
        $manager->validate($user);
    }

    /**
     * Test 8 : Un email vide déclenche une exception.
     * Règle métier : l'email est obligatoire et doit être valide.
     */
    public function testUserSansEmail()
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = $this->createValidUser();
        $user->setEmail('');

        $manager = new UserManager();
        $manager->validate($user);
    }
}
