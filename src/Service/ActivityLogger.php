<?php

namespace App\Service;

use App\Entity\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLogger
{
    private $em;
    private $security;
    private $requestStack;

    public function __construct(EntityManagerInterface $em, Security $security, RequestStack $requestStack)
    {
        $this->em = $em;
        $this->security = $security;
        $this->requestStack = $requestStack;
    }

    public function logActivity(string $action, ?string $targetData = null): void
    {
        $user = $this->security->getUser();
        if (!$user) {
            return; // No user is logged in
        }

        $request = $this->requestStack->getCurrentRequest();
        $ipAddress = $request ? $request->getClientIp() : null;

        $activityLog = new ActivityLog();
        $activityLog->setUserId($user);
        $activityLog->setUsername($user->getUsername());
        $activityLog->setRole(implode(',', $user->getRoles()));
        $activityLog->setAction($action);
        $activityLog->setTargetData($targetData);
        $activityLog->setIpAddress($ipAddress);
        $activityLog->setCreatedAt(new \DateTime());

        $this->em->persist($activityLog);
        $this->em->flush();
    }
}