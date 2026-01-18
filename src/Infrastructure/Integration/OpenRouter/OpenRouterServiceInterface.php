<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\OpenRouter;

use App\Infrastructure\Integration\OpenRouter\DTO\Flashcard;
use App\Infrastructure\Integration\OpenRouter\DTO\OpenRouterResponse;
use App\Infrastructure\Integration\OpenRouter\Exception\OpenRouterException;

/**
 * Service interface for interacting with OpenRouter AI API.
 * Provides high-level methods for AI text generation tasks.
 */
interface OpenRouterServiceInterface
{
    /**
     * Send a chat completion request to OpenRouter API.
     *
     * @param array<int, array{role: string, content: string}> $messages Array of messages with role and content
     * @param array<string, mixed>                             $options  Optional parameters:
     *                                                                   - model: string - Model to use (overrides default)
     *                                                                   - temperature: float - Randomness (0.0-2.0)
     *                                                                   - max_tokens: int - Maximum response length
     *                                                                   - top_p: float - Nucleus sampling
     *                                                                   - frequency_penalty: float - Penalize frequent tokens
     *                                                                   - presence_penalty: float - Penalize present tokens
     *                                                                   - response_format: array - JSON Schema for structured responses
     *
     * @throws OpenRouterException If request fails or validation fails
     */
    public function chatCompletion(array $messages, array $options = []): OpenRouterResponse;

    /**
     * Generate flashcards from source text using AI.
     *
     * @param string               $sourceText Text to generate flashcards from (1000-10000 characters)
     * @param array<string, mixed> $options    Optional parameters to override defaults
     *
     * @return array<int, Flashcard> Array of generated flashcards
     *
     * @throws OpenRouterException       If generation fails
     * @throws \InvalidArgumentException If source text length is invalid
     */
    public function generateFlashcards(string $sourceText, array $options = []): array;

    /**
     * Suggest a name for a flashcard set based on source text.
     *
     * @param string               $sourceText Text to analyze (can be truncated for performance)
     * @param array<string, mixed> $options    Optional parameters to override defaults
     *
     * @throws OpenRouterException If generation fails
     */
    public function suggestSetName(string $sourceText, array $options = []): string;

    /**
     * Generate flashcards with full metadata (model, tokens).
     * This is an enhanced version of generateFlashcards() that returns metadata.
     *
     * @param string               $sourceText Text to generate flashcards from (1000-10000 characters)
     * @param array<string, mixed> $options    Optional parameters to override defaults
     *
     * @throws OpenRouterException       If generation fails
     * @throws \InvalidArgumentException If source text length is invalid
     */
    public function generateFlashcardsWithMetadata(string $sourceText, array $options = []): DTO\FlashcardGenerationResult;

    /**
     * Validate API connection and credentials.
     * Sends a minimal test request to check if the API is accessible.
     *
     * @return bool True if connection is successful, false otherwise
     */
    public function validateApiConnection(): bool;
}
