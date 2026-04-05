<?php

namespace App\Entity; // Déclaration du namespace pour l'entité Reponse

use App\Repository\ReponseRepository; // Import du repository associé à cette entité
use Doctrine\DBAL\Types\Types; // Import des types de colonnes Doctrine (TEXT, DATETIME, etc.)
use Doctrine\ORM\Mapping as ORM; // Import des annotations ORM pour le mapping base de données
use Symfony\Component\Validator\Constraints as Assert; // Import des contraintes de validation Symfony

#[ORM\Entity(repositoryClass: ReponseRepository::class)] // Déclare cette classe comme entité Doctrine liée au repository ReponseRepository
class Reponse
{
    #[ORM\Id] // Marque ce champ comme clé primaire
    #[ORM\GeneratedValue] // L'ID est auto-généré par la base de données
    #[ORM\Column] // Mappe ce champ à une colonne en base de données
    private ?int $id = null; // Identifiant unique de la réponse, null par défaut (avant persistance)

    #[ORM\Column(type: Types::TEXT)] // Colonne de type TEXT pour stocker le contenu de la réponse
    #[Assert\NotBlank(message: 'Le contenu de la réponse ne peut pas être vide')] // Validation : le contenu est obligatoire
    #[Assert\Length( // Validation : contrainte de longueur sur le contenu
        min: 10, // Minimum 10 caractères
        max: 5000, // Maximum 5000 caractères
        minMessage: 'Le contenu doit contenir au moins {{ limit }} caractères', // Message si trop court
        maxMessage: 'Le contenu ne peut pas dépasser {{ limit }} caractères' // Message si trop long
    )]
    private ?string $contenu = null; // Contenu textuel de la réponse à la réclamation

    #[ORM\Column(type: Types::DATETIME_MUTABLE)] // Colonne de type datetime modifiable
    private ?\DateTimeInterface $dateReponse = null; // Date à laquelle la réponse a été créée

    #[ORM\Column(length: 255)] // Colonne de type string avec une longueur max de 255 caractères
    #[Assert\NotBlank(message: 'Le nom de l\'administrateur ne peut pas être vide')] // Validation : le nom admin est obligatoire
    #[Assert\Length( // Validation : contrainte de longueur sur le nom admin
        min: 2, // Minimum 2 caractères
        max: 255, // Maximum 255 caractères
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères', // Message si trop court
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères' // Message si trop long
    )]
    private ?string $adminNom = null; // Nom de l'administrateur qui a répondu

    #[ORM\Column(length: 255)] // Colonne de type string avec une longueur max de 255 caractères
    #[Assert\NotBlank(message: 'L\'email de l\'administrateur ne peut pas être vide')] // Validation : l'email admin est obligatoire
    #[Assert\Email(message: 'L\'adresse email "{{ value }}" est invalide')] // Validation : vérifie le format de l'email
    private ?string $adminEmail = null; // Adresse email de l'administrateur qui a répondu

    #[ORM\Column(length: 255, nullable: true)] // Colonne nullable de type string (max 255 caractères)
    private ?string $adminAdresse = null; // Adresse physique de l'administrateur (optionnel)

    #[ORM\ManyToOne(inversedBy: 'reponses')] // Relation Many-To-One : plusieurs réponses peuvent appartenir à une réclamation
    #[ORM\JoinColumn(nullable: false)] // La clé étrangère vers la réclamation ne peut pas être null
    #[Assert\NotNull(message: 'Une réclamation doit être associée à cette réponse')] // Validation : une réclamation doit être liée
    private ?Reclamation $reclamation = null; // Réclamation à laquelle cette réponse est associée

    // Constructeur : initialise les valeurs par défaut lors de la création d'une réponse
    public function __construct()
    {
        $this->dateReponse = new \DateTime(); // Définit la date de réponse à maintenant
    }

    // Retourne l'identifiant unique de la réponse
    public function getId(): ?int
    {
        return $this->id; // Retourne l'ID ou null si non persisté
    }

    // Retourne le contenu de la réponse
    public function getContenu(): ?string
    {
        return $this->contenu; // Retourne le contenu textuel ou null
    }

    // Définit le contenu de la réponse
    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu; // Affecte le nouveau contenu
        return $this; // Retourne l'instance courante pour le chaînage de méthodes (fluent interface)
    }

    // Retourne la date de la réponse
    public function getDateReponse(): ?\DateTimeInterface
    {
        return $this->dateReponse; // Retourne la date de réponse ou null
    }

    // Définit la date de la réponse
    public function setDateReponse(\DateTimeInterface $dateReponse): static
    {
        $this->dateReponse = $dateReponse; // Affecte la nouvelle date
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Retourne le nom de l'administrateur
    public function getAdminNom(): ?string
    {
        return $this->adminNom; // Retourne le nom de l'admin ou null
    }

    // Définit le nom de l'administrateur
    public function setAdminNom(string $adminNom): static
    {
        $this->adminNom = $adminNom; // Affecte le nouveau nom admin
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Retourne l'email de l'administrateur
    public function getAdminEmail(): ?string
    {
        return $this->adminEmail; // Retourne l'email de l'admin ou null
    }

    // Définit l'email de l'administrateur
    public function setAdminEmail(string $adminEmail): static
    {
        $this->adminEmail = $adminEmail; // Affecte le nouvel email admin
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Retourne l'adresse de l'administrateur
    public function getAdminAdresse(): ?string
    {
        return $this->adminAdresse; // Retourne l'adresse de l'admin ou null
    }

    // Définit l'adresse de l'administrateur
    public function setAdminAdresse(?string $adminAdresse): static
    {
        $this->adminAdresse = $adminAdresse; // Affecte la nouvelle adresse admin (peut être null)
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Retourne la réclamation associée à cette réponse
    public function getReclamation(): ?Reclamation
    {
        return $this->reclamation; // Retourne l'objet Reclamation ou null
    }

    // Définit la réclamation associée à cette réponse
    public function setReclamation(?Reclamation $reclamation): static
    {
        $this->reclamation = $reclamation; // Affecte la réclamation liée (peut être null)
        return $this; // Retourne l'instance courante pour le chaînage
    }
}