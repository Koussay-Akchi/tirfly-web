<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\VoyageRepository;

#[ORM\Entity(repositoryClass: VoyageRepository::class)]
#[ORM\Table(name: 'voyages')]
class Voyage
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

    #[ORM\Column(type: 'date', nullable: true, name: 'dateArrive')]
    private ?\DateTimeInterface $dateArrive = null;

    public function getDateArrive(): ?\DateTimeInterface
    {
        return $this->dateArrive;
    }

    public function setDateArrive(?\DateTimeInterface $dateArrive): self
    {
        $this->dateArrive = $dateArrive;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true, name: 'dateDepart')]
    private ?\DateTimeInterface $dateDepart = null;

    public function getDateDepart(): ?\DateTimeInterface
    {
        return $this->dateDepart;
    }

    public function setDateDepart(?\DateTimeInterface $dateDepart): self
    {
        $this->dateDepart = $dateDepart;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true, name: 'description')]
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

    #[ORM\Column(type: 'blob', nullable: true, name: 'image')]
    private $image = null;

    /*
    public function getImage(): ?string
    {
        if ($this->image === null) {
            return null;
        }

        if (is_resource($this->image)) {
            $imageData = stream_get_contents($this->image);
            return base64_encode($imageData);
        }

        return base64_encode($this->image);
    }

    public function getImageBase64(): ?string {
        if ($this->image === null) {
            return null;
        }

        return 'data:image/jpeg;base64,' . base64_encode(stream_get_contents($this->image));
    }

    public function getImageUrl(): ?string
{
    if ($this->image === null) {
        return null;
    }
    $uploadDir = 'uploads/voyages/';
    $fileName = uniqid('voyage_', true) . '.jpg';
    $filePath = $uploadDir . $fileName;

    // Write the BLOB data to the file system
    $imageData = stream_get_contents($this->image);
    $result = file_put_contents($filePath, $imageData);

        if ($result === false) {
            throw new \RuntimeException('Failed to save the image.');
        }
        return 'uploads/voyages/' . $fileName;
}

    */

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true, name: 'nom')]
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

    #[ORM\Column(type: 'decimal', nullable: false, name: 'prix')]
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

    #[ORM\Column(type: 'string', nullable: true, name: 'formule')]
    private ?string $formule = null;

    public function getFormule(): ?string
    {
        return $this->formule;
    }

    public function setFormule(?string $formule): self
    {
        $this->formule = $formule;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: false, name: 'note')]
    private ?float $note = null;

    public function getNote(): ?float
    {
        return $this->note;
    }

    public function setNote(float $note): self
    {
        $this->note = $note;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Destination::class, inversedBy: 'voyages')]
    #[ORM\JoinColumn(name: 'destination_id', referencedColumnName: 'id')]
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

    #[ORM\OneToMany(targetEntity: Feedback::class, mappedBy: 'voyage')]
    private Collection $feedbacks;

    /**
     * @return Collection<int, Feedback>
     */
    public function getFeedbacks(): Collection
    {
        if (!$this->feedbacks instanceof Collection) {
            $this->feedbacks = new ArrayCollection();
        }
        return $this->feedbacks;
    }

    public function addFeedback(Feedback $feedback): self
    {
        if (!$this->getFeedbacks()->contains($feedback)) {
            $this->getFeedbacks()->add($feedback);
        }
        return $this;
    }

    public function removeFeedback(Feedback $feedback): self
    {
        $this->getFeedbacks()->removeElement($feedback);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'voyage')]
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

    #[ORM\ManyToMany(targetEntity: Pack::class, inversedBy: 'voyages')]
    #[ORM\JoinTable(
        name: 'packs_voyages',
        joinColumns: [
            new ORM\JoinColumn(name: 'voyages_id', referencedColumnName: 'id')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'Pack_id', referencedColumnName: 'id')
        ]
    )]
    private Collection $packs;

    public function __construct()
    {
        $this->feedbacks = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->packs = new ArrayCollection();
    }

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
}
