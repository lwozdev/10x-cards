<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Handler;

use App\Application\Command\CreateSetCardDto;
use App\Application\Command\CreateSetCommand;
use App\Application\Handler\CreateSetHandler;
use App\Domain\Event\SetCreatedEvent;
use App\Domain\Exception\AiJobNotFoundException;
use App\Domain\Exception\DuplicateSetNameException;
use App\Domain\Model\AiJob;
use App\Domain\Model\CardOrigin;
use App\Domain\Repository\AiJobRepositoryInterface;
use App\Domain\Repository\CardRepositoryInterface;
use App\Domain\Repository\SetRepositoryInterface;
use App\Domain\Value\AiJobId;
use App\Domain\Value\CardBack;
use App\Domain\Value\CardFront;
use App\Domain\Value\SetName;
use App\Domain\Value\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for CreateSetHandler
 *
 * Tests business logic for creating flashcard sets with optional cards and AI job linkage.
 * Reference: test-plan.md Section 5.1 (AI-05), Section 5.3 (TC-EDIT-004)
 *
 * Priority: P0 (Critical)
 */
class CreateSetHandlerTest extends TestCase
{
    private SetRepositoryInterface&MockObject $setRepository;
    private CardRepositoryInterface&MockObject $cardRepository;
    private AiJobRepositoryInterface&MockObject $aiJobRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private CreateSetHandler $handler;

