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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\String\Slugger\SluggerInterface;

class StoreProductController extends AbstractController
{
    #[Route('admin/store/product', name: 'app_store_product_index', methods: ['GET'])]
    public function index(Request $request, StoreProductRepository $storeProductRepository, CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();
        $categoryId = $request->query->get('category');
        $searchTerm = $request->query->get('search');

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
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $storeProduct = new StoreProduct();
        $form = $this->createForm(StoreProductType::class, $storeProduct);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // --- Image Upload ---
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('wine_images_directory'),
                        $newFilename
                    );
                    $storeProduct->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image.');
                    return $this->redirectToRoute('app_store_product_new');
                }
            }

            $storeProduct->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($storeProduct);

            // --- Inventory ---
            $quantity = $form->get('quantity')->getData();
            $inventory = new WineInventory();
            $inventory->setProduct($storeProduct);
            $inventory->setAcquiredDate(new \DateTime());
            $inventory->setQuantity($quantity ?? 0);

            $entityManager->persist($inventory);
            $entityManager->flush();

            $this->addFlash('success', 'Product created successfully!');
            return $this->redirectToRoute('app_store_product_index');
        }

        return $this->render('admin/store_product/new.html.twig', [
            'storeProduct' => $storeProduct,
            'form' => $form->createView(),
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
    public function edit(Request $request, StoreProduct $storeProduct, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // --- Pre-load imageFile for preview ---
        if ($storeProduct->getImage()) {
            $storeProduct->setImageFile(
                new File($this->getParameter('wine_images_directory') . '/' . $storeProduct->getImage())
            );
        }

        $form = $this->createForm(StoreProductType::class, $storeProduct);
        $form->get('quantity')->setData(
            $storeProduct->getWineInventories()->first() ? $storeProduct->getWineInventories()->first()->getQuantity() : 0
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // --- Image Upload (replace old) ---
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('wine_images_directory'),
                        $newFilename
                    );

                    // Delete old image
                    if ($storeProduct->getImage()) {
                        $oldFile = $this->getParameter('wine_images_directory') . '/' . $storeProduct->getImage();
                        if (file_exists($oldFile)) {
                            @unlink($oldFile);
                        }
                    }

                    $storeProduct->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload new image.');
                }
            }

            // --- Update Inventory ---
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

            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('app_store_product_index');
        }

        return $this->render('admin/store_product/edit.html.twig', [
            'storeProduct' => $storeProduct,
            'form' => $form->createView(),
        ]);
    }

    #[Route('admin/store/product/{id}', name: 'app_store_product_delete', methods: ['POST'])]
    public function delete(Request $request, StoreProduct $storeProduct, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $storeProduct->getId(), $request->request->get('_token'))) {
            // Delete associated image
            if ($storeProduct->getImage()) {
                $imagePath = $this->getParameter('wine_images_directory') . '/' . $storeProduct->getImage();
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }

            // Delete inventory
            $inventory = $entityManager->getRepository(WineInventory::class)->findOneBy(['product' => $storeProduct]);
            if ($inventory) {
                $entityManager->remove($inventory);
            }

            $entityManager->remove($storeProduct);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_store_product_index');
    }
}