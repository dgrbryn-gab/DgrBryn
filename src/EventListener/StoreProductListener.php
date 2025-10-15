<?php

namespace App\EventListener;

use App\Entity\StoreProduct;
use App\Entity\WineInventory;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class StoreProductListener
{
    public function postPersist(LifecycleEventArgs $event): void
    {
        $entity = $event->getObject();
        
        // Check if the entity is a StoreProduct
        if (!$entity instanceof StoreProduct) {
            return;
        }

        $entityManager = $event->getObjectManager();
        
        // Check if a WineInventory record already exists for this product
        $existingInventory = $entityManager->getRepository(WineInventory::class)
            ->findOneBy(['product' => $entity]);
        
        if (!$existingInventory) {
            $inventory = new WineInventory();
            $inventory->setProduct($entity);
            $inventory->setQuantity(0); // Default quantity
            $inventory->setAcquiredDate(new \DateTime());
            // lastUpdated is set in the WineInventory constructor
            $entityManager->persist($inventory);
            $entityManager->flush();
        }
    }
}