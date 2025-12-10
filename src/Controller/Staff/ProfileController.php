<?php

namespace App\Controller\Staff;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\ActivityLogger;

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
        UserPasswordHasherInterface $passwordHasher,
        ActivityLogger $activityLogger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');
        
        $user = $this->getUser();
        $error = null;

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

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
                    $user->setUpdatedAt(new \DateTimeImmutable());
                    $em->persist($user);
                    $em->flush();

                    // Log password change activity
                    $activityLogger->logActivity(
                        'PASSWORD_CHANGED',
                        json_encode(['userId' => $user->getId(), 'email' => $user->getEmail()])
                    );

                    $this->addFlash('success', 'Your password has been updated successfully!');
                    return $this->redirectToRoute('staff_profile_index');
                }
            } else {
                // If no password change attempted, still show message but redirect
                $this->addFlash('info', 'No changes were made.');
                return $this->redirectToRoute('staff_profile_index');
            }
        }

        return $this->render('staff/security/edit_profile.html.twig', [
            'user' => $user,
            'error' => $error,
        ]);
    }
}
