<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\GenerateFlashcardsCommand;
use App\Domain\Model\AiJob;
use App\Domain\Model\AnalyticsEvent;
use App\Domain\Repository\AiJobRepositoryInterface;
use App\Domain\Repository\AnalyticsEventRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * Handler for GenerateFlashcardsCommand.
 *
 * Creates an AiJob record with status "queued" and publishes
 * analytics event for tracking. The actual AI generation
 * happens asynchronously in a separate worker process.
 */
final readonly class GenerateFlashcardsHandler
{
    public function __construct(
        private AiJobRepositoryInterface $aiJobRepository,
        private AnalyticsEventRepositoryInterface $analyticsRepository,
    ) {}

    /**
     * Handle the command and return job ID.
     *
     * @return string UUID of the created AiJob
     * @throws \InvalidArgumentException if source text validation fails
     */
    public function handle(GenerateFlashcardsCommand $command): string
    {
        $now = new DateTimeImmutable();
        $jobId = Uuid::v4()->toString();

        // Create AiJob entity (validation happens in constructor)
        $aiJob = AiJob::create(
            id: $jobId,
            userId: $command->userId,
            requestPrompt: $command->sourceText,
            createdAt: $now
        );

        // Persist the job
        $this->aiJobRepository->save($aiJob);

        // Publish analytics event
        $analyticsEvent = AnalyticsEvent::create(
            eventType: 'ai_generate_started',
            userId: $command->userId,
            payload: [
                'job_id' => $jobId,
                'text_length' => mb_strlen($command->sourceText),
            ],
            occurredAt: $now
        );

        $this->analyticsRepository->save($analyticsEvent);

        return $jobId;
    }
}
