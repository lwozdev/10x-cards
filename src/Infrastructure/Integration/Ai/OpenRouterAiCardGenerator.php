<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\Ai;

use App\Domain\Service\AiCardGeneratorInterface;
use App\Domain\Service\GenerateCardsResult;
use App\Domain\Value\CardPreview;
use App\Domain\Value\SourceText;
use App\Domain\Value\SuggestedSetName;
use App\Infrastructure\Integration\Ai\Exception\AiGenerationException;
use App\Infrastructure\Integration\Ai\Exception\AiTimeoutException;
use App\Infrastructure\Integration\OpenRouter\Exception\OpenRouterAuthenticationException;
use App\Infrastructure\Integration\OpenRouter\Exception\OpenRouterException;
use App\Infrastructure\Integration\OpenRouter\Exception\OpenRouterInvalidRequestException;
use App\Infrastructure\Integration\OpenRouter\Exception\OpenRouterParseException;
use App\Infrastructure\Integration\OpenRouter\Exception\OpenRouterRateLimitException;
use App\Infrastructure\Integration\OpenRouter\Exception\OpenRouterServerException;
use App\Infrastructure\Integration\OpenRouter\Exception\OpenRouterTimeoutException;
use App\Infrastructure\Integration\OpenRouter\OpenRouterServiceInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Adapter that integrates OpenRouter service with domain layer.
 *
 * Responsibilities:
 * - Convert domain ValueObjects to OpenRouter service format
 * - Convert OpenRouter DTOs back to domain ValueObjects
 * - Map OpenRouter exceptions to domain exceptions
 * - Log generation attempts and results
 */
final class OpenRouterAiCardGenerator implements AiCardGeneratorInterface
{
    public function __construct(
        private readonly OpenRouterServiceInterface $openRouterService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function generate(SourceText $sourceText): GenerateCardsResult
    {
        $this->logger->info('Starting AI flashcard generation', [
            'text_length' => $sourceText->length(),
        ]);

        try {
            // Generate flashcards with metadata
            $generationResult = $this->openRouterService->generateFlashcardsWithMetadata($sourceText->toString());

            // Convert OpenRouter Flashcard DTOs to domain CardPreview value objects
            $cards = $this->convertToDomainCards($generationResult->flashcards);

            if (empty($cards)) {
                $this->logger->error('AI generated no valid flashcards');
                throw AiGenerationException::emptyCards();
            }

            $result = new GenerateCardsResult(
                cards: $cards,
                suggestedName: SuggestedSetName::fromString($generationResult->suggestedName),
                modelName: $generationResult->modelName,
                tokensIn: $generationResult->promptTokens,
                tokensOut: $generationResult->completionTokens,
            );

            $this->logger->info('AI flashcard generation completed', [
                'cards_generated' => count($cards),
                'suggested_name' => $generationResult->suggestedName,
                'model' => $generationResult->modelName,
                'total_tokens' => $generationResult->totalTokens,
            ]);

            return $result;
        } catch (OpenRouterTimeoutException $e) {
            $this->logger->warning('AI generation timed out', [
                'error' => $e->getMessage(),
            ]);

            throw AiTimeoutException::create(60); // Use configured timeout
        } catch (OpenRouterAuthenticationException $e) {
            $this->logger->error('AI service authentication failed', [
                'error' => $e->getMessage(),
            ]);

            throw AiGenerationException::serviceError(401, 'Authentication failed');
        } catch (OpenRouterRateLimitException $e) {
            $this->logger->warning('AI service rate limit exceeded', [
                'error' => $e->getMessage(),
                'retry_after' => $e->getRetryAfterSeconds(),
            ]);

            throw AiGenerationException::serviceError(
                429,
                'Rate limit exceeded. Please try again later.'
            );
        } catch (OpenRouterInvalidRequestException $e) {
            $this->logger->error('Invalid request to AI service', [
                'error' => $e->getMessage(),
            ]);

            throw AiGenerationException::invalidResponse($e->getMessage());
        } catch (OpenRouterServerException $e) {
            $this->logger->error('AI service server error', [
                'error' => $e->getMessage(),
                'status_code' => $e->getHttpStatusCode(),
            ]);

            throw AiGenerationException::serviceError(
                $e->getHttpStatusCode(),
                'AI service is temporarily unavailable'
            );
        } catch (OpenRouterParseException $e) {
            $this->logger->error('Failed to parse AI response', [
                'error' => $e->getMessage(),
            ]);

            throw AiGenerationException::invalidResponse('Malformed AI response');
        } catch (InvalidArgumentException $e) {
            // This catches validation errors from CardPreview creation
            $this->logger->error('AI returned invalid card data', [
                'error' => $e->getMessage(),
            ]);

            throw AiGenerationException::invalidResponse($e->getMessage());
        } catch (OpenRouterException $e) {
            // Catch-all for any other OpenRouter exceptions
            $this->logger->error('Unexpected AI service error', [
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            throw AiGenerationException::invalidResponse($e->getMessage());
        }
    }

    /**
     * Convert OpenRouter Flashcard DTOs to domain CardPreview value objects.
     *
     * @param \App\Infrastructure\Integration\OpenRouter\DTO\Flashcard[] $flashcards
     * @return CardPreview[]
     */
    private function convertToDomainCards(array $flashcards): array
    {
        $cards = [];

        foreach ($flashcards as $flashcard) {
            try {
                $cards[] = CardPreview::create(
                    front: $flashcard->front,
                    back: $flashcard->back
                );
            } catch (InvalidArgumentException $e) {
                // Log but skip invalid cards
                $this->logger->warning('Skipping invalid flashcard from AI', [
                    'front' => mb_substr($flashcard->front, 0, 50),
                    'back' => mb_substr($flashcard->back, 0, 50),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $cards;
    }
}
