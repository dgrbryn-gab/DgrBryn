<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class LoginSuccessListener implements EventSubscriberInterface
{
    public function __construct(private RouterInterface $router)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $request = $event->getRequest();

        // Only redirect if we're on the login page
        if (strpos($request->getPathInfo(), '/admin/login') !== 0) {
            return;
        }

        // Check user roles and set redirect target
        $roles = $user->getRoles();
        
        if (in_array('ROLE_ADMIN', $roles)) {
            $redirectUrl = $this->router->generate('admin_dashboard');
        } elseif (in_array('ROLE_STAFF', $roles)) {
            $redirectUrl = $this->router->generate('staff_dashboard');
        } else {
            // Default fallback
            return;
        }

        // Set the response to redirect immediately
        $response = new RedirectResponse($redirectUrl, 302);
        $event->setResponse($response);
    }
}
