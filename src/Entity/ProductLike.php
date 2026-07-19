<?php

namespace App\Entity;

use App\Repository\ProductLikeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductLikeRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_product_like_visitor', columns: ['product_id', 'visitor_key'])]
#[ORM\Index(name: 'idx_product_like_product', columns: ['product_id'])]
class ProductLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    /** Clé stable : user:{id} ou sess:{sessionId} */
    #[ORM\Column(length: 64)]
    private string $visitorKey = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getVisitorKey(): string
    {
        return $this->visitorKey;
    }

    public function setVisitorKey(string $visitorKey): static
    {
        $this->visitorKey = $visitorKey;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
