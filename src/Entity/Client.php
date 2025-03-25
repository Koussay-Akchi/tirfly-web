<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ClientRepository;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'clients')]
class Client
{
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $adresse = null;

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $age = null;

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): self
    {
        $this->age = $age;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $sexe = null;

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): self
    {
        $this->sexe = $sexe;
        return $this;
    }

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
    private ?string $phoneNumber = null;

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Feedback::class, mappedBy: 'client')]
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

    #[ORM\OneToOne(targetEntity: Niveau::class, mappedBy: 'client')]
    private ?Niveau $niveau = null;

    public function getNiveau(): ?Niveau
    {
        return $this->niveau;
    }

    public function setNiveau(?Niveau $niveau): self
    {
        $this->niveau = $niveau;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Panier::class, mappedBy: 'client')]
    private Collection $paniers;

    /**
     * @return Collection<int, Panier>
     */
    public function getPaniers(): Collection
    {
        if (!$this->paniers instanceof Collection) {
            $this->paniers = new ArrayCollection();
        }
        return $this->paniers;
    }

    public function addPanier(Panier $panier): self
    {
        if (!$this->getPaniers()->contains($panier)) {
            $this->getPaniers()->add($panier);
        }
        return $this;
    }

    public function removePanier(Panier $panier): self
    {
        $this->getPaniers()->removeElement($panier);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'client')]
    private Collection $payments;

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        if (!$this->payments instanceof Collection) {
            $this->payments = new ArrayCollection();
        }
        return $this->payments;
    }

    public function addPayment(Payment $payment): self
    {
        if (!$this->getPayments()->contains($payment)) {
            $this->getPayments()->add($payment);
        }
        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        $this->getPayments()->removeElement($payment);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Reclamation::class, mappedBy: 'client')]
    private Collection $reclamations;

    /**
     * @return Collection<int, Reclamation>
     */
    public function getReclamations(): Collection
    {
        if (!$this->reclamations instanceof Collection) {
            $this->reclamations = new ArrayCollection();
        }
        return $this->reclamations;
    }

    public function addReclamation(Reclamation $reclamation): self
    {
        if (!$this->getReclamations()->contains($reclamation)) {
            $this->getReclamations()->add($reclamation);
        }
        return $this;
    }

    public function removeReclamation(Reclamation $reclamation): self
    {
        $this->getReclamations()->removeElement($reclamation);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Refund::class, mappedBy: 'client')]
    private Collection $refunds;

    /**
     * @return Collection<int, Refund>
     */
    public function getRefunds(): Collection
    {
        if (!$this->refunds instanceof Collection) {
            $this->refunds = new ArrayCollection();
        }
        return $this->refunds;
    }

    public function addRefund(Refund $refund): self
    {
        if (!$this->getRefunds()->contains($refund)) {
            $this->getRefunds()->add($refund);
        }
        return $this;
    }

    public function removeRefund(Refund $refund): self
    {
        $this->getRefunds()->removeElement($refund);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'client')]
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

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'clients')]
    #[ORM\JoinTable(
        name: 'chats',
        joinColumns: [
            new ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'support_id', referencedColumnName: 'id')
        ]
    )]
    private Collection $users;

    public function __construct()
    {
        $this->feedbacks = new ArrayCollection();
        $this->paniers = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->reclamations = new ArrayCollection();
        $this->refunds = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        if (!$this->users instanceof Collection) {
            $this->users = new ArrayCollection();
        }
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->getUsers()->contains($user)) {
            $this->getUsers()->add($user);
        }
        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->getUsers()->removeElement($user);
        return $this;
    }

}
