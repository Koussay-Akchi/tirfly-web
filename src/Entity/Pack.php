<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\PackRepository;

#[ORM\Entity(repositoryClass: PackRepository::class)]
#[ORM\Table(name: 'packs')]
class Pack
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'string', nullable: true, name: 'nom')]
    #[Assert\NotBlank(message: 'Le nom ne doit pas être vide.')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Le nom doit comporter au moins {{ limit }} caractères.')]
    private ?string $nom = null;

    #[ORM\Column(type: 'decimal', nullable: false)]
    #[Assert\NotBlank(message: 'Le prix est requis.')]
    #[Assert\Positive(message: 'Le prix doit être supérieur à 0.')]
    private ?float $prix = null;

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'pack')]
    private Collection $reservations;

    #[ORM\ManyToMany(targetEntity: Sejour::class, inversedBy: 'packs')]
    #[ORM\JoinTable(
        name: 'packs_sejours',
        joinColumns: [new ORM\JoinColumn(name: 'Pack_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'sejours_id', referencedColumnName: 'id')]
    )]
    #[Assert\Count(min: 1, minMessage: 'Veuillez sélectionner au moins un séjour.')]
    private Collection $sejours;

    #[ORM\ManyToMany(targetEntity: Voyage::class, inversedBy: 'packs')]
    #[ORM\JoinTable(
        name: 'packs_voyages',
        joinColumns: [new ORM\JoinColumn(name: 'Pack_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'voyages_id', referencedColumnName: 'id')]
    )]
    #[Assert\Count(min: 1, minMessage: 'Veuillez sélectionner au moins un voyage.')]
    private Collection $voyages;


   
    #[ORM\ManyToMany(targetEntity: Evenement::class, inversedBy: 'packs')]
    #[ORM\JoinTable(
    name: 'packs_evenements',
    joinColumns: [new ORM\JoinColumn(name: 'pack_id', referencedColumnName: 'id')],
    inverseJoinColumns: [new ORM\JoinColumn(name: 'evenement_id', referencedColumnName: 'id')]
    )]
    #[Assert\Count(min: 0, minMessage: 'Vous pouvez sélectionner des événements')]
    private Collection $evenements;


    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->sejours = new ArrayCollection();
        $this->voyages = new ArrayCollection();
        $this->evenements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        $this->reservations->removeElement($reservation);
        return $this;
    }

    public function getSejours(): Collection
    {
        return $this->sejours;
    }

    public function addSejour(Sejour $sejour): self
    {
        if (!$this->sejours->contains($sejour)) {
            $this->sejours->add($sejour);
        }
        return $this;
    }

    public function removeSejour(Sejour $sejour): self
    {
        $this->sejours->removeElement($sejour);
        return $this;
    }

    public function getVoyages(): Collection
    {
        return $this->voyages;
    }

    public function addVoyage(Voyage $voyage): self
    {
        if (!$this->voyages->contains($voyage)) {
            $this->voyages->add($voyage);
        }
        return $this;
    }

    public function removeVoyage(Voyage $voyage): self
    {
        $this->voyages->removeElement($voyage);
        return $this;
    }
    
// Ajoutez les getters et setters
public function getEvenements(): Collection
{
    return $this->evenements;
}

public function addEvenement(Evenement $evenement): self
{
    if (!$this->evenements->contains($evenement)) {
        $this->evenements->add($evenement);
    }
    return $this;
}

public function removeEvenement(Evenement $evenement): self
{
    $this->evenements->removeElement($evenement);
    return $this;
}
}
