<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\ReclamationRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReclamationRepository::class)]
#[ORM\Table(name: 'reclamations')]
#[ORM\HasLifecycleCallbacks] // Add this to enable lifecycle callbacks
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'La description ne peut pas être vide.')]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $etat = null;

    #[ORM\Column(type: 'string', nullable: true, name: "titre")]
    #[Assert\NotBlank(message: 'Title cannot be blank.')]
    #[Assert\Length(max: 100, maxMessage: 'Title cannot be longer than 100 characters.')]
    private ?string $titre = null;

    #[ORM\Column(type: 'datetime', nullable: true, name: "dateCreation")]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'reclamations')]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id')]
    private ?Client $client = null;

    #[ORM\Column(type: 'string', nullable: true, name: "videoPath")] // Change to nullable: true since video is optional
    #[Assert\Regex(
        pattern: "/\.(mp4|avi|mpeg|mov)$/i",
        message: "Le fichier doit être une vidéo avec une extension valide (mp4, avi, mpeg, mov).",
        match: true
    )]
    private ?string $videoPath = null;

    #[ORM\Column(type: 'string', nullable: false, name: "isRed")]
    private ?string $isRed = '0';

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $urgence = null;

    #[ORM\OneToMany(mappedBy: 'reclamation', targetEntity: Reponse::class, orphanRemoval: true)]
    private Collection $reponses;

    public function __construct()
    {
        $this->reponses = new ArrayCollection();
    }

    #[ORM\PrePersist] // Automatically set dateCreation before persisting
    public function onPrePersist(): void
    {
        if ($this->dateCreation === null) {
            $this->dateCreation = new \DateTime();
        }
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

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(?string $etat): self
    {
        $this->etat = $etat;
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

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getVideoPath(): ?string
    {
        return $this->videoPath;
    }

    public function setVideoPath(?string $videoPath): self
    {
        $this->videoPath = $videoPath;
        return $this;
    }

    public function getIsRed(): ?string
    {
        return $this->isRed;
    }

    public function setIsRed(string $isRed): self
    {
        $this->isRed = $isRed;
        return $this;
    }

    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(Reponse $reponse): self
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->setReclamation($this);
        }
        return $this;
    }

    public function removeReponse(Reponse $reponse): self
    {
        if ($this->reponses->removeElement($reponse)) {
            if ($reponse->getReclamation() === $this) {
                $reponse->setReclamation(null);
            }
        }
        return $this;
    }

    public function getUrgence(): ?string
    {
        return $this->urgence;
    }

    public function setUrgence(?string $urgence): self
    {
        $this->urgence = $urgence;
        return $this;
    }
}