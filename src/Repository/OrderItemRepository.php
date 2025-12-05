<?php

namespace App\Repository;

use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderItem>
 */
class OrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }

    /**
     * Find items for an order
     */
    public function findByOrder(int $orderId): array
    {
        return $this->createQueryBuilder('oi')
            ->where('oi.order = :orderId')
            ->setParameter('orderId', $orderId)
            ->orderBy('oi.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get most sold products
     */
    public function getMostSoldProducts(int $limit = 10): array
    {
        return $this->createQueryBuilder('oi')
            ->select('oi.product, SUM(oi.quantity) as total_quantity')
            ->groupBy('oi.product')
            ->orderBy('total_quantity', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculate total quantity sold for a product
     */
    public function getTotalQuantitySold(int $productId): int
    {
        return $this->createQueryBuilder('oi')
            ->select('SUM(oi.quantity)')
            ->where('oi.product = :productId')
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }
}
