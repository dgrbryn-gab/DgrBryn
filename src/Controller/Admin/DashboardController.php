<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CategoryRepository;
use App\Repository\StoreProductRepository;
use App\Repository\WineInventoryRepository;
use App\Repository\OrderRepository;

class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(
        CategoryRepository $categoryRepository,
        StoreProductRepository $productRepository,
        WineInventoryRepository $inventoryRepository,
        OrderRepository $orderRepository
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_ADMIN'); //  <-- IMPORTANT SECURITY CHECK

        // URLs for navigation
        $categoryUrl = $this->generateUrl('app_category_index');
        $productUrl = $this->generateUrl('app_store_product_index');
        $inventoryUrl = $this->generateUrl('app_wine_inventory_index');
        $orderUrl = $this->generateUrl('app_order_index');

        // Fetch live counts
        $totalCategories = $categoryRepository->count([]);
        $totalProducts = $productRepository->count([]);
        $totalInventory = $inventoryRepository->count([]);
        $totalOrders = $orderRepository->count([]);

        // Count low-stock items
        $lowStockItems = $inventoryRepository->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.quantity < :limit')
            ->setParameter('limit', 10)
            ->getQuery()
            ->getSingleScalarResult();

        // Order statistics
        $pendingOrders = $orderRepository->countByStatus('pending');
        $processingOrders = $orderRepository->countByStatus('processing');
        $shippedOrders = $orderRepository->countByStatus('shipped');
        $deliveredOrders = $orderRepository->countByStatus('delivered');
        $totalRevenue = $orderRepository->getTotalRevenue();

        return $this->render('admin/dashboard.html.twig', [
            'categoryUrl' => $categoryUrl,
            'productUrl' => $productUrl,
            'inventoryUrl' => $inventoryUrl,
            'orderUrl' => $orderUrl,
            'totalCategories' => $totalCategories,
            'totalProducts' => $totalProducts,
            'totalInventory' => $totalInventory,
            'totalOrders' => $totalOrders,
            'lowStockItems' => $lowStockItems,
            'pendingOrders' => $pendingOrders,
            'processingOrders' => $processingOrders,
            'shippedOrders' => $shippedOrders,
            'deliveredOrders' => $deliveredOrders,
            'totalRevenue' => $totalRevenue,
        ]);
    }
}
