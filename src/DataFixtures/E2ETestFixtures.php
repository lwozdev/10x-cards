<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Model\User;
use App\Domain\Value\Email;
use App\Domain\Value\UserId;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixtures for E2E tests.
 *
 * Creates a test user for Playwright E2E tests.
 * Credentials match those in tests/e2e/flashcard-generation-full-flow.spec.ts
 */
class E2ETestFixtures extends Fixture implements FixtureGroupInterface
{
    public const TEST_USER_EMAIL = 'admin@example.com';
    public const TEST_USER_PASSWORD = 'admin1234';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $user = $this->createTestUser();
        $manager->persist($user);
        $manager->flush();
    }

    private function createTestUser(): User
    {
        $userId = UserId::generate();
        $email = Email::fromString(self::TEST_USER_EMAIL);

        // Create a temporary user to hash the password
        $tempUser = User::create(
            $userId,
            $email,
            str_repeat('x', 60), // Temporary hash
            new \DateTimeImmutable(),
            true // isVerified - user can log in immediately
        );

        // Hash the password using Symfony's password hasher
        $hashedPassword = $this->passwordHasher->hashPassword(
            $tempUser,
            self::TEST_USER_PASSWORD
        );

        // Create the actual user with the hashed password
        return User::create(
            $userId,
            $email,
            $hashedPassword,
            new \DateTimeImmutable(),
            true // isVerified
        );
    }

    public static function getGroups(): array
    {
        return ['e2e'];
    }
}
