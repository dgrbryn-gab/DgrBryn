<?php

namespace App\Controller\Staff;

use App\Entity\StoreProduct;
use App\Entity\WineInventory;
use App\Entity\OrderItem;
use App\Form\StoreProductType;
use App\Repository\StoreProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/staff/products', name: 'staff_products_')]
class StaffProductController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        StoreProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $user = $this->getUser();
        $searchTerm = $request->query->get('search');
        $categoryId = $request->query->get('category');

        // Get all products (staff can see all, but can only edit/delete their own)
        $queryBuilder = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->orderBy('p.createdAt', 'DESC');

        if ($categoryId) {
            $queryBuilder->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        if ($searchTerm) {
            $queryBuilder->andWhere('LOWER(p.name) LIKE :search OR LOWER(p.description) LIKE :search')
                ->setParameter('search', '%' . strtolower($searchTerm) . '%');
        }

        $products = $queryBuilder->getQuery()->getResult();
        $categories = $categoryRepository->findAll();

        // Mark which products belong to this staff member
        $ownProductIds = array_map(fn($p) => $p->getId(), array_filter($products, fn($p) => $p->getCreatedBy() === $user));

        return $this->render('staff/products/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'search_term' => $searchTerm,
            'selected_category' => $categoryId,
            'own_product_ids' => $ownProductIds,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $storeProduct = new StoreProduct();
        $form = $this->createForm(StoreProductType::class, $storeProduct);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Image Upload
            $imageFile = $form->get('imageFile')->getData();
            
            error_log('=== PRODUCT CREATION DEBUG ===');
            error_log('Image file: ' . ($imageFile ? get_class($imageFile) : 'null'));
            
            if ($imageFile) {
                try {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                    $uploadDir = $this->getParameter('wine_images_directory');
                    
                    error_log('Original: ' . $imageFile->getClientOriginalName());
                    error_log('Safe name: ' . $safeFilename);
                    error_log('New filename: ' . $newFilename);
                    error_log('Upload dir param: ' . $uploadDir);
                    error_log('File temp path: ' . $imageFile->getPathname());
                    error_log('File size: ' . $imageFile->getSize());
                    
                    $imageFile->move($uploadDir, $newFilename);
                    
                    error_log('File successfully moved to: ' . $uploadDir . '/' . $newFilename);
                    $storeProduct->setImage($newFilename);
                } catch (FileException $e) {
                    error_log('FileException: ' . $e->getMessage() . ' | Code: ' . $e->getCode());
                    $this->addFlash('error', '❌ Failed to upload image: ' . $e->getMessage());
                    return $this->redirectToRoute('staff_products_new');
                } catch (\Exception $e) {
                    error_log('Exception: ' . get_class($e) . ' | ' . $e->getMessage());
                    $this->addFlash('error', '❌ Error: ' . $e->getMessage());
                    return $this->redirectToRoute('staff_products_new');
                }
            }

            $storeProduct->setCreatedAt(new \DateTimeImmutable());
            $storeProduct->setCreatedBy($this->getUser());

            $entityManager->persist($storeProduct);
            $entityManager->flush();

            // Create inventory record with quantity from form
            $quantityFormData = $form->get('quantity')->getData();
            $quantity = $quantityFormData !== null ? (int)$quantityFormData : 0;
            
            $wineInventory = new WineInventory();
            $wineInventory->setProduct($storeProduct);
            $wineInventory->setQuantity($quantity);
            $wineInventory->setAcquiredDate(new \DateTime());
            $wineInventory->setLastUpdated(new \DateTime());
            
            $entityManager->persist($wineInventory);
            $entityManager->flush();

            $this->addFlash('success', '✅ Product created successfully!');
            return $this->redirectToRoute('staff_products_show', ['id' => $storeProduct->getId()]);
        }

        return $this->render('staff/products/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(StoreProduct $product): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $user = $this->getUser();
        $isOwnProduct = $product->getCreatedBy() === $user;

        return $this->render('staff/products/show.html.twig', [
            'product' => $product,
            'is_own_product' => $isOwnProduct,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        StoreProduct $product,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $user = $this->getUser();
        if ($product->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException('You can only edit your own products.');
        }

        $form = $this->createForm(StoreProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Image Upload - Get file from request files
            $uploadedFiles = $request->files->get($form->getName());
            $imageFile = null;
            
            if (isset($uploadedFiles['imageFile'])) {
                $imageFile = $uploadedFiles['imageFile'];
            } else {
                // Try alternate method
                $imageFile = $form->get('imageFile')->getData();
            }
            
            if ($imageFile && $imageFile->getSize() > 0) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $uploadDir = $this->getParameter('wine_images_directory');
                    // Normalize path for Windows/Linux compatibility
                    $uploadDir = str_replace('/', DIRECTORY_SEPARATOR, $uploadDir);
                    $imageFile->move(
                        $uploadDir,
                        $newFilename
                    );
                    $product->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', '❌ Failed to upload image: ' . $e->getMessage());
                    return $this->redirectToRoute('staff_products_edit', ['id' => $product->getId()]);
                }
            }

            $entityManager->flush();

            // Update inventory record with new quantity if provided
            $quantityFormData = $form->get('quantity')->getData();
            if ($quantityFormData !== null) {
                $quantity = (int)$quantityFormData;
                $existingInventory = $product->getWineInventories()->first();
                
                if ($existingInventory) {
                    // Update existing inventory
                    $existingInventory->setQuantity($quantity);
                    $existingInventory->setLastUpdated(new \DateTime());
                } else {
                    // Create new inventory if doesn't exist
                    $wineInventory = new WineInventory();
                    $wineInventory->setProduct($product);
                    $wineInventory->setQuantity($quantity);
                    $wineInventory->setAcquiredDate(new \DateTime());
                    $wineInventory->setLastUpdated(new \DateTime());
                    $entityManager->persist($wineInventory);
                }
                
                $entityManager->flush();
            }

            $this->addFlash('success', '✅ Product updated successfully!');
            return $this->redirectToRoute('staff_products_show', ['id' => $product->getId()]);
        }

        return $this->render('staff/products/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        StoreProduct $product,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $user = $this->getUser();
        if ($product->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException('You can only delete your own products.');
        }

        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            // Check if product has order items
            $orderItems = $entityManager->getRepository(OrderItem::class)->findBy(['product' => $product]);
            if (!empty($orderItems)) {
                $this->addFlash('error', '❌ Cannot delete this product because it has associated orders.');
                return $this->redirectToRoute('staff_products_show', ['id' => $product->getId()]);
            }

            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', '✅ Product deleted successfully!');
        }

        return $this->redirectToRoute('staff_products_index');
    }
}
