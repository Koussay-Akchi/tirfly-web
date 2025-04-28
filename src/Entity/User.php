<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'role', type: 'string')]
#[ORM\DiscriminatorMap([
    'VOYAGEUR' => User::class,
    'CLIENT'   => Client::class,
    'SUPPORT'  => User::class,
    'ADMIN'=> User::class
])]
class User implements UserInterface, PasswordAuthenticatedUserInterface,EquatableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    // the image of face id auth
    #[ORM\Column(type: 'json', nullable: true,name:"faceDescriptor")]
    private ?array $faceDescriptor = null;

    #[ORM\Column(type: 'datetime', nullable: true, name: "dateCreation")]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: 'string', nullable: true)]
    //assert of mail
    #[Assert\NotBlank(message: "Email is required")]
    #[Assert\Email(message: "The email '{{ value }}' is not a valid email.")]
    private ?string $email = null;

    #[ORM\Column(type: 'string', nullable: false, name: "MotDePasse")]
    private ?string $motDePasse = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: "Nom is required")]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'Nom must be at least {{ limit }} characters long',
        maxMessage: 'Nom cannot be longer than {{ limit }} characters',
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z\s]+$/',
        message: 'Nom can only contain letters and spaces.',
    )]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: "Prenom is required")]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'Prenom must be at least {{ limit }} characters long',
        maxMessage: 'Prenom cannot be longer than {{ limit }} characters',
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z\s]+$/',
        message: 'Prenom can only contain letters and spaces.',
    )]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true, name : 'resetToken')]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true , name : 'resetTokenExpiresAt')]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    // Collections (messages, notifications, clients, etc.)
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'user')]
    private Collection $messages;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user')]
    private Collection $notifications;

    #[ORM\ManyToMany(targetEntity: Client::class, inversedBy: 'users')]
    #[ORM\JoinTable(
        name: 'chats',
        joinColumns: [new ORM\JoinColumn(name: 'support_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id')]
    )]
    private Collection $clients;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->clients = new ArrayCollection();
    }
    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof User) {
            return false;
        }
        // Compare only the properties that should be consistent for authentication
        return $this->getUserIdentifier() === $user->getUserIdentifier()
            && $this->getPassword() === $user->getPassword();
    }
    // Getters and setters for id, dateCreation, email, motDePasse, nom, prenom

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getFaceDescriptor(): ?array
    {
        return $this->faceDescriptor;
    }
    public function setFaceDescriptor(?array $faceDescriptor): self
    {
        $this->faceDescriptor = $faceDescriptor;
        return $this;
    }
    

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(?string $motDePasse): self
    {
        $this->motDePasse = $motDePasse;
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    // Collection getters for messages, notifications, and clients

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
        }
        return $this;
    }

    public function removeMessage(Message $message): self
    {
        $this->messages->removeElement($message);
        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): self
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
        }
        return $this;
    }

    public function removeNotification(Notification $notification): self
    {
        $this->notifications->removeElement($notification);
        return $this;
    }

    /**
     * @return Collection<int, Client>
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addClient(Client $client): self
    {
        if (!$this->clients->contains($client)) {
            $this->clients->add($client);
        }
        return $this;
    }

    public function removeClient(Client $client): self
    {
        $this->clients->removeElement($client);
        return $this;
    }
    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeInterface $resetTokenExpiresAt): self
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;
        return $this;
    }

    // Do not try to access a non-existent $role property. Instead, derive the role from the actual class.
    // This method is used by the security component.
    public function getRoles(): array
    {
        // For example, if this is a Client, return ROLE_CLIENT; otherwise, a default.
        if ($this instanceof \App\Entity\Client) {
            return ['ROLE_CLIENT'];
        }
        // You can add other instanceof checks here for different subclasses.
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->motDePasse;
    }
}
