<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\HebergementRepository;

#[ORM\Entity(repositoryClass: HebergementRepository::class)]
#[ORM\Table(name: 'hebergements')]
class Hebergement
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

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $classement = null;

    public function getClassement(): ?int
    {
        return $this->classement;
    }

    public function setClassement(int $classement): self
    {
        $this->classement = $classement;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $contact = null;

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): self
    {
        $this->contact = $contact;
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
    private $image = null;

    public function getImage(): ?string
    {
        if ($this->image === null) {
            return null;
        }
        
        if (is_resource($this->image)) {
            // If it's a resource (when freshly fetched from DB)
            rewind($this->image);
            return stream_get_contents($this->image);
        }
        
        // If it's already a string (when cached)
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

    #[ORM\ManyToOne(targetEntity: Destination::class, inversedBy: 'hebergements')]
    #[ORM\JoinColumn(name: 'location_id', referencedColumnName: 'id')]
    private ?Destination $destination = null;

    public function getDestination(): ?Destination
    {
        return $this->destination;
    }

    public function setDestination(?Destination $destination): self
    {
        $this->destination = $destination;
        return $this;
    }


    #[ORM\OneToMany(targetEntity: Foyer::class, mappedBy: 'hebergement')]
    private Collection $foyers;

    /**
     * @return Collection<int, Foyer>
     */
    public function getFoyers(): Collection
    {
        if (!$this->foyers instanceof Collection) {
            $this->foyers = new ArrayCollection();
        }
        return $this->foyers;
    }

    public function addFoyer(Foyer $foyer): self
    {
        if (!$this->getFoyers()->contains($foyer)) {
            $this->getFoyers()->add($foyer);
        }
        return $this;
    }

    public function removeFoyer(Foyer $foyer): self
    {
        $this->getFoyers()->removeElement($foyer);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Hotel::class, mappedBy: 'hebergement')]
    private Collection $hotels;

    /**
     * @return Collection<int, Hotel>
     */
    public function getHotels(): Collection
    {
        if (!$this->hotels instanceof Collection) {
            $this->hotels = new ArrayCollection();
        }
        return $this->hotels;
    }

    public function addHotel(Hotel $hotel): self
    {
        if (!$this->getHotels()->contains($hotel)) {
            $this->getHotels()->add($hotel);
        }
        return $this;
    }

    public function removeHotel(Hotel $hotel): self
    {
        $this->getHotels()->removeElement($hotel);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Logement::class, mappedBy: 'hebergement')]
    private Collection $logements;

    /**
     * @return Collection<int, Logement>
     */
    public function getLogements(): Collection
    {
        if (!$this->logements instanceof Collection) {
            $this->logements = new ArrayCollection();
        }
        return $this->logements;
    }

    public function addLogement(Logement $logement): self
    {
        if (!$this->getLogements()->contains($logement)) {
            $this->getLogements()->add($logement);
        }
        return $this;
    }

    public function removeLogement(Logement $logement): self
    {
        $this->getLogements()->removeElement($logement);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'hebergement')]
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

    #[ORM\OneToMany(targetEntity: Sejour::class, mappedBy: 'hebergement')]
    private Collection $sejours;

    public function __construct()
    {
        $this->sejours = new ArrayCollection();
        $this->foyers = new ArrayCollection();
        $this->hotels = new ArrayCollection();
        $this->logements = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->packs = new ArrayCollection();
        $this->services = new ArrayCollection();

    }

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

    #[ORM\ManyToMany(targetEntity: Pack::class, inversedBy: 'hebergements')]
    #[ORM\JoinTable(
        name: 'packs_hebergements',
        joinColumns: [
            new ORM\JoinColumn(name: 'hebergements_id', referencedColumnName: 'id')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'Pack_id', referencedColumnName: 'id')
        ]
    )]
    private Collection $packs;

    /**
     * @return Collection<int, Pack>
     */
    public function getPacks(): Collection
    {
        if (!$this->packs instanceof Collection) {
            $this->packs = new ArrayCollection();
        }
        return $this->packs;
    }

    public function addPack(Pack $pack): self
    {
        if (!$this->getPacks()->contains($pack)) {
            $this->getPacks()->add($pack);
        }
        return $this;
    }

    public function removePack(Pack $pack): self
    {
        $this->getPacks()->removeElement($pack);
        return $this;
    }
    #[ORM\OneToMany(mappedBy: 'hebergement', targetEntity: Service::class, cascade: ['persist', 'remove'])]
private Collection $services;
/**
 * @return Collection<int, Service>
 */
public function getServices(): Collection
{
    return $this->services;
}

public function addService(Service $service): self
{
    if (!$this->services->contains($service)) {
        $this->services[] = $service;
        $service->setHebergement($this);
    }

    return $this;
}

public function removeService(Service $service): self
{
    if ($this->services->removeElement($service)) {
        // Set the owning side to null (unless already changed)
        if ($service->getHebergement() === $this) {
            $service->setHebergement(null);
        }
    }

    return $this;
}






}
