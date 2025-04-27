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
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $etat = null;
    
    #[ORM\Column(type: 'string', nullable: true, name: "titre")]
    #[Assert\NotBlank(message: 'Title cannot be blank.')]
    #[Assert\Length(max: 100, maxMessage: 'Title cannot be longer than 100 characters.')]
    private ?string $titre = null;

    #[ORM\Column(type: 'date', nullable: true, name: "dateCreation")]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'reclamations')]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id')]
    private ?Client $client = null;

    #[ORM\Column(type: 'string', nullable: false, name: "videoPath")]
    #[Assert\Regex(
        pattern: "/\.(mp4|avi|mpeg|mov)$/i",
        message: "Le fichier doit être une vidéo avec une extension valide (mp4, avi, mpeg, mov)."
    )]
    private ?string $videoPath = null;

    #[ORM\Column(type: 'string', nullable: false, name: "isRed")]
    private ?string $isRed = '0';

    // =======================
    // ==== GETTERS/SETTERS ==
    // =======================

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
}
