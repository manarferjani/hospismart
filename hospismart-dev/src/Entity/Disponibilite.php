<?php

namespace App\Entity;

use App\Repository\DisponibiliteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DisponibiliteRepository::class)]
class Disponibilite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de début est obligatoire')]
    private ?\DateTimeInterface $date_debut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire')]
    #[Assert\GreaterThan(propertyPath: "date_debut", message: "La date de fin doit être après la date de début")]
    private ?\DateTimeInterface $date_fin = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "L'état de réservation est obligatoire")]
    private ?bool $est_reserve = false;

    // Correction : On pointe vers User et on lie avec la propriété 'disponibilites' dans User.php
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'disponibilites')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Un médecin doit être associé')]
    private ?User $medecin = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->date_debut;
    }

    public function setDateDebut(\DateTimeInterface $date_debut): static
    {
        $this->date_debut = $date_debut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->date_fin;
    }

    public function setDateFin(\DateTimeInterface $date_fin): static
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

    // Le getter et setter utilisent désormais la classe User
    public function getMedecin(): ?User
    {
        return $this->medecin;
    }

    public function setMedecin(?User $medecin): static
    {
        $this->medecin = $medecin;
        return $this;
    }
}