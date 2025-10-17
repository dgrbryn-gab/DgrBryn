<?php

namespace App\Entity;

use App\Repository\StoreProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Category;

#[ORM\Entity(repositoryClass: StoreProductRepository::class)]
class StoreProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?bool $isAvailable = null;

    #[ORM\ManyToOne(inversedBy: 'storeProducts')]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?Category $category = null;

    /**
     * @var Collection<int, WineInventory>
     */
    #[ORM\OneToMany(targetEntity: WineInventory::class, mappedBy: 'product')]
    private Collection $wineInventories;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->wineInventories = new ArrayCollection();
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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function isAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): static
    {
        $this->isAvailable = $isAvailable;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return Collection<int, WineInventory>
     */
    public function getWineInventories(): Collection
    {
        return $this->wineInventories;
    }

    public function addWineInventory(WineInventory $wineInventory): static
    {
        if (!$this->wineInventories->contains($wineInventory)) {
            $this->wineInventories->add($wineInventory);
            $wineInventory->setProduct($this);
        }

        return $this;
    }

    public function removeWineInventory(WineInventory $wineInventory): static
    {
        if ($this->wineInventories->removeElement($wineInventory)) {
            // set the owning side to null (unless already changed)
            if ($wineInventory->getProduct() === $this) {
                $wineInventory->setProduct(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return (string) ($this->name ?? 'Product #'.$this->id);
    }
}   