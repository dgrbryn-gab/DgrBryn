<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Find all users by a specific role
     * @return User[] Returns an array of User objects with the specified role
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%' . $role . '%')
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Count users by role
     */
    public function countByRole(string $role): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%' . $role . '%')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Get all admins
     * @return User[] Returns an array of admin users
     */
    public function findAllAdmins(): array
    {
        return $this->findByRole('ROLE_ADMIN');
    }

    /**
     * Get all staff members
     * @return User[] Returns an array of staff users
     */
    public function findAllStaff(): array
    {
        return $this->findByRole('ROLE_STAFF');
    }

    /**
     * Get all customers
     * @return User[] Returns an array of customer users
     */
    public function findAllCustomers(): array
    {
        return $this->findByRole('ROLE_CUSTOMER');
    }

    /**
     * Get all active users
     * @return User[] Returns an array of active users
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
