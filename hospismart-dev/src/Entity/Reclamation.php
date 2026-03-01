<?php

namespace App\Entity; // Déclaration du namespace pour l'entité Reclamation

use App\Repository\ReclamationRepository; // Import du repository associé à cette entité
use Doctrine\Common\Collections\ArrayCollection; // Import de la classe ArrayCollection pour gérer les collections d'objets
use Doctrine\Common\Collections\Collection; // Import de l'interface Collection pour le typage
use Doctrine\DBAL\Types\Types; // Import des types de colonnes Doctrine (TEXT, DATETIME, etc.)
use Doctrine\ORM\Mapping as ORM; // Import des annotations ORM pour le mapping base de données
use Symfony\Component\Validator\Constraints as Assert; // Import des contraintes de validation Symfony

#[ORM\Entity(repositoryClass: ReclamationRepository::class)] // Déclare cette classe comme entité Doctrine liée au repository ReclamationRepository
class Reclamation
{
    #[ORM\Id] // Marque ce champ comme clé primaire
    #[ORM\GeneratedValue] // L'ID est auto-généré par la base de données
    #[ORM\Column] // Mappe ce champ à une colonne en base de données
    private ?int $id = null; // Identifiant unique de la réclamation, null par défaut (avant persistance)

    #[ORM\Column(length: 255)] // Colonne de type string avec une longueur max de 255 caractères
    #[Assert\NotBlank(message: 'Le titre ne peut pas être vide')] // Validation : le titre ne doit pas être vide
    #[Assert\Length( // Validation : contrainte de longueur sur le titre
        min: 5, // Minimum 5 caractères
        max: 255, // Maximum 255 caractères
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères', // Message si trop court
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères' // Message si trop long
    )]
    private ?string $titre = null; // Titre de la réclamation

    #[ORM\Column(type: Types::TEXT)] // Colonne de type TEXT pour stocker de longs textes
    #[Assert\NotBlank(message: 'La description ne peut pas être vide')] // Validation : la description ne doit pas être vide
    #[Assert\Length( // Validation : contrainte de longueur sur la description
        min: 10, // Minimum 10 caractères
        max: 5000, // Maximum 5000 caractères
        minMessage: 'La description doit contenir au moins {{ limit }} caractères', // Message si trop court
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères' // Message si trop long
    )]
    private ?string $description = null; // Description détaillée de la réclamation

    #[ORM\Column(type: Types::DATETIME_MUTABLE)] // Colonne de type datetime modifiable
    private ?\DateTimeInterface $dateCreation = null; // Date de création de la réclamation

    #[ORM\Column(length: 255)] // Colonne de type string avec une longueur max de 255 caractères
    #[Assert\NotBlank(message: 'L\'email ne peut pas être vide')] // Validation : l'email est obligatoire
    #[Assert\Email(message: 'L\'adresse email "{{ value }}" est invalide')] // Validation : vérifie le format de l'email
    private ?string $email = null; // Adresse email du patient ayant créé la réclamation

    #[ORM\Column(length: 255)] // Colonne de type string avec une longueur max de 255 caractères
    #[Assert\NotBlank(message: 'Le nom du patient ne peut pas être vide')] // Validation : le nom est obligatoire
    #[Assert\Length( // Validation : contrainte de longueur sur le nom
        min: 2, // Minimum 2 caractères
        max: 255, // Maximum 255 caractères
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères', // Message si trop court
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères' // Message si trop long
    )]
    private ?string $nomPatient = null; // Nom complet du patient

    #[ORM\Column(length: 50)] // Colonne de type string avec une longueur max de 50 caractères
    #[Assert\NotBlank(message: 'Le statut ne peut pas être vide')] // Validation : le statut est obligatoire
    #[Assert\Choice( // Validation : le statut doit être parmi les choix définis
        choices: ['En attente', 'En cours', 'Traité'], // Valeurs autorisées pour le statut
        message: 'Le statut "{{ value }}" est invalide' // Message d'erreur si valeur invalide
    )]
    private ?string $statut = null; // Statut actuel de la réclamation (En attente, En cours, Traité)

    #[ORM\Column(length: 100)] // Colonne de type string avec une longueur max de 100 caractères
    #[Assert\NotBlank(message: 'La catégorie est obligatoire')] // Validation : la catégorie est obligatoire
    #[Assert\Length( // Validation : contrainte de longueur sur la catégorie
        max: 100, // Maximum 100 caractères
        maxMessage: 'La catégorie ne peut pas dépasser {{ limit }} caractères' // Message si trop long
    )]
    private ?string $categorie = null; // Catégorie de la réclamation (Service médical, Accueil, Facturation, etc.)

    #[ORM\Column(length: 50)] // Colonne de type string avec une longueur max de 50 caractères
    #[Assert\Choice( // Validation : la priorité doit être parmi les choix définis
        choices: ['Basse', 'Normale', 'Haute', 'Urgente'], // Valeurs autorisées pour la priorité
        message: 'La priorité "{{ value }}" est invalide' // Message d'erreur si valeur invalide
    )]
    private ?string $priorite = 'Normale'; // Niveau de priorité de la réclamation, par défaut "Normale"

    #[ORM\Column(length: 50, nullable: true)] // Colonne nullable de type string (max 50 caractères)
    private ?string $etatMental = null; // État mental du patient détecté par le chatbot IA (Calme, Frustré, etc.)

    #[ORM\OneToMany(mappedBy: 'reclamation', targetEntity: Reponse::class, orphanRemoval: true)] // Relation One-To-Many : une réclamation peut avoir plusieurs réponses, suppression en cascade des orphelins
    private Collection $reponses; // Collection des réponses associées à cette réclamation

    // Constructeur : initialise les valeurs par défaut lors de la création d'une réclamation
    public function __construct()
    {
        $this->reponses = new ArrayCollection(); // Initialise la collection de réponses comme une ArrayCollection vide
        $this->dateCreation = new \DateTime(); // Définit la date de création à maintenant
        $this->statut = 'En attente'; // Définit le statut initial à "En attente"
    }

    // Retourne l'identifiant unique de la réclamation
    public function getId(): ?int
    {
        return $this->id; // Retourne l'ID ou null si non persisté
    }

    // Retourne le titre de la réclamation
    public function getTitre(): ?string
    {
        return $this->titre; // Retourne le titre ou null
    }

    // Définit le titre de la réclamation
    public function setTitre(string $titre): static
    {
        $this->titre = $titre; // Affecte le nouveau titre
        return $this; // Retourne l'instance courante pour le chaînage de méthodes (fluent interface)
    }

    // Retourne la description de la réclamation
    public function getDescription(): ?string
    {
        return $this->description; // Retourne la description ou null
    }

    // Définit la description de la réclamation
    public function setDescription(string $description): static
    {
        $this->description = $description; // Affecte la nouvelle description
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Retourne la date de création de la réclamation
    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation; // Retourne la date de création ou null
    }

    // Définit la date de création de la réclamation
    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation; // Affecte la nouvelle date de création
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Retourne l'email du patient
    public function getEmail(): ?string
    {
        return $this->email; // Retourne l'email ou null
    }

    // Définit l'email du patient
    public function setEmail(string $email): static
    {
        $this->email = $email; // Affecte le nouvel email
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Retourne le nom du patient
    public function getNomPatient(): ?string
    {
        return $this->nomPatient; // Retourne le nom du patient ou null
    }

    // Définit le nom du patient
    public function setNomPatient(string $nomPatient): static
    {
        $this->nomPatient = $nomPatient; // Affecte le nouveau nom du patient
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Retourne le statut actuel de la réclamation
    public function getStatut(): ?string
    {
        return $this->statut; // Retourne le statut ou null
    }

    // Définit le statut de la réclamation (En attente, En cours, Traité)
    public function setStatut(string $statut): static
    {
        $this->statut = $statut; // Affecte le nouveau statut
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Retourne la catégorie de la réclamation
    public function getCategorie(): ?string
    {
        return $this->categorie; // Retourne la catégorie ou null
    }

    // Définit la catégorie de la réclamation
    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie; // Affecte la nouvelle catégorie (peut être null)
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Retourne le niveau de priorité de la réclamation
    public function getPriorite(): ?string
    {
        return $this->priorite; // Retourne la priorité ou null
    }

    // Définit le niveau de priorité de la réclamation
    public function setPriorite(string $priorite): static
    {
        $this->priorite = $priorite; // Affecte la nouvelle priorité
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Retourne l'état mental du patient (détecté par le chatbot)
    public function getEtatMental(): ?string
    {
        return $this->etatMental; // Retourne l'état mental ou null
    }

    // Définit l'état mental du patient
    public function setEtatMental(?string $etatMental): static
    {
        $this->etatMental = $etatMental; // Affecte le nouvel état mental (peut être null)
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Retourne la collection de toutes les réponses associées à cette réclamation
    public function getReponses(): Collection
    {
        return $this->reponses; // Retourne la collection de réponses
    }

    // Ajoute une réponse à la réclamation
    public function addReponse(Reponse $reponse): static
    {
        if (!$this->reponses->contains($reponse)) { // Vérifie si la réponse n'est pas déjà dans la collection
            $this->reponses->add($reponse); // Ajoute la réponse à la collection
            $reponse->setReclamation($this); // Définit la relation inverse : la réponse pointe vers cette réclamation
        }
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Supprime une réponse de la réclamation
    public function removeReponse(Reponse $reponse): static
    {
        if ($this->reponses->removeElement($reponse)) { // Tente de supprimer la réponse de la collection
            if ($reponse->getReclamation() === $this) { // Vérifie que la réponse appartient bien à cette réclamation
                $reponse->setReclamation(null); // Supprime la relation inverse (met la réclamation à null)
            }
        }
        return $this; // Retourne l'instance courante pour le chaînage
    }
}