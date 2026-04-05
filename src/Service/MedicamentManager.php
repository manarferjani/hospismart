<?php

namespace App\Service;

use App\Entity\Medicament;

/**
 * Service métier pour la gestion des médicaments.
 * Contient les règles de validation métier.
 */
class MedicamentManager
{
    /**
     * Valide les règles métier d'un médicament.
     *
     * Règle 1 : Le nom est obligatoire et doit contenir au moins 3 caractères.
     * Règle 2 : La quantité doit être positive ou nulle.
     * Règle 3 : Le seuil d'alerte doit être strictement positif.
     * Règle 4 : Le prix unitaire doit être positif ou nul.
     * Règle 5 : La date de péremption est obligatoire et doit être dans le futur.
     *
     * @return bool true si le médicament est valide
     * @throws \InvalidArgumentException si une règle métier n'est pas respectée
     */
    public function validate(Medicament $medicament): bool
    {
        // Règle 1 : Le nom est obligatoire et doit contenir au moins 3 caractères
        if (empty($medicament->getNom()) || strlen($medicament->getNom()) < 3) {
            throw new \InvalidArgumentException('Le nom du médicament est obligatoire et doit contenir au moins 3 caractères');
        }

        // Règle 2 : La quantité doit être positive ou nulle
        if ($medicament->getQuantite() === null || $medicament->getQuantite() < 0) {
            throw new \InvalidArgumentException('La quantité doit être positive ou nulle');
        }

        // Règle 3 : Le seuil d'alerte doit être strictement positif
        if ($medicament->getSeuilAlerte() === null || $medicament->getSeuilAlerte() <= 0) {
            throw new \InvalidArgumentException('Le seuil d\'alerte doit être strictement positif');
        }

        // Règle 4 : Le prix unitaire doit être positif ou nul
        if ($medicament->getPrixUnitaire() === null || $medicament->getPrixUnitaire() < 0) {
            throw new \InvalidArgumentException('Le prix unitaire doit être positif ou nul');
        }

        // Règle 5 : La date de péremption est obligatoire et doit être dans le futur
        if ($medicament->getDatePeremption() === null) {
            throw new \InvalidArgumentException('La date de péremption est obligatoire');
        }
        if ($medicament->getDatePeremption() <= new \DateTime('today')) {
            throw new \InvalidArgumentException('La date de péremption doit être dans le futur');
        }

        return true;
    }
}
