<?php

namespace App\Entity;

use App\Repository\FicheMedicaleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FicheMedicaleRepository::class)]
class FicheMedicale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'fichesMedicales')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $patient = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rawSpeech = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mainSymptom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $duration = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $intensity = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $triggers = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $secondarySymptoms = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $alertLevel = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    #[ORM\Column]
    private ?bool $isConfirmed = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatient(): ?User
    {
        return $this->patient;
    }

    public function setPatient(?User $patient): static
    {
        $this->patient = $patient;
        return $this;
    }

    public function getRawSpeech(): ?string
    {
        return $this->rawSpeech;
    }

    public function setRawSpeech(?string $rawSpeech): static
    {
        $this->rawSpeech = $rawSpeech;
        return $this;
    }

    public function getMainSymptom(): ?string
    {
        return $this->mainSymptom;
    }

    public function setMainSymptom(?string $mainSymptom): static
    {
        $this->mainSymptom = $mainSymptom;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getIntensity(): ?string
    {
        return $this->intensity;
    }

    public function setIntensity(?string $intensity): static
    {
        $this->intensity = $intensity;
        return $this;
    }

    public function getTriggers(): ?string
    {
        return $this->triggers;
    }

    public function setTriggers(?string $triggers): static
    {
        $this->triggers = $triggers;
        return $this;
    }

    public function getSecondarySymptoms(): ?string
    {
        return $this->secondarySymptoms;
    }

    public function setSecondarySymptoms(?string $secondarySymptoms): static
    {
        $this->secondarySymptoms = $secondarySymptoms;
        return $this;
    }

    public function getAlertLevel(): ?string
    {
        return $this->alertLevel;
    }

    public function setAlertLevel(?string $alertLevel): static
    {
        $this->alertLevel = $alertLevel;
        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): static
    {
        $this->summary = $summary;
        return $this;
    }

    public function isConfirmed(): ?bool
    {
        return $this->isConfirmed;
    }

    public function setConfirmed(bool $isConfirmed): static
    {
        $this->isConfirmed = $isConfirmed;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
