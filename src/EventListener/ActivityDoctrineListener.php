<?php

namespace App\EventListener;

use App\Service\ActivityLogger;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use App\Entity\Order;
use App\Entity\StoreProduct;
use App\Entity\WineInventory;
use App\Entity\Category;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
class ActivityDoctrineListener
{
    public function __construct(
        private ActivityLogger $activityLogger,
        private Security $security
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $user = $this->security->getUser();

        if (!$user) {
            return;
        }

        if ($entity instanceof Order) {
            $this->activityLogger->logActivity(
                'ORDER_CREATED',
                json_encode(['orderId' => $entity->getId(), 'orderNumber' => $entity->getOrderNumber()])
            );
        } elseif ($entity instanceof StoreProduct) {
            $this->activityLogger->logActivity(
                'PRODUCT_CREATED',
                json_encode(['productId' => $entity->getId(), 'productName' => $entity->getName()])
            );
        } elseif ($entity instanceof WineInventory) {
            $this->activityLogger->logActivity(
                'INVENTORY_CREATED',
                json_encode(['inventoryId' => $entity->getId()])
            );
        } elseif ($entity instanceof Category) {
            $this->activityLogger->logActivity(
                'CATEGORY_CREATED',
                json_encode(['categoryId' => $entity->getId(), 'categoryName' => $entity->getName()])
            );
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $user = $this->security->getUser();

        if (!$user) {
            return;
        }

        $unitOfWork = $args->getObjectManager()->getUnitOfWork();
        $changes = $unitOfWork->getEntityChangeSet($entity);

        if ($entity instanceof Order) {
            $this->activityLogger->logActivity(
                'ORDER_UPDATED',
                json_encode([
                    'orderId' => $entity->getId(),
                    'changes' => array_keys($changes)
                ])
            );
        } elseif ($entity instanceof StoreProduct) {
            $this->activityLogger->logActivity(
                'PRODUCT_UPDATED',
                json_encode([
                    'productId' => $entity->getId(),
                    'changes' => array_keys($changes)
                ])
            );
        } elseif ($entity instanceof WineInventory) {
            $this->activityLogger->logActivity(
                'INVENTORY_UPDATED',
                json_encode([
                    'inventoryId' => $entity->getId(),
                    'changes' => array_keys($changes)
                ])
            );
        } elseif ($entity instanceof Category) {
            $this->activityLogger->logActivity(
                'CATEGORY_UPDATED',
                json_encode([
                    'categoryId' => $entity->getId(),
                    'changes' => array_keys($changes)
                ])
            );
        }
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        $user = $this->security->getUser();

        if (!$user) {
            return;
        }

        if ($entity instanceof Order) {
            $this->activityLogger->logActivity(
                'ORDER_DELETED',
                json_encode(['orderId' => $entity->getId()])
            );
        } elseif ($entity instanceof StoreProduct) {
            $this->activityLogger->logActivity(
                'PRODUCT_DELETED',
                json_encode(['productId' => $entity->getId()])
            );
        } elseif ($entity instanceof WineInventory) {
            $this->activityLogger->logActivity(
                'INVENTORY_DELETED',
                json_encode(['inventoryId' => $entity->getId()])
            );
        } elseif ($entity instanceof Category) {
            $this->activityLogger->logActivity(
                'CATEGORY_DELETED',
                json_encode(['categoryId' => $entity->getId()])
            );
        }
    }
}
