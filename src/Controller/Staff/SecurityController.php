<?php

namespace App\Controller\Staff;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/staff', name: 'staff_')]
class SecurityController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function login(): Response
    {
        // Redirect to the unified admin login
        return $this->redirectToRoute('app_login');
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
    }
}
