<?php

namespace App\Controller;

use App\Entity\WineInventory;
use App\Entity\Category;
use App\Form\WineInventoryType;
use App\Repository\WineInventoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/wine/inventory')]
class WineInventoryController extends AbstractController
{
    #[Route('', name: 'app_wine_inventory_index', methods: ['GET'])]
    public function index(Request $request, WineInventoryRepository $wineInventoryRepository, EntityManagerInterface $entityManager): Response
    {
        $selectedCategory = $request->query->get('category');
        $searchTerm = $request->query->get('search'); 

        // Get all categories for dropdown
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Base query builder
        $queryBuilder = $wineInventoryRepository->createQueryBuilder('wi')
            ->join('wi.product', 'p')
            ->join('p.category', 'c');

        // Filter by category if selected
        if ($selectedCategory) {
            $queryBuilder->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $selectedCategory);
        }

        // Filter by search term if provided
        if ($searchTerm) {
            $queryBuilder->andWhere('LOWER(p.name) LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%');
        }

        $inventories = $queryBuilder->getQuery()->getResult();

        // Group inventories by category name
        $groupedInventories = [];
        foreach ($inventories as $inventory) {
            $categoryName = $inventory->getProduct()?->getCategory()?->getName() ?? 'Uncategorized';
            $groupedInventories[$categoryName][] = $inventory;
        }

        return $this->render('admin/wine_inventory/index.html.twig', [
            'grouped_inventories' => $groupedInventories,
            'categories' => $categories,
            'selected_category' => $selectedCategory,
            'search_term' => $searchTerm,
        ]);
    }

    #[Route('/new', name: 'app_wine_inventory_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $wineInventory = new WineInventory();
        $form = $this->createForm(WineInventoryType::class, $wineInventory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $wineInventory->setLastUpdated(new \DateTime());

            $entityManager->persist($wineInventory);
            $entityManager->flush();

            $this->addFlash('success', '✅ Wine inventory added successfully!');
            return $this->redirectToRoute('app_wine_inventory_index');
        }

        return $this->render('admin/wine_inventory/new.html.twig', [
            'wine_inventory' => $wineInventory,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_wine_inventory_show', methods: ['GET'])]
    public function show(WineInventory $wineInventory): Response
    {
        return $this->render('admin/wine_inventory/show.html.twig', [
            'wine_inventory' => $wineInventory,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_wine_inventory_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, WineInventory $wineInventory, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(WineInventoryType::class, $wineInventory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $wineInventory->setLastUpdated(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', '✅ Wine inventory updated successfully!');
            return $this->redirectToRoute('app_wine_inventory_index');
        }

        return $this->render('admin/wine_inventory/edit.html.twig', [
            'wine_inventory' => $wineInventory,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_wine_inventory_delete', methods: ['POST'])]
    public function delete(Request $request, WineInventory $wineInventory, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $wineInventory->getId(), $request->request->get('_token'))) {
            $entityManager->remove($wineInventory);
            $entityManager->flush();

            $this->addFlash('success', '🗑️ Wine inventory deleted successfully!');
        }

        return $this->redirectToRoute('app_wine_inventory_index');
    }
}
