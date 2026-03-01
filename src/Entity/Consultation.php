<?php

namespace App\Entity;

use App\Enum\ConsultationStatus;
use App\Repository\ConsultationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
class Consultation
{

    public const PRIORITE_URGENT = 5;    // Urgence vitale
    public const PRIORITE_ELEVE = 4;     // Douleur intense / Risque
    public const PRIORITE_MOYEN = 3;     // Pathologie aiguë stable
    public const PRIORITE_STANDARD = 2;  // Suivi / Routine
    public const PRIORITE_ADMIN = 1;     // Certificats / Ordonnances

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $priorite = self::PRIORITE_STANDARD;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'La date et heure sont obligatoires')]
    private ?\DateTimeInterface $date_heure = null;

    #[ORM\Column(type: 'string', length: 255, enumType: ConsultationStatus::class)]
    private ConsultationStatus $statut = ConsultationStatus::EN_ATTENTE;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le motif est obligatoire')]
    private ?string $motif = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observations = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $patient = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $medecin = null;

    #[ORM\OneToOne(inversedBy: 'consultation', cascade: ['persist'])]
    private ?RendezVous $rendezVous = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $examen_clinique = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $diagnostic = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $traitement = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $examens_complementaires = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $recommandations = null;

    public function __construct()
    {
        $this->date_heure = new \DateTime();
    }

    // ... Getters & Setters (Gardez ceux que vous avez déjà)
    public function getId(): ?int { return $this->id; }
    public function getDateHeure(): ?\DateTimeInterface { return $this->date_heure; }
    public function setDateHeure(\DateTimeInterface $date_heure): static { $this->date_heure = $date_heure; return $this; }
    public function getStatut(): ConsultationStatus { return $this->statut; }
    public function setStatut(ConsultationStatus $statut): static { $this->statut = $statut; return $this; }
    public function getMotif(): ?string { return $this->motif; }
    public function setMotif(string $motif): static { $this->motif = $motif; return $this; }
    public function getObservations(): ?string { return $this->observations; }
    public function setObservations(?string $observations): static { $this->observations = $observations; return $this; }
    public function getPatient(): ?User { return $this->patient; }
    public function setPatient(?User $patient): static { $this->patient = $patient; return $this; }
    public function getMedecin(): ?User { return $this->medecin; }
    public function setMedecin(?User $medecin): static { $this->medecin = $medecin; return $this; }
    public function getRendezVous(): ?RendezVous { return $this->rendezVous; }
    public function setRendezVous(?RendezVous $rendezVous): static { $this->rendezVous = $rendezVous; return $this; }

    public function getExamenClinique(): ?string
    {
        return $this->examen_clinique;
    }

    public function setExamenClinique(string $examen_clinique): static
    {
        $this->examen_clinique = $examen_clinique;

        return $this;
    }

    public function getDiagnostic(): ?string
    {
        return $this->diagnostic;
    }

    public function setDiagnostic(string $diagnostic): static
    {
        $this->diagnostic = $diagnostic;

        return $this;
    }

    public function getTraitement(): ?string
    {
        return $this->traitement;
    }

    public function setTraitement(string $traitement): static
    {
        $this->traitement = $traitement;

        return $this;
    }

    public function getExamensComplementaires(): ?string
    {
        return $this->examens_complementaires;
    }

    public function setExamensComplementaires(string $examens_complementaires): static
    {
        $this->examens_complementaires = $examens_complementaires;

        return $this;
    }

    public function getRecommandations(): ?string
    {
        return $this->recommandations;
    }

    public function setRecommandations(string $recommandations): static
    {
        $this->recommandations = $recommandations;

        return $this;
    }

    public function getPriorite(): int
    {
        return $this->priorite;
    }

    public function setPriorite(int $priorite): static
    {
        $this->priorite = $priorite;
        return $this;
    }
}