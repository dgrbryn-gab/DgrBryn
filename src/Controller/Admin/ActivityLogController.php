<?php

namespace App\Controller\Admin;

use App\Repository\ActivityLogRepository;
use App\Service\ActivityLogFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/activity-logs', name: 'admin_activity_logs')]
#[IsGranted('ROLE_ADMIN')]
class ActivityLogController extends AbstractController
{
    #[Route('', name: '_index', methods: ['GET'])]
    public function index(
        ActivityLogRepository $repository,
        ActivityLogFormatter $formatter,
        Request $request
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $qb = $this->buildQuery($request, $repository);
        
        $activityLogs = $qb
            ->orderBy('a.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $countQb = $this->buildQuery($request, $repository);
        $total = $countQb
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $formattedLogs = array_map(fn($log) => $formatter->format($log), $activityLogs);

        return $this->render('admin/activity_log/index.html.twig', [
            'activity_logs' => $formattedLogs,
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'pages' => ceil($total / $limit),
            'username_filter' => $request->query->get('username'),
            'action_filter' => $request->query->get('action'),
        ]);
    }

    #[Route('/user/{userId}', name: '_by_user', methods: ['GET'])]
    public function byUser(
        int $userId,
        ActivityLogRepository $repository,
        ActivityLogFormatter $formatter,
        Request $request
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $activityLogs = $repository->createQueryBuilder('a')
            ->andWhere('a.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('a.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $total = $repository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        $formattedLogs = array_map(fn($log) => $formatter->format($log), $activityLogs);

        return $this->render('admin/activity_log/user.html.twig', [
            'activity_logs' => $formattedLogs,
            'user_id' => $userId,
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'pages' => ceil($total / $limit),
        ]);
    }

    #[Route('/export/csv', name: '_export_csv', methods: ['GET'])]
    public function exportCsv(
        ActivityLogRepository $repository,
        Request $request
    ): Response {
        $query = $this->buildQuery($request, $repository);
        
        $activityLogs = $repository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $csv = "ID,Username,User ID,Role,Action,Target Data,IP Address,Created At\n";
        
        foreach ($activityLogs as $log) {
            $csv .= sprintf(
                "%d,\"%s\",%d,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $log->getId(),
                $log->getUsername(),
                $log->getUserId()?->getId() ?? '',
                $log->getRole(),
                $log->getAction(),
                str_replace('"', '""', $log->getTargetData() ?? ''),
                $log->getIpAddress() ?? '',
                $log->getCreatedAt()?->format('Y-m-d H:i:s') ?? ''
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="activity_logs.csv"');

        return $response;
    }

    private function buildQuery(Request $request, ActivityLogRepository $repository)
    {
        $qb = $repository->createQueryBuilder('a');

        if ($username = $request->query->get('username')) {
            $qb->andWhere('a.username LIKE :username')
                ->setParameter('username', '%' . $username . '%');
        }

        if ($action = $request->query->get('action')) {
            $qb->andWhere('a.action = :action')
                ->setParameter('action', $action);
        }

        if ($startDate = $request->query->get('startDate')) {
            $qb->andWhere('a.createdAt >= :startDate')
                ->setParameter('startDate', new \DateTime($startDate));
        }

        if ($endDate = $request->query->get('endDate')) {
            $qb->andWhere('a.createdAt <= :endDate')
                ->setParameter('endDate', new \DateTime($endDate . ' 23:59:59'));
        }

        return $qb;
    }
}
