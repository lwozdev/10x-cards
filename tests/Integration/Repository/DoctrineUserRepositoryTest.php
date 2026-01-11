<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Domain\Model\User;
use App\Domain\Value\Email;
use App\Domain\Value\UserId;
use App\Infrastructure\Doctrine\Repository\DoctrineUserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Integration tests for DoctrineUserRepository
 *
 * Tests user CRUD operations, email uniqueness, and authentication support.
 * Reference: test-plan.md Section 5.2 (TC-AUTH-001, SEC-04)
 *
 * Priority: P0 (Critical)
 */
class DoctrineUserRepositoryTest extends KernelTestCase
{
    private DoctrineUserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(DoctrineUserRepository::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test: Create and save a new user
     * TC-AUTH-001: Registration
     */
    public function testCanCreateAndSaveUser(): void
    {
        // Arrange
        $userId = UserId::fromString(Uuid::v4()->toString());
        $email = Email::fromString('test_' . uniqid() . '@example.com');
        $passwordHash = password_hash('SecurePass123!', PASSWORD_BCRYPT);
        $now = new \DateTimeImmutable();

        $user = User::create($userId, $email, $passwordHash, $now);

        // Act
        $this->repository->save($user);

        // Assert
        $foundUser = $this->repository->findById($userId);
        $this->assertNotNull($foundUser);
        $this->assertEquals($userId, $foundUser->getId());
        $this->assertEquals($email, $foundUser->getEmail());
        $this->assertSame($passwordHash, $foundUser->getPassword());
        $this->assertFalse($foundUser->isVerified());
        $this->assertNull($foundUser->getLastLoginAt());
    }

    /**
     * Test: Find user by email
     */
    public function testCanFindUserByEmail(): void
    {
        // Arrange
        $userId = UserId::fromString(Uuid::v4()->toString());
        $email = Email::fromString('findme_' . uniqid() . '@example.com');
        $passwordHash = password_hash('password123', PASSWORD_BCRYPT);
        $now = new \DateTimeImmutable();

        $user = User::create($userId, $email, $passwordHash, $now);
        $this->repository->save($user);

        // Act
        $foundUser = $this->repository->findByEmail($email);

        // Assert
        $this->assertNotNull($foundUser);
        $this->assertEquals($userId, $foundUser->getId());
        $this->assertEquals($email, $foundUser->getEmail());
    }

    /**
     * Test: findByEmail returns null for non-existent email
     */
    public function testFindByEmailReturnsNullForNonExistentEmail(): void
    {
        $result = $this->repository->findByEmail(Email::fromString('nonexistent@example.com'));
        $this->assertNull($result);
    }

    /**
     * Test: Email uniqueness constraint (SEC-04)
     * Doctrine should throw exception on duplicate email
     */
    public function testCannotSaveUserWithDuplicateEmail(): void
    {
        // Arrange
        $email = Email::fromString('duplicate_' . uniqid() . '@example.com');
        $passwordHash = password_hash('password123', PASSWORD_BCRYPT);
        $now = new \DateTimeImmutable();

        $user1 = User::create(
            UserId::fromString(Uuid::v4()->toString()),
            $email,
            $passwordHash,
            $now
        );

        $user2 = User::create(
            UserId::fromString(Uuid::v4()->toString()),
            $email,
            $passwordHash,
            $now
        );

        $this->repository->save($user1);

        // Act & Assert: Expect database unique constraint violation
        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);
        $this->repository->save($user2);
    }

    /**
     * Test: exists() checks if email is already registered
     */
    public function testExistsReturnsTrueForRegisteredEmail(): void
    {
        // Arrange
        $email = Email::fromString('registered_' . uniqid() . '@example.com');
        $user = User::create(
            UserId::fromString(Uuid::v4()->toString()),
            $email,
            password_hash('password123', PASSWORD_BCRYPT),
            new \DateTimeImmutable()
        );

        $this->repository->save($user);

        // Act & Assert
        $this->assertTrue($this->repository->exists($email));
        $this->assertFalse($this->repository->exists(Email::fromString('notregistered_' . uniqid() . '@example.com')));
    }

    /**
     * Test: Update user's last login timestamp
     */
    public function testCanUpdateLastLoginAt(): void
    {
        // Arrange
        $userId = UserId::fromString(Uuid::v4()->toString());
        $email = Email::fromString('login_' . uniqid() . '@example.com');
        $passwordHash = password_hash('password123', PASSWORD_BCRYPT);
        $createdAt = new \DateTimeImmutable('2025-01-01 10:00:00');

        $user = User::create($userId, $email, $passwordHash, $createdAt);
        $this->repository->save($user);

        $this->assertNull($user->getLastLoginAt());

        // Act: Update last login
        $loginTime = new \DateTimeImmutable('2025-01-11 15:30:00');
        $user->updateLastLogin($loginTime);
        $this->repository->save($user);

        // Assert
        $foundUser = $this->repository->findById($userId);
        $this->assertEquals($loginTime, $foundUser->getLastLoginAt());
    }

