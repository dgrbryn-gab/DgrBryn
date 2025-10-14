<?php
// src/Controller/WineController.php
namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\StoreProductRepository;
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
        CategoryRepository $categoryRepository
    ): Response {
        // Fetch all categories for the dropdown
        $categories = $categoryRepository->findAll();

        // Get selected category ID from query parameter
        $selectedCategoryId = $request->query->get('category');

        // Filter products by category (no inventory yet)
        if ($selectedCategoryId) {
            $products = $storeProductRepository->findBy(['category' => $selectedCategoryId]);
        } else {
            $products = $storeProductRepository->findAll();
        }

        // Render the template
        return $this->render('wine/index.html.twig', [
            'store_products' => $products,
            'categories' => $categories,
            'selected_category' => $selectedCategoryId,
        ]);
    }
}
