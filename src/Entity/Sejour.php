<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\SejourRepository;

#[ORM\Entity(repositoryClass: SejourRepository::class)]
#[ORM\Table(name: 'sejours')]
class Sejour
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
    private ?int $jours = null;

    public function getJours(): ?int
    {
        return $this->jours;
    }

    public function setJours(?int $jours): self
    {
        $this->jours = $jours;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Hebergement::class, inversedBy: 'sejours')]
    #[ORM\JoinColumn(name: 'hebergement_id', referencedColumnName: 'id')]
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

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'sejour')]
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

    #[ORM\ManyToMany(targetEntity: Pack::class, inversedBy: 'sejours')]
    #[ORM\JoinTable(
        name: 'packs_sejours',
        joinColumns: [
            new ORM\JoinColumn(name: 'sejours_id', referencedColumnName: 'id')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'Pack_id', referencedColumnName: 'id')
        ]
    )]
    private Collection $packs;

    public function __construct()
    {
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
