<?php

namespace App\Entity;

use App\Repository\DisponibiliteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DisponibiliteRepository::class)]
class Disponibilite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La date de début est obligatoire')]
    private ?\DateTime $date_debut = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire')]
    private ?\DateTime $date_fin = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'L\'etat de réservation est obligatoire')]
    private ?bool $est_reserve = null;

    #[ORM\ManyToOne(inversedBy: 'disponibilites')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Un médecin doit être associé')]
    private ?Medecin $medecin = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->date_debut;
    }

    public function setDateDebut(\DateTime $date_debut): static
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->date_fin;
    }

    public function setDateFin(\DateTime $date_fin): static
    {
        $this->date_fin = $date_fin;

        return $this;
    }

    public function isEstReserve(): ?bool
    {
        return $this->est_reserve;
    }

    public function setEstReserve(bool $est_reserve): static
    {
        $this->est_reserve = $est_reserve;

        return $this;
    }

    public function getMedecin(): ?Medecin
    {
        return $this->medecin;
    }

    public function setMedecin(?Medecin $medecin): static
    {
        $this->medecin = $medecin;

        return $this;
    }
}
