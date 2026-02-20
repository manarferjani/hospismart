<?php

namespace App\Enum;

enum RendezVousStatut: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case CONFIRME = 'CONFIRME';
    case REFUSE = 'REFUSE';
    case ANNULE = 'ANNULE';
}
