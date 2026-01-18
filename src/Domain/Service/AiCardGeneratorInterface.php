<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Value\SourceText;

/**
 * Domain interface for AI-powered flashcard generation.
 *
 * Implementation should:
 * - Call external AI service (e.g., OpenRouter.ai)
 * - Parse AI response into domain objects
 * - Handle timeouts and errors from AI service
 * - Return structured result with cards and metadata
 *
 * This is a Domain interface - implementation lives in Infrastructure layer.
 */
interface AiCardGeneratorInterface
{
    /**
     * Generate flashcards from source text using AI.
     *
     * @param SourceText $sourceText Text to generate flashcards from (1000-10000 chars)
     *
     * @return GenerateCardsResult Contains generated cards, suggested name, and metadata
     *
     * @throws \App\Infrastructure\Integration\Ai\Exception\AiTimeoutException
     *                                                                            When AI service takes longer than 30 seconds to respond
     * @throws \App\Infrastructure\Integration\Ai\Exception\AiGenerationException
     *                                                                            When AI service returns an error or invalid response
     */
    public function generate(SourceText $sourceText): GenerateCardsResult;
}
