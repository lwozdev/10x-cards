<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Domain\Model\Set;
use App\Domain\Model\User;
use App\Domain\Value\Email;
use App\Domain\Value\SetName;
use App\Domain\Value\UserId;
use App\Infrastructure\Doctrine\Repository\DoctrineSetRepository;
use App\Infrastructure\Doctrine\Repository\DoctrineUserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Uid\Uuid;

/**
 * Integration tests for DoctrineSetRepository.
 *
 * Tests CRUD operations, soft delete, and RLS filtering.
 * Reference: test-plan.md Section 5.3 (SET-01, SET-02)
 *
 * Priority: P0 (Critical)
 */
class DoctrineSetRepositoryTest extends KernelTestCase
{
    private DoctrineSetRepository $repository;
    private DoctrineUserRepository $userRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(DoctrineSetRepository::class);
        $this->userRepository = self::getContainer()->get(DoctrineUserRepository::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Helper method to create a test user.
     */
    private function createTestUser(?string $email = null): UserId
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $userEmail = Email::fromString($email ?? 'user_'.uniqid().'@example.com');
        $passwordHash = password_hash('password123', PASSWORD_BCRYPT);
        $now = new \DateTimeImmutable();

        $user = User::create($userId, $userEmail, $passwordHash, $now);
        $this->userRepository->save($user);

        return $userId;
    }

    /**
     * Test: Create and save a new set.
     */
    public function testCanCreateAndSaveSet(): void
    {
        // Arrange
        $setId = Uuid::v4()->toString();
        $userId = $this->createTestUser();
        $setName = SetName::fromString('Test Set');
        $now = new \DateTimeImmutable();

        $set = Set::create($setId, $userId, $setName, $now);

        // Act
        $this->repository->save($set);

        // Assert
        $foundSet = $this->repository->findById($setId);
        $this->assertNotNull($foundSet);
        $this->assertSame($setId, $foundSet->getId());
        $this->assertEquals($userId, $foundSet->getOwnerId());
        $this->assertEquals($setName, $foundSet->getName());
        $this->assertSame(0, $foundSet->getCardCount());
        $this->assertFalse($foundSet->isDeleted());
    }

    /**
     * Test: Find sets owned by specific user.
     */
    public function testFindOwnedByReturnsOnlyUsersSets(): void
    {
        // Arrange
        $user1Id = $this->createTestUser();
        $user2Id = $this->createTestUser();
        $now = new \DateTimeImmutable();

        // Create 2 sets for user1
        $set1 = Set::create(Uuid::v4()->toString(), $user1Id, SetName::fromString('User1 Set1'), $now);
        $set2 = Set::create(Uuid::v4()->toString(), $user1Id, SetName::fromString('User1 Set2'), $now);

        // Create 1 set for user2
        $set3 = Set::create(Uuid::v4()->toString(), $user2Id, SetName::fromString('User2 Set1'), $now);

        $this->repository->save($set1);
        $this->repository->save($set2);
        $this->repository->save($set3);

        // Act
        $user1Sets = $this->repository->findOwnedBy($user1Id);

        // Assert
        $this->assertCount(2, $user1Sets);
        $setIds = array_map(fn (Set $s) => $s->getId(), $user1Sets);
        $this->assertContains($set1->getId(), $setIds);
        $this->assertContains($set2->getId(), $setIds);
        $this->assertNotContains($set3->getId(), $setIds);
    }

    /**
     * Test: findActiveOwnedBy excludes soft-deleted sets
     * TC-SET-01: Soft Delete.
     */
    public function testFindActiveOwnedByExcludesSoftDeletedSets(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $now = new \DateTimeImmutable();

        $activeSet = Set::create(Uuid::v4()->toString(), $userId, SetName::fromString('Active Set'), $now);
        $deletedSet = Set::create(Uuid::v4()->toString(), $userId, SetName::fromString('Deleted Set'), $now);

        $this->repository->save($activeSet);
        $this->repository->save($deletedSet);

        // Act: Soft delete one set
        $this->repository->softDelete($deletedSet);

        // Assert
        $activeSets = $this->repository->findActiveOwnedBy($userId);

        $this->assertCount(1, $activeSets);
        $this->assertSame($activeSet->getId(), $activeSets[0]->getId());

        // Verify deleted set has deletedAt timestamp
        $foundDeletedSet = $this->repository->findById($deletedSet->getId());
        $this->assertNotNull($foundDeletedSet);
        $this->assertTrue($foundDeletedSet->isDeleted());
        $this->assertNotNull($foundDeletedSet->getDeletedAt());
    }

    /**
     * Test: Soft delete sets deletedAt timestamp.
     */
    public function testSoftDeleteSetsDeletedAtTimestamp(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $set = Set::create(
            Uuid::v4()->toString(),
            $userId,
            SetName::fromString('To Be Deleted'),
            new \DateTimeImmutable()
        );

        $this->repository->save($set);
        $this->assertFalse($set->isDeleted());

        // Act
        $this->repository->softDelete($set);

        // Assert
        $this->assertTrue($set->isDeleted());
        $this->assertNotNull($set->getDeletedAt());

        // Verify persistence
        $foundSet = $this->repository->findById($set->getId());
        $this->assertTrue($foundSet->isDeleted());
    }

    /**
     * Test: existsByOwnerAndName checks for duplicate names (case-insensitive).
     */
    public function testExistsByOwnerAndNameDetectsDuplicates(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $setName = 'Biology Notes';
        $now = new \DateTimeImmutable();

        $set = Set::create(
            Uuid::v4()->toString(),
            $userId,
            SetName::fromString($setName),
            $now
        );

        $this->repository->save($set);

        // Act & Assert
        $this->assertTrue($this->repository->existsByOwnerAndName($userId, $setName));
        $this->assertTrue($this->repository->existsByOwnerAndName($userId, 'biology notes')); // case-insensitive
        $this->assertTrue($this->repository->existsByOwnerAndName($userId, 'BIOLOGY NOTES')); // case-insensitive
        $this->assertFalse($this->repository->existsByOwnerAndName($userId, 'Chemistry Notes'));
    }

    /**
     * Test: existsByOwnerAndName excludes soft-deleted sets.
     */
    public function testExistsByOwnerAndNameExcludesDeletedSets(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $setName = 'History Notes';
        $now = new \DateTimeImmutable();

        $set = Set::create(
            Uuid::v4()->toString(),
            $userId,
            SetName::fromString($setName),
            $now
        );

        $this->repository->save($set);
        $this->assertTrue($this->repository->existsByOwnerAndName($userId, $setName));

        // Act: Soft delete
        $this->repository->softDelete($set);

        // Assert: Should return false now (deleted sets don't count)
        $this->assertFalse($this->repository->existsByOwnerAndName($userId, $setName));
    }

    /**
     * Test: findActiveOwnedBy supports pagination.
     */
    public function testFindActiveOwnedBySupportsPagination(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $now = new \DateTimeImmutable();

        // Create 5 sets
        for ($i = 1; $i <= 5; ++$i) {
            $set = Set::create(
                Uuid::v4()->toString(),
                $userId,
                SetName::fromString("Set $i"),
                $now->modify("+$i seconds") // Different timestamps for ordering
            );
            $this->repository->save($set);
        }

        // Act: Fetch first 2 sets
        $page1 = $this->repository->findActiveOwnedBy($userId, limit: 2, offset: 0);
        $page2 = $this->repository->findActiveOwnedBy($userId, limit: 2, offset: 2);

        // Assert
        $this->assertCount(2, $page1);
        $this->assertCount(2, $page2);

        // Verify no overlap
        $page1Ids = array_map(fn (Set $s) => $s->getId(), $page1);
        $page2Ids = array_map(fn (Set $s) => $s->getId(), $page2);
        $this->assertEmpty(array_intersect($page1Ids, $page2Ids));
    }

    /**
     * Test: findActiveOwnedBy orders by updatedAt DESC.
     */
    public function testFindActiveOwnedByOrdersByUpdatedAtDesc(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $baseTime = new \DateTimeImmutable('2025-01-01 10:00:00');

        $oldSet = Set::create(
            Uuid::v4()->toString(),
            $userId,
            SetName::fromString('Old Set'),
            $baseTime
        );

        $newSet = Set::create(
            Uuid::v4()->toString(),
            $userId,
            SetName::fromString('New Set'),
            $baseTime->modify('+1 hour')
        );

        $this->repository->save($oldSet);
        $this->repository->save($newSet);

        // Act
        $sets = $this->repository->findActiveOwnedBy($userId);

        // Assert: Newest first
        $this->assertSame($newSet->getId(), $sets[0]->getId());
        $this->assertSame($oldSet->getId(), $sets[1]->getId());
    }

    /**
     * Test: findById returns null for non-existent ID.
     */
    public function testFindByIdReturnsNullForNonExistentId(): void
    {
        $result = $this->repository->findById(Uuid::v4()->toString());
        $this->assertNull($result);
    }

    /**
     * RLS Test: User cannot access another user's set via findById.
     */
    #[Group('rls')]
    #[Group('incomplete')]
    public function testRlsUserCannotAccessAnotherUsersSetViaFindById(): void
    {
        $this->markTestIncomplete(
            'RLS (Row-Level Security) not yet implemented. '.
            'PostgresRLSSubscriber must set current_app_user() before queries. '.
            'When implemented, this test should verify that findById returns null '.
            'when trying to access another user\'s set.'
        );

        // Future implementation:
        // 1. Create User A and User B
        // 2. User A creates a Set
        // 3. Authenticate as User B (set current_app_user())
        // 4. Try to findById(Set A's ID)
        // 5. Assert: Repository returns null (filtered by RLS policy)
    }

    /**
     * TC-SET-02: Card count trigger test.
     *
     * NOTE: This test requires database trigger to be implemented.
     * The trigger should automatically update set.card_count when cards are added/deleted.
     */
    #[Group('trigger')]
    #[Group('incomplete')]
    public function testCardCountTriggerUpdatesAutomatically(): void
    {
        $this->markTestIncomplete(
            'Database trigger for automatic card_count updates not yet implemented. '.
            'Trigger should increment/decrement set.card_count on card INSERT/soft DELETE. '.
            'This test will verify that adding 2 cards increases card_count by 2, '.
            'and soft-deleting 1 card decreases it by 1.'
        );

        // Future implementation:
        // 1. Create a Set (card_count = 0)
        // 2. Add 2 Cards to the Set
        // 3. Refresh Set entity
        // 4. Assert: card_count = 2 (incremented by trigger)
        // 5. Soft-delete 1 Card
        // 6. Refresh Set entity
        // 7. Assert: card_count = 1 (decremented by trigger)
    }
}
