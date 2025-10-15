<?php

namespace App\Controller;

use App\Entity\WineInventory;
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
    #[Route(name: 'app_wine_inventory_index', methods: ['GET'])]
    public function index(WineInventoryRepository $wineInventoryRepository): Response
    {
        $inventories = $wineInventoryRepository->findAll();

        // ✅ Group inventories by category name
        $groupedInventories = [];
        foreach ($inventories as $inventory) {
            $categoryName = $inventory->getProduct()?->getCategory()?->getName() ?? 'Uncategorized';
            $groupedInventories[$categoryName][] = $inventory;
        }

        return $this->render('wine_inventory/index.html.twig', [
            'grouped_inventories' => $groupedInventories,
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
