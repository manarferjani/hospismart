<?php

namespace App\Entity;

use App\Repository\MedicamentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    #[Assert\NotBlank(message: 'Le nom du médicament est obligatoire.')]
    #[Assert\Length(min: 3, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.')]
    private ?string $nom = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La quantité est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La quantité doit être positive ou nulle.')]
    private ?int $quantite = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le seuil d\'alerte est obligatoire.')]
    #[Assert\Positive(message: 'Le seuil d\'alerte doit être strictement positif.')]
    private ?int $seuil_alerte = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le prix unitaire est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le prix unitaire doit être positif ou nul.')]
    private ?float $prix_unitaire = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de péremption est obligatoire.')]
    private ?\DateTime $date_peremption = null;

    /**
     * @var Collection<int, MouvementStock>
     */
    #[ORM\OneToMany(targetEntity: MouvementStock::class, mappedBy: 'medicament', cascade: ['persist'])]
    private Collection $mouvements;

    #[ORM\ManyToOne(inversedBy: 'medicaments')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Categorie $categorie = null;

    public function __construct()
    {
        $this->mouvements = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }

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

    public function setQuantite(?int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getSeuilAlerte(): ?int
    {
        return $this->seuil_alerte;
    }

    public function setSeuilAlerte(?int $seuil_alerte): static
    {
        $this->seuil_alerte = $seuil_alerte;

        return $this;
    }

    public function getPrixUnitaire(): ?float
    {
        return $this->prix_unitaire;
    }

    public function setPrixUnitaire(?float $prix_unitaire): static
    {
        $this->prix_unitaire = $prix_unitaire;

        return $this;
    }

    public function getDatePeremption(): ?\DateTime
    {
        return $this->date_peremption;
    }

    public function setDatePeremption(?\DateTime $date_peremption): static
    {
        $this->date_peremption = $date_peremption;

        return $this;
    }

    /**
     * @return Collection<int, MouvementStock>
     */
    public function getMouvements(): Collection
    {
        return $this->mouvements;
    }

    public function addMouvement(MouvementStock $mouvement): static
    {
        if (!$this->mouvements->contains($mouvement)) {
            $this->mouvements->add($mouvement);
            $mouvement->setMedicament($this);
        }

        return $this;
    }

    public function removeMouvement(MouvementStock $mouvement): static
    {
        if ($this->mouvements->removeElement($mouvement)) {
            // set the owning side to null (unless already changed)
            if ($mouvement->getMedicament() === $this) {
                $mouvement->setMedicament(null);
            }
        }

        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }
}
