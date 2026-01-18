<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Value\UserId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'review_events')]
#[ORM\Index(name: 'review_events_user_time', columns: ['user_id', 'answered_at'])]
#[ORM\Index(name: 'review_events_card_time', columns: ['card_id', 'answered_at'])]
class ReviewEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', type: 'guid')]
    private string $userId;

    #[ORM\Column(name: 'card_id', type: 'guid', nullable: true)]
    private ?string $cardId;

    #[ORM\Column(name: 'answered_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $answeredAt;

    #[ORM\Column(type: 'smallint')]
    private int $grade;

    #[ORM\Column(name: 'duration_ms', type: 'integer', nullable: true)]
    private ?int $durationMs = null;

    private function __construct(
        UserId $userId,
        ?string $cardId,
        \DateTimeImmutable $answeredAt,
        int $grade,
        ?int $durationMs = null,
    ) {
        if ($grade < 0 || $grade > 1) {
            throw new \InvalidArgumentException('Grade must be 0 (Don\'t know) or 1 (Know)');
        }

        $this->userId = $userId->toString();
        $this->cardId = $cardId;
        $this->answeredAt = $answeredAt;
        $this->grade = $grade;
        $this->durationMs = $durationMs;
    }

    public static function record(
        UserId $userId,
        string $cardId,
        \DateTimeImmutable $answeredAt,
        int $grade,
        ?int $durationMs = null,
    ): self {
        return new self($userId, $cardId, $answeredAt, $grade, $durationMs);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }

    public function getCardId(): ?string
    {
        return $this->cardId;
    }

    public function getAnsweredAt(): \DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function getGrade(): int
    {
        return $this->grade;
    }

    public function getDurationMs(): ?int
    {
        return $this->durationMs;
    }

    public function wasCorrect(): bool
    {
        return 1 === $this->grade;
    }
}
