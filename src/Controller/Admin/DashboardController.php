<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\StoreProduct;
use App\Entity\WineInventory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(private AdminUrlGenerator $adminUrlGenerator)
    {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $categoryUrl = $this->adminUrlGenerator->setController(CategoryCrudController::class)->generateUrl();
        $productUrl = $this->adminUrlGenerator->setController(StoreProductCrudController::class)->generateUrl();
        $inventoryUrl = $this->adminUrlGenerator->setController(WineInventoryCrudController::class)->generateUrl();

        return $this->render('admin/dashboard.html.twig', [
            'categoryUrl' => $categoryUrl,
            'productUrl' => $productUrl,
            'inventoryUrl' => $inventoryUrl,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Admin Dashboard');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Catalog');
        yield MenuItem::linkToCrud('Categories', 'fa fa-tags', Category::class);
        yield MenuItem::linkToCrud('Products', 'fa fa-wine-bottle', StoreProduct::class);
        yield MenuItem::linkToCrud('Inventory', 'fa fa-boxes', WineInventory::class);
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('/admin.css');
    }
}


