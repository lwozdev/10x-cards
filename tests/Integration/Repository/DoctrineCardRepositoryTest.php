<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Domain\Model\Card;
use App\Domain\Model\CardOrigin;
use App\Domain\Model\Set;
use App\Domain\Model\User;
use App\Domain\Value\CardBack;
use App\Domain\Value\CardFront;
use App\Domain\Value\Email;
use App\Domain\Value\SetName;
use App\Domain\Value\UserId;
use App\Infrastructure\Doctrine\Repository\DoctrineCardRepository;
use App\Infrastructure\Doctrine\Repository\DoctrineSetRepository;
use App\Infrastructure\Doctrine\Repository\DoctrineUserRepository;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Integration tests for DoctrineCardRepository.
 *
 * Tests CRUD operations on cards, soft delete, and RLS filtering.
 * Reference: test-plan.md Section 5.3
 *
 * Priority: P0 (Critical)
 */
class DoctrineCardRepositoryTest extends KernelTestCase
{
    private DoctrineCardRepository $cardRepository;
    private DoctrineSetRepository $setRepository;
    private DoctrineUserRepository $userRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->cardRepository = self::getContainer()->get(DoctrineCardRepository::class);
        $this->setRepository = self::getContainer()->get(DoctrineSetRepository::class);
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
     * Helper: Create a test set.
     */
    private function createTestSet(UserId $userId, string $setName): Set
    {
        $set = Set::create(
            Uuid::v4()->toString(),
            $userId,
            SetName::fromString($setName),
            new \DateTimeImmutable()
        );
        $this->setRepository->save($set);

        return $set;
    }

    /**
     * Test: Create and save a card.
     */
    public function testCanCreateAndSaveCard(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $set = $this->createTestSet($userId, 'Test Set');

        $cardId = Uuid::v4()->toString();
        $front = CardFront::fromString('What is PHP?');
        $back = CardBack::fromString('A programming language');
        $now = new \DateTimeImmutable();

        $card = Card::create($cardId, $set->getId(), CardOrigin::MANUAL, $front, $back, $now);

        // Act
        $this->cardRepository->save($card);

        // Assert
        $foundCard = $this->cardRepository->findById($cardId);
        $this->assertNotNull($foundCard);
        $this->assertSame($cardId, $foundCard->getId());
        $this->assertSame($set->getId(), $foundCard->getSetId());
        $this->assertEquals(CardOrigin::MANUAL, $foundCard->getOrigin());
        $this->assertEquals($front, $foundCard->getFront());
        $this->assertEquals($back, $foundCard->getBack());
        $this->assertFalse($foundCard->isDeleted());
        $this->assertFalse($foundCard->wasEditedByUser());
    }

    /**
     * Test: Create card with wasEditedByUser flag.
     */
    public function testCanCreateCardWithEditedFlag(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $set = $this->createTestSet($userId, 'AI Set');

        $cardId = Uuid::v4()->toString();
        $front = CardFront::fromString('AI Question');
        $back = CardBack::fromString('AI Answer (edited)');
        $now = new \DateTimeImmutable();

        // Act: Create with edited flag
        $card = Card::create($cardId, $set->getId(), CardOrigin::AI, $front, $back, $now, wasEditedByUser: true);
        $this->cardRepository->save($card);

        // Assert
        $foundCard = $this->cardRepository->findById($cardId);
        $this->assertTrue($foundCard->wasEditedByUser());
        $this->assertNotNull($foundCard->getEditedByUserAt());
    }

    /**
     * Test: Find active cards by set ID.
     */
    public function testFindActiveBySetIdReturnsOnlyNonDeletedCards(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $set = $this->createTestSet($userId, 'Test Set');
        $now = new \DateTimeImmutable();

        $card1 = Card::create(
            Uuid::v4()->toString(),
            $set->getId(),
            CardOrigin::MANUAL,
            CardFront::fromString('Question 1'),
            CardBack::fromString('Answer 1'),
            $now
        );

        $card2 = Card::create(
            Uuid::v4()->toString(),
            $set->getId(),
            CardOrigin::MANUAL,
            CardFront::fromString('Question 2'),
            CardBack::fromString('Answer 2'),
            $now
        );

        $card3 = Card::create(
            Uuid::v4()->toString(),
            $set->getId(),
            CardOrigin::MANUAL,
            CardFront::fromString('Question 3 (to delete)'),
            CardBack::fromString('Answer 3'),
            $now
        );

        $this->cardRepository->save($card1);
        $this->cardRepository->save($card2);
        $this->cardRepository->save($card3);

        // Act: Soft delete card3
        $this->cardRepository->softDelete($card3);

        // Assert
        $activeCards = $this->cardRepository->findActiveBySetId($set->getId());

        $this->assertCount(2, $activeCards);
        $cardIds = array_map(fn (Card $c) => $c->getId(), $activeCards);
        $this->assertContains($card1->getId(), $cardIds);
        $this->assertContains($card2->getId(), $cardIds);
        $this->assertNotContains($card3->getId(), $cardIds);
    }

