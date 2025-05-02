<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\DestinationRepository;

#[ORM\Entity(repositoryClass: DestinationRepository::class)]
#[ORM\Table(name: 'destinations')]
class Destination
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

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $climat = null;

    public function getClimat(): ?int
    {
        return $this->climat;
    }

    public function setClimat(?int $climat): self
    {
        $this->climat = $climat;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $pays = null;

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): self
    {
        $this->pays = $pays;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $ville = null;

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): self
    {
        $this->ville = $ville;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $latitude = null;

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $longitude = null;

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'destination')]
    private Collection $evenements;

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenements(): Collection
    {
        if (!$this->evenements instanceof Collection) {
            $this->evenements = new ArrayCollection();
        }
        return $this->evenements;
    }

    public function addEvenement(Evenement $evenement): self
    {
        if (!$this->getEvenements()->contains($evenement)) {
            $this->getEvenements()->add($evenement);
        }
        return $this;
    }

    public function removeEvenement(Evenement $evenement): self
    {
        $this->getEvenements()->removeElement($evenement);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Hebergement::class, mappedBy: 'destination')]
    private Collection $hebergements;

    /**
     * @return Collection<int, Hebergement>
     */
    public function getHebergements(): Collection
    {
        if (!$this->hebergements instanceof Collection) {
            $this->hebergements = new ArrayCollection();
        }
        return $this->hebergements;
    }

    public function addHebergement(Hebergement $hebergement): self
    {
        if (!$this->getHebergements()->contains($hebergement)) {
            $this->getHebergements()->add($hebergement);
        }
        return $this;
    }

    public function removeHebergement(Hebergement $hebergement): self
    {
        $this->getHebergements()->removeElement($hebergement);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Voyage::class, mappedBy: 'destination')]
    private Collection $voyages;

    public function __construct()
    {
        $this->voyages = new ArrayCollection();
        $this->hebergements = new ArrayCollection();
        $this->evenements = new ArrayCollection();
    }

    /**
     * @return Collection<int, Voyage>
     */
    public function getVoyages(): Collection
    {
        if (!$this->voyages instanceof Collection) {
            $this->voyages = new ArrayCollection();
        }
        return $this->voyages;
    }

    public function addVoyage(Voyage $voyage): self
    {
        if (!$this->getVoyages()->contains($voyage)) {
            $this->getVoyages()->add($voyage);
        }
        return $this;
    }

    public function removeVoyage(Voyage $voyage): self
    {
        $this->getVoyages()->removeElement($voyage);
        return $this;
    }

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;
            
    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

}
