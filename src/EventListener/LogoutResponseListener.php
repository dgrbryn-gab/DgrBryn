<?php

namespace App\EventListener;

use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\HttpFoundation\Response;

class LogoutResponseListener
{
    public function onLogout(LogoutEvent $event): void
    {
        $response = $event->getResponse();
        
        if ($response) {
            // Set cache control headers to prevent back button access
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            $response->headers->set('X-UA-Compatible', 'IE=edge');
        }
    }
}
