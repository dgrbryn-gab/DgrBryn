<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\StoreProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        StoreProductRepository $productRepository,
        EntityManagerInterface $em
    ): Response {
        // Get cart from session
        $cart = $request->getSession()->get('cart', []);

        // If cart is empty, redirect back to wines
        if (empty($cart)) {
            $this->addFlash('warning', 'Your cart is empty!');
            return $this->redirectToRoute('app_wine');
        }

        // Handle POST request (form submission)
        if ($request->isMethod('POST')) {
            return $this->processCheckout($request, $cart, $productRepository, $em);
        }

        // Calculate cart totals
        $subtotal = 0;
        $cartItems = [];
        
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
            $cartItems[] = $item;
        }

        $tax = $subtotal * 0.10; // 10% tax
        $shipping = $subtotal > 5000 ? 0 : 250; // Free shipping over 5000
        $total = $subtotal + $tax + $shipping;

        return $this->render('checkout/index.html.twig', [
            'cart' => $cartItems,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total,
        ]);
    }

    private function processCheckout(
        Request $request,
        array $cart,
        StoreProductRepository $productRepository,
        EntityManagerInterface $em
    ): Response {
        // Get form data
        $customerName = $request->request->get('customer_name');
        $customerEmail = $request->request->get('customer_email');
        $customerPhone = $request->request->get('customer_phone');
        $shippingAddress = $request->request->get('shipping_address');
        $billingAddress = $request->request->get('billing_address');
        $paymentMethod = $request->request->get('payment_method', 'credit_card');
        $shippingCost = (float) $request->request->get('shipping_cost', 0);
        $taxAmount = (float) $request->request->get('tax_amount', 0);
        $discountAmount = (float) $request->request->get('discount_amount', 0);

        // Validate required fields
        if (!$customerName || !$customerEmail || !$customerPhone || !$shippingAddress) {
            $this->addFlash('error', 'Please fill in all required fields.');
            return $this->redirectToRoute('app_checkout');
        }

        // Validate email format
        if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Invalid email address.');
            return $this->redirectToRoute('app_checkout');
        }

        // Validate phone format (basic)
        if (strlen($customerPhone) < 10) {
            $this->addFlash('error', 'Invalid phone number.');
            return $this->redirectToRoute('app_checkout');
        }

        // Create order
        $order = new Order();
        $order->setOrderNumber('ORD-' . date('YmdHis') . '-' . random_int(1000, 9999));
        $order->setCustomerName($customerName);
        $order->setCustomerEmail($customerEmail);
        $order->setCustomerPhone($customerPhone);
        $order->setShippingAddress($shippingAddress);
        $order->setBillingAddress($billingAddress ?: $shippingAddress);
        $order->setPaymentMethod($paymentMethod);
        $order->setPaymentStatus('pending');
        $order->setStatus('pending');
        $order->setShippingCost($shippingCost);
        $order->setTaxAmount($taxAmount);
        $order->setDiscountAmount($discountAmount);
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setUpdatedAt(new \DateTimeImmutable());

        // Add order items
        $totalAmount = 0;
        foreach ($cart as $item) {
            $product = $productRepository->find($item['id']);
            
            if (!$product) {
                $this->addFlash('error', 'Product not found: ' . $item['name']);
                return $this->redirectToRoute('app_checkout');
            }

            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setProduct($product);
            $orderItem->setQuantity($item['quantity']);
            $orderItem->setUnitPrice((float) $item['price']);
            $orderItem->setSubtotal((float) $item['price'] * $item['quantity']);
            $orderItem->setDiscount(0);

            $order->addOrderItem($orderItem);
            $totalAmount += $orderItem->getSubtotal();
        }

        // Set total amount
        $order->setTotalAmount((string) ($totalAmount + $shippingCost + $taxAmount - $discountAmount));

        // Persist order
        $em->persist($order);
        $em->flush();

        // Clear cart from session
        $request->getSession()->set('cart', []);

        // Add success message
        $this->addFlash('success', 'Order created successfully! Order Number: ' . $order->getOrderNumber());

        // Redirect to order confirmation page
        return $this->redirectToRoute('app_checkout_success', ['orderId' => $order->getId()]);
    }

    #[Route('/checkout/success/{orderId}', name: 'app_checkout_success', methods: ['GET'])]
    public function success(int $orderId, EntityManagerInterface $em): Response
    {
        $order = $em->getRepository(Order::class)->find($orderId);

        if (!$order) {
            $this->addFlash('error', 'Order not found.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('checkout/success.html.twig', [
            'order' => $order,
        ]);
    }
}
