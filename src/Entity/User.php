<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // -------- INFOS COMMUNES --------

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class, orphanRemoval: true)]
    private Collection $notifications;

    // -------- TYPE UTILISATEUR (ADMIN, PATIENT, MEDECIN) --------

    #[ORM\Column(length: 20)]
    private ?string $type = null;

    // Relations
    #[ORM\OneToMany(mappedBy: 'medecin', targetEntity: Disponibilite::class, orphanRemoval: true)]
    private Collection $disponibilites;

    #[ORM\OneToMany(mappedBy: 'medecin', targetEntity: RendezVous::class)]
    private Collection $rendezVousMedecin;

    #[ORM\OneToMany(mappedBy: 'patient', targetEntity: RendezVous::class)]
    private Collection $rendezVousPatient;

    // -------- CHAMPS SPÉCIFIQUES PATIENT --------

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_naissance = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $genre = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $groupe_sanguin = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image_patient = null;

    // -------- CHAMPS SPÉCIFIQUES MEDECIN --------

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $specialite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $matricule = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $service = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image_medecin = null;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'medecins')]
    private ?Service $service_entity = null;

    public function __construct()
    {
        $this->roles = [];
        $this->disponibilites = new ArrayCollection();
        $this->rendezVousMedecin = new ArrayCollection();
        $this->rendezVousPatient = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    // ================= GETTERS & SETTERS =================

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }

    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function eraseCredentials(): void {}

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    // --- Getters/Setters Patient ---
    public function getDateNaissance(): ?\DateTimeInterface { return $this->date_naissance; }
    public function setDateNaissance(?\DateTimeInterface $date_naissance): static { $this->date_naissance = $date_naissance; return $this; }

    public function getGenre(): ?string { return $this->genre; }
    public function setGenre(?string $genre): static { $this->genre = $genre; return $this; }

    public function getGroupeSanguin(): ?string { return $this->groupe_sanguin; }
    public function setGroupeSanguin(?string $groupe_sanguin): static { $this->groupe_sanguin = $groupe_sanguin; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): static { $this->adresse = $adresse; return $this; }

    // --- Getters/Setters Medecin & Relations ---
    public function getSpecialite(): ?string { return $this->specialite; }
    public function setSpecialite(?string $specialite): static { $this->specialite = $specialite; return $this; }

    public function getMatricule(): ?string { return $this->matricule; }
    public function setMatricule(?string $matricule): static { $this->matricule = $matricule; return $this; }

    public function getNotifications(): Collection { return $this->notifications; }
    public function getServiceEntity(): ?Service { return $this->service_entity; }
    public function setServiceEntity(?Service $service_entity): static { $this->service_entity = $service_entity; return $this; }

    /**
     * @return Collection<int, RendezVous>
     */
    public function getRendezVousMedecin(): Collection
    {
        return $this->rendezVousMedecin;
    }

    /**
     * @return Collection<int, RendezVous>
     */
    public function getRendezVousPatient(): Collection
    {
        return $this->rendezVousPatient;
    }

    /**
     * @return Collection<int, Disponibilite>
     */
    public function getDisponibilites(): Collection
    {
        return $this->disponibilites;
    }
    public function getImage(): ?string 
    { 
        return $this->image; 
    }

    public function setImage(?string $image): static 
    { 
        $this->image = $image; 
        return $this; 
    }
}