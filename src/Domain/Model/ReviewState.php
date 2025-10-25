<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Value\UserId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'review_states')]
#[ORM\Index(name: 'review_states_due', columns: ['user_id', 'due_at'])]
class ReviewState
{
    #[ORM\Id]
    #[ORM\Column(name: 'user_id', type: 'guid')]
    private string $userId;

    #[ORM\Id]
    #[ORM\Column(name: 'card_id', type: 'guid')]
    private string $cardId;

    #[ORM\Column(name: 'due_at', type: 'datetime_immutable')]
    private DateTimeImmutable $dueAt;

    #[ORM\Column(type: 'decimal', precision: 4, scale: 2)]
    private string $ease;

    #[ORM\Column(name: 'interval_days', type: 'integer')]
    private int $intervalDays;

    #[ORM\Column(type: 'integer')]
    private int $reps;

    #[ORM\Column(name: 'last_grade', type: 'smallint', nullable: true)]
    private ?int $lastGrade = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        UserId $userId,
        string $cardId,
        DateTimeImmutable $dueAt,
        float $ease = 2.50,
        int $intervalDays = 0,
        int $reps = 0
    ) {
        $this->userId = $userId->toString();
        $this->cardId = $cardId;
        $this->dueAt = $dueAt;
        $this->ease = number_format($ease, 2, '.', '');
        $this->intervalDays = $intervalDays;
        $this->reps = $reps;
        $this->updatedAt = $dueAt;
    }

    public static function initialize(
        UserId $userId,
        string $cardId,
        DateTimeImmutable $dueAt
    ): self {
        return new self($userId, $cardId, $dueAt);
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }

    public function getCardId(): string
    {
        return $this->cardId;
    }

    public function getDueAt(): DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function getEase(): float
    {
        return (float) $this->ease;
    }

    public function getIntervalDays(): int
    {
        return $this->intervalDays;
    }

    public function getReps(): int
    {
        return $this->reps;
    }

    public function getLastGrade(): ?int
    {
        return $this->lastGrade;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isDue(DateTimeImmutable $now): bool
    {
        return $this->dueAt <= $now;
    }

    /**
     * Update review state after answering
     *
     * @param int $grade 0 = "Don't know", 1 = "Know"
     */
    public function updateAfterReview(
        int $grade,
        DateTimeImmutable $nextDueAt,
        float $newEase,
        int $newIntervalDays,
        DateTimeImmutable $updatedAt
    ): void {
        if ($grade < 0 || $grade > 1) {
            throw new \InvalidArgumentException('Grade must be 0 or 1');
        }

        $this->lastGrade = $grade;
        $this->dueAt = $nextDueAt;
        $this->ease = number_format($newEase, 2, '.', '');
        $this->intervalDays = $newIntervalDays;
        $this->reps++;
        $this->updatedAt = $updatedAt;
    }
}
