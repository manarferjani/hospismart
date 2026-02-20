<?php

namespace App\Enum;

enum ConsultationStatus: string
{
    case EN_ATTENTE = 'en_attente';
    case CONFIRME = 'confirme';
    case EN_COURS = 'en_cours';
    case TERMINEE = 'terminee';
    case ANNULEE = 'annulee';

    /**
     * Retourne un label lisible pour l'interface utilisateur
     */
    public function getLabel(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::CONFIRME => 'Confirmée',
            self::EN_COURS => 'En cours',
            self::TERMINEE => 'Terminée',
            self::ANNULEE => 'Annulée',
        };
    }
}