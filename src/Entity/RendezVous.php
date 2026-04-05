<?php

namespace App\Entity;

use App\Repository\RendezVousRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RendezVousRepository::class)]
class RendezVous
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $datetime;

    #[ORM\Column(length: 255)]
    private string $statut = 'EN_ATTENTE';

    #[ORM\Column(type: Types::TEXT)]
    private string $motif = '';

    // Lien avec la collection 'rendezVousPatient' dans User.php
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'rendezVousPatient')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $patient = null;
    #[ORM\OneToOne(mappedBy: 'rendezVous', targetEntity: Consultation::class)]
    private ?Consultation $consultation = null;

    // Lien avec la collection 'rendezVousMedecin' dans User.php
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'rendezVousMedecin')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $medecin = null;

    #[ORM\ManyToOne(targetEntity: Disponibilite::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Disponibilite $disponibilite = null;

    #[ORM\Column]
    private int $priorite = 0;

    public function __construct()
    {
        $this->datetime = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getDatetime(): ?\DateTimeInterface { return $this->datetime; }

    public function setDatetime(\DateTimeInterface $datetime): static {
        $this->datetime = $datetime;
        return $this;
    }

    public function getStatut(): ?string { return $this->statut; }

    public function setStatut(string $statut): static {
        $this->statut = $statut;
        return $this;
    }

    public function getMotif(): ?string { return $this->motif; }

    public function setMotif(string $motif): static {
        $this->motif = $motif;
        return $this;
    }

    public function getPatient(): ?User {
        return $this->patient;
    }

    public function setPatient(?User $patient): static {
        $this->patient = $patient;
        return $this;
    }

    public function getMedecin(): ?User {
        return $this->medecin;
    }

    public function setMedecin(?User $medecin): static {
        $this->medecin = $medecin;
        return $this;
    }

    public function getDisponibilite(): ?Disponibilite {
        return $this->disponibilite;
    }

    public function setDisponibilite(?Disponibilite $disponibilite): static {
        $this->disponibilite = $disponibilite;
        return $this;
    }
    public function getConsultation(): ?Consultation 
{ 
    return $this->consultation; 
}

public function setConsultation(?Consultation $consultation): static 
{
    $this->consultation = $consultation;
    return $this;
}

public function getPriorite(): ?int
{
    return $this->priorite;
}

public function setPriorite(int $priorite): static
{
    $this->priorite = $priorite;

    return $this;
}
}