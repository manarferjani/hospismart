<?php

namespace App\Entity;

use App\Repository\MedicamentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MedicamentRepository::class)]
class Medicament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du médicament est obligatoire')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le nom doit contenir au moins 2 caractères', maxMessage: 'Le nom ne doit pas dépasser 255 caractères')]
    private ?string $nom = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La quantité est obligatoire')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'La quantité doit être supérieure ou égale à 0')]
    private ?int $quantite = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le seuil d\'alerte est obligatoire')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Le seuil d\'alerte doit être supérieur ou égal à 0')]
    private ?int $seuil_alerte = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le prix unitaire est obligatoire')]
    #[Assert\GreaterThan(value: 0, message: 'Le prix unitaire doit être supérieur à 0')]
    private ?float $prix_unitaire = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de péremption est obligatoire')]
    #[Assert\GreaterThan('today', message: 'La date de péremption doit être dans le futur')]
    private ?\DateTime $date_peremption = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getSeuilAlerte(): ?int
    {
        return $this->seuil_alerte;
    }

    public function setSeuilAlerte(int $seuil_alerte): static
    {
        $this->seuil_alerte = $seuil_alerte;

        return $this;
    }

    public function getPrixUnitaire(): ?float
    {
        return $this->prix_unitaire;
    }

    public function setPrixUnitaire(float $prix_unitaire): static
    {
        $this->prix_unitaire = $prix_unitaire;

        return $this;
    }

    public function getDatePeremption(): ?\DateTime
    {
        return $this->date_peremption;
    }

    public function setDatePeremption(\DateTime $date_peremption): static
    {
        $this->date_peremption = $date_peremption;

        return $this;
    }
}
