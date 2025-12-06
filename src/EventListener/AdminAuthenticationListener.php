<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AdminAuthenticationListener implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private RouterInterface $router,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        // Check if this is an admin route (except login)
        if (str_starts_with($pathInfo, '/admin') && $pathInfo !== '/admin/login') {
            // Get the current user token
            $token = $this->tokenStorage->getToken();
            
            // If no token or user is null/not authenticated, redirect to login
            if (!$token || !$token->getUser()) {
                $loginUrl = $this->router->generate('app_login');
                $event->setResponse(new RedirectResponse($loginUrl, 302));
            }
        }
    }
}
