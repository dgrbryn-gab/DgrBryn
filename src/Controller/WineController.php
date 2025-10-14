<?php
// src/Controller/WineController.php
namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\StoreProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WineController extends AbstractController
{
    #[Route('/wine', name: 'app_wine', methods: ['GET'])]
    public function index(
        Request $request,
        StoreProductRepository $storeProductRepository,
        CategoryRepository $categoryRepository
    ): Response {
        // Get all categories for the dropdown
        $categories = $categoryRepository->findAll();

        // Get the selected category ID from query parameter
        $selectedCategoryId = $request->query->get('category');

        // Fetch products with inventory based on category filter
        if ($selectedCategoryId) {
            $products = $storeProductRepository->findByCategoryWithInventory($selectedCategoryId);
        } else {
            $products = $storeProductRepository->findAllWithInventory();
        }

        // Pass data to Twig template
        return $this->render('wine/index.html.twig', [
            'store_products' => $products,
            'categories' => $categories,
            'selected_category' => $selectedCategoryId,
        ]);
    }
}