<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\CouponRepository;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
#[ORM\Table(name: 'coupons')]
class Coupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    #[Assert\NotBlank(message: "Le code est obligatoire")]
    #[Assert\Length(
        min: 5,
        max: 20,
        minMessage: "Le code doit faire au moins 5 caractères",
        maxMessage: "Le code ne peut pas dépasser 20 caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z0-9]+$/",
        message: "Seules les lettres majuscules et minuscules et chiffres sont autorisés"
    )]
    private ?string $code = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Assert\NotBlank(message: "La remise est obligatoire")]
    #[Assert\Type(type: 'numeric', message: "La remise doit être un nombre")]
    #[Assert\Range(
        min: 1,
        max: 100,
        notInRangeMessage: "La remise doit être entre 1% et 100%"
    )]
    private ?float $remise = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getRemise(): ?float
    {
        return $this->remise;
    }

    public function setRemise(float $remise): self
    {
        $this->remise = $remise;
        return $this;
    }
}
