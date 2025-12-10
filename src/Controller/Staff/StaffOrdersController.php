<?php

namespace App\Controller\Staff;

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

#[Route('/staff/orders', name: 'staff_orders_')]
class StaffOrdersController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        OrderRepository $orderRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $user = $this->getUser();
        $status = $request->query->get('status');

        $queryBuilder = $orderRepository->createQueryBuilder('o')
            ->where('o.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC');

        if ($status) {
            $queryBuilder->andWhere('o.status = :status')
                ->setParameter('status', $status);
        }

        $orders = $queryBuilder->getQuery()->getResult();

        // Calculate statistics
        $stats = [
            'total' => count($orders),
            'pending' => 0,
            'processing' => 0,
            'shipped' => 0,
            'delivered' => 0,
        ];

        foreach ($orders as $order) {
            $stats[$order->getStatus()] = ($stats[$order->getStatus()] ?? 0) + 1;
        }

        return $this->render('staff/orders/index.html.twig', [
            'orders' => $orders,
            'current_status' => $status,
            'stats' => $stats,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        StoreProductRepository $productRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

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
                return $this->redirectToRoute('staff_orders_new');
            }

            // Generate order number if not set
            if (!$order->getOrderNumber()) {
                $order->setOrderNumber('ORD-' . date('YmdHis') . '-' . random_int(1000, 9999));
            }

            $order->setStatus('pending');
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setUpdatedAt(new \DateTimeImmutable());
            $order->setCreatedBy($this->getUser());

            // Process order items
            foreach ($order->getOrderItems() as $item) {
                $item->setOrder($order);
                $unitPrice = (float)$item->getUnitPrice();
                $quantity = $item->getQuantity();
                $subtotal = $unitPrice * $quantity;
                $item->setSubtotal((string)$subtotal);
            }

            // Calculate order total
            $this->calculateOrderTotal($order);

            $em->persist($order);
            $em->flush();

            $this->addFlash('success', '✅ Order created successfully!');
            return $this->redirectToRoute('staff_orders_show', ['id' => $order->getId()]);
        }

        $products = $productRepository->findBy(['isAvailable' => true]);
        
        return $this->render('staff/orders/new.html.twig', [
            'form' => $form,
            'products' => $products,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $user = $this->getUser();
        if ($order->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException('You can only view your own orders.');
        }

        return $this->render('staff/orders/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Order $order,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $user = $this->getUser();
        if ($order->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException('You can only edit your own orders.');
        }

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setUpdatedAt(new \DateTimeImmutable());

            foreach ($order->getOrderItems() as $item) {
                $item->setOrder($order);
                $unitPrice = (float)$item->getUnitPrice();
                $quantity = $item->getQuantity();
                $subtotal = $unitPrice * $quantity;
                $item->setSubtotal((string)$subtotal);
            }

            $this->calculateOrderTotal($order);

            $em->flush();

            $this->addFlash('success', '✅ Order updated successfully!');
            return $this->redirectToRoute('staff_orders_show', ['id' => $order->getId()]);
        }

        return $this->render('staff/orders/edit.html.twig', [
            'form' => $form,
            'order' => $order,
        ]);
    }

    #[Route('/{id}/status', name: 'update_status', methods: ['POST'])]
    public function updateStatus(
        Request $request,
        Order $order,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $user = $this->getUser();
        if ($order->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException('You can only update your own orders.');
        }

        $newStatus = $request->request->get('status');
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

        if (in_array($newStatus, $validStatuses)) {
            $order->setStatus($newStatus);
            $order->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();
            $this->addFlash('success', "✅ Order status updated to '{$newStatus}'!");
        } else {
            $this->addFlash('error', '❌ Invalid status!');
        }

        return $this->redirectToRoute('staff_orders_show', ['id' => $order->getId()]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Order $order,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $user = $this->getUser();
        if ($order->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException('You can only delete your own orders.');
        }

        if ($this->isCsrfTokenValid('delete' . $order->getId(), $request->request->get('_token'))) {
            $em->remove($order);
            $em->flush();
            $this->addFlash('success', '✅ Order deleted successfully!');
        }

        return $this->redirectToRoute('staff_orders_index');
    }

    /**
     * Calculate the total amount for an order
     */
    private function calculateOrderTotal(Order $order): void
    {
        $subtotal = 0;
        foreach ($order->getOrderItems() as $item) {
            $subtotal += (float)$item->getSubtotal();
        }

        $shipping = (float)($order->getShippingCost() ?? 0);
        $tax = (float)($order->getTaxAmount() ?? 0);
        $discount = (float)($order->getDiscountAmount() ?? 0);

        $total = $subtotal + $shipping + $tax - $discount;
        $order->setTotalAmount((string)$total);
    }
}