    /**
     * Test: Soft delete sets deletedAt timestamp.
     */
    public function testSoftDeleteSetsDeletedAtTimestamp(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $set = $this->createTestSet($userId, 'Test Set');
        $now = new \DateTimeImmutable();

        $card = Card::create(
            Uuid::v4()->toString(),
            $set->getId(),
            CardOrigin::MANUAL,
            CardFront::fromString('To Delete'),
            CardBack::fromString('Will be deleted'),
            $now
        );

        $this->cardRepository->save($card);
        $this->assertFalse($card->isDeleted());

        // Act
        $this->cardRepository->softDelete($card);

        // Assert
        $this->assertTrue($card->isDeleted());
        $this->assertNotNull($card->getDeletedAt());

        // Verify persistence
        $foundCard = $this->cardRepository->findById($card->getId());
        $this->assertTrue($foundCard->isDeleted());
    }

    /**
     * Test: Count active cards by set ID.
     */
    public function testCountActiveBySetIdExcludesDeletedCards(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $set = $this->createTestSet($userId, 'Test Set');
        $now = new \DateTimeImmutable();

        // Create 3 cards
        for ($i = 1; $i <= 3; ++$i) {
            $card = Card::create(
                Uuid::v4()->toString(),
                $set->getId(),
                CardOrigin::MANUAL,
                CardFront::fromString("Question $i"),
                CardBack::fromString("Answer $i"),
                $now
            );
            $this->cardRepository->save($card);
        }

        // Act & Assert: Count before deletion
        $countBefore = $this->cardRepository->countActiveBySetId($set->getId());
        $this->assertSame(3, $countBefore);

        // Delete one card
        $cards = $this->cardRepository->findActiveBySetId($set->getId());
        $this->cardRepository->softDelete($cards[0]);

        // Act & Assert: Count after deletion
        $countAfter = $this->cardRepository->countActiveBySetId($set->getId());
        $this->assertSame(2, $countAfter);
    }

    /**
     * Test: saveAll persists multiple cards in batch.
     */
    public function testSaveAllPersistsMultipleCards(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $set = $this->createTestSet($userId, 'Batch Set');
        $now = new \DateTimeImmutable();

        $cards = [];
        for ($i = 1; $i <= 5; ++$i) {
            $cards[] = Card::create(
                Uuid::v4()->toString(),
                $set->getId(),
                CardOrigin::AI,
                CardFront::fromString("AI Question $i"),
                CardBack::fromString("AI Answer $i"),
                $now
            );
        }

        // Act
        $this->cardRepository->saveAll($cards);

        // Assert
        $savedCards = $this->cardRepository->findActiveBySetId($set->getId());
        $this->assertCount(5, $savedCards);
    }

    /**
     * Test: Edit card updates front, back, and editedByUserAt.
     */
    public function testEditCardUpdatesFieldsAndTimestamp(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $set = $this->createTestSet($userId, 'Test Set');
        $now = new \DateTimeImmutable();

        $card = Card::create(
            Uuid::v4()->toString(),
            $set->getId(),
            CardOrigin::AI,
            CardFront::fromString('Original Question'),
            CardBack::fromString('Original Answer'),
            $now,
            wasEditedByUser: false
        );

        $this->cardRepository->save($card);
        $this->assertFalse($card->wasEditedByUser());

        // Act: Edit the card
        $editTime = $now->modify('+1 hour');
        $newFront = CardFront::fromString('Edited Question');
        $newBack = CardBack::fromString('Edited Answer');

        $card->editFrontBack($newFront, $newBack, $editTime);
        $this->cardRepository->save($card);

        // Assert
        $foundCard = $this->cardRepository->findById($card->getId());
        $this->assertEquals($newFront, $foundCard->getFront());
        $this->assertEquals($newBack, $foundCard->getBack());
        $this->assertTrue($foundCard->wasEditedByUser());
        $this->assertEquals($editTime, $foundCard->getEditedByUserAt());
    }

