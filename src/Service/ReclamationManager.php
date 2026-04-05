<?php

namespace App\Service;

use App\Entity\Reclamation;

class ReclamationManager
{
    /**
     * Valide les règles métier d'une réclamation.
     *
     * Règle 1 : Le titre est obligatoire et doit contenir au moins 5 caractères.
     * Règle 2 : L'email doit être valide.
     * Règle 3 : La priorité doit être parmi les valeurs autorisées.
     * Règle 4 : La description est obligatoire et doit contenir au moins 10 caractères.
     * Règle 5 : La catégorie doit être parmi les valeurs autorisées.
     *
     * @return bool true si la réclamation est valide
     * @throws \InvalidArgumentException si une règle métier n'est pas respectée
     */
    public function validate(Reclamation $reclamation): bool
    {
        // Règle 1 : Le titre est obligatoire et doit contenir au moins 5 caractères
        if (empty($reclamation->getTitre()) || strlen($reclamation->getTitre()) < 5) {
            throw new \InvalidArgumentException('Le titre est obligatoire et doit contenir au moins 5 caractères');
        }

        // Règle 2 : L'email doit être valide
        if (!filter_var($reclamation->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('L\'adresse email est invalide');
        }

        // Règle 3 : La priorité doit être parmi les valeurs autorisées
        $prioritesAutorisees = ['Basse', 'Normale', 'Haute', 'Urgente'];
        if (!in_array($reclamation->getPriorite(), $prioritesAutorisees, true)) {
            throw new \InvalidArgumentException('La priorité "' . $reclamation->getPriorite() . '" est invalide');
        }

        // Règle 4 : La description est obligatoire et doit contenir au moins 10 caractères
        if (empty($reclamation->getDescription()) || strlen($reclamation->getDescription()) < 10) {
            throw new \InvalidArgumentException('La description est obligatoire et doit contenir au moins 10 caractères');
        }

        // Règle 5 : La catégorie doit être parmi les valeurs autorisées
        $categoriesAutorisees = ['Service médical', 'Accueil', 'Facturation', 'Hygiène', 'Autre'];
        if (!in_array($reclamation->getCategorie(), $categoriesAutorisees, true)) {
            throw new \InvalidArgumentException('La catégorie "' . $reclamation->getCategorie() . '" est invalide');
        }

        return true;
    }
}
