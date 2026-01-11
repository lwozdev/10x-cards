<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Model;

use App\Domain\Model\AnalyticsEvent;
use App\Domain\Value\UserId;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for AnalyticsEvent Domain Model
 *
 * Business rules:
 * - Event type cannot be empty
 * - Supports optional set and card associations
 * - Stores flexible payload as JSON
 */
final class AnalyticsEventTest extends TestCase
{
    // ===== Creation Tests =====

    public function testCreateEventWithRequiredFields(): void
    {
        $eventType = 'card_reviewed';
        $userId = UserId::fromString(Uuid::v4()->toString());
        $payload = ['grade' => 1, 'duration_ms' => 2500];
        $occurredAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $event = AnalyticsEvent::create($eventType, $userId, $payload, $occurredAt);

        $this->assertInstanceOf(AnalyticsEvent::class, $event);
        $this->assertSame($eventType, $event->getEventType());
        $this->assertEquals($userId, $event->getUserId());
        $this->assertSame($payload, $event->getPayload());
        $this->assertEquals($occurredAt, $event->getOccurredAt());
        $this->assertNull($event->getSetId());
        $this->assertNull($event->getCardId());
    }

    public function testCreateEventWithSetIdAndCardId(): void
    {
        $eventType = 'card_deleted_in_edit';
        $userId = UserId::fromString(Uuid::v4()->toString());
        $payload = ['reason' => 'duplicate'];
        $occurredAt = new \DateTimeImmutable('2024-01-15 10:00:00');
        $setId = Uuid::v4()->toString();
        $cardId = Uuid::v4()->toString();

        $event = AnalyticsEvent::create(
            $eventType,
            $userId,
            $payload,
            $occurredAt,
            $setId,
            $cardId
        );

        $this->assertSame($setId, $event->getSetId());
        $this->assertSame($cardId, $event->getCardId());
    }

    public function testCreateEventWithOnlySetId(): void
    {
        $eventType = 'set_created';
        $userId = UserId::fromString(Uuid::v4()->toString());
        $payload = ['source' => 'ai'];
        $occurredAt = new \DateTimeImmutable('2024-01-15 10:00:00');
        $setId = Uuid::v4()->toString();

        $event = AnalyticsEvent::create(
            $eventType,
            $userId,
            $payload,
            $occurredAt,
            $setId,
            null
        );

        $this->assertSame($setId, $event->getSetId());
        $this->assertNull($event->getCardId());
    }

    public function testCreateEventWithOnlyCardId(): void
    {
        $eventType = 'card_viewed';
        $userId = UserId::fromString(Uuid::v4()->toString());
        $payload = ['duration_ms' => 1500];
        $occurredAt = new \DateTimeImmutable('2024-01-15 10:00:00');
        $cardId = Uuid::v4()->toString();

        $event = AnalyticsEvent::create(
            $eventType,
            $userId,
            $payload,
            $occurredAt,
            null,
            $cardId
        );

        $this->assertNull($event->getSetId());
        $this->assertSame($cardId, $event->getCardId());
    }

    // ===== Validation Tests =====

    public function testCreateEventThrowsExceptionForEmptyEventType(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $payload = [];
        $occurredAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Event type cannot be empty');

        AnalyticsEvent::create('', $userId, $payload, $occurredAt);
    }

    #[DataProvider('validEventTypesProvider')]
    public function testCreateEventAcceptsVariousEventTypes(string $eventType): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $payload = [];
        $occurredAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $event = AnalyticsEvent::create($eventType, $userId, $payload, $occurredAt);

