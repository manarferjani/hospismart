<?php

namespace App\Enum;

enum ConsultationStatus: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case EN_COURS = 'EN_COURS';
    case TERMINEE = 'TERMINEE';
    case ANNULEE = 'ANNULEE';
}
