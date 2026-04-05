<?php

namespace App\Service;

use App\Entity\Service as ServiceEntity;

/**
 * Service métier pour la gestion des services hospitaliers.
 * Contient les règles de validation métier.
 */
class ServiceManager
{
    /**
     * Valide les règles métier d'un service hospitalier.
     *
     * Règle 1 : Le nom est obligatoire et doit contenir au moins 3 caractères.
     * Règle 2 : La description est obligatoire et doit contenir au moins 10 caractères.
     * Règle 3 : Le nom ne doit pas dépasser 255 caractères.
     * Règle 4 : La description ne doit pas dépasser 255 caractères.
     * Règle 5 : Le nom ne doit pas contenir de caractères spéciaux (uniquement lettres, chiffres, espaces, tirets et apostrophes).
     *
     * @return bool true si le service est valide
     * @throws \InvalidArgumentException si une règle métier n'est pas respectée
     */
    public function validate(ServiceEntity $service): bool
    {
        // Règle 1 : Le nom est obligatoire et doit contenir au moins 3 caractères
        if (empty($service->getNom()) || strlen($service->getNom()) < 3) {
            throw new \InvalidArgumentException('Le nom du service est obligatoire et doit contenir au moins 3 caractères');
        }

        // Règle 2 : La description est obligatoire et doit contenir au moins 10 caractères
        if (empty($service->getDescription()) || strlen($service->getDescription()) < 10) {
            throw new \InvalidArgumentException('La description est obligatoire et doit contenir au moins 10 caractères');
        }

        // Règle 3 : Le nom ne doit pas dépasser 255 caractères
        if (strlen($service->getNom()) > 255) {
            throw new \InvalidArgumentException('Le nom ne doit pas dépasser 255 caractères');
        }

        // Règle 4 : La description ne doit pas dépasser 255 caractères
        if (strlen($service->getDescription()) > 255) {
            throw new \InvalidArgumentException('La description ne doit pas dépasser 255 caractères');
        }

        // Règle 5 : Le nom ne doit pas contenir de caractères spéciaux
        if (!preg_match('/^[\p{L}0-9\s\-\']+$/u', $service->getNom())) {
            throw new \InvalidArgumentException('Le nom du service ne doit contenir que des lettres, chiffres, espaces, tirets et apostrophes');
        }

        return true;
    }
}
