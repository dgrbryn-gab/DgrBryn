<?php

namespace App\Controller\Staff;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\WineInventoryRepository;
use App\Repository\OrderRepository;

#[Route('/staff', name: 'staff_')]
class StaffDashboardController extends AbstractController
{
    #[Route('', name: 'dashboard')]
    public function index(
        WineInventoryRepository $inventoryRepository,
        OrderRepository $orderRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $user = $this->getUser();

        // Get stats for the current staff member
        $myInventoryRecords = $inventoryRepository->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.product IN (
                SELECT p.id FROM App\Entity\StoreProduct p 
                WHERE p.createdBy = :user
            )')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $myOrders = $orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.createdBy = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $pendingOrders = $orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.createdBy = :user')
            ->andWhere('o.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();

        $processingOrders = $orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.createdBy = :user')
            ->andWhere('o.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'processing')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('staff/dashboard.html.twig', [
            'myInventoryRecords' => $myInventoryRecords,
            'myOrders' => $myOrders,
            'pendingOrders' => $pendingOrders,
            'processingOrders' => $processingOrders,
            'user' => $user,
        ]);
    }
}
