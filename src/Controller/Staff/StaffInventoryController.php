<?php

namespace App\Controller\Staff;

use App\Entity\WineInventory;
use App\Entity\StoreProduct;
use App\Form\WineInventoryType;
use App\Repository\WineInventoryRepository;
use App\Repository\StoreProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/staff/inventory', name: 'staff_inventory_')]
class StaffInventoryController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        WineInventoryRepository $inventoryRepository,
        StoreProductRepository $productRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        
        $user = $this->getUser();
        $searchTerm = $request->query->get('search');
        $sortBy = $request->query->get('sort', 'lastUpdated');

        // Get ALL products (not just staff-created ones) with their inventory
        $queryBuilder = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.wineInventories', 'wi')
            ->addSelect('wi');

        // Apply search filter
        if ($searchTerm) {
            $queryBuilder->andWhere('LOWER(p.name) LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%');
        }

        // Apply sorting
        if ($sortBy === 'name') {
            $queryBuilder->orderBy('p.name', 'ASC');
        } elseif ($sortBy === 'quantity') {
            $queryBuilder->addOrderBy('wi.quantity', 'DESC');
        } else {
            $queryBuilder->addOrderBy('wi.lastUpdated', 'DESC');
        }

        $products = $queryBuilder->getQuery()->getResult();

        // Get inventory statistics
        $totalInventory = 0;
        $lowStockItems = 0;
        foreach ($products as $product) {
            foreach ($product->getWineInventories() as $inventory) {
                $totalInventory += $inventory->getQuantity();
                if ($inventory->getQuantity() < 10) {
                    $lowStockItems++;
                }
            }
        }

        // Calculate which inventories belong to the current user's products
        $ownProductIds = array_map(fn($p) => $p->getId(), array_filter($products, fn($p) => $p->getCreatedBy() === $user));

        return $this->render('staff/inventory/index.html.twig', [
            'products' => $products,
            'search_term' => $searchTerm,
            'sort_by' => $sortBy,
            'total_inventory' => $totalInventory,
            'low_stock_items' => $lowStockItems,
            'own_product_ids' => $ownProductIds,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        StoreProductRepository $productRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $user = $this->getUser();
        $wineInventory = new WineInventory();
        $form = $this->createForm(WineInventoryType::class, $wineInventory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Verify the product belongs to this staff member
            $product = $wineInventory->getProduct();
            if ($product && $product->getCreatedBy() !== $user) {
                $this->addFlash('error', '❌ You can only add inventory to your own products!');
                return $this->redirectToRoute('staff_inventory_index');
            }

            $wineInventory->setLastUpdated(new \DateTime());
            $entityManager->persist($wineInventory);
            $entityManager->flush();

            $this->addFlash('success', '✅ Inventory record added successfully!');
            return $this->redirectToRoute('staff_inventory_index');
        }

        return $this->render('staff/inventory/new.html.twig', [
            'wine_inventory' => $wineInventory,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        int $id,
        WineInventoryRepository $inventoryRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $wineInventory = $inventoryRepository->find($id);
        if (!$wineInventory) {
            throw $this->createNotFoundException('Inventory record not found.');
        }

        $user = $this->getUser();
        $isOwnProduct = $wineInventory->getProduct()?->getCreatedBy() === $user;

        return $this->render('staff/inventory/show.html.twig', [
            'wine_inventory' => $wineInventory,
            'is_own_product' => $isOwnProduct,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        WineInventoryRepository $inventoryRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $wineInventory = $inventoryRepository->find($id);
        if (!$wineInventory) {
            throw $this->createNotFoundException('Inventory record not found.');
        }

        $user = $this->getUser();
        if ($wineInventory->getProduct()?->getCreatedBy() !== $user) {
            $this->addFlash('error', '❌ You can only edit inventory for your own products!');
            return $this->redirectToRoute('staff_inventory_show', ['id' => $wineInventory->getId()]);
        }

        $form = $this->createForm(WineInventoryType::class, $wineInventory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $wineInventory->setLastUpdated(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', '✅ Inventory record updated successfully!');
            return $this->redirectToRoute('staff_inventory_show', ['id' => $wineInventory->getId()]);
        }

        return $this->render('staff/inventory/edit.html.twig', [
            'wine_inventory' => $wineInventory,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        WineInventoryRepository $inventoryRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $wineInventory = $inventoryRepository->find($id);
        if (!$wineInventory) {
            throw $this->createNotFoundException('Inventory record not found.');
        }

        $user = $this->getUser();
        if ($wineInventory->getProduct()?->getCreatedBy() !== $user) {
            $this->addFlash('error', '❌ You can only delete inventory for your own products!');
            return $this->redirectToRoute('staff_inventory_index');
        }

        if ($this->isCsrfTokenValid('delete' . $wineInventory->getId(), $request->request->get('_token'))) {
            $entityManager->remove($wineInventory);
            $entityManager->flush();
            $this->addFlash('success', '✅ Inventory record deleted successfully!');
        }

        return $this->redirectToRoute('staff_inventory_index');
    }
}
