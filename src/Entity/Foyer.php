<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\FoyerRepository;

#[ORM\Entity(repositoryClass: FoyerRepository::class)]
#[ORM\Table(name: 'foyers')]
class Foyer
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $documents = null;

    public function getDocuments(): ?string
    {
        return $this->documents;
    }

    public function setDocuments(?string $documents): self
    {
        $this->documents = $documents;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: false)]
    private ?float $frais = null;

    public function getFrais(): ?float
    {
        return $this->frais;
    }

    public function setFrais(float $frais): self
    {
        $this->frais = $frais;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $type = null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Hebergement::class, inversedBy: 'foyers')]
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

    public function getId(): ?int
    {
        return $this->id;
    }

}