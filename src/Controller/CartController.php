<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    #[Route('/cart/add', name: 'app_cart_add')]
    public function add(Request $request): Response
    {
        $id = $request->query->get('id');
        $name = $request->query->get('name');
        $price = $request->query->get('price');
        $image = $request->query->get('image', '/assets/images/wine_images/placeholder.webp');

        // Debug: Log query parameters
        // dump($request->query->all());

        // Validate inputs
        if (!$id || !$name || !$price) {
            $this->addFlash('error', 'Invalid item data.');
            return $this->redirectToRoute('app_wine');
        }

        // Get the current cart from session or initialize an empty array
        $cart = $request->getSession()->get('cart', []);

        // Check if the item is already in the cart
        $found = false;
        foreach ($cart as &$item) {
            if ($item['id'] == $id) {
                $item['quantity'] += 1;
                $item['image'] = $item['image'] ?? $image;
                $found = true;
                break;
            }
        }

        // If item not in cart, add it with quantity 1
        if (!$found) {
            $cart[] = [
                'id' => $id,
                'name' => $name,
                'price' => (float) $price,
                'quantity' => 1,
                'image' => $image,
            ];
        }

        // Debug: Log cart after update
        // dump($cart);

        // Save the updated cart to the session
        $request->getSession()->set('cart', $cart);

        // Add success message
        $this->addFlash('success', 'Item added to cart!');
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart', name: 'app_cart')]
    public function index(Request $request): Response
    {
        $cart = $request->getSession()->get('cart', []);

        // Migrate old cart items to include image
        $wineImages = [
            1 => '/assets/images/wine_images/wine1.webp',
            2 => '/assets/images/wine_images/wine2.webp',
            3 => '/assets/images/wine_images/wine3.webp',
            4 => '/assets/images/wine_images/wine4.webp',
            5 => '/assets/images/wine_images/wine5.webp',
            6 => '/assets/images/wine_images/wine6.webp',
            7 => '/assets/images/wine_images/wine7.webp',
            8 => '/assets/images/wine_images/wine8.webp',
            9 => '/assets/images/wine_images/wine9.webp',
            10 => '/assets/images/wine_images/wine10.webp',
            11 => '/assets/images/wine_images/wine11.webp',
            12 => '/assets/images/wine_images/wine12.webp',
        ];
        foreach ($cart as &$item) {
            if (!isset($item['image']) || empty($item['image']) || str_ends_with($item['image'], '.jpg')) {
                $item['image'] = $wineImages[$item['id']] ?? '/assets/images/wine_images/placeholder.webp';
            }
        }
        $request->getSession()->set('cart', $cart);

        // Debug: Log cart
        // dump($cart);

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(Request $request, int $id): Response
    {
        $cart = $request->getSession()->get('cart', []);

        // Debug: Log cart and id before removal
        // dump('Before removal:', $cart, 'ID to remove:', $id);

        $cart = array_filter($cart, fn($item) => (int) $item['id'] !== $id);
        $cart = array_values($cart); // Reindex array

        // Debug: Log cart after removal
        // dump('After removal:', $cart);

        $request->getSession()->set('cart', $cart);

        $this->addFlash('success', 'Item removed from cart!');
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/clear', name: 'app_cart_clear')]
    public function clear(Request $request): Response
    {
        $request->getSession()->set('cart', []);
        $this->addFlash('success', 'Cart cleared!');
        return $this->redirectToRoute('app_cart');
    }
}