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

    #[ORM\Column(type: 'date', nullable: true, name: 'dateArrive')]
    private ?\DateTimeInterface $dateArrive = null;

    #[ORM\Column(type: 'date', nullable: true, name: 'dateDepart')]
    private ?\DateTimeInterface $dateDepart = null;

    #[ORM\Column(type: 'string', nullable: true, name: 'description')]
       private ?string $description = null;


    #[ORM\Column(type: 'blob', nullable: true, name: 'image')]
    private $image = null;

    #[ORM\Column(type: 'string', nullable: true, name: 'nom')]
    private ?string $nom = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: false, name: 'prix')]
    private ?float $prix = null;

    #[ORM\Column(type: 'string', nullable: true, name: 'formule')]
    private ?string $formule = null;

    #[ORM\Column(type: 'float', nullable: false, name: 'note')]
    private float $note = 0.0;

    #[ORM\ManyToOne(targetEntity: Destination::class, inversedBy: 'voyages')]
    #[ORM\JoinColumn(name: 'destination_id', referencedColumnName: 'id')]
    private ?Destination $destination = null;

    #[ORM\OneToMany(targetEntity: Feedback::class, mappedBy: 'voyage')]
    private Collection $feedbacks;

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'voyage')]
    private Collection $reservations;

    #[ORM\ManyToMany(targetEntity: Pack::class, inversedBy: 'voyages')]
    #[ORM\JoinTable(
        name: 'packs_voyages',
        joinColumns: [new ORM\JoinColumn(name: 'voyages_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'Pack_id', referencedColumnName: 'id')]
    )]
    private Collection $packs;

    public function __construct()
    {
        $this->feedbacks = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->packs = new ArrayCollection();
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

    public function getDateArrive(): ?\DateTimeInterface
    {
        return $this->dateArrive;
    }

    public function setDateArrive(?\DateTimeInterface $dateArrive): self
    {
        $this->dateArrive = $dateArrive;
        return $this;
    }

    public function getDateDepart(): ?\DateTimeInterface
    {
        return $this->dateDepart;
    }

    public function setDateDepart(?\DateTimeInterface $dateDepart): self
    {
        $this->dateDepart = $dateDepart;
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

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image): self
    {
        if (is_string($image) && str_contains($image, 'base64')) {
            $image = base64_decode(explode(',', $image)[1]);
        }
        $this->image = $image;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        if (!$this->image) {
            return null;
        }

        if (is_resource($this->image)) {
            rewind($this->image);
            $content = stream_get_contents($this->image);
            return 'data:image/jpeg;base64,'.base64_encode($content);
        }

        return 'data:image/jpeg;base64,'.base64_encode($this->image);
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

    public function getFormule(): ?string
    {
        return $this->formule;
    }

    public function setFormule(?string $formule): self
    {
        $this->formule = $formule;
        return $this;
    }

    public function getNote(): float
    {
        return $this->note;
    }

    public function setNote(float $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function updateNote(): self
    {
        $total = 0;
        $count = $this->feedbacks->count();

        if ($count > 0) {
            foreach ($this->feedbacks as $feedback) {
                $total += $feedback->getNote();
            }
            $this->note = round($total / $count, 1);
        }

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

    /**
     * @return Collection<int, Feedback>
     */
    public function getFeedbacks(): Collection
    {
        return $this->feedbacks;
    }

    public function addFeedback(Feedback $feedback): self
    {
        if (!$this->feedbacks->contains($feedback)) {
            $this->feedbacks->add($feedback);
            $feedback->setVoyage($this);
        }
        return $this;
    }

    public function removeFeedback(Feedback $feedback): self
    {
        if ($this->feedbacks->removeElement($feedback)) {
            if ($feedback->getVoyage() === $this) {
                $feedback->setVoyage(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setVoyage($this);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getVoyage() === $this) {
                $reservation->setVoyage(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Pack>
     */
    public function getPacks(): Collection
    {
        return $this->packs;
    }

    public function addPack(Pack $pack): self
    {
        if (!$this->packs->contains($pack)) {
            $this->packs->add($pack);
            $pack->addVoyage($this);
        }
        return $this;
    }

    public function removePack(Pack $pack): self
    {
        if ($this->packs->removeElement($pack)) {
            $pack->removeVoyage($this);
        }
        return $this;
    }
}