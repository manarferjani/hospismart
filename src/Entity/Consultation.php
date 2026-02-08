<?php

namespace App\Entity;

use App\Enum\ConsultationStatus; // Import de l'Enum
use App\Repository\ConsultationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
class Consultation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    // Utilisation de l'Enum pour le statut
    #[ORM\Column(type: 'string', length: 255, enumType: ConsultationStatus::class)]
    private ConsultationStatus $statut = ConsultationStatus::EN_ATTENTE;


    #[ORM\Column(type: Types::TEXT, nullable: true)] // Nullable car on remplit après la séance
    private ?string $observations = null;


    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?RendezVous $rendezVous = null;

    public function getId(): ?int
    {
        return $this->id;
    }



    public function getStatut(): ConsultationStatus
    {
        return $this->statut;
    }

    public function setStatut(ConsultationStatus $statut): static
    {
        $this->statut = $statut;
        return $this;
    }



    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): static
    {
        $this->observations = $observations;
        return $this;
    }

    public function getRendezVous(): ?RendezVous
    {
        return $this->rendezVous;
    }

    public function setRendezVous(?RendezVous $rendezVous): static
    {
        $this->rendezVous = $rendezVous;

        return $this;
    }
}