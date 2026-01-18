<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\OpenRouter\DTO;

use App\Infrastructure\Integration\OpenRouter\Exception\OpenRouterParseException;

/**
 * Represents the parsed response from OpenRouter API.
 * Immutable DTO containing the content and metadata from the API response.
 */
final readonly class OpenRouterResponse
{
    public function __construct(
        public string $id,
        public string $model,
        public string $content,
        public int $promptTokens,
        public int $completionTokens,
        public int $totalTokens,
        public string $finishReason,
        public ?array $rawResponse = null,
    ) {
    }

    /**
     * Factory method to create OpenRouterResponse from API response array.
     *
     * @param array<string, mixed> $response Raw API response
     *
     * @throws OpenRouterParseException If response structure is invalid
     */
    public static function fromApiResponse(array $response): self
    {
        // Validate required fields
        if (!isset($response['id'])) {
            throw new OpenRouterParseException('Missing required field: id');
        }

        if (!isset($response['model'])) {
            throw new OpenRouterParseException('Missing required field: model');
        }

        if (!isset($response['choices'][0]['message']['content'])) {
            throw new OpenRouterParseException('Missing required field: choices[0].message.content');
        }

        if (!isset($response['choices'][0]['finish_reason'])) {
            throw new OpenRouterParseException('Missing required field: choices[0].finish_reason');
        }

        $usage = $response['usage'] ?? [];

        return new self(
            id: (string) $response['id'],
            model: (string) $response['model'],
            content: (string) $response['choices'][0]['message']['content'],
            promptTokens: (int) ($usage['prompt_tokens'] ?? 0),
            completionTokens: (int) ($usage['completion_tokens'] ?? 0),
            totalTokens: (int) ($usage['total_tokens'] ?? 0),
            finishReason: (string) $response['choices'][0]['finish_reason'],
            rawResponse: $response,
        );
    }

    /**
     * Parse and return the content as JSON array.
     * Useful when response_format is set to json_schema.
     *
     * @return array<string, mixed>
     *
     * @throws OpenRouterParseException If content is not valid JSON
     */
    public function getJsonContent(): array
    {
        try {
            $decoded = json_decode($this->content, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($decoded)) {
                throw new OpenRouterParseException('JSON content is not an array');
            }

            return $decoded;
        } catch (\JsonException $e) {
            throw new OpenRouterParseException('Failed to parse JSON content: '.$e->getMessage(), 0, $e);
        }
    }
}
