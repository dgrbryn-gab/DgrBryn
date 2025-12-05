<?php

namespace App\Command;

use App\Repository\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-admin-role',
    description: 'Add ROLE_ADMIN to an admin user',
)]
class AddAdminRoleCommand extends Command
{
    public function __construct(
        private AdminRepository $adminRepository,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email of the admin user')
            ->setHelp('This command adds the ROLE_ADMIN role to an admin user.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        $admin = $this->adminRepository->findOneBy(['email' => $email]);

        if (!$admin) {
            $io->error(sprintf('Admin user with email "%s" not found.', $email));
            return Command::FAILURE;
        }

        $roles = $admin->getRoles();

        if (in_array('ROLE_ADMIN', $roles)) {
            $io->warning(sprintf('Admin user "%s" already has ROLE_ADMIN.', $email));
            return Command::SUCCESS;
        }

        $roles[] = 'ROLE_ADMIN';
        $admin->setRoles($roles);

        $this->em->flush();

        $io->success(sprintf('Added ROLE_ADMIN to user "%s".', $email));

        return Command::SUCCESS;
    }
}
