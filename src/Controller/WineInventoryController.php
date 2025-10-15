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
use Symfony\Component\Routing\Attribute\Route;

#[Route('/wine/inventory')]
final class WineInventoryController extends AbstractController
{
    #[Route(name: 'app_wine_inventory_index', methods: ['GET'])] // route to a certain page
    public function index(Request $request, WineInventoryRepository $wineInventoryRepository, EntityManagerInterface $entityManager): Response // action
    {
        $selectedCategory = $request->query->get('category');

        // Get all categories for dropdown
        $categories = $entityManager->getRepository(Category::class)->findAll(); // fetch data from category entity

        // Filter by category if selected
        if ($selectedCategory) {
            $inventories = $wineInventoryRepository->createQueryBuilder('wi')
                ->join('wi.product', 'p')
                ->join('p.category', 'c')
                ->where('c.id = :categoryId')
                ->setParameter('categoryId', $selectedCategory)
                ->getQuery()
                ->getResult();
        } else {
            $inventories = $wineInventoryRepository->findAll();
        }

        // Group inventories by category name (for display)
        $groupedInventories = [];
        foreach ($inventories as $inventory) {
            $categoryName = $inventory->getProduct()?->getCategory()?->getName() ?? 'Uncategorized';
            $groupedInventories[$categoryName][] = $inventory;
        }

        // return a response
        return $this->render('wine_inventory/index.html.twig', [
            'grouped_inventories' => $groupedInventories,
            'categories' => $categories,
            'selected_category' => $selectedCategory,
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

            return $this->redirectToRoute('app_wine_inventory_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('wine_inventory/new.html.twig', [
            'wine_inventory' => $wineInventory,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_wine_inventory_show', methods: ['GET'])]
    public function show(WineInventory $wineInventory): Response
    {
        return $this->render('wine_inventory/show.html.twig', [
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

            return $this->redirectToRoute('app_wine_inventory_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('wine_inventory/edit.html.twig', [
            'wine_inventory' => $wineInventory,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_wine_inventory_delete', methods: ['POST'])]
    public function delete(Request $request, WineInventory $wineInventory, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $wineInventory->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($wineInventory);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_wine_inventory_index', [], Response::HTTP_SEE_OTHER);
    }
}
