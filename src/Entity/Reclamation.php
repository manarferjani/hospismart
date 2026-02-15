<?php

namespace App\Entity;

use App\Repository\ReclamationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReclamationRepository::class)]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre ne peut pas être vide')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description ne peut pas être vide')]
    #[Assert\Length(
        min: 10,
        max: 5000,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères',
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L\'email ne peut pas être vide')]
    #[Assert\Email(message: 'L\'adresse email "{{ value }}" est invalide')]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du patient ne peut pas être vide')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $nomPatient = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le statut ne peut pas être vide')]
    #[Assert\Choice(
        choices: ['En attente', 'En cours', 'Traité'],
        message: 'Le statut "{{ value }}" est invalide'
    )]
    private ?string $statut = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La catégorie est obligatoire')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'La catégorie ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $categorie = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(
        choices: ['Basse', 'Normale', 'Haute', 'Urgente'],
        message: 'La priorité "{{ value }}" est invalide'
    )]
    private ?string $priorite = 'Normale';

    #[ORM\OneToMany(mappedBy: 'reclamation', targetEntity: Reponse::class, orphanRemoval: true)]
    private Collection $reponses;

    public function __construct()
    {
        $this->reponses = new ArrayCollection();
        $this->dateCreation = new \DateTime();
        $this->statut = 'En attente';
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getNomPatient(): ?string
    {
        return $this->nomPatient;
    }

    public function setNomPatient(string $nomPatient): static
    {
        $this->nomPatient = $nomPatient;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getPriorite(): ?string
    {
        return $this->priorite;
    }

    public function setPriorite(string $priorite): static
    {
        $this->priorite = $priorite;
        return $this;
    }

    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(Reponse $reponse): static
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->setReclamation($this);
        }
        return $this;
    }

    public function removeReponse(Reponse $reponse): static
    {
        if ($this->reponses->removeElement($reponse)) {
            if ($reponse->getReclamation() === $this) {
                $reponse->setReclamation(null);
            }
        }
        return $this;
    }
}