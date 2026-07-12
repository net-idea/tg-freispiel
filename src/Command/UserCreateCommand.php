<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\UserEntity;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:user:create',
    description: 'Create a user for the upcoming admin area'
)]
class UserCreateCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Login email address (asked interactively when omitted)')
            ->addArgument('name', InputArgument::REQUIRED, 'Display name (asked interactively when omitted)')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password (asked interactively when omitted)')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Grant ROLE_ADMIN (asked interactively when the email argument is omitted)');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);

        if (null === $input->getArgument('email')) {
            $input->setArgument('email', $io->ask('Login email address', null, static function (?string $value): string {
                if (null === $value || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException('Please enter a valid email address.');
                }

                return $value;
            }));

            if (false === $input->getOption('admin')) {
                $input->setOption('admin', $io->confirm('Grant ROLE_ADMIN?', false));
            }
        }

        if (null === $input->getArgument('name')) {
            $input->setArgument('name', $io->ask('Display name', null, static function (?string $value): string {
                if (null === $value || '' === trim($value)) {
                    throw new \InvalidArgumentException('The display name must not be empty.');
                }

                return $value;
            }));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');
        $name = (string) $input->getArgument('name');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error(sprintf('"%s" is not a valid email address.', $email));

            return Command::FAILURE;
        }

        if (null !== $this->users->findOneBy(['email' => $email])) {
            $io->error(sprintf('A user with email "%s" already exists.', $email));

            return Command::FAILURE;
        }

        $password = (string) ($input->getOption('password') ?? $io->askHidden('Password'));

        if (strlen($password) < 8) {
            $io->error('Password must be at least 8 characters long.');

            return Command::FAILURE;
        }

        $isAdmin = true === $input->getOption('admin');

        $user = (new UserEntity())
            ->setEmail($email)
            ->setName($name)
            ->setRoles($isAdmin ? ['ROLE_ADMIN'] : []);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('User #%d "%s" <%s> created%s.', $user->getId(), $name, $email, $isAdmin ? ' with ROLE_ADMIN' : ''));

        return Command::SUCCESS;
    }
}
