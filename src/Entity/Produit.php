<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\Table(name: 'produits')]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $categorie = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean',name:'ecoFriendly')]
    private bool $ecoFriendly = false;

    #[ORM\Column(type: 'blob', nullable: true)]
    private $image = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2,name:'prixUnitaire')]
    private ?string $prixUnitaire = null;

    #[ORM\Column(type: 'integer',name:'quantiteStock')]
    private ?int $quantiteStock = null;

    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'produit')]
    private Collection $articles;

    #[ORM\ManyToMany(targetEntity: Panier::class, inversedBy: 'produits')]
    #[ORM\JoinTable(
        name: 'panier_produits',
        joinColumns: [
            new ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'panier_id', referencedColumnName: 'id')
        ]
    )]
    private Collection $paniers;
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'favorites')]
    private Collection $favoritedBy;
    public function __construct()
    {
        $this->articles = new ArrayCollection();
        $this->paniers = new ArrayCollection();
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

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): self
    {
        $this->categorie = $categorie;
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

    public function isEcoFriendly(): bool
    {
        return $this->ecoFriendly;
    }

    public function setEcoFriendly(bool $ecoFriendly): self
    {
        $this->ecoFriendly = $ecoFriendly;
        return $this;
    }

    public function getImage(): mixed
    {
        return $this->image;
    }

    public function setImage(mixed $image): self
    {
        $this->image = $image;
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

    public function getPrixUnitaire(): ?string
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(string $prixUnitaire): self
    {
        $this->prixUnitaire = $prixUnitaire;
        return $this;
    }

    public function getQuantiteStock(): ?int
    {
        return $this->quantiteStock;
    }

    public function setQuantiteStock(int $quantiteStock): self
    {
        $this->quantiteStock = $quantiteStock;
        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): self
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
        }
        return $this;
    }

    public function removeArticle(Article $article): self
    {
        $this->articles->removeElement($article);
        return $this;
    }

    /**
     * @return Collection<int, Panier>
     */
    public function getPaniers(): Collection
    {
        return $this->paniers;
    }

    public function addPanier(Panier $panier): self
    {
        if (!$this->paniers->contains($panier)) {
            $this->paniers->add($panier);
        }
        return $this;
    }

    public function removePanier(Panier $panier): self
    {
        $this->paniers->removeElement($panier);
        return $this;
    }
}