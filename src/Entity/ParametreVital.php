<?php

namespace App\Entity;

use App\Repository\ParametreVitalRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParametreVitalRepository::class)]
class ParametreVital
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $tension = null;

    #[ORM\Column]
    private ?float $temperature = null;

    #[ORM\Column]
    private ?int $frequence_cardiaque = null;

    #[ORM\Column]
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
