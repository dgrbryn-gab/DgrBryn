<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CategoryRepository;
use App\Repository\StoreProductRepository;
use App\Repository\WineInventoryRepository;

class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(
        CategoryRepository $categoryRepository,
        StoreProductRepository $productRepository,
        WineInventoryRepository $inventoryRepository
    ): Response {
        // URLs for navigation
        $categoryUrl = $this->generateUrl('app_category_index');
        $productUrl = $this->generateUrl('app_store_product_index');
        $inventoryUrl = $this->generateUrl('app_wine_inventory_index');

        // Fetch live counts
        $totalCategories = $categoryRepository->count([]);
        $totalProducts = $productRepository->count([]);
        $totalInventory = $inventoryRepository->count([]);

        // Count low-stock items
        $lowStockItems = $inventoryRepository->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.quantity < :limit')
            ->setParameter('limit', 10)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/dashboard.html.twig', [
            'categoryUrl' => $categoryUrl,
            'productUrl' => $productUrl,
            'inventoryUrl' => $inventoryUrl,
            'totalCategories' => $totalCategories,
            'totalProducts' => $totalProducts,
            'totalInventory' => $totalInventory,
            'lowStockItems' => $lowStockItems,
        ]);
    }
}
