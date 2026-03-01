<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du service est obligatoire')]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    private ?string $description = null;

    /**
     * @var Collection<int, Equipement>
     */
    #[ORM\OneToMany(targetEntity: Equipement::class, mappedBy: 'service')]
    private Collection $equipements;

    /**
     * Relation vers User (filtrée logiquement pour les médecins)
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'service_entity')]
    private Collection $medecins;

    public function __construct()
    {
        $this->equipements = new ArrayCollection();
        $this->medecins = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string { return $this->description; }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return Collection<int, Equipement>
     */
    public function getEquipements(): Collection { return $this->equipements; }

    /**
     * Retourne uniquement les utilisateurs de type MEDECIN
     * @return Collection<int, User>
     */
    public function getMedecins(): Collection
    {
        return $this->medecins->filter(function(User $user) {
            return $user->getType() === 'MEDECIN';
        });
    }

    /**
     * Ajoute un médecin au service après vérification de son type
     */
    public function addMedecin(User $user): static
    {
        if ($user->getType() !== 'MEDECIN') {
            throw new \InvalidArgumentException("L'utilisateur doit être de type MEDECIN pour rejoindre un service.");
        }

        if (!$this->medecins->contains($user)) {
            $this->medecins->add($user);
            $user->setServiceEntity($this);
        }
        return $this;
    }

    public function removeMedecin(User $user): static
    {
        if ($this->medecins->removeElement($user)) {
            if ($user->getServiceEntity() === $this) {
                $user->setServiceEntity(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? 'Nouveau Service';
    }
}