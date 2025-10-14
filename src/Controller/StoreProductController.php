<?php

namespace App\Controller;

use App\Entity\StoreProduct;
use App\Form\StoreProductType;
use App\Repository\StoreProductRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StoreProductController extends AbstractController
{
    #[Route('/store/product', name: 'app_store_product_index', methods: ['GET'])]
    public function index(Request $request, StoreProductRepository $storeProductRepository, CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();
        $categoryId = $request->query->get('category');
        if ($categoryId) {
            $storeProducts = $storeProductRepository->findBy(['category' => $categoryId]);
        } else {
            $storeProducts = $storeProductRepository->findAll();
        }

        return $this->render('store_product/index.html.twig', [
            'store_products' => $storeProducts,
            'categories' => $categories,
        ]);
    }

    #[Route('/store/product/new', name: 'app_store_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, StoreProductRepository $storeProductRepository): Response
    {
        $storeProduct = new StoreProduct();
        $form = $this->createForm(StoreProductType::class, $storeProduct);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $storeProductRepository->save($storeProduct, true);
            return $this->redirectToRoute('app_store_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('store_product/new.html.twig', [
            'storeProduct' => $storeProduct,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/store/product/{id}', name: 'app_store_product_show', methods: ['GET'])]
    public function show(StoreProduct $storeProduct): Response
    {
        return $this->render('store_product/show.html.twig', [
            'storeProduct' => $storeProduct,
        ]);
    }

    #[Route('/store/product/{id}/edit', name: 'app_store_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, StoreProduct $storeProduct, StoreProductRepository $storeProductRepository): Response
    {
        $form = $this->createForm(StoreProductType::class, $storeProduct);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $storeProductRepository->save($storeProduct, true);
            return $this->redirectToRoute('app_store_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('store_product/edit.html.twig', [
            'storeProduct' => $storeProduct,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/store/product/{id}', name: 'app_store_product_delete', methods: ['POST'])]
    public function delete(Request $request, StoreProduct $storeProduct, StoreProductRepository $storeProductRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$storeProduct->getId(), $request->request->get('_token'))) {
            $storeProductRepository->remove($storeProduct, true);
        }

        return $this->redirectToRoute('app_store_product_index', [], Response::HTTP_SEE_OTHER);
    }
}