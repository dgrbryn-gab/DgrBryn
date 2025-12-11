<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Repository\StoreProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/order', name: 'app_order_')]
class OrderController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        OrderRepository $orderRepository
    ): Response {

        $page = $request->query->getInt('page', 1);
        $status = $request->query->get('status');

        $query = $orderRepository->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC');

        if ($status) {
            $query->where('o.status = :status')
                ->setParameter('status', $status);
        }

        $orders = $query->getQuery()->getResult();

        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders,
            'currentStatus' => $status,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        StoreProductRepository $productRepository,
        OrderRepository $orderRepository
    ): Response {
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validate that all products in the order are available
            $unavailableProducts = [];
            foreach ($order->getOrderItems() as $item) {
                $product = $item->getProduct();
                if (!$product || !$product->isAvailable()) {
                    $unavailableProducts[] = $product ? $product->getName() : 'Unknown Product';
                }
            }

            // If there are unavailable products, show error and prevent order creation
            if (!empty($unavailableProducts)) {
                $this->addFlash('error', '❌ Cannot create order: The following products are not available: ' . implode(', ', $unavailableProducts));
                return $this->redirectToRoute('app_order_new');
            }

            // Generate order number if not set
            if (!$order->getOrderNumber()) {
                $order->setOrderNumber('ORD-' . date('YmdHis') . '-' . random_int(1000, 9999));
            }
            
            $order->setStatus('pending');
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setUpdatedAt(new \DateTimeImmutable());
            $order->setCreatedBy($this->getUser());

            // Process order items - calculate subtotal for each and associate with order
            foreach ($order->getOrderItems() as $item) {
                // Ensure item is associated with order
                $item->setOrder($order);
                
                $unitPrice = (float)$item->getUnitPrice();
                $quantity = $item->getQuantity();
                $subtotal = $unitPrice * $quantity;
                $item->setSubtotal((string)$subtotal);
            }

            // Recalculate total
            $this->calculateOrderTotal($order);

            $em->persist($order);
            $em->flush();

            $this->addFlash('success', 'Order created successfully!');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }

        // Only show available products in the dropdown
        $products = $productRepository->findBy(['isAvailable' => true]);

        return $this->render('admin/order/new.html.twig', [
            'form' => $form,
            'products' => $products,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('admin/order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Order $order,
        EntityManagerInterface $em,
        StoreProductRepository $productRepository
    ): Response {
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setUpdatedAt(new \DateTimeImmutable());

            // Recalculate total
            $this->calculateOrderTotal($order);

            $em->flush();

            $this->addFlash('success', 'Order updated successfully!');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }

        // Get available products for the add item dropdown
        $products = $productRepository->findBy(['isAvailable' => true]);

        return $this->render('admin/order/edit.html.twig', [
            'form' => $form,
            'order' => $order,
            'products' => $products,
        ]);
    }

    #[Route('/{id}/status/{status}', name: 'change_status', methods: ['POST'])]
    public function changeStatus(
        Request $request,
        Order $order,
        string $status,
        EntityManagerInterface $em
    ): Response {
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

        if (!in_array($status, $validStatuses)) {
            $this->addFlash('error', 'Invalid status!');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }

        if ($this->isCsrfTokenValid('status' . $order->getId(), $request->request->get('_token'))) {
            $order->setStatus($status);
            $order->setUpdatedAt(new \DateTimeImmutable());

            if ($status === 'shipped') {
                $order->setShippedAt(new \DateTimeImmutable());
            } elseif ($status === 'delivered') {
                $order->setDeliveredAt(new \DateTimeImmutable());
            }

            $em->flush();

            $this->addFlash('success', sprintf('Order status changed to %s!', $status));
        }

        return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Order $order,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $order->getId(), $request->request->get('_token'))) {
            $orderNumber = $order->getOrderNumber();
            
            $em->remove($order);
            $em->flush();

            $this->addFlash('success', sprintf('Order %s has been deleted successfully!', $orderNumber));
        } else {
            $this->addFlash('error', 'Invalid security token!');
        }

        return $this->redirectToRoute('app_order_index');
    }

    #[Route('/{id}/add-item', name: 'add_item', methods: ['POST'])]
    public function addItem(
        Request $request,
        Order $order,
        StoreProductRepository $productRepository,
        EntityManagerInterface $em
    ): Response {
        $productId = $request->request->get('product_id');
        $quantity = (int)$request->request->get('quantity', 1);

        if (!$productId || $quantity <= 0) {
            $this->addFlash('error', 'Invalid product or quantity!');
            return $this->redirectToRoute('app_order_edit', ['id' => $order->getId()]);
        }

        $product = $productRepository->find($productId);
        if (!$product) {
            $this->addFlash('error', 'Product not found!');
            return $this->redirectToRoute('app_order_edit', ['id' => $order->getId()]);
        }

        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setProduct($product);
        $orderItem->setQuantity($quantity);
        $orderItem->setUnitPrice((string)$product->getPrice());
        $orderItem->setSubtotal((string)($product->getPrice() * $quantity));

        $em->persist($orderItem);
        $order->addOrderItem($orderItem);

        $this->calculateOrderTotal($order);

        $em->flush();

        $this->addFlash('success', 'Item added to order!');
        return $this->redirectToRoute('app_order_edit', ['id' => $order->getId()]);
    }

    #[Route('/{orderId}/remove-item/{itemId}', name: 'remove_item', methods: ['POST'])]
    public function removeItem(
        Request $request,
        Order $order,
        OrderItem $orderItem,
        EntityManagerInterface $em
    ): Response {
        if ($orderItem->getOrder() !== $order) {
            $this->addFlash('error', 'Invalid item!');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }

        if ($this->isCsrfTokenValid('remove_item' . $orderItem->getId(), $request->request->get('_token'))) {
            $order->removeOrderItem($orderItem);
            $em->remove($orderItem);

            $this->calculateOrderTotal($order);

            $em->flush();

            $this->addFlash('success', 'Item removed from order!');
        }

        return $this->redirectToRoute('app_order_edit', ['id' => $order->getId()]);
    }

    #[Route('/api/products', name: 'api_products', methods: ['GET'])]
    public function getProductsApi(StoreProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        
        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'label' => sprintf('%s (₱%.2f)', $product->getName(), $product->getPrice()),
            ];
        }

        return $this->json($data);
    }

    private function calculateOrderTotal(Order $order): void
    {
        $total = 0;

        foreach ($order->getOrderItems() as $item) {
            $itemTotal = (float)$item->getSubtotal();
            if ($item->getDiscount()) {
                $itemTotal -= (float)$item->getDiscount();
            }
            $total += $itemTotal;
        }

        $shippingCost = $order->getShippingCost() ? (float)$order->getShippingCost() : 0;
        $taxAmount = $order->getTaxAmount() ? (float)$order->getTaxAmount() : 0;
        $discountAmount = $order->getDiscountAmount() ? (float)$order->getDiscountAmount() : 0;

        $total += $shippingCost + $taxAmount - $discountAmount;

        $order->setTotalAmount((string)round($total, 2));
    }
}
