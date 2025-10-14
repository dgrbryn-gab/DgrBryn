<?php

namespace App\Repository;

use App\Entity\StoreProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StoreProduct>
 *
 * @method StoreProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method StoreProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method StoreProduct[]    findAll()
 * @method StoreProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
     * Find all products with available inventory (stockQuantity > 0 and isAvailable = true).
     *
     * @return StoreProduct[]
     */
    public function findAllWithInventory(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.stockQuantity > :stock')
            ->andWhere('p.isAvailable = :available')
            ->setParameter('stock', 0)
            ->setParameter('available', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}