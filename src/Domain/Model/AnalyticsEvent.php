<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Value\UserId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'analytics_events')]
#[ORM\Index(name: 'analytics_user_time', columns: ['user_id', 'occurred_at'])]
class AnalyticsEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'event_type', type: 'text')]
    private string $eventType;

    #[ORM\Column(name: 'user_id', type: 'guid')]
    private string $userId;

    #[ORM\Column(name: 'set_id', type: 'guid', nullable: true)]
    private ?string $setId = null;

    #[ORM\Column(name: 'card_id', type: 'guid', nullable: true)]
    private ?string $cardId = null;

    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(name: 'occurred_at', type: 'datetime_immutable')]
    private DateTimeImmutable $occurredAt;

    private function __construct(
        string $eventType,
        UserId $userId,
        array $payload,
        DateTimeImmutable $occurredAt,
        ?string $setId = null,
        ?string $cardId = null
    ) {
        if (empty($eventType)) {
            throw new \InvalidArgumentException('Event type cannot be empty');
        }

        $this->eventType = $eventType;
        $this->userId = $userId->toString();
        $this->payload = $payload;
        $this->occurredAt = $occurredAt;
        $this->setId = $setId;
        $this->cardId = $cardId;
    }

    public static function create(
        string $eventType,
        UserId $userId,
        array $payload,
        DateTimeImmutable $occurredAt,
        ?string $setId = null,
        ?string $cardId = null
    ): self {
        return new self($eventType, $userId, $payload, $occurredAt, $setId, $cardId);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }

    public function getSetId(): ?string
    {
        return $this->setId;
    }

    public function getCardId(): ?string
    {
        return $this->cardId;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
