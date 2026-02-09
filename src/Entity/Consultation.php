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
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'La date et heure de consultation sont obligatoires')]
    private ?\DateTimeInterface $date_heure = null;

    #[ORM\Column(type: 'string', length: 255, enumType: ConsultationStatus::class)]
    #[Assert\NotBlank(message: 'Le statut de consultation est obligatoire')]
    private ConsultationStatus $statut = ConsultationStatus::EN_ATTENTE;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le motif de consultation est obligatoire')]
    #[Assert\Length(
        min: 5,
        max: 1000,
        minMessage: 'Le motif doit contenir au moins 5 caractères',
        maxMessage: 'Le motif ne doit pas dépasser 1000 caractères'
    )]
    private ?string $motif = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'Les observations ne doivent pas dépasser 2000 caractères')]
    private ?string $observations = null;

    #[ORM\ManyToOne(inversedBy: 'consultations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Un patient doit être associé')]
    private ?Patient $patient = null;

    #[ORM\ManyToOne(inversedBy: 'consultations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Un médecin doit être associé')]
    private ?Medecin $medecin = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?RendezVous $rendezVous = null;

    public function getId(): ?int
    {
        return $this->id
