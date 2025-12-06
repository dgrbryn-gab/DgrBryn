<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-admin-role',
    description: 'Add ROLE_ADMIN to a user',
)]
class AddAdminRoleCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email of the user')
            ->setHelp('This command adds the ROLE_ADMIN role to a user.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('User with email "%s" not found.', $email));
            return Command::FAILURE;
        }

        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles)) {
            $io->warning(sprintf('User "%s" already has ROLE_ADMIN.', $email));
            return Command::SUCCESS;
        }

        $roles[] = 'ROLE_ADMIN';
        $user->setRoles($roles);

        $this->em->flush();

        $io->success(sprintf('Added ROLE_ADMIN to user "%s".', $email));

        return Command::SUCCESS;
    }
}
