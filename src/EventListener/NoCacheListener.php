<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class NoCacheListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        // Apply no-cache headers to admin routes
        if (str_starts_with($pathInfo, '/admin')) {
            $response = $event->getResponse();

            // Aggressive cache prevention headers
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '-1');
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
            $response->headers->set('ETag', '"' . md5(time()) . '"');
            
            // Force revalidation on every request
            $response->setMaxAge(0);
            $response->setSharedMaxAge(0);
            $response->mustRevalidate();
            $response->setPublic(false);
        }
    }
}
