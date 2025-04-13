<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\FeedbackRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FeedbackRepository::class)]
#[ORM\Table(name: 'feedbacks')]
class Feedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: "Le contenu ne doit pas être vide.")]
    #[Assert\Length(
        min: 5,
        max: 1000,
        minMessage: "Le contenu doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le contenu ne doit pas dépasser {{ limit }} caractères."
    )]
    private ?string $contenu = null;

    #[ORM\Column(type: 'datetime', nullable: true, name: 'dateFeedback')]
    private ?\DateTimeInterface $dateFeedback = null;

    #[ORM\Column(type: 'decimal', precision: 2)]
    #[Assert\NotNull(message: "La note est obligatoire.")]
    #[Assert\Range(
        notInRangeMessage: "La note doit être comprise entre {{ min }} et {{ max }}.",
        min: 0,
        max: 10
    )]
    private ?float $note = null;

    #[ORM\ManyToOne(targetEntity: Voyage::class)]
    private ?Voyage $voyage = null;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    private ?Client $client = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): self
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getDateFeedback(): ?\DateTimeInterface
    {
        return $this->dateFeedback;
    }

    public function setDateFeedback(?\DateTimeInterface $dateFeedback): self
    {
        $this->dateFeedback = $dateFeedback;
        return $this;
    }

    



    public function getNote(): ?float
    {
        return $this->note;
    }

    public function setNote(float $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function getVoyage(): ?Voyage
    {
        return $this->voyage;
    }

    public function setVoyage(?Voyage $voyage): self
    {
        $this->voyage = $voyage;
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
}
