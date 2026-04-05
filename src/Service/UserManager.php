<?php

namespace App\Service;

use App\Entity\User;

class UserManager
{
    /**
     * Valide les règles métier d'un utilisateur.
     *
     * Règle 1 : Le nom est obligatoire.
     * Règle 2 : Le prénom est obligatoire.
     * Règle 3 : L'email doit être valide.
     * Règle 4 : Le mot de passe doit contenir au moins 8 caractères.
     * Règle 5 : Le type d'utilisateur doit être parmi les valeurs autorisées.
     *
     * @return bool true si l'utilisateur est valide
     * @throws \InvalidArgumentException si une règle métier n'est pas respectée
     */
    public function validate(User $user): bool
    {
        // Règle 1 : Le nom est obligatoire
        if (empty($user->getNom())) {
            throw new \InvalidArgumentException('Le nom est obligatoire');
        }

        // Règle 2 : Le prénom est obligatoire
        if (empty($user->getPrenom())) {
            throw new \InvalidArgumentException('Le prénom est obligatoire');
        }

        // Règle 3 : L'email doit être valide
        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('L\'adresse email est invalide');
        }

        // Règle 4 : Le mot de passe doit contenir au moins 8 caractères
        if (empty($user->getPassword()) || strlen($user->getPassword()) < 8) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères');
        }

        // Règle 5 : Le type d'utilisateur doit être parmi les valeurs autorisées
        $typesAutorises = ['admin', 'patient', 'medecin'];
        if (!in_array($user->getType(), $typesAutorises, true)) {
            throw new \InvalidArgumentException('Le type d\'utilisateur "' . $user->getType() . '" est invalide');
        }

        return true;
    }
}
