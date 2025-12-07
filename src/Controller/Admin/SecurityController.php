<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/admin/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $user = $this->getUser();
        
        if ($user) {
            // Redirect based on user role
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                return $this->redirectToRoute('admin_dashboard');
            } elseif (in_array('ROLE_STAFF', $user->getRoles())) {
                return $this->redirectToRoute('staff_dashboard');
            }
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $response = $this->render('admin/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);

        // Prevent caching of login page
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '-1');

        return $response;
    }

    #[Route('/admin/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
    }

    #[Route('/staff/logout', name: 'staff_logout')]
    public function staffLogout(): void
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
    }
}
