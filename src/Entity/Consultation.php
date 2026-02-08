<?php

namespace App\Entity;

use App\Enum\ConsultationStatus; // Import de l'Enum
use App\Repository\ConsultationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
class Consultation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'La date et heure de consultation sont obligatoires')]
    private ?\DateTimeInterface $date_heure = null;

    // Utilisation de l'Enum pour le statut
    #[ORM\Column(type: 'string', length: 255, enumType: ConsultationStatus::class)]
    #[Assert\NotBlank(message: 'Le statut de consultation est obligatoire')]
    private ConsultationStatus $statut = ConsultationStatus::EN_ATTENTE;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le motif de consultation est obligatoire')]
    #[Assert\Length(min: 5, max: 1000, minMessage: 'Le motif doit contenir au moins 5 caractères', maxMessage: 'Le motif ne doit pas dépasser 1000 caractères')]
    private ?string $motif = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)] // Nullable car on remplit après la séance
    #[Assert\Length(max: 2000, maxMessage: 'Les observations ne doivent pas dépasser 2000 caractères')]
    private ?string $observations = null;

    // Correction : inversedBy doit pointer vers la collection dans l'entité Patient
    #[ORM\ManyToOne(inversedBy: 'consultations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Un patient doit être associé')]
    private ?Patient $patient = null;

    // AJOUT : La relation avec le médecin
    #[ORM\ManyToOne(inversedBy: 'consultations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Un médecin doit être associé')]
    private ?Medecin $medecin = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateHeure(): ?\DateTimeInterface
    {
        return $this->date_heure;
    }

    public function setDateHeure(\DateTimeInterface $date_heure): static
    {
        $this->date_heure = $date_heure;
        return $this;
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

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): static
    {
        $this->motif = $motif;
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

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): static
    {
        $this->patient = $patient;
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