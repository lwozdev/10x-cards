<?php

declare(strict_types=1);

namespace App\Infrastructure\Console\Command;

use App\Domain\Model\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Value\Email;
use App\Domain\Value\UserId;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Console command to create users for testing/development.
 *
 * Usage:
 *   php bin/console app:create-user test@example.com test123
 *   php bin/console app:create-user admin@example.com secure_password
 *
 * Features:
 * - Validates email format (via Email value object)
 * - Hashes password using Symfony password hasher (bcrypt/argon2)
 * - Checks for duplicate emails
 * - Uses Domain layer (User entity, UserRepositoryInterface)
 *
 * Security notes:
 * - Passwords are immediately hashed (never stored in plain text)
 * - Only use for development/testing (not production user management)
 * - For production: implement proper registration flow with email verification
 */
#[AsCommand(
    name: 'app:create-user',
    description: 'Create a new user (for testing/development)',
)]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email address')
            ->addArgument('password', InputArgument::REQUIRED, 'User password (plain text, will be hashed)')
            ->setHelp(
                <<<'HELP'
                The <info>app:create-user</info> command creates a new user in the database:

                  <info>php bin/console app:create-user test@example.com test123</info>

                This command is intended for development and testing purposes only.
                For production, use the web registration flow.

                The password will be automatically hashed using the configured password hasher
                (bcrypt or argon2, depending on PHP extensions available).
                HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $emailString = $input->getArgument('email');
        $plainPassword = $input->getArgument('password');

        // Validate inputs
        if (!is_string($emailString) || !is_string($plainPassword)) {
            $io->error('Email and password must be strings.');

            return Command::FAILURE;
        }

        // Validate password length (minimum 8 characters per auth-spec.md)
        if (strlen($plainPassword) < 8) {
            $io->error('Password must be at least 8 characters long.');

            return Command::FAILURE;
        }

        try {
            // Create Email value object (validates format)
            $email = Email::fromString($emailString);

            // Check if user already exists
            if ($this->userRepository->exists($email)) {
                $io->error(sprintf('User with email "%s" already exists.', $email->toString()));

                return Command::FAILURE;
            }

            // Generate UUID and create timestamp first
            $userId = UserId::generate();
            $createdAt = new \DateTimeImmutable();

            // Create placeholder user for password hashing
            // Workaround: UserPasswordHasherInterface requires UserInterface instance
            // We use a temporary hash that meets length requirement (60+ chars)
            $tempHash = str_repeat('x', 60); // Temporary hash for UserInterface requirement
            $tempUser = User::create(
                id: $userId,
                email: $email,
                passwordHash: $tempHash,
                createdAt: $createdAt
            );

            // Hash password using Symfony password hasher
            $passwordHash = $this->passwordHasher->hashPassword($tempUser, $plainPassword);

            // Create final user with real hashed password
            // Use same ID and timestamp for consistency
            $user = User::create(
                id: $userId,
                email: $email,
                passwordHash: $passwordHash,
                createdAt: $createdAt
            );

            // Save to database
            $this->userRepository->save($user);

            $io->success(sprintf(
                'User created successfully!'.PHP_EOL.
                'Email: %s'.PHP_EOL.
                'ID: %s'.PHP_EOL.
                'You can now log in at /login',
                $user->getEmail()->toString(),
                $user->getId()->toString()
            ));

            return Command::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            // Email validation failed or other domain constraint violated
            $io->error($e->getMessage());

            return Command::FAILURE;
        } catch (\Throwable $e) {
            // Unexpected error (DB connection, etc.)
            $io->error(sprintf('Failed to create user: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