    /**
     * Test: findActiveBySetId orders by createdAt ASC.
     */
    public function testFindActiveBySetIdOrdersByCreatedAtAsc(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $set = $this->createTestSet($userId, 'Test Set');
        $baseTime = new \DateTimeImmutable('2025-01-01 10:00:00');

        $card1 = Card::create(
            Uuid::v4()->toString(),
            $set->getId(),
            CardOrigin::MANUAL,
            CardFront::fromString('First Card'),
            CardBack::fromString('Answer 1'),
            $baseTime
        );

        $card2 = Card::create(
            Uuid::v4()->toString(),
            $set->getId(),
            CardOrigin::MANUAL,
            CardFront::fromString('Second Card'),
            CardBack::fromString('Answer 2'),
            $baseTime->modify('+1 minute')
        );

        $card3 = Card::create(
            Uuid::v4()->toString(),
            $set->getId(),
            CardOrigin::MANUAL,
            CardFront::fromString('Third Card'),
            CardBack::fromString('Answer 3'),
            $baseTime->modify('+2 minutes')
        );

        $this->cardRepository->save($card1);
        $this->cardRepository->save($card2);
        $this->cardRepository->save($card3);

        // Act
        $cards = $this->cardRepository->findActiveBySetId($set->getId());

        // Assert: Oldest first
        $this->assertSame($card1->getId(), $cards[0]->getId());
        $this->assertSame($card2->getId(), $cards[1]->getId());
        $this->assertSame($card3->getId(), $cards[2]->getId());
    }

    /**
     * Test: findById returns null for non-existent ID.
     */
    public function testFindByIdReturnsNullForNonExistentId(): void
    {
        $result = $this->cardRepository->findById(Uuid::v4()->toString());
        $this->assertNull($result);
    }

    /**
     * Test: Cards from different sets are isolated.
     */
    public function testCardsFromDifferentSetsAreIsolated(): void
    {
        // Arrange
        $userId = $this->createTestUser();
        $set1 = $this->createTestSet($userId, 'Set 1');
        $set2 = $this->createTestSet($userId, 'Set 2');
        $now = new \DateTimeImmutable();

        $card1 = Card::create(
            Uuid::v4()->toString(),
            $set1->getId(),
            CardOrigin::MANUAL,
            CardFront::fromString('Set 1 Card'),
            CardBack::fromString('Answer'),
            $now
        );

        $card2 = Card::create(
            Uuid::v4()->toString(),
            $set2->getId(),
            CardOrigin::MANUAL,
            CardFront::fromString('Set 2 Card'),
            CardBack::fromString('Answer'),
            $now
        );

        $this->cardRepository->save($card1);
        $this->cardRepository->save($card2);

        // Act
        $set1Cards = $this->cardRepository->findActiveBySetId($set1->getId());
        $set2Cards = $this->cardRepository->findActiveBySetId($set2->getId());

        // Assert
        $this->assertCount(1, $set1Cards);
        $this->assertCount(1, $set2Cards);
        $this->assertSame($card1->getId(), $set1Cards[0]->getId());
        $this->assertSame($card2->getId(), $set2Cards[0]->getId());
    }

    /**
     * RLS Test: User cannot access cards from another user's set.
     */
    #[Group('rls')]
    #[Group('incomplete')]
    public function testRlsUserCannotAccessAnotherUsersCards(): void
    {
        $this->markTestIncomplete(
            'RLS (Row-Level Security) not yet implemented. '.
            'When implemented, this test should verify that findActiveBySetId '.
            'and findById return no results when trying to access cards from '.
            'another user\'s set.'
        );

        // Future implementation:
        // 1. User A creates a Set with Cards
        // 2. Authenticate as User B (set current_app_user())
        // 3. Try to findActiveBySetId(Set A's ID)
        // 4. Assert: Returns empty array (filtered by RLS)
        // 5. Try to findById(Card A's ID)
        // 6. Assert: Returns null (filtered by RLS)
    }
}
