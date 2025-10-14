<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    private SessionInterface $session;
    private const CART_SESSION_KEY = 'cart';

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function addItem(string $productId, int $quantity = 1, array $productData = []): void
    {
        $cart = $this->getCart();
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'quantity' => $quantity,
                'data' => $productData
            ];
        }
        $this->session->set(self::CART_SESSION_KEY, $cart);
    }

    public function updateItem(string $productId, int $quantity): bool
    {
        $cart = $this->getCart();
        if (!isset($cart[$productId])) {
            return false;
        }
        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId]['quantity'] = $quantity;
        }
        $this->session->set(self::CART_SESSION_KEY, $cart);
        return true;
    }

    public function removeItem(string $productId): bool
    {
        $cart = $this->getCart();
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            $this->session->set(self::CART_SESSION_KEY, $cart);
            return true;
        }
        return false;
    }

    public function getCart(): array
    {
        return $this->session->get(self::CART_SESSION_KEY, []);
    }

    public function getCartItems(): array
    {
        $cart = $this->getCart();
        $items = [];
        foreach ($cart as $productId => $item) {
            $items[] = [
                'id' => $productId,
                'name' => $item['data']['name'] ?? 'Unknown Wine',
                'vintage' => $item['data']['vintage'] ?? '',
                'region' => $item['data']['region'] ?? '',
                'price' => $item['data']['price'] ?? 0,
                'image' => $item['data']['image'] ?? 'default-wine.jpg',
                'quantity' => $item['quantity']
            ];
        }
        return $items;
    }

    public function getSubtotal(): float
    {
        $items = $this->getCartItems();
        return array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $items));
    }

    public function getTotal(): float
    {
        $subtotal = $this->getSubtotal();
        return $subtotal + ($subtotal * 0.08); // Example 8% tax
    }

    public function clearCart(): void
    {
        $this->session->remove(self::CART_SESSION_KEY);
    }

    public function getItemCount(): int
    {
        $cart = $this->getCart();
        return array_sum(array_column($cart, 'quantity'));
    }
}