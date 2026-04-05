<?php

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $titre = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    #[ORM\Column(length: 50)]
    private string $type_evenement = '';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $date_debut;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $date_fin;

    #[ORM\Column(length: 255)]
    private string $lieu = '';

    #[ORM\Column(length: 50)]
    private string $statut = 'planifie';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $budget_alloue = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $longitude = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createur = null;

    #[ORM\OneToMany(targetEntity: ParticipantEvenement::class, mappedBy: 'evenement', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $participants;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->date_debut = new \DateTime();
        $this->date_fin = new \DateTime();
    }
    public function getId(): ?int { return $this->id; }
    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(?string $t): static { $this->titre=$t; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description=$d; return $this; }
    public function getTypeEvenement(): ?string { return $this->type_evenement; }
    public function setTypeEvenement(?string $t): static { $this->type_evenement=$t; return $this; }
    public function getDateDebut(): ?\DateTimeInterface { return $this->date_debut; }
    public function setDateDebut(?\DateTimeInterface $d): static { $this->date_debut=$d; return $this; }
    public function getDateFin(): ?\DateTimeInterface { return $this->date_fin; }
    public function setDateFin(?\DateTimeInterface $d): static { $this->date_fin=$d; return $this; }
    public function getLieu(): ?string { return $this->lieu; }
    public function setLieu(?string $l): static { $this->lieu=$l; return $this; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(?string $s): static { $this->statut=$s; return $this; }
    public function getBudgetAlloue(): ?string { return $this->budget_alloue; }
    public function setBudgetAlloue(?string $b): static { $this->budget_alloue=$b; return $this; }
    public function getLatitude(): ?float { return $this->latitude; }
    public function setLatitude(?float $lat): static { $this->latitude=$lat; return $this; }
    public function getLongitude(): ?float { return $this->longitude; }
    public function setLongitude(?float $lng): static { $this->longitude=$lng; return $this; }
    public function getCreateur(): ?User { return $this->createur; }
    public function setCreateur(?User $u): static { $this->createur=$u; return $this; }
    public function getParticipants(): Collection { return $this->participants; }
    public function addParticipant(ParticipantEvenement $p): static { if(!$this->participants->contains($p)){$this->participants->add($p);$p->setEvenement($this);} return $this; }
    public function removeParticipant(ParticipantEvenement $p): static { if($this->participants->removeElement($p)){if($p->getEvenement()===$this){$p->setEvenement(null);}} return $this; }
}
