<?php

namespace App\Entity;

use App\Repository\ParametreVitalRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParametreVitalRepository::class)]
class ParametreVital
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La tension artérielle est obligatoire')]
    #[Assert\Regex(pattern: '/^\d{1,3}\/\d{1,3}$/', message: 'La tension doit être au format (ex: 120/80)')]
    private ?string $tension = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La température est obligatoire')]
    #[Assert\Range(min: 35, max: 45, notInRangeMessage: 'La température doit être entre 35°C et 45°C')]
    private ?float $temperature = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La fréquence cardiaque est obligatoire')]
    #[Assert\Range(min: 40, max: 200, notInRangeMessage: 'La fréquence cardiaque doit être entre 40 et 200 bpm')]
    private ?int $frequence_cardiaque = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La date de prise est obligatoire')]
    #[Assert\LessThanOrEqual('today', message: 'La date de prise ne peut pas être dans le futur')]
    private ?\DateTime $date_prise = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTension(): ?string
    {
        return $this->tension;
    }

    public function setTension(string $tension): static
    {
        $this->tension = $tension;

        return $this;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(float $temperature): static
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function getFrequenceCardiaque(): ?int
    {
        return $this->frequence_cardiaque;
    }

    public function setFrequenceCardiaque(int $frequence_cardiaque): static
    {
        $this->frequence_cardiaque = $frequence_cardiaque;

        return $this;
    }

    public function getDatePrise(): ?\DateTime
    {
        return $this->date_prise;
    }

    public function setDatePrise(\DateTime $date_prise): static
    {
        $this->date_prise = $date_prise;

        return $this;
    }
}
