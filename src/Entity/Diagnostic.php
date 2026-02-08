<?php

namespace App\Entity;

use App\Repository\DiagnosticRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DiagnosticRepository::class)]
class Diagnostic
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu du diagnostic est obligatoire')]
    #[Assert\Length(min: 10, max: 5000, minMessage: 'Le contenu doit contenir au moins 10 caractères', maxMessage: 'Le contenu ne doit pas dépasser 5000 caractères')]
    private ?string $contenu = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La probabilité IA est obligatoire')]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: 'La probabilité doit être entre 0 et 100')]
    private ?float $probabilite_ia = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getProbabiliteIa(): ?float
    {
        return $this->probabilite_ia;
    }

    public function setProbabiliteIa(float $probabilite_ia): static
    {
        $this->probabilite_ia = $probabilite_ia;

        return $this;
    }
}
