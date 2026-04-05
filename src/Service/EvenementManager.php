<?php

namespace App\Service;

use App\Entity\Evenement;

/**
 * Service métier pour la gestion des événements.
 * Contient les règles de validation métier.
 */
class EvenementManager
{
    /**
     * Valide les règles métier d'un événement.
     *
     * Règle 1 : Le titre est obligatoire et doit contenir au moins 5 caractères.
     * Règle 2 : La description est obligatoire.
     * Règle 3 : Le type d'événement doit être parmi les valeurs autorisées.
     * Règle 4 : Le lieu est obligatoire.
     * Règle 5 : La date de fin doit être après la date de début.
     * Règle 6 : Le statut doit être parmi les valeurs autorisées.
     *
     * @return bool true si l'événement est valide
     * @throws \InvalidArgumentException si une règle métier n'est pas respectée
     */
    public function validate(Evenement $evenement): bool
    {
        // Règle 1 : Le titre est obligatoire et doit contenir au moins 5 caractères
        if (empty($evenement->getTitre()) || strlen($evenement->getTitre()) < 5) {
            throw new \InvalidArgumentException('Le titre est obligatoire et doit contenir au moins 5 caractères');
        }

        // Règle 2 : La description est obligatoire
        if (empty($evenement->getDescription())) {
            throw new \InvalidArgumentException('La description est obligatoire');
        }

        // Règle 3 : Le type d'événement doit être parmi les valeurs autorisées
        $typesAutorises = ['conference', 'formation', 'reunion', 'seminaire', 'atelier'];
        if (!in_array($evenement->getTypeEvenement(), $typesAutorises, true)) {
            throw new \InvalidArgumentException('Le type d\'événement "' . $evenement->getTypeEvenement() . '" est invalide');
        }

        // Règle 4 : Le lieu est obligatoire
        if (empty($evenement->getLieu())) {
            throw new \InvalidArgumentException('Le lieu est obligatoire');
        }

        // Règle 5 : La date de fin doit être après la date de début
        if ($evenement->getDateDebut() === null || $evenement->getDateFin() === null) {
            throw new \InvalidArgumentException('Les dates de début et de fin sont obligatoires');
        }
        if ($evenement->getDateFin() <= $evenement->getDateDebut()) {
            throw new \InvalidArgumentException('La date de fin doit être après la date de début');
        }

        // Règle 6 : Le statut doit être parmi les valeurs autorisées
        $statutsAutorises = ['planifie', 'en_cours', 'termine', 'annule'];
        if (!in_array($evenement->getStatut(), $statutsAutorises, true)) {
            throw new \InvalidArgumentException('Le statut "' . $evenement->getStatut() . '" est invalide');
        }

        return true;
    }
}
