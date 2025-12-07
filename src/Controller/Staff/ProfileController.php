<?php

namespace App\Controller\Staff;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/staff/profile', name: 'staff_profile_')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        
        $user = $this->getUser();

        return $this->render('staff/security/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        
        $user = $this->getUser();
        $error = null;

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            // Validate email
            if (empty($email)) {
                $error = 'Email is required';
            } elseif ($email !== $user->getEmail()) {
                // Check if email already exists
                $existingUser = $em->getRepository('App:User')->findOneBy(['email' => $email]);
                if ($existingUser) {
                    $error = 'This email is already in use';
                }
            }

            // Handle password change
            if (!empty($newPassword) || !empty($currentPassword)) {
                if (empty($currentPassword)) {
                    $error = 'Current password is required to change password';
                } elseif (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $error = 'Current password is incorrect';
                } elseif (empty($newPassword)) {
                    $error = 'New password is required';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'New passwords do not match';
                } elseif (strlen($newPassword) < 6) {
                    $error = 'Password must be at least 6 characters long';
                }

                if (!$error) {
                    $user->setPassword(
                        $passwordHasher->hashPassword($user, $newPassword)
                    );
                }
            }

            // Update email if no errors
            if (!$error) {
                $user->setEmail($email);
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Your profile has been updated successfully!');
                return $this->redirectToRoute('staff_profile_index');
            }
        }

        return $this->render('staff/security/edit_profile.html.twig', [
            'user' => $user,
            'error' => $error,
        ]);
    }
}
