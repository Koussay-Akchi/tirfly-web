<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\PackRepository;

#[ORM\Entity(repositoryClass: PackRepository::class)]
#[ORM\Table(name: 'packs')]
class Pack
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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'blob', nullable: true)]
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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $nom = null;

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
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

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'pack')]
    private Collection $reservations;

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        if (!$this->reservations instanceof Collection) {
            $this->reservations = new ArrayCollection();
        }
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->getReservations()->contains($reservation)) {
            $this->getReservations()->add($reservation);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        $this->getReservations()->removeElement($reservation);
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Hebergement::class, inversedBy: 'packs')]
    #[ORM\JoinTable(
        name: 'packs_hebergements',
        joinColumns: [
            new ORM\JoinColumn(name: 'Pack_id', referencedColumnName: 'id')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'hebergements_id', referencedColumnName: 'id')
        ]
    )]
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

    #[ORM\ManyToMany(targetEntity: Sejour::class, inversedBy: 'packs')]
    #[ORM\JoinTable(
        name: 'packs_sejours',
        joinColumns: [
            new ORM\JoinColumn(name: 'Pack_id', referencedColumnName: 'id')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'sejours_id', referencedColumnName: 'id')
        ]
    )]
    private Collection $sejours;

    /**
     * @return Collection<int, Sejour>
     */
    public function getSejours(): Collection
    {
        if (!$this->sejours instanceof Collection) {
            $this->sejours = new ArrayCollection();
        }
        return $this->sejours;
    }

    public function addSejour(Sejour $sejour): self
    {
        if (!$this->getSejours()->contains($sejour)) {
            $this->getSejours()->add($sejour);
        }
        return $this;
    }

    public function removeSejour(Sejour $sejour): self
    {
        $this->getSejours()->removeElement($sejour);
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Voyage::class, inversedBy: 'packs')]
    #[ORM\JoinTable(
        name: 'packs_voyages',
        joinColumns: [
            new ORM\JoinColumn(name: 'Pack_id', referencedColumnName: 'id')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'voyages_id', referencedColumnName: 'id')
        ]
    )]
    private Collection $voyages;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->hebergements = new ArrayCollection();
        $this->sejours = new ArrayCollection();
        $this->voyages = new ArrayCollection();
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

}
