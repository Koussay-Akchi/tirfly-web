<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\NiveauRepository;

#[ORM\Entity(repositoryClass: NiveauRepository::class)]
#[ORM\Table(name: 'niveaus')]
class Niveau
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true,name:"MaxNiveauXP")]
    private ?int $MaxNiveauXP = null;

    public function getMaxNiveauXP(): ?int
    {
        return $this->MaxNiveauXP;
    }

    public function setMaxNiveauXP(?int $MaxNiveauXP): self
    {
        $this->MaxNiveauXP = $MaxNiveauXP;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true,name:"Niveau")]
    private ?int $niveau = null;

    public function getNiveau(): ?int
    {
        return $this->niveau;
    }

    public function setNiveau(?int $niveau): self
    {
        $this->niveau = $niveau;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true,name:"NiveauXP")]
    private ?int $niveauXP = null;

    public function getNiveauXP(): ?int
    {
        return $this->niveauXP;
    }

    public function setNiveauXP(?int $niveauXP): self
    {
        $this->niveauXP = $niveauXP;
        return $this;
    }

    #[ORM\OneToOne(inversedBy: 'niveau', targetEntity: Client::class)]

    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Client $client = null;

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;
        return $this;
    }

}
