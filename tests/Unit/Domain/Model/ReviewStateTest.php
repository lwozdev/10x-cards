<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Model;

use App\Domain\Model\ReviewState;
use App\Domain\Value\UserId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for ReviewState Domain Model.
 *
 * Critical business logic:
 * - Spaced repetition algorithm (SM-2 based)
 * - isDue calculation for scheduling reviews
 * - State updates after user responses (Know/Don't Know)
 */
final class ReviewStateTest extends TestCase
{
    // ===== Initialization Tests =====

    public function testInitializeCreatesNewReviewState(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        $this->assertInstanceOf(ReviewState::class, $state);
        $this->assertEquals($userId, $state->getUserId());
        $this->assertSame($cardId, $state->getCardId());
        $this->assertEquals($dueAt, $state->getDueAt());
        $this->assertSame(2.50, $state->getEase()); // Default ease factor
        $this->assertSame(0, $state->getIntervalDays());
        $this->assertSame(0, $state->getReps());
        $this->assertNull($state->getLastGrade());
    }

    public function testInitializeWithDefaultValues(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable();

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        // SM-2 default values
        $this->assertSame(2.50, $state->getEase());
        $this->assertSame(0, $state->getIntervalDays());
        $this->assertSame(0, $state->getReps());
    }

    // ===== isDue Tests (Critical for Review Scheduling) =====

    public function testIsDueReturnsTrueWhenDueAtEqualsNow(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        $now = new \DateTimeImmutable('2024-01-15 10:00:00');
        $this->assertTrue($state->isDue($now));
    }

    public function testIsDueReturnsTrueWhenDueAtBeforeNow(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        $now = new \DateTimeImmutable('2024-01-16 10:00:00'); // 1 day later
        $this->assertTrue($state->isDue($now));
    }

    public function testIsDueReturnsFalseWhenDueAtAfterNow(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        $now = new \DateTimeImmutable('2024-01-14 10:00:00'); // 1 day before
        $this->assertFalse($state->isDue($now));
    }

    #[DataProvider('isDueScenarioProvider')]
    public function testIsDueWithVariousScenarios(
        string $dueAtString,
        string $nowString,
        bool $expectedIsDue,
    ): void {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable($dueAtString);

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        $now = new \DateTimeImmutable($nowString);
        $this->assertSame($expectedIsDue, $state->isDue($now));
    }

    public static function isDueScenarioProvider(): array
    {
        return [
            'exact_match' => ['2024-01-15 10:00:00', '2024-01-15 10:00:00', true],
            'one_second_overdue' => ['2024-01-15 10:00:00', '2024-01-15 10:00:01', true],
            'one_hour_overdue' => ['2024-01-15 10:00:00', '2024-01-15 11:00:00', true],
            'one_day_overdue' => ['2024-01-15 10:00:00', '2024-01-16 10:00:00', true],
            'one_week_overdue' => ['2024-01-15 10:00:00', '2024-01-22 10:00:00', true],
            'one_second_early' => ['2024-01-15 10:00:00', '2024-01-15 09:59:59', false],
            'one_hour_early' => ['2024-01-15 10:00:00', '2024-01-15 09:00:00', false],
            'one_day_early' => ['2024-01-15 10:00:00', '2024-01-14 10:00:00', false],
        ];
    }

    // ===== updateAfterReview Tests (Spaced Repetition Logic) =====

    public function testUpdateAfterReviewWithGradeKnow(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $initialDueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $initialDueAt);

        $nextDueAt = new \DateTimeImmutable('2024-01-16 10:00:00');
        $newEase = 2.60;
        $newInterval = 1;
        $updatedAt = new \DateTimeImmutable('2024-01-15 10:05:00');

        $state->updateAfterReview(1, $nextDueAt, $newEase, $newInterval, $updatedAt);

        $this->assertSame(1, $state->getLastGrade());
        $this->assertEquals($nextDueAt, $state->getDueAt());
        $this->assertSame(2.60, $state->getEase());
        $this->assertSame(1, $state->getIntervalDays());
        $this->assertSame(1, $state->getReps()); // Incremented from 0 to 1
        $this->assertEquals($updatedAt, $state->getUpdatedAt());
    }

    public function testUpdateAfterReviewWithGradeDontKnow(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $initialDueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $initialDueAt);

        $nextDueAt = new \DateTimeImmutable('2024-01-15 10:10:00'); // Review again soon
        $newEase = 2.20; // Ease decreases for wrong answers
        $newInterval = 0; // Reset interval
        $updatedAt = new \DateTimeImmutable('2024-01-15 10:05:00');

        $state->updateAfterReview(0, $nextDueAt, $newEase, $newInterval, $updatedAt);

        $this->assertSame(0, $state->getLastGrade());
        $this->assertEquals($nextDueAt, $state->getDueAt());
        $this->assertSame(2.20, $state->getEase());
        $this->assertSame(0, $state->getIntervalDays());
        $this->assertSame(1, $state->getReps()); // Incremented
    }

    public function testUpdateAfterReviewIncrementsRepsCorrectly(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        $this->assertSame(0, $state->getReps());

        // First review
        $state->updateAfterReview(
            1,
            new \DateTimeImmutable('2024-01-16 10:00:00'),
            2.60,
            1,
            new \DateTimeImmutable('2024-01-15 10:05:00')
        );
        $this->assertSame(1, $state->getReps());

        // Second review
        $state->updateAfterReview(
            1,
            new \DateTimeImmutable('2024-01-18 10:00:00'),
            2.70,
            3,
            new \DateTimeImmutable('2024-01-16 10:05:00')
        );
        $this->assertSame(2, $state->getReps());

        // Third review
        $state->updateAfterReview(
            0,
            new \DateTimeImmutable('2024-01-19 10:00:00'),
            2.50,
            0,
            new \DateTimeImmutable('2024-01-18 10:05:00')
        );
        $this->assertSame(3, $state->getReps());
    }

    public function testUpdateAfterReviewThrowsExceptionForInvalidGrade(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Grade must be 0 or 1');

        $state->updateAfterReview(
            2, // Invalid grade
            new \DateTimeImmutable('2024-01-16 10:00:00'),
            2.60,
            1,
            new \DateTimeImmutable('2024-01-15 10:05:00')
        );
    }

    public function testUpdateAfterReviewThrowsExceptionForNegativeGrade(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Grade must be 0 or 1');

        $state->updateAfterReview(
            -1, // Invalid grade
            new \DateTimeImmutable('2024-01-16 10:00:00'),
            2.60,
            1,
            new \DateTimeImmutable('2024-01-15 10:05:00')
        );
    }

    #[DataProvider('validGradesProvider')]
    public function testUpdateAfterReviewAcceptsValidGrades(int $grade): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        $state->updateAfterReview(
            $grade,
            new \DateTimeImmutable('2024-01-16 10:00:00'),
            2.60,
            1,
            new \DateTimeImmutable('2024-01-15 10:05:00')
        );

        $this->assertSame($grade, $state->getLastGrade());
    }

    public static function validGradesProvider(): array
    {
        return [
            'grade_0_dont_know' => [0],
            'grade_1_know' => [1],
        ];
    }

    // ===== Ease Factor Tests =====

    public function testEaseFactorIsStoredWithTwoDecimalPlaces(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        $state->updateAfterReview(
            1,
            new \DateTimeImmutable('2024-01-16 10:00:00'),
            2.666666, // More than 2 decimal places
            1,
            new \DateTimeImmutable('2024-01-15 10:05:00')
        );

        // Should be rounded to 2 decimal places
        $this->assertSame(2.67, $state->getEase());
    }

    public function testEaseFactorHandlesLowValues(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        $state->updateAfterReview(
            0,
            new \DateTimeImmutable('2024-01-16 10:00:00'),
            1.30, // Minimum ease in SM-2 is typically 1.3
            0,
            new \DateTimeImmutable('2024-01-15 10:05:00')
        );

        $this->assertSame(1.30, $state->getEase());
    }

    public function testEaseFactorHandlesHighValues(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        $state->updateAfterReview(
            1,
            new \DateTimeImmutable('2024-01-16 10:00:00'),
            5.00, // Very high ease
            10,
            new \DateTimeImmutable('2024-01-15 10:05:00')
        );

        $this->assertSame(5.00, $state->getEase());
    }

    // ===== Interval Tests =====

    public function testIntervalDaysCanBeZero(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        $state->updateAfterReview(
            0,
            new \DateTimeImmutable('2024-01-15 10:10:00'),
            2.20,
            0,
            new \DateTimeImmutable('2024-01-15 10:05:00')
        );

        $this->assertSame(0, $state->getIntervalDays());
    }

    public function testIntervalDaysIncreasesProperly(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        // First review: interval 1 day
        $state->updateAfterReview(
            1,
            new \DateTimeImmutable('2024-01-16 10:00:00'),
            2.60,
            1,
            new \DateTimeImmutable('2024-01-15 10:05:00')
        );
        $this->assertSame(1, $state->getIntervalDays());

        // Second review: interval 3 days
        $state->updateAfterReview(
            1,
            new \DateTimeImmutable('2024-01-19 10:00:00'),
            2.70,
            3,
            new \DateTimeImmutable('2024-01-16 10:05:00')
        );
        $this->assertSame(3, $state->getIntervalDays());
    }

    // ===== Timestamp Tests =====

    public function testUpdatedAtIsSetOnInitialization(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        $this->assertEquals($dueAt, $state->getUpdatedAt());
    }

    public function testUpdatedAtChangesAfterReview(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        $originalUpdatedAt = $state->getUpdatedAt();
        $newUpdatedAt = new \DateTimeImmutable('2024-01-15 10:10:00');

        $state->updateAfterReview(
            1,
            new \DateTimeImmutable('2024-01-16 10:00:00'),
            2.60,
            1,
            $newUpdatedAt
        );

        $this->assertEquals($newUpdatedAt, $state->getUpdatedAt());
        $this->assertNotEquals($originalUpdatedAt, $state->getUpdatedAt());
    }

    // ===== Edge Cases =====

    public function testMultipleReviewsCumulativeEffect(): void
    {
        $userId = UserId::fromString(Uuid::v4()->toString());
        $cardId = Uuid::v4()->toString();
        $dueAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $state = ReviewState::initialize($userId, $cardId, $dueAt);

        // Simulate 5 successful reviews
        for ($i = 1; $i <= 5; ++$i) {
            $state->updateAfterReview(
                1,
                (new \DateTimeImmutable('2024-01-15 10:00:00'))->modify("+{$i} days"),
                2.50 + ($i * 0.1),
                $i,
                new \DateTimeImmutable('2024-01-15 10:05:00')
            );
        }

        $this->assertSame(5, $state->getReps());
        $this->assertSame(3.00, $state->getEase()); // 2.50 + (5 * 0.1)
        $this->assertSame(5, $state->getIntervalDays());
    }
}
