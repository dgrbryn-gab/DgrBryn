<?php

namespace App\Entity;

use App\Repository\WineInventoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WineInventoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class WineInventory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $quantity = 0;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $acquiredDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTime $lastUpdated;

    #[ORM\ManyToOne(inversedBy: 'wineInventories')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] // ✅ Added onDelete cascade
    private ?StoreProduct $product = null;

    public function __construct()
    {
        $this->lastUpdated = new \DateTime();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateLastUpdated(): void
    {
        $this->lastUpdated = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getAcquiredDate(): ?\DateTime
    {
        return $this->acquiredDate;
    }

    public function setAcquiredDate(?\DateTime $acquiredDate): static
    {
        $this->acquiredDate = $acquiredDate;
        return $this;
    }

    public function getLastUpdated(): \DateTime
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTime $lastUpdated): static
    {
        $this->lastUpdated = $lastUpdated;
        return $this;
    }

    public function getProduct(): ?StoreProduct
    {
        return $this->product;
    }

    public function setProduct(?StoreProduct $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function __toString(): string
    {
        $productName = $this->product ? $this->product->getName() : 'Unknown Product';
        return sprintf('%s (Qty: %d)', $productName, $this->quantity);
    }
}
