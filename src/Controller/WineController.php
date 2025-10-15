<?php
// src/Controller/WineController.php
namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\StoreProductRepository;
use App\Repository\WineInventoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class WineController extends AbstractController
{
    #[Route('/wine', name: 'app_wine', methods: ['GET'])]
    public function index(
        Request $request,
        StoreProductRepository $storeProductRepository,
        CategoryRepository $categoryRepository,
        WineInventoryRepository $wineInventoryRepository
    ): Response {
        // Fetch all categories for the dropdown
        $categories = $categoryRepository->findAll();

        // Get selected category ID from query parameter
        $selectedCategoryId = $request->query->get('category');

        // Filter products by category
        if ($selectedCategoryId) {
            $products = $storeProductRepository->findBy(['category' => $selectedCategoryId]);
        } else {
            $products = $storeProductRepository->findAll();
        }

        // 🔹 Attach stock quantity to each product
        foreach ($products as $product) {
            $inventory = $wineInventoryRepository->findOneBy(['product' => $product]);
            $product->stockQuantity = $inventory ? $inventory->getQuantity() : 0;
        }

        // Render the template
        return $this->render('wine/index.html.twig', [
            'store_products' => $products,
            'categories' => $categories,
            'selected_category' => $selectedCategoryId,
        ]);
    }
}
