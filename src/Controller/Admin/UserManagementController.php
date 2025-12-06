<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserRegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users', name: 'admin_users_')]
class UserManagementController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userRepository->findAll();
        $stats = [
            'total' => count($users),
            'admins' => $userRepository->countByRole('ROLE_ADMIN'),
            'staff' => $userRepository->countByRole('ROLE_STAFF'),
        ];

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'stats' => $stats,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();
        $form = $this->createForm(UserRegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            // Extract password
            $plainPassword = $form->get('plainPassword')->getData();

            if (empty($plainPassword)) {
                $form->get('plainPassword')->addError(new FormError('Password is required.'));
            }

            if ($form->isValid()) {

                // Hash password
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

                // Set role (single role from form)
                $role = $form->get('roles')->getData();
                $user->setRoles([$role]);

                $user->setIsActive(true);

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', sprintf('User "%s" created successfully!', $user->getEmail()));
                return $this->redirectToRoute('admin_users_index');
            }
        }

        return $this->render('admin/user/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(UserRegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $plainPassword = $form->get('plainPassword')->getData();
            $role = $form->get('roles')->getData();

            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $user->setRoles([$role]);
            $user->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', sprintf('User "%s" updated successfully!', $user->getEmail()));
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {

            $email = $user->getEmail();
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', sprintf('User "%s" deleted successfully!', $email));
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/toggle-status', name: 'toggle_status', methods: ['POST'])]
    public function toggleStatus(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('toggle' . $user->getId(), $request->request->get('_token'))) {

            $user->setIsActive(!$user->isActive());
            $user->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $status = $user->isActive() ? 'activated' : 'deactivated';
            $this->addFlash('success', sprintf('User "%s" has been %s.', $user->getEmail(), $status));

        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_users_index');
    }
}
