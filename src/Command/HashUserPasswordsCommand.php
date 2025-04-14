<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:hash-user-passwords',
    description: 'Hashes all plain text user passwords in the database.',
)]
class HashUserPasswordsCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->userRepository->findAll();

        foreach ($users as $user) {
            $rawPassword = $user->getMotDePasse();

            if (str_starts_with($rawPassword, '$2y$')) {
                continue;
            }

            $hashed = $this->hasher->hashPassword($user, $rawPassword);
            $user->setMotDePasse($hashed);

            $output->writeln("Hashed password for: " . $user->getEmail());
        }

        $this->em->flush();

        $output->writeln("✅ All passwords hashed.");
        return Command::SUCCESS;
    }
}