    /**
     * Test: User can be marked as verified
     */
    public function testCanMarkUserAsVerified(): void
    {
        // Arrange
        $userId = UserId::fromString(Uuid::v4()->toString());
        $email = Email::fromString('verify_' . uniqid() . '@example.com');
        $passwordHash = password_hash('password123', PASSWORD_BCRYPT);
        $now = new \DateTimeImmutable();

        $user = User::create($userId, $email, $passwordHash, $now, isVerified: false);
        $this->repository->save($user);

        $this->assertFalse($user->isVerified());

        // Act: Mark as verified
        $user->markAsVerified();
        $this->repository->save($user);

        // Assert
        $foundUser = $this->repository->findById($userId);
        $this->assertTrue($foundUser->isVerified());
    }

    /**
     * Test: User entity implements UserInterface for Symfony Security
     */
    public function testUserImplementsSymfonySecurityInterfaces(): void
    {
        // Arrange
        $userId = UserId::fromString(Uuid::v4()->toString());
        $email = Email::fromString('security_' . uniqid() . '@example.com');
        $passwordHash = password_hash('password123', PASSWORD_BCRYPT);
        $now = new \DateTimeImmutable();

        $user = User::create($userId, $email, $passwordHash, $now);

        // Assert: Verify Symfony Security interface methods
        $this->assertSame($email->toString(), $user->getUserIdentifier());
        $this->assertSame(['ROLE_USER'], $user->getRoles());
        $this->assertSame($passwordHash, $user->getPassword());

        // eraseCredentials() should not throw
        $user->eraseCredentials();
        $this->assertTrue(true); // If we get here, it didn't throw
    }

    /**
     * Test: Password hash must be at least 60 characters (bcrypt minimum)
     */
    public function testPasswordHashMustBeAtLeast60Characters(): void
    {
        // Arrange
        $userId = UserId::fromString(Uuid::v4()->toString());
        $email = Email::fromString('short_' . uniqid() . '@example.com');
        $shortHash = 'tooshort'; // Less than 60 chars
        $now = new \DateTimeImmutable();

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password hash must be at least 60 characters');
        User::create($userId, $email, $shortHash, $now);
    }

    /**
     * Test: Valid bcrypt hash is accepted
     */
    public function testValidBcryptHashIsAccepted(): void
    {
        // Arrange
        $userId = UserId::fromString(Uuid::v4()->toString());
        $email = Email::fromString('bcrypt_' . uniqid() . '@example.com');
        $validHash = password_hash('SecurePassword123!', PASSWORD_BCRYPT);
        $now = new \DateTimeImmutable();

        // Act: Should not throw
        $user = User::create($userId, $email, $validHash, $now);

        // Assert
        $this->assertSame($validHash, $user->getPassword());
        $this->assertGreaterThanOrEqual(60, strlen($validHash));
    }

    /**
     * Test: findById returns null for non-existent user ID
     */
    public function testFindByIdReturnsNullForNonExistentId(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $result = $this->repository->findById($userId);
        $this->assertNull($result);
    }

    /**
     * Test: Email addresses are case-sensitive in database
     * NOTE: The application layer should normalize emails to lowercase before saving
     */
    public function testEmailAddressesAreCaseSensitiveInDatabase(): void
    {
        // Arrange
        $uniquePart = uniqid();
        $email1 = Email::fromString('test_' . $uniquePart . '@example.com');
        $email2 = Email::fromString('TEST_' . $uniquePart . '@example.com');
        $passwordHash = password_hash('password123', PASSWORD_BCRYPT);
        $now = new \DateTimeImmutable();

        $user1 = User::create(
            UserId::fromString(Uuid::v4()->toString()),
            $email1,
            $passwordHash,
            $now
        );

        $this->repository->save($user1);

        // Act: Search with different case
        $foundLower = $this->repository->findByEmail($email1);
        $foundUpper = $this->repository->findByEmail($email2);

        // Assert: Database search is case-sensitive
        $this->assertNotNull($foundLower);

        // Note: If Email value object normalizes to lowercase, these will be the same
        // If not normalized, foundUpper should be null (different case)
        if ($email1->toString() === $email2->toString()) {
            // Email VO normalizes - both queries return same user
            $this->assertNotNull($foundUpper);
        } else {
            // Email VO preserves case - upper case query returns null
            $this->assertNull($foundUpper);
        }
    }
}