    protected function setUp(): void
    {
        $this->setRepository = $this->createMock(SetRepositoryInterface::class);
        $this->cardRepository = $this->createMock(CardRepositoryInterface::class);
        $this->aiJobRepository = $this->createMock(AiJobRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->handler = new CreateSetHandler(
            $this->setRepository,
            $this->cardRepository,
            $this->aiJobRepository,
            $this->eventDispatcher
        );
    }

    /**
     * Test: Create empty set (manual creation, no cards)
     */
    public function testCanCreateEmptySetWithoutCards(): void
    {
        // Arrange
        $userId = UserId::fromString(Uuid::v4()->toString());
        $setName = SetName::fromString('My Empty Set');

        $command = new CreateSetCommand(
            userId: $userId,
            name: $setName,
            cards: [], // No cards
            jobId: null // No AI job
        );

        $this->setRepository->expects($this->once())
            ->method('existsByOwnerAndName')
            ->with($userId, 'My Empty Set')
            ->willReturn(false);

        $this->setRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(\App\Domain\Model\Set::class));

        $this->cardRepository->expects($this->never())
            ->method('saveAll'); // No cards to save

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SetCreatedEvent::class));

        // Act
        $result = ($this->handler)($command);

        // Assert
        $this->assertSame('My Empty Set', $result->name);
        $this->assertSame(0, $result->cardCount);
    }

    /**
     * Test: Create set with manual cards
     */
    public function testCanCreateSetWithManualCards(): void
    {
        // Arrange
        $userId = UserId::fromString(Uuid::v4()->toString());
        $setName = SetName::fromString('Manual Set');

        $cards = [
            new CreateSetCardDto(
                front: CardFront::fromString('Question 1'),
                back: CardBack::fromString('Answer 1'),
                origin: CardOrigin::MANUAL,
                wasEdited: false
            ),
            new CreateSetCardDto(
                front: CardFront::fromString('Question 2'),
                back: CardBack::fromString('Answer 2'),
                origin: CardOrigin::MANUAL,
                wasEdited: false
            ),
        ];

        $command = new CreateSetCommand($userId, $setName, $cards, jobId: null);

        $this->setRepository->expects($this->once())
            ->method('existsByOwnerAndName')
            ->willReturn(false);

        $this->setRepository->expects($this->once())
            ->method('save');

        $this->cardRepository->expects($this->once())
            ->method('saveAll')
            ->with($this->callback(function ($cards) {
                return count($cards) === 2 &&
                       $cards[0] instanceof \App\Domain\Model\Card &&
                       $cards[1] instanceof \App\Domain\Model\Card;
            }));

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (SetCreatedEvent $event) {
                return $event->totalCardCount === 2 &&
                       $event->aiCardCount === 0 && // All manual
                       $event->editedAiCardCount === 0;
            }));

        // Act
        $result = ($this->handler)($command);

        // Assert
        $this->assertSame(2, $result->cardCount);
    }

    /**
     * Test: Create set with AI-generated cards (no edits)
     * TC-EDIT-004: Save AI-generated set
     */
    public function testCanCreateSetWithAiGeneratedCards(): void
    {
        // Arrange
        $userId = UserId::fromString(Uuid::v4()->toString());
        $jobId = AiJobId::fromString(Uuid::v4()->toString());
        $setName = SetName::fromString('AI Set');

        $cards = [
            new CreateSetCardDto(
                front: CardFront::fromString('AI Question 1'),
                back: CardBack::fromString('AI Answer 1'),
                origin: CardOrigin::AI,
                wasEdited: false
            ),
            new CreateSetCardDto(
                front: CardFront::fromString('AI Question 2'),
                back: CardBack::fromString('AI Answer 2'),
                origin: CardOrigin::AI,
                wasEdited: false
            ),
        ];

        $command = new CreateSetCommand($userId, $setName, $cards, $jobId);

        // Mock AI job exists
        $aiJob = AiJob::createSucceeded(
            Uuid::fromString($userId->toString()),
            'Source text',
            generatedCount: 5, // Generated 5, user keeps 2
            suggestedName: 'AI Set',
            modelName: 'claude-3.5-sonnet',
            tokensIn: 1000,
            tokensOut: 500
        );

        $this->setRepository->expects($this->once())
            ->method('existsByOwnerAndName')
            ->willReturn(false);

        $this->aiJobRepository->expects($this->once())
            ->method('findById')
            ->with($jobId->toString())
            ->willReturn($aiJob);

        $this->aiJobRepository->expects($this->once())
            ->method('save')
            ->with($aiJob);

        $this->setRepository->expects($this->once())
            ->method('save');

        $this->cardRepository->expects($this->once())
            ->method('saveAll');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (SetCreatedEvent $event) use ($jobId) {
                return $event->totalCardCount === 2 &&
                       $event->aiCardCount === 2 &&
                       $event->editedAiCardCount === 0 &&
                       $event->jobId === $jobId->toString();
            }));

        // Act
        $result = ($this->handler)($command);

        // Assert
        $this->assertSame(2, $result->cardCount);
        $this->assertNotNull($aiJob->getSetId());
        $this->assertSame(2, $aiJob->getAcceptedCount());
        $this->assertSame(0, $aiJob->getEditedCount());
    }

    /**
     * Test: Create set with AI cards (some edited)
     * TC-EDIT-004: Save with edited cards
     */
    public function testCanCreateSetWithEditedAiCards(): void
    {
        // Arrange
        $userId = UserId::fromString(Uuid::v4()->toString());
        $jobId = AiJobId::fromString(Uuid::v4()->toString());
        $setName = SetName::fromString('Edited AI Set');

        $cards = [
            new CreateSetCardDto(
                front: CardFront::fromString('Original AI Question'),
                back: CardBack::fromString('Original AI Answer'),
                origin: CardOrigin::AI,
                wasEdited: false // Not edited
            ),
            new CreateSetCardDto(
                front: CardFront::fromString('Edited AI Question'),
                back: CardBack::fromString('Edited AI Answer'),
                origin: CardOrigin::AI,
                wasEdited: true // User edited this
            ),
            new CreateSetCardDto(
                front: CardFront::fromString('Another edited'),
                back: CardBack::fromString('Another edited answer'),
                origin: CardOrigin::AI,
                wasEdited: true // User edited this
            ),
        ];

        $command = new CreateSetCommand($userId, $setName, $cards, $jobId);

        $aiJob = AiJob::createSucceeded(
            Uuid::fromString($userId->toString()),
            'Source text',
            generatedCount: 10,
            suggestedName: 'Test',
            modelName: 'claude-3.5-sonnet',
            tokensIn: 1000,
            tokensOut: 500
        );

        $this->aiJobRepository->expects($this->once())
            ->method('findById')
            ->willReturn($aiJob);

        $this->setRepository->expects($this->once())
            ->method('existsByOwnerAndName')
            ->willReturn(false);

        $this->aiJobRepository->expects($this->once())
            ->method('save')
            ->with($aiJob);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (SetCreatedEvent $event) {
                return $event->totalCardCount === 3 &&
                       $event->aiCardCount === 3 &&
                       $event->editedAiCardCount === 2; // 2 cards were edited
            }));

        // Act
        ($this->handler)($command);

        // Assert: Verify AI job KPI metrics
        $this->assertSame(3, $aiJob->getAcceptedCount());
        $this->assertSame(2, $aiJob->getEditedCount());
        $this->assertSame(7, $aiJob->getDeletedCount()); // 10 - 3 = 7 deleted
    }

    /**
     * Test: Throws exception when set name already exists
     */
    public function testThrowsExceptionWhenSetNameAlreadyExists(): void
    {
        // Arrange
        $userId = UserId::fromString(Uuid::v4()->toString());
        $setName = SetName::fromString('Duplicate Name');

        $command = new CreateSetCommand($userId, $setName, [], jobId: null);

        $this->setRepository->expects($this->once())
            ->method('existsByOwnerAndName')
            ->with($userId, 'Duplicate Name')
            ->willReturn(true); // Name already exists

        // Expect no save calls
        $this->setRepository->expects($this->never())->method('save');
        $this->cardRepository->expects($this->never())->method('saveAll');

        // Act & Assert
        $this->expectException(DuplicateSetNameException::class);
        $this->expectExceptionMessage('A set with the name "Duplicate Name" already exists');
        ($this->handler)($command);
    }

    /**
     * Test: Throws exception when AI job not found
     */
    public function testThrowsExceptionWhenAiJobNotFound(): void
    {
        // Arrange
        $userId = UserId::fromString(Uuid::v4()->toString());
        $jobId = AiJobId::fromString(Uuid::v4()->toString());
        $setName = SetName::fromString('Test Set');

        $command = new CreateSetCommand($userId, $setName, [], $jobId);

        $this->setRepository->expects($this->once())
            ->method('existsByOwnerAndName')
            ->willReturn(false);

        $this->aiJobRepository->expects($this->once())
            ->method('findById')
            ->with($jobId->toString())
            ->willReturn(null); // Job not found

        // Expect no save calls
        $this->setRepository->expects($this->never())->method('save');

        // Act & Assert
        $this->expectException(AiJobNotFoundException::class);
        $this->expectExceptionMessage('AI job with ID');
        ($this->handler)($command);
    }

    /**
     * Test: Mixed AI and manual cards in same set
     */
    public function testCanCreateSetWithMixedOriginCards(): void
    {
        // Arrange
        $userId = UserId::fromString(Uuid::v4()->toString());
        $setName = SetName::fromString('Mixed Set');

        $cards = [
            new CreateSetCardDto(
                front: CardFront::fromString('AI Card'),
                back: CardBack::fromString('AI Answer'),
                origin: CardOrigin::AI,
                wasEdited: false
            ),
            new CreateSetCardDto(
                front: CardFront::fromString('Manual Card'),
                back: CardBack::fromString('Manual Answer'),
                origin: CardOrigin::MANUAL,
                wasEdited: false
            ),
        ];

        $command = new CreateSetCommand($userId, $setName, $cards, jobId: null);

        $this->setRepository->expects($this->once())
            ->method('existsByOwnerAndName')
            ->willReturn(false);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (SetCreatedEvent $event) {
                return $event->totalCardCount === 2 &&
                       $event->aiCardCount === 1 && // Only 1 AI card
                       $event->editedAiCardCount === 0;
            }));

        // Act
        $result = ($this->handler)($command);

        // Assert
        $this->assertSame(2, $result->cardCount);
    }

    /**
     * Test: SetCreatedEvent contains correct data
     */
    public function testSetCreatedEventContainsCorrectData(): void
    {
        // Arrange
        $userId = UserId::fromString(Uuid::v4()->toString());
        $setName = SetName::fromString('Event Test Set');

        $command = new CreateSetCommand($userId, $setName, [], jobId: null);

        $this->setRepository->expects($this->once())
            ->method('existsByOwnerAndName')
            ->willReturn(false);

        $capturedEvent = null;
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (SetCreatedEvent $event) use (&$capturedEvent, $userId) {
                $capturedEvent = $event;
                return $event->userId === $userId->toString() &&
                       $event->totalCardCount === 0 &&
                       $event->aiCardCount === 0 &&
                       $event->editedAiCardCount === 0 &&
                       $event->jobId === null;
            }));

        // Act
        $result = ($this->handler)($command);

        // Assert
        $this->assertNotNull($capturedEvent);
        $this->assertSame($result->setId, $capturedEvent->setId);
    }

    /**
     * Test: Handler validates that job belongs to user (via RLS)
     * NOTE: RLS at database level ensures findById returns null for other user's jobs
     */
    public function testAiJobRlsValidation(): void
    {
        // Arrange
        $userId = UserId::fromString(Uuid::v4()->toString());
        $jobId = AiJobId::fromString(Uuid::v4()->toString());
        $setName = SetName::fromString('Test Set');

        $command = new CreateSetCommand($userId, $setName, [], $jobId);

        $this->setRepository->expects($this->once())
            ->method('existsByOwnerAndName')
            ->willReturn(false);

        // RLS in database will make findById return null for jobs not owned by user
        $this->aiJobRepository->expects($this->once())
            ->method('findById')
            ->with($jobId->toString())
            ->willReturn(null); // RLS filtered out the job

        // Act & Assert
        $this->expectException(AiJobNotFoundException::class);
        ($this->handler)($command);
    }
}
