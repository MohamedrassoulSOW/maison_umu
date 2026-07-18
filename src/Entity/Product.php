<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\AddProductHistory;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 191, unique: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $price = null;

    /**
     * @var Collection<int, SubCategory>
     */
    #[ORM\ManyToMany(targetEntity: SubCategory::class, inversedBy: 'products')]
    private Collection $subcategories;

    #[ORM\Column(length: 191, nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    private ?int $stock = null;

    /**
     * @var Collection<int, OrderProducts>
     */
    #[ORM\OneToMany(targetEntity: OrderProducts::class, mappedBy: 'product')]
    private Collection $orderProducts;

    /**
     * @var Collection<int, AddProductHistory>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: AddProductHistory::class)]
    private Collection $addProductHistories;

    public function __construct()
    {
        $this->subcategories = new ArrayCollection();
        $this->orderProducts = new ArrayCollection();
        $this->addProductHistories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getSubcategories(): Collection
    {
        return $this->subcategories;
    }

    public function addSubcategory(SubCategory $subcategory): static
    {
        if (!$this->subcategories->contains($subcategory)) {
            $this->subcategories->add($subcategory);
        }
        return $this;
    }

    public function removeSubcategory(SubCategory $subcategory): static
    {
        $this->subcategories->removeElement($subcategory);
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;
        return $this;
    }

    public function getOrderProducts(): Collection
    {
        return $this->orderProducts;
    }

    public function addOrderProduct(OrderProducts $orderProduct): static
    {
        if (!$this->orderProducts->contains($orderProduct)) {
            $this->orderProducts->add($orderProduct);
            $orderProduct->setProduct($this);
        }
        return $this;
    }

    public function removeOrderProduct(OrderProducts $orderProduct): static
    {
        if ($this->orderProducts->removeElement($orderProduct)) {
            if ($orderProduct->getProduct() === $this) {
                $orderProduct->setProduct(null);
            }
        }
        return $this;
    }

    public function getAddProductHistories(): Collection
    {
        return $this->addProductHistories;
    }

    public function addAddProductHistory(AddProductHistory $history): static
    {
        if (!$this->addProductHistories->contains($history)) {
            $this->addProductHistories->add($history);
            $history->setProduct($this);
        }
        return $this;
    }

    public function removeAddProductHistory(AddProductHistory $history): static
    {
        if ($this->addProductHistories->removeElement($history)) {
            if ($history->getProduct() === $this) {
                $history->setProduct(null);
            }
        }
        return $this;
    }
}