        $this->assertSame($eventType, $event->getEventType());
    }

    public static function validEventTypesProvider(): array
    {
        return [
            'card_reviewed' => ['card_reviewed'],
            'card_deleted_in_edit' => ['card_deleted_in_edit'],
            'set_created' => ['set_created'],
            'set_deleted' => ['set_deleted'],
            'flashcard_generated' => ['flashcard_generated'],
            'learning_session_started' => ['learning_session_started'],
            'learning_session_completed' => ['learning_session_completed'],
        ];
    }

    // ===== Payload Tests =====

    public function testPayloadCanBeEmptyArray(): void
    {
        $eventType = 'simple_event';
        $userId = UserId::fromString(Uuid::v4()->toString());
        $payload = [];
        $occurredAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $event = AnalyticsEvent::create($eventType, $userId, $payload, $occurredAt);

        $this->assertSame([], $event->getPayload());
    }

    public function testPayloadCanContainMixedData(): void
    {
        $eventType = 'complex_event';
        $userId = UserId::fromString(Uuid::v4()->toString());
        $payload = [
            'string_value' => 'test',
            'int_value' => 42,
            'float_value' => 3.14,
            'bool_value' => true,
            'array_value' => ['nested', 'array'],
            'null_value' => null,
        ];
        $occurredAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $event = AnalyticsEvent::create($eventType, $userId, $payload, $occurredAt);

        $this->assertSame($payload, $event->getPayload());
    }

    public function testPayloadCanContainNestedStructures(): void
    {
        $eventType = 'nested_event';
        $userId = UserId::fromString(Uuid::v4()->toString());
        $payload = [
            'level1' => [
                'level2' => [
                    'level3' => 'deep value',
                ],
            ],
        ];
        $occurredAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $event = AnalyticsEvent::create($eventType, $userId, $payload, $occurredAt);

        $this->assertSame($payload, $event->getPayload());
    }

    // ===== Common Analytics Events Tests =====

    public function testCardDeletedInEditEvent(): void
    {
        $eventType = 'card_deleted_in_edit';
        $userId = UserId::fromString(Uuid::v4()->toString());
        $setId = Uuid::v4()->toString();
        $cardId = Uuid::v4()->toString();
        $payload = [
            'reason' => 'user_action',
            'card_position' => 3,
        ];
        $occurredAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $event = AnalyticsEvent::create(
            $eventType,
            $userId,
            $payload,
            $occurredAt,
            $setId,
            $cardId
        );

        $this->assertSame('card_deleted_in_edit', $event->getEventType());
        $this->assertSame($setId, $event->getSetId());
        $this->assertSame($cardId, $event->getCardId());
        $this->assertArrayHasKey('reason', $event->getPayload());
    }

    public function testSetCreatedEvent(): void
    {
        $eventType = 'set_created';
        $userId = UserId::fromString(Uuid::v4()->toString());
        $setId = Uuid::v4()->toString();
        $payload = [
            'source' => 'ai',
            'card_count' => 10,
            'model_used' => 'anthropic/claude-3.5-sonnet',
        ];
        $occurredAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $event = AnalyticsEvent::create(
            $eventType,
            $userId,
            $payload,
            $occurredAt,
            $setId
        );

        $this->assertSame('set_created', $event->getEventType());
        $this->assertSame($setId, $event->getSetId());
        $this->assertNull($event->getCardId());
        $this->assertSame('ai', $event->getPayload()['source']);
    }

    public function testCardReviewedEvent(): void
    {
        $eventType = 'card_reviewed';
        $userId = UserId::fromString(Uuid::v4()->toString());
        $setId = Uuid::v4()->toString();
        $cardId = Uuid::v4()->toString();
        $payload = [
            'grade' => 1,
            'duration_ms' => 2500,
            'new_interval_days' => 3,
        ];
        $occurredAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $event = AnalyticsEvent::create(
            $eventType,
            $userId,
            $payload,
            $occurredAt,
            $setId,
            $cardId
        );

        $this->assertSame('card_reviewed', $event->getEventType());
        $this->assertSame(1, $event->getPayload()['grade']);
        $this->assertSame(2500, $event->getPayload()['duration_ms']);
    }

    // ===== Getters Tests =====

    public function testGetIdReturnsNullBeforePersistence(): void
    {
        $event = AnalyticsEvent::create(
            'test_event',
            UserId::fromString(Uuid::v4()->toString()),
            [],
            new \DateTimeImmutable()
        );

        // ID is auto-generated by database (IDENTITY strategy)
        $this->assertNull($event->getId());
    }

    public function testGetUserIdReturnsCorrectUserId(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $event = AnalyticsEvent::create(
            'test_event',
            $userId,
            [],
            new \DateTimeImmutable()
        );

        $this->assertEquals($userId, $event->getUserId());
    }

    public function testGetOccurredAtReturnsCorrectTimestamp(): void
    {
        $occurredAt = new \DateTimeImmutable('2024-01-15 14:30:00');
        $event = AnalyticsEvent::create(
            'test_event',
            UserId::fromString(Uuid::v4()->toString()),
            [],
            $occurredAt
        );

        $this->assertEquals($occurredAt, $event->getOccurredAt());
    }

    // ===== Edge Cases =====

    public function testCreateMultipleEventsForSameUser(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $occurredAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $event1 = AnalyticsEvent::create('event_1', $userId, ['data' => 1], $occurredAt);
        $event2 = AnalyticsEvent::create('event_2', $userId, ['data' => 2], $occurredAt);
        $event3 = AnalyticsEvent::create('event_3', $userId, ['data' => 3], $occurredAt);

        $this->assertSame('event_1', $event1->getEventType());
        $this->assertSame('event_2', $event2->getEventType());
        $this->assertSame('event_3', $event3->getEventType());
    }

    public function testPayloadWithLargeDataStructure(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $largePayload = [
            'cards' => array_fill(0, 100, ['front' => 'Question', 'back' => 'Answer']),
            'metadata' => [
                'total_count' => 100,
                'processing_time_ms' => 5000,
            ],
        ];

        $event = AnalyticsEvent::create(
            'bulk_operation',
            $userId,
            $largePayload,
            new \DateTimeImmutable()
        );

        $this->assertCount(100, $event->getPayload()['cards']);
    }

    public function testEventTypeWithSpecialCharacters(): void
    {
        $eventType = 'user:action:completed';
        $userId = UserId::fromString(Uuid::v4()->toString());

        $event = AnalyticsEvent::create(
            $eventType,
            $userId,
            [],
            new \DateTimeImmutable()
        );

        $this->assertSame('user:action:completed', $event->getEventType());
    }
}
