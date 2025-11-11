<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSubscriber;

use App\Domain\Event\SetCreatedEvent;
use App\Domain\Model\AnalyticsEvent;
use App\Domain\Repository\AnalyticsEventRepositoryInterface;
use App\Domain\Value\UserId;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to SetCreatedEvent and persists analytics data.
 *
 * Tracks KPI metrics for flashcard set creation:
 * - Total cards created
 * - AI vs manual card distribution
 * - AI card edit rate
 * - Linkage to AI generation jobs
 */
final readonly class SetCreatedEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AnalyticsEventRepositoryInterface $analyticsRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SetCreatedEvent::class => 'onSetCreated',
        ];
    }

    public function onSetCreated(SetCreatedEvent $event): void
    {
        $analyticsEvent = AnalyticsEvent::create(
            eventType: 'set_created',
            userId: UserId::fromString($event->userId),
            payload: [
                'total_cards' => $event->totalCardCount,
                'ai_cards' => $event->aiCardCount,
                'manual_cards' => $event->getManualCardCount(),
                'edited_ai_cards' => $event->editedAiCardCount,
                'ai_edit_rate' => $event->getAiEditRate(),
                'job_id' => $event->jobId,
            ],
            occurredAt: new \DateTimeImmutable(),
            setId: $event->setId
        );

        $this->analyticsRepository->save($analyticsEvent);
    }
}
