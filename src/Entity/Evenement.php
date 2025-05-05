<?php

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as CustomAssert;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
#[ORM\Table(name: 'evenements')]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'date', nullable: false, name: 'dateDebut')]
    #[Assert\NotBlank(message: "La date de début est obligatoire")]
    #[Assert\Type("\DateTimeInterface", message: "La date de début doit être une date valide")]
    #[CustomAssert\DateMinimumOffset(minDays: 15)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'date', nullable: false, name: 'dateFin')]
    #[Assert\NotBlank(message: "La date de fin est obligatoire")]
    #[Assert\Type("\DateTimeInterface", message: "La date de fin doit être une date valide")]
    #[Assert\Expression(
        "this.getDateDebut() === null || this.getDateFin() === null || this.getDateFin() >= this.getDateDebut()",
        message: "La date de fin doit être postérieure ou égale à la date de début"
    )]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Assert\Length(
        min: 10,
        max: 2000,
        minMessage: "La description doit faire au moins {{ limit }} caractères",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: false)]
    #[Assert\NotBlank(message: "Le prix est obligatoire")]
    #[Assert\Type(type: 'numeric', message: "Le prix doit être un nombre")]
    #[Assert\Positive(message: "Le prix doit être positif")]
    private ?float $prix = null;

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
        min: 5,
        max: 100,
        minMessage: "Le titre doit faire au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $titre = null;

    #[ORM\ManyToOne(targetEntity: Destination::class, inversedBy: 'evenements')]
    #[ORM\JoinColumn(name: 'location_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotBlank(message: "La destination est obligatoire")]
    private ?Destination $destination = null;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    
    private ?string $image = null;
    #[ORM\ManyToMany(targetEntity: Pack::class, mappedBy: 'evenements')]
    private Collection $packs;
    // Dans le constructeur :
public function __construct()
{
    $this->packs = new ArrayCollection();
}

// Ajoutez les getters et setters
public function getPacks(): Collection
{
    return $this->packs;
}

public function addPack(Pack $pack): self
{
    if (!$this->packs->contains($pack)) {
        $this->packs->add($pack);
        $pack->addEvenement($this);
    }
    return $this;
}

public function removePack(Pack $pack): self
{
    if ($this->packs->removeElement($pack)) {
        $pack->removeEvenement($this);
    }
    return $this;
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

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;
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

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDestination(): ?Destination
    {
        return $this->destination;
    }

    public function setDestination(?Destination $destination): self
    {
        $this->destination = $destination;
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
}
