<?php

namespace App\Repository;

use App\Entity\StoreProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StoreProduct>
 */
class StoreProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreProduct::class);
    }

    public function save(StoreProduct $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(StoreProduct $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Placeholder method — returns all products for now.
     * Later, when an Inventory entity exists, this can be updated to join that table.
     */
    public function findAllWithInventory()
    {
        return $this->findAll();
    }

    /**
     * Temporary replacement for findByCategoryWithInventory
     * Filters products by category only (no inventory logic yet)
     */
    public function findByCategoryWithInventory($categoryId)
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.category = :cat')
            ->setParameter('cat', $categoryId)
            ->getQuery()
            ->getResult();
    }
}
