<?php

namespace App\Enum; // Vérifie bien cette ligne !

enum ConsultationStatus: string
{
    case EN_ATTENTE = 'en_attente';
    case CONFIRME = 'confirme';
    case ANNULE = 'annule';
    case TERMINE = 'termine';
}