<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ReservationRepository;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservations')]
class Reservation
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

    #[ORM\Column(type: 'datetime', nullable: true, name:'dateReservation')]
    private ?\DateTimeInterface $dateReservation = null;

    public function getDateReservation(): ?\DateTimeInterface
    {
        return $this->dateReservation;
    }

    public function setDateReservation(?\DateTimeInterface $dateReservation): self
    {
        $this->dateReservation = $dateReservation;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false, name:'nombrePersonnes')]
    private ?int $nombrePersonnes = null;

    public function getNombrePersonnes(): ?int
    {
        return $this->nombrePersonnes;
    }

    public function setNombrePersonnes(int $nombrePersonnes): self
    {
        $this->nombrePersonnes = $nombrePersonnes;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false, name:'prixTotal')]
    private ?float $prixTotal = null;

    public function getPrixTotal(): ?float
    {
        return $this->prixTotal;
    }

    public function setPrixTotal(float $prixTotal): self
    {
        $this->prixTotal = $prixTotal;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Voyage::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'voyage_id', referencedColumnName: 'id')]
    private ?Voyage $voyage = null;

    public function getVoyage(): ?Voyage
    {
        return $this->voyage;
    }

    public function setVoyage(?Voyage $voyage): self
    {
        $this->voyage = $voyage;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id')]
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

    #[ORM\Column(type: 'string', nullable: true, name:'paymentToken')]
    private ?string $paymentToken = null;

    public function getPaymentToken(): ?string
    {
        return $this->paymentToken;
    }

    public function setPaymentToken(?string $paymentToken): self
    {
        $this->paymentToken = $paymentToken;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Sejour::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'sejour_id', referencedColumnName: 'id')]
    private ?Sejour $sejour = null;

    public function getSejour(): ?Sejour
    {
        return $this->sejour;
    }

    public function setSejour(?Sejour $sejour): self
    {
        $this->sejour = $sejour;
        return $this;
    }



    #[ORM\OneToOne(targetEntity: Payment::class, inversedBy: 'reservation')]
    #[ORM\JoinColumn(name: 'payments_id', referencedColumnName: 'id', unique: true)]
    private ?Payment $payment = null;

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): self
    {
        $this->payment = $payment;
        return $this;
    }

}
