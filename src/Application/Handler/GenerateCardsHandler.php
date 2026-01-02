<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\GenerateCardsCommand;
use App\Domain\Model\AiJob;
use App\Domain\Repository\AiJobRepositoryInterface;
use App\Domain\Service\AiCardGeneratorInterface;
use App\Domain\Value\AiJobId;
use App\Infrastructure\Integration\Ai\Exception\AiGenerationException;
use App\Infrastructure\Integration\Ai\Exception\AiTimeoutException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Handler for generating flashcards using AI.
 *
 * Orchestrates the entire generation process:
 * 1. Call AI service to generate cards (synchronous, max 30s)
 * 2. Create AiJob record for KPI tracking
 * 3. Handle success and failure cases
 * 4. Return result with generated cards
 *
 * Transaction handling:
 * - AiJob is persisted regardless of success/failure
 * - On timeout/error: AiJob.status = FAILED, exception is re-thrown
 * - On success: AiJob.status = SUCCEEDED, cards returned
 */
final class GenerateCardsHandler
{
    public function __construct(
        private readonly AiCardGeneratorInterface $aiCardGenerator,
        private readonly AiJobRepositoryInterface $aiJobRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws AiTimeoutException When AI takes longer than 30s (re-thrown for HTTP 504)
     * @throws AiGenerationException When AI service fails (re-thrown for HTTP 500)
     */
    public function handle(GenerateCardsCommand $command): GenerateCardsHandlerResult
    {
        $startTime = microtime(true);
        $userId = Uuid::fromString($command->userId->toString());
        $sourceText = $command->sourceText->toString();

        $this->logger->info('Starting AI flashcard generation', [
            'user_id' => $command->userId->toString(),
            'text_length' => $command->sourceText->length(),
        ]);

        try {
            // Call AI service (blocking, max 30s)
            $result = $this->aiCardGenerator->generate($command->sourceText);

            // Create successful AiJob for KPI tracking
            $aiJob = AiJob::createSucceeded(
                userId: $userId,
                requestPrompt: $sourceText,
                generatedCount: $result->generatedCount(),
                suggestedName: $result->suggestedName->toString(),
                modelName: $result->modelName,
                tokensIn: $result->tokensIn,
                tokensOut: $result->tokensOut
            );

            $this->aiJobRepository->save($aiJob);

            $duration = (microtime(true) - $startTime) * 1000; // ms

            $this->logger->info('AI flashcard generation succeeded', [
                'user_id' => $command->userId->toString(),
                'job_id' => $aiJob->getId()->toString(),
                'generated_count' => $result->generatedCount(),
                'duration_ms' => round($duration, 2),
            ]);

            return new GenerateCardsHandlerResult(
                jobId: AiJobId::fromString($aiJob->getId()->toString()),
                suggestedName: $result->suggestedName,
                cards: $result->cards,
                generatedCount: $result->generatedCount()
            );
        } catch (AiTimeoutException $e) {
            // Timeout - create failed AiJob and re-throw for HTTP 504
            $this->handleFailure($userId, $sourceText, $e, 'timeout');
            throw $e;
        } catch (AiGenerationException $e) {
            // AI service error - create failed AiJob and re-throw for HTTP 500
            $this->handleFailure($userId, $sourceText, $e, 'generation_error');
            throw $e;
        } catch (\Throwable $e) {
            // Unexpected error - log and re-throw as AiGenerationException
            $this->handleFailure($userId, $sourceText, $e, 'unexpected_error');
            throw AiGenerationException::invalidResponse($e->getMessage());
        }
    }

    /**
     * Handle failure by creating failed AiJob and logging error
     */
    private function handleFailure(
        Uuid $userId,
        string $sourceText,
        \Throwable $exception,
        string $errorType
    ): void {
        $aiJob = AiJob::createFailed(
            userId: $userId,
            requestPrompt: $sourceText,
            errorMessage: $this->truncateErrorMessage($exception->getMessage())
        );

        $this->aiJobRepository->save($aiJob);

        $this->logger->error('AI flashcard generation failed', [
            'user_id' => $userId->toString(),
            'job_id' => $aiJob->getId()->toString(),
            'error_type' => $errorType,
            'error_message' => $exception->getMessage(),
            'exception_class' => get_class($exception),
        ]);
    }

    /**
     * Truncate error message to fit DB constraint (max 255 chars in error_message)
     */
    private function truncateErrorMessage(string $message): string
    {
        if (mb_strlen($message, 'UTF-8') <= 255) {
            return $message;
        }

        return mb_substr($message, 0, 252, 'UTF-8') . '...';
    }
}
