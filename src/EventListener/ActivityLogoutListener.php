<?php

namespace App\EventListener;

use App\Service\ActivityLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class ActivityLogoutListener implements EventSubscriberInterface
{
    public function __construct(private ActivityLogger $activityLogger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $this->activityLogger->logActivity('USER_LOGOUT');
    }
}
