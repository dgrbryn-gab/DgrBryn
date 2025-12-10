<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TimezoneController extends AbstractController
{
    #[Route('/api/set-timezone', name: 'api_set_timezone', methods: ['POST'])]
    public function setTimezone(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $timezone = $data['timezone'] ?? null;

            if ($timezone) {
                // Validate timezone
                try {
                    new \DateTimeZone($timezone);
                    // Store in session
                    $request->getSession()->set('user_timezone', $timezone);
                    
                    return new JsonResponse(['success' => true, 'timezone' => $timezone]);
                } catch (\Exception $e) {
                    return new JsonResponse(['success' => false, 'message' => 'Invalid timezone'], 400);
                }
            }

            return new JsonResponse(['success' => false, 'message' => 'No timezone provided'], 400);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
