<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un utilisateur avec le rôle admin.',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('nom', InputArgument::REQUIRED, 'Nom d\'utilisateur (login)')
            ->addArgument('email', InputArgument::REQUIRED, 'Email')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $nom = $input->getArgument('nom');
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        if ($this->userRepository->findOneBy(['nom' => $nom]) || $this->userRepository->findOneBy(['email' => $email])) {
            $io->error('Un utilisateur avec ce nom ou cet email existe déjà.');
            return Command::FAILURE;
        }

        $user = new User();
        $user->setNom($nom);
        $user->setPrenom('Admin');
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setType('admin');
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Admin créé : nom="%s", email="%s". Vous pouvez vous connecter.', $nom, $email));
        return Command::SUCCESS;
    }
}
