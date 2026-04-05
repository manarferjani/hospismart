<?php

namespace App\Entity;

use App\Repository\CampagneRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CampagneRepository::class)]
class Campagne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre de la campagne est obligatoire')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Le titre doit contenir au moins 3 caractères', maxMessage: 'Le titre ne doit pas dépasser 255 caractères')]
    private string $titre = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le thème est obligatoire')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le thème doit contenir au moins 2 caractères', maxMessage: 'Le thème ne doit pas dépasser 255 caractères')]
    private string $theme = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(min: 10, max: 5000, minMessage: 'La description doit contenir au moins 10 caractères', maxMessage: 'La description ne doit pas dépasser 5000 caractères')]
    private string $description = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotBlank(message: 'La date de début est obligatoire')]
    #[Assert\GreaterThan('today', message: 'La date de début doit être dans le futur')]
    private \DateTimeImmutable $date_debut;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire')]
    private \DateTimeImmutable $date_fin;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le budget est obligatoire')]
    #[Assert\GreaterThan(value: 0, message: 'Le budget doit être supérieur à 0')]
    private float $budget = 0.0;

    public function __construct()
    {
        $this->date_debut = new \DateTimeImmutable();
        $this->date_fin = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->date_debut;
    }

    public function setDateDebut(\DateTimeImmutable $date_debut): static
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->date_fin;
    }

    public function setDateFin(\DateTimeImmutable $date_fin): static
    {
        $this->date_fin = $date_fin;

        return $this;
    }

    public function getBudget(): ?float
    {
        return $this->budget;
    }

    public function setBudget(float $budget): static
    {
        $this->budget = $budget;

        return $this;
    }
}
