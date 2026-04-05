<?php

namespace App\Entity; // Déclaration du namespace pour l'entité Notification

use App\Repository\NotificationRepository; // Import du repository associé à cette entité
use Doctrine\ORM\Mapping as ORM; // Import des annotations ORM pour le mapping base de données

#[ORM\Entity(repositoryClass: NotificationRepository::class)] // Déclare cette classe comme entité Doctrine liée au repository NotificationRepository
class Notification
{
    #[ORM\Id] // Marque ce champ comme clé primaire
    #[ORM\GeneratedValue] // L'ID est auto-généré par la base de données
    #[ORM\Column] // Mappe ce champ à une colonne en base de données
    private ?int $id = null; // Identifiant unique de la notification, null par défaut (avant persistance)

    #[ORM\Column(length: 255)] // Colonne de type string avec une longueur max de 255 caractères
    private ?string $content = null; // Contenu textuel de la notification (message affiché à l'utilisateur)

    #[ORM\Column] // Colonne mappée automatiquement (type déduit de DateTimeImmutable)
    private ?\DateTimeImmutable $createdAt = null; // Date de création de la notification (immuable, ne change jamais)

    #[ORM\Column] // Colonne mappée automatiquement (type boolean)
    private ?bool $isRead = false; // Indique si la notification a été lue (false par défaut = non lue)

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'notifications')] // Relation Many-To-One : plusieurs notifications peuvent appartenir à un utilisateur
    #[ORM\JoinColumn(nullable: false)] // La clé étrangère vers l'utilisateur ne peut pas être null (notification toujours liée à un user)
    private ?User $user = null; // L'utilisateur destinataire de cette notification

    #[ORM\Column(length: 50, nullable: true)] // Colonne nullable de type string (max 50 caractères)
    private ?string $type = null; // Type de la notification (ex: 'reclamation', 'info', etc.) pour le style d'affichage

    #[ORM\Column(length: 500, nullable: true)] // Colonne nullable de type string (max 500 caractères)
    private ?string $linkUrl = null; // URL de redirection quand l'utilisateur clique sur la notification (optionnel)

    // Constructeur : initialise les valeurs par défaut lors de la création d'une notification
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable(); // Définit la date de création à maintenant (immuable)
        $this->isRead = false; // Initialise la notification comme non lue
    }

    // Retourne l'identifiant unique de la notification
    public function getId(): ?int { return $this->id; } // Retourne l'ID ou null si non persisté

    // Retourne le contenu textuel de la notification
    public function getContent(): ?string { return $this->content; } // Retourne le contenu ou null

    // Définit le contenu textuel de la notification
    public function setContent(string $content): static
    {
        $this->content = $content; // Affecte le nouveau contenu
        return $this; // Retourne l'instance courante pour le chaînage de méthodes (fluent interface)
    }

    // Retourne la date de création de la notification
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; } // Retourne la date ou null

    // Définit la date de création de la notification
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt; // Affecte la nouvelle date de création
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Retourne si la notification a été lue
    public function isRead(): ?bool { return $this->isRead; } // Retourne true si lue, false sinon

    // Définit l'état de lecture de la notification
    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead; // Affecte le nouvel état de lecture (true = lue, false = non lue)
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Retourne l'utilisateur destinataire de la notification
    public function getUser(): ?User
    {
        return $this->user; // Retourne l'objet User ou null
    }

    // Définit l'utilisateur destinataire de la notification
    public function setUser(?User $user): static
    {
        $this->user = $user; // Affecte l'utilisateur destinataire
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Retourne le type de la notification
    public function getType(): ?string { return $this->type; } // Retourne le type ou null

    // Définit le type de la notification (ex: 'reclamation', 'info', etc.)
    public function setType(?string $type): static
    {
        $this->type = $type; // Affecte le nouveau type de notification
        return $this; // Retourne l'instance courante pour le chaînage
    }

    // Retourne l'URL de redirection de la notification
    public function getLinkUrl(): ?string { return $this->linkUrl; } // Retourne l'URL ou null

    // Définit l'URL de redirection quand on clique sur la notification
    public function setLinkUrl(?string $linkUrl): static
    {
        $this->linkUrl = $linkUrl; // Affecte la nouvelle URL de redirection
        return $this; // Retourne l'instance courante pour le chaînage
    }
}