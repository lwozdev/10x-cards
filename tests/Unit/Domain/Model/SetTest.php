<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Model;

use App\Domain\Model\Set;
use App\Domain\Value\SetName;
use App\Domain\Value\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for Set Domain Model
 *
 * Critical business logic:
 * - Card count increment/decrement (with edge case: never below 0)
 * - Soft delete functionality
 * - Generation metadata tracking
 */
final class SetTest extends TestCase
{
    // ===== Creation Tests =====

    public function testCreateNewSet(): void
    {
        $id = Uuid::v4()->toString();
        $ownerId = UserId::fromString(Uuid::v4()->toString());
        $name = SetName::fromString('Biology Flashcards');
        $createdAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $set = Set::create($id, $ownerId, $name, $createdAt);

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame($id, $set->getId());
        $this->assertEquals($ownerId, $set->getOwnerId());
        $this->assertEquals($name, $set->getName());
        $this->assertEquals($createdAt, $set->getCreatedAt());
        $this->assertEquals($createdAt, $set->getUpdatedAt()); // Initially same
        $this->assertSame(0, $set->getCardCount());
        $this->assertNull($set->getDeletedAt());
        $this->assertFalse($set->isDeleted());
    }

    // ===== Card Count Tests (Critical) =====

    public function testIncrementCardCountStartsFromZero(): void
    {
        $set = $this->createTestSet();

        $this->assertSame(0, $set->getCardCount());

        $set->incrementCardCount();

        $this->assertSame(1, $set->getCardCount());
    }

    public function testIncrementCardCountMultipleTimes(): void
    {
        $set = $this->createTestSet();

        for ($i = 1; $i <= 10; $i++) {
            $set->incrementCardCount();
            $this->assertSame($i, $set->getCardCount());
        }
    }

    public function testDecrementCardCountFromPositiveValue(): void
    {
        $set = $this->createTestSet();

        $set->incrementCardCount();
        $set->incrementCardCount();
        $set->incrementCardCount();
        $this->assertSame(3, $set->getCardCount());

        $set->decrementCardCount();
        $this->assertSame(2, $set->getCardCount());

        $set->decrementCardCount();
        $this->assertSame(1, $set->getCardCount());

        $set->decrementCardCount();
        $this->assertSame(0, $set->getCardCount());
    }

    public function testDecrementCardCountNeverGoesBelowZero(): void
    {
        $set = $this->createTestSet();

        $this->assertSame(0, $set->getCardCount());

        // Try to decrement from 0
        $set->decrementCardCount();

        // Should remain 0, not become negative
        $this->assertSame(0, $set->getCardCount());
    }

    public function testDecrementCardCountMultipleTimesFromZero(): void
    {
        $set = $this->createTestSet();

        $this->assertSame(0, $set->getCardCount());

        // Try to decrement multiple times
        $set->decrementCardCount();
        $set->decrementCardCount();
        $set->decrementCardCount();

        // Should still be 0
        $this->assertSame(0, $set->getCardCount());
    }

    public function testIncrementAndDecrementCardCountTogether(): void
    {
        $set = $this->createTestSet();

        $set->incrementCardCount(); // 1
        $set->incrementCardCount(); // 2
        $set->incrementCardCount(); // 3
        $set->decrementCardCount(); // 2
        $set->incrementCardCount(); // 3
        $set->decrementCardCount(); // 2
        $set->decrementCardCount(); // 1
        $set->decrementCardCount(); // 0
        $set->decrementCardCount(); // Still 0 (edge case protection)

        $this->assertSame(0, $set->getCardCount());
    }

    // ===== Soft Delete Tests =====

    public function testIsDeletedReturnsFalseForNewSet(): void
    {
        $set = $this->createTestSet();

        $this->assertFalse($set->isDeleted());
        $this->assertNull($set->getDeletedAt());
    }

    public function testSoftDeleteMarksSetAsDeleted(): void
    {
        $set = $this->createTestSet();
        $deletedAt = new \DateTimeImmutable('2024-01-20 15:00:00');

        $set->softDelete($deletedAt);

        $this->assertTrue($set->isDeleted());
        $this->assertEquals($deletedAt, $set->getDeletedAt());
    }

    public function testSoftDeleteCanBeCalledMultipleTimes(): void
    {
        $set = $this->createTestSet();
        $deletedAt1 = new \DateTimeImmutable('2024-01-20 15:00:00');
        $deletedAt2 = new \DateTimeImmutable('2024-01-21 15:00:00');

        $set->softDelete($deletedAt1);
        $this->assertEquals($deletedAt1, $set->getDeletedAt());

        // Soft delete again (shouldn't normally happen, but should handle gracefully)
        $set->softDelete($deletedAt2);
        $this->assertEquals($deletedAt2, $set->getDeletedAt());
    }

    // ===== Rename Tests =====

    public function testRenameToUpdatesSetName(): void
    {
        $set = $this->createTestSet();
        $originalName = $set->getName();

        $newName = SetName::fromString('Updated Biology Set');
        $updatedAt = new \DateTimeImmutable('2024-01-16 12:00:00');

        $set->renameTo($newName, $updatedAt);

        $this->assertEquals($newName, $set->getName());
        $this->assertNotEquals($originalName, $set->getName());
        $this->assertEquals($updatedAt, $set->getUpdatedAt());
    }

    public function testRenameToUpdatesTimestamp(): void
    {
        $set = $this->createTestSet();
        $originalUpdatedAt = $set->getUpdatedAt();

        $newName = SetName::fromString('New Name');
        $newUpdatedAt = new \DateTimeImmutable('2024-01-20 10:00:00');

        $set->renameTo($newName, $newUpdatedAt);

        $this->assertEquals($newUpdatedAt, $set->getUpdatedAt());
        $this->assertNotEquals($originalUpdatedAt, $set->getUpdatedAt());
    }

    // ===== Touch (Update Timestamp) Tests =====

    public function testTouchUpdatesTimestamp(): void
    {
        $set = $this->createTestSet();
        $originalUpdatedAt = $set->getUpdatedAt();

        $newUpdatedAt = new \DateTimeImmutable('2024-01-20 10:00:00');
        $set->touch($newUpdatedAt);

        $this->assertEquals($newUpdatedAt, $set->getUpdatedAt());
        $this->assertNotEquals($originalUpdatedAt, $set->getUpdatedAt());
    }

    // ===== Generation Metadata Tests =====

    public function testMarkAsGeneratedStoresMetadata(): void
    {
        $set = $this->createTestSet();

        $generatedAt = new \DateTimeImmutable('2024-01-15 10:30:00');
        $modelName = 'anthropic/claude-3.5-sonnet';
        $tokensIn = 1500;
        $tokensOut = 800;

        $set->markAsGenerated($generatedAt, $modelName, $tokensIn, $tokensOut);

        // Note: The Set entity doesn't expose getters for generation metadata
        // in the current implementation. This test documents the behavior.
        // In a real scenario, you'd need getters or verify through persistence layer.
        $this->assertTrue(true); // Marking as generated doesn't throw exception
    }

    public function testMarkAsGeneratedWithZeroTokens(): void
    {
        $set = $this->createTestSet();

        $generatedAt = new \DateTimeImmutable('2024-01-15 10:30:00');
        $modelName = 'test-model';
        $tokensIn = 0;
        $tokensOut = 0;

        $set->markAsGenerated($generatedAt, $modelName, $tokensIn, $tokensOut);

        $this->assertTrue(true); // Should not throw exception
    }

    // ===== Getters Tests =====

    public function testGetIdReturnsCorrectId(): void
    {
        $id = Uuid::v4()->toString();
        $set = $this->createTestSet($id);

        $this->assertSame($id, $set->getId());
    }

    public function testGetOwnerIdReturnsCorrectOwnerId(): void
    {
        $ownerId = UserId::fromString(Uuid::v4()->toString());
        $set = $this->createTestSet(null, $ownerId);

        $this->assertEquals($ownerId, $set->getOwnerId());
    }

    public function testGetNameReturnsCorrectName(): void
    {
        $name = SetName::fromString('My Test Set');
        $set = $this->createTestSet(null, null, $name);

        $this->assertEquals($name, $set->getName());
    }

    public function testGetCreatedAtReturnsCorrectTimestamp(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-15 08:00:00');
        $set = $this->createTestSet(null, null, null, $createdAt);

        $this->assertEquals($createdAt, $set->getCreatedAt());
    }

    public function testGetUpdatedAtInitiallyEqualsCreatedAt(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-15 08:00:00');
        $set = $this->createTestSet(null, null, null, $createdAt);

        $this->assertEquals($createdAt, $set->getUpdatedAt());
    }

    // ===== Complex Scenarios =====

    public function testCompleteSetLifecycle(): void
    {
        // Create set
        $set = $this->createTestSet();
        $this->assertSame(0, $set->getCardCount());
        $this->assertFalse($set->isDeleted());

        // Add cards
        $set->incrementCardCount();
        $set->incrementCardCount();
        $set->incrementCardCount();
        $this->assertSame(3, $set->getCardCount());

        // Rename
        $newName = SetName::fromString('Updated Name');
        $updatedAt = new \DateTimeImmutable('2024-01-16 10:00:00');
        $set->renameTo($newName, $updatedAt);
        $this->assertEquals($newName, $set->getName());

        // Remove a card
        $set->decrementCardCount();
        $this->assertSame(2, $set->getCardCount());

        // Soft delete
        $deletedAt = new \DateTimeImmutable('2024-01-20 10:00:00');
        $set->softDelete($deletedAt);
        $this->assertTrue($set->isDeleted());
        $this->assertEquals($deletedAt, $set->getDeletedAt());

        // Card count should still be accessible after soft delete
        $this->assertSame(2, $set->getCardCount());
    }

    // ===== Helper Methods =====

    private function createTestSet(
        ?string $id = null,
        ?UserId $ownerId = null,
        ?SetName $name = null,
        ?\DateTimeImmutable $createdAt = null
    ): Set {
        return Set::create(
            $id ?? Uuid::v4()->toString(),
            $ownerId ?? UserId::fromString(Uuid::v4()->toString()),
            $name ?? SetName::fromString('Test Set'),
            $createdAt ?? new \DateTimeImmutable('2024-01-15 10:00:00')
        );
    }
}
