<?php

namespace App\Controller;

use App\Entity\StoreProduct;
use App\Entity\WineInventory;
use App\Form\StoreProductType;
use App\Repository\StoreProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StoreProductController extends AbstractController
{
    #[Route('admin/store/product', name: 'app_store_product_index', methods: ['GET'])]
    public function index(Request $request, StoreProductRepository $storeProductRepository, CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();
        $categoryId = $request->query->get('category');
        $searchTerm = $request->query->get('search');

        // Build dynamic query
        $queryBuilder = $storeProductRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c');

        if ($categoryId) {
            $queryBuilder->andWhere('c.id = :categoryId')
                         ->setParameter('categoryId', $categoryId);
        }

        if ($searchTerm) {
            $queryBuilder->andWhere('p.name LIKE :search OR p.description LIKE :search')
                         ->setParameter('search', '%' . $searchTerm . '%');
        }

        $storeProducts = $queryBuilder->getQuery()->getResult();

        return $this->render('admin/store_product/index.html.twig', [
            'store_products' => $storeProducts,
            'categories' => $categories,
            'selected_category' => $categoryId,
            'search_term' => $searchTerm,
        ]);
    }

    #[Route('admin/store/product/new', name: 'app_store_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        $storeProduct = new StoreProduct();
        $form = $this->createForm(StoreProductType::class, $storeProduct);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $storeProduct->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($storeProduct);

            // Create or update WineInventory
            $quantity = $form->get('quantity')->getData();
            $inventory = new WineInventory();
            $inventory->setProduct($storeProduct);
            $inventory->setAcquiredDate(new \DateTime());
            $inventory->setQuantity($quantity ?? 0);

            $entityManager->persist($inventory);
            $entityManager->flush();

            return $this->redirectToRoute('app_store_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/store_product/new.html.twig', [
            'storeProduct' => $storeProduct,
            'form' => $form->createView(),
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('admin/store/product/{id}', name: 'app_store_product_show', methods: ['GET'])]
    public function show(StoreProduct $storeProduct): Response
    {
        return $this->render('admin/store_product/show.html.twig', [
            'storeProduct' => $storeProduct,
        ]);
    }

    #[Route('admin/store/product/{id}/edit', name: 'app_store_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, StoreProduct $storeProduct, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        $form = $this->createForm(StoreProductType::class, $storeProduct);
        $form->get('quantity')->setData(
            $storeProduct->getWineInventories()->first() ? $storeProduct->getWineInventories()->first()->getQuantity() : 0
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quantity = $form->get('quantity')->getData();
            $inventory = $entityManager->getRepository(WineInventory::class)->findOneBy(['product' => $storeProduct]);

            if (!$inventory) {
                $inventory = new WineInventory();
                $inventory->setProduct($storeProduct);
                $inventory->setAcquiredDate(new \DateTime());
            }

            $inventory->setQuantity($quantity ?? 0);
            $entityManager->persist($inventory);
            $entityManager->flush();

            return $this->redirectToRoute('app_store_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/store_product/edit.html.twig', [
            'storeProduct' => $storeProduct,
            'form' => $form->createView(),
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('admin/store/product/{id}', name: 'app_store_product_delete', methods: ['POST'])]
    public function delete(Request $request, StoreProduct $storeProduct, EntityManagerInterface $entityManager, StoreProductRepository $storeProductRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $storeProduct->getId(), $request->request->get('_token'))) {
            $inventory = $entityManager->getRepository(WineInventory::class)->findOneBy(['product' => $storeProduct]);
            if ($inventory) {
                $entityManager->remove($inventory);
            }

            $storeProductRepository->remove($storeProduct, true);
        }

        return $this->redirectToRoute('app_store_product_index', [], Response::HTTP_SEE_OTHER);
    }
}
