<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\LogementRepository;

#[ORM\Entity(repositoryClass: LogementRepository::class)]
#[ORM\Table(name: 'logements')]
class Logement
{
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $jour_dispo = null;

    public function getJour_dispo(): ?\DateTimeInterface
    {
        return $this->jour_dispo;
    }

    public function setJour_dispo(?\DateTimeInterface $jour_dispo): self
    {
        $this->jour_dispo = $jour_dispo;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: false)]
    private ?float $prix = null;

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Hebergement::class, inversedBy: 'logements')]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'id')]
    private ?Hebergement $hebergement = null;

    public function getHebergement(): ?Hebergement
    {
        return $this->hebergement;
    }

    public function setHebergement(?Hebergement $hebergement): self
    {
        $this->hebergement = $hebergement;
        return $this;
    }

}
