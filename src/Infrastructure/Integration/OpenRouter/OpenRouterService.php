<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\OpenRouter;

use App\Infrastructure\Integration\OpenRouter\DTO\Flashcard;
use App\Infrastructure\Integration\OpenRouter\DTO\OpenRouterResponse;
use App\Infrastructure\Integration\OpenRouter\Exception\OpenRouterAuthenticationException;
use App\Infrastructure\Integration\OpenRouter\Exception\OpenRouterInvalidRequestException;
use App\Infrastructure\Integration\OpenRouter\Exception\OpenRouterNetworkException;
use App\Infrastructure\Integration\OpenRouter\Exception\OpenRouterParseException;
use App\Infrastructure\Integration\OpenRouter\Exception\OpenRouterRateLimitException;
use App\Infrastructure\Integration\OpenRouter\Exception\OpenRouterServerException;
use App\Infrastructure\Integration\OpenRouter\Exception\OpenRouterTimeoutException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TimeoutExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Service for interacting with OpenRouter AI API.
 * Provides abstraction layer between the application and OpenRouter.
 */
final class OpenRouterService implements OpenRouterServiceInterface
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 1000; // 1 second base delay
    private const MIN_FLASHCARD_TEXT_LENGTH = 1000;
    private const MAX_FLASHCARD_TEXT_LENGTH = 10000;
    private const SET_NAME_EXCERPT_LENGTH = 500;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
        private readonly string $apiUrl,
        private readonly string $defaultModel = 'x-ai/grok-4.1-fast:free',
        private readonly int $defaultTimeout = 60,
    ) {
        // Validate API key at construction
        if (trim($this->apiKey) === '') {
            throw new InvalidArgumentException('OpenRouter API key cannot be empty');
        }

        if (trim($this->apiUrl) === '') {
            throw new InvalidArgumentException('OpenRouter API URL cannot be empty');
        }
    }

    public function chatCompletion(array $messages, array $options = []): OpenRouterResponse
    {
        // Validate messages
        $this->validateMessages($messages);

        // Build payload
        $payload = $this->buildRequestPayload($messages, $options);

        // Execute with retry logic
        return $this->executeWithRetry(function () use ($payload) {
            $response = $this->sendRequest($payload);
            return $this->parseResponse($response);
        });
    }

    public function generateFlashcards(string $sourceText, array $options = []): array
    {
        // Sanitize and validate input
        $sanitizedText = $this->sanitizeUserInput($sourceText);

        $this->validateFlashcardTextLength($sanitizedText);

        // Build system prompt
        $systemMessage = <<<'PROMPT'
Jesteś asystentem edukacyjnym specjalizującym się w tworzeniu fiszek do nauki.

Zadanie:
- Przeanalizuj podany tekst źródłowy
- Wygeneruj 10-20 fiszek edukacyjnych w języku polskim
- Każda fiszka powinna zawierać pytanie (front) i odpowiedź (back)
- Fiszki powinny być zwięzłe, jasne i pomocne w nauce
- Używaj prostego języka odpowiedniego dla uczniów szkół podstawowych i średnich

Wymagania:
- Front: krótkie pytanie lub hasło (max 2-3 zdania)
- Back: zwięzła odpowiedź (max 3-4 zdania)
- Fiszki powinny pokrywać kluczowe koncepty z tekstu

WAŻNE - Format odpowiedzi:
Zwróć odpowiedź w formacie JSON według tego schematu:
{
  "flashcards": [
    {"front": "pytanie lub hasło", "back": "odpowiedź"},
    {"front": "pytanie lub hasło", "back": "odpowiedź"}
  ]
}
PROMPT;

        // Build response format for OpenRouter (json_object)
        $responseFormat = [
            'type' => 'json_object',
        ];

        // Merge options
        $mergedOptions = array_merge([
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'response_format' => $responseFormat,
        ], $options);

        // Call chatCompletion
        $response = $this->chatCompletion([
            ['role' => 'system', 'content' => $systemMessage],
            ['role' => 'user', 'content' => $sanitizedText],
        ], $mergedOptions);

        // Parse JSON content
        $jsonContent = $response->getJsonContent();

        if (!isset($jsonContent['flashcards']) || !is_array($jsonContent['flashcards'])) {
            throw new OpenRouterParseException('Response missing flashcards array');
        }

        // Convert to Flashcard DTOs
        $flashcards = [];
        foreach ($jsonContent['flashcards'] as $index => $flashcardData) {
            try {
                $flashcards[] = Flashcard::fromArray($flashcardData);
            } catch (\InvalidArgumentException $e) {
                $this->logger->warning('Invalid flashcard in response', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                // Skip invalid flashcards
            }
        }

        if (empty($flashcards)) {
            throw new OpenRouterParseException('No valid flashcards generated');
        }

        return $flashcards;
    }

    public function generateFlashcardsWithMetadata(string $sourceText, array $options = []): DTO\FlashcardGenerationResult
    {
        // Sanitize and validate input
        $sanitizedText = $this->sanitizeUserInput($sourceText);
        $this->validateFlashcardTextLength($sanitizedText);

        // Build system prompt (same as generateFlashcards)
        $systemMessage = <<<'PROMPT'
Jesteś asystentem edukacyjnym specjalizującym się w tworzeniu fiszek do nauki.

Zadanie:
- Przeanalizuj podany tekst źródłowy
- Wygeneruj 10-20 fiszek edukacyjnych w języku polskim
- Każda fiszka powinna zawierać pytanie (front) i odpowiedź (back)
- Fiszki powinny być zwięzłe, jasne i pomocne w nauce
- Używaj prostego języka odpowiedniego dla uczniów szkół podstawowych i średnich

Wymagania:
- Front: krótkie pytanie lub hasło (max 2-3 zdania)
- Back: zwięzła odpowiedź (max 3-4 zdania)
- Fiszki powinny pokrywać kluczowe koncepty z tekstu

WAŻNE - Format odpowiedzi:
Zwróć odpowiedź w formacie JSON według tego schematu:
{
  "flashcards": [
    {"front": "pytanie lub hasło", "back": "odpowiedź"},
    {"front": "pytanie lub hasło", "back": "odpowiedź"}
  ]
}
PROMPT;

        // Build response format for OpenRouter (json_object)
        $responseFormat = [
            'type' => 'json_object',
        ];

        $mergedOptions = array_merge([
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'response_format' => $responseFormat,
        ], $options);

        // Call chatCompletion and keep the response for metadata
        $response = $this->chatCompletion([
            ['role' => 'system', 'content' => $systemMessage],
            ['role' => 'user', 'content' => $sanitizedText],
        ], $mergedOptions);

        // Parse flashcards
        $jsonContent = $response->getJsonContent();

        if (!isset($jsonContent['flashcards']) || !is_array($jsonContent['flashcards'])) {
            throw new OpenRouterParseException('Response missing flashcards array');
        }

        $flashcards = [];
        foreach ($jsonContent['flashcards'] as $index => $flashcardData) {
            try {
                $flashcards[] = Flashcard::fromArray($flashcardData);
            } catch (\InvalidArgumentException $e) {
                $this->logger->warning('Invalid flashcard in response', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (empty($flashcards)) {
            throw new OpenRouterParseException('No valid flashcards generated');
        }

        // Generate suggested name (reuse existing method)
        $suggestedName = $this->suggestSetName($sanitizedText, $options);

        // Return result with metadata
        return new DTO\FlashcardGenerationResult(
            flashcards: $flashcards,
            suggestedName: $suggestedName,
            modelName: $response->model,
            promptTokens: $response->promptTokens,
            completionTokens: $response->completionTokens,
            totalTokens: $response->totalTokens,
        );
    }

    public function suggestSetName(string $sourceText, array $options = []): string
    {
        // Sanitize input and create excerpt for performance
        $sanitizedText = $this->sanitizeUserInput($sourceText);
        $excerpt = mb_substr($sanitizedText, 0, self::SET_NAME_EXCERPT_LENGTH);

        // Build system prompt
        $systemMessage = <<<'PROMPT'
Jesteś asystentem edukacyjnym. Zaproponuj zwięzłą nazwę (3-8 słów) dla zestawu fiszek na podstawie podanego tekstu.

Wymagania:
- Nazwa powinna być opisowa i konkretna
- Maksymalnie 8 słów
- Język polski
- Bez cudzysłowów ani znaków specjalnych
PROMPT;

        // Merge options with defaults
        $mergedOptions = array_merge([
            'temperature' => 0.5, // Lower temperature for more deterministic results
            'max_tokens' => 50,
        ], $options);

        // Call chatCompletion
        $response = $this->chatCompletion([
            ['role' => 'system', 'content' => $systemMessage],
            ['role' => 'user', 'content' => $excerpt],
        ], $mergedOptions);

        // Return suggested name (trim to remove any extra whitespace)
        return trim($response->content);
    }

    public function validateApiConnection(): bool
    {
        try {
            // Send minimal test request
            $response = $this->chatCompletion([
                ['role' => 'user', 'content' => 'test'],
            ], [
                'max_tokens' => 5,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('OpenRouter API connection validation failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Execute callable with retry logic for transient errors.
     * Implements exponential backoff strategy.
     *
     * @template T
     * @param callable(): T $callable
     * @return T
     * @throws OpenRouterException
     */
    private function executeWithRetry(callable $callable): mixed
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_RETRIES) {
            try {
                return $callable();
            } catch (OpenRouterNetworkException | OpenRouterTimeoutException | OpenRouterServerException $e) {
                // These errors are retryable
                $attempt++;
                $lastException = $e;

                if ($attempt >= self::MAX_RETRIES) {
                    // Max retries reached
                    break;
                }

                // Calculate delay with exponential backoff
                $delayMs = self::RETRY_DELAY_MS * (2 ** ($attempt - 1));

                $this->logger->warning('Retrying OpenRouter request', [
                    'attempt' => $attempt,
                    'max_retries' => self::MAX_RETRIES,
                    'delay_ms' => $delayMs,
                    'error' => $e->getMessage(),
                ]);

                // Sleep before retry (convert ms to microseconds)
                usleep($delayMs * 1000);
            } catch (OpenRouterException $e) {
                // Non-retryable errors (auth, rate limit, invalid request, parse errors)
                throw $e;
            }
        }

        // All retries exhausted, throw the last exception
        throw $lastException;
    }

    /**
     * Validate messages array structure.
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @throws InvalidArgumentException If validation fails
     */
    private function validateMessages(array $messages): void
    {
        if (empty($messages)) {
            throw new InvalidArgumentException('Messages array cannot be empty');
        }

        $allowedRoles = ['system', 'user', 'assistant'];

        foreach ($messages as $index => $message) {
            if (!isset($message['role'])) {
                throw new InvalidArgumentException("Message at index {$index} missing 'role' field");
            }

            if (!isset($message['content'])) {
                throw new InvalidArgumentException("Message at index {$index} missing 'content' field");
            }

            if (!in_array($message['role'], $allowedRoles, true)) {
                throw new InvalidArgumentException(
                    "Message at index {$index} has invalid role '{$message['role']}'. " .
                    'Allowed roles: ' . implode(', ', $allowedRoles)
                );
            }

            if (trim((string) $message['content']) === '') {
                throw new InvalidArgumentException("Message at index {$index} has empty content");
            }
        }
    }

    /**
     * Validate response_format structure.
     *
     * @param array<string, mixed> $responseFormat
     * @throws InvalidArgumentException If validation fails
     */
    private function validateResponseFormat(array $responseFormat): void
    {
        if (!isset($responseFormat['type'])) {
            throw new InvalidArgumentException('response_format missing required field: type');
        }

        $allowedTypes = ['text', 'json', 'json_object', 'regex', 'ebnf', 'structural_tag'];

        if (!in_array($responseFormat['type'], $allowedTypes, true)) {
            throw new InvalidArgumentException(
                "response_format type must be one of: " . implode(', ', $allowedTypes) .
                ", got: {$responseFormat['type']}"
            );
        }
    }

    /**
     * Sanitize user input to prevent prompt injection and remove control characters.
     *
     * @param string $input User-provided text
     * @return string Sanitized text
     */
    private function sanitizeUserInput(string $input): string
    {
        // Remove control characters except newline, carriage return, and tab
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $input);

        // Trim whitespace
        return trim($sanitized ?? '');
    }

    /**
     * Validate source text length for flashcard generation.
     *
     * @throws InvalidArgumentException If text length is invalid
     */
    private function validateFlashcardTextLength(string $text): void
    {
        $length = mb_strlen($text);

        if ($length < self::MIN_FLASHCARD_TEXT_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'Source text too short. Minimum %d characters required, got %d',
                    self::MIN_FLASHCARD_TEXT_LENGTH,
                    $length
                )
            );
        }

        if ($length > self::MAX_FLASHCARD_TEXT_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'Source text too long. Maximum %d characters allowed, got %d',
                    self::MAX_FLASHCARD_TEXT_LENGTH,
                    $length
                )
            );
        }
    }

    /**
     * Build request payload for OpenRouter API.
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function buildRequestPayload(array $messages, array $options): array
    {
        $payload = [
            'model' => $options['model'] ?? $this->defaultModel,
            'messages' => $messages,
        ];

        // Add optional parameters if provided
        if (isset($options['temperature'])) {
            $payload['temperature'] = (float) $options['temperature'];
        }

        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = (int) $options['max_tokens'];
        }

        if (isset($options['top_p'])) {
            $payload['top_p'] = (float) $options['top_p'];
        }

        if (isset($options['frequency_penalty'])) {
            $payload['frequency_penalty'] = (float) $options['frequency_penalty'];
        }

        if (isset($options['presence_penalty'])) {
            $payload['presence_penalty'] = (float) $options['presence_penalty'];
        }

        if (isset($options['response_format'])) {
            $this->validateResponseFormat($options['response_format']);
            $payload['response_format'] = $options['response_format'];
        }

        return $payload;
    }

    /**
     * Send HTTP request to OpenRouter API.
     *
     * @param array<string, mixed> $payload
     * @return ResponseInterface
     * @throws OpenRouterNetworkException On network errors
     * @throws OpenRouterTimeoutException On timeout
     */
    private function sendRequest(array $payload): ResponseInterface
    {
        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => 'https://flashcards-app.local', // For OpenRouter stats
                    'X-Title' => 'AI Flashcard Generator',
                ],
                'json' => $payload,
                'timeout' => $this->defaultTimeout,
            ]);

            return $response;
        } catch (TimeoutExceptionInterface $e) {
            $this->logger->warning('OpenRouter request timed out', [
                'timeout' => $this->defaultTimeout,
                'model' => $payload['model'] ?? 'unknown',
            ]);

            throw new OpenRouterTimeoutException(
                'Request to OpenRouter API timed out after ' . $this->defaultTimeout . ' seconds',
                0,
                $e
            );
        } catch (TransportExceptionInterface $e) {
            $this->logger->warning('OpenRouter network error', [
                'error' => $e->getMessage(),
                'model' => $payload['model'] ?? 'unknown',
            ]);

            throw new OpenRouterNetworkException(
                'Network error while communicating with OpenRouter API: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Parse and validate API response.
     *
     * @throws OpenRouterAuthenticationException On 401
     * @throws OpenRouterRateLimitException On 429
     * @throws OpenRouterInvalidRequestException On 400
     * @throws OpenRouterServerException On 500+
     * @throws OpenRouterParseException On parsing errors
     */
    private function parseResponse(ResponseInterface $response): OpenRouterResponse
    {
        $statusCode = $response->getStatusCode();

        // Handle non-200 status codes
        if ($statusCode !== 200) {
            $this->handleErrorResponse($response, $statusCode);
        }

        try {
            $data = $response->toArray();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to parse OpenRouter response as JSON', [
                'error' => $e->getMessage(),
                'status_code' => $statusCode,
            ]);

            throw new OpenRouterParseException(
                'Failed to parse API response as JSON: ' . $e->getMessage(),
                0,
                $e
            );
        }

        // Log successful request
        $this->logger->info('OpenRouter request successful', [
            'model' => $data['model'] ?? 'unknown',
            'tokens' => $data['usage']['total_tokens'] ?? 0,
            'finish_reason' => $data['choices'][0]['finish_reason'] ?? 'unknown',
        ]);

        // Create DTO from response
        return OpenRouterResponse::fromApiResponse($data);
    }

    /**
     * Handle error responses from API.
     *
     * @throws OpenRouterAuthenticationException
     * @throws OpenRouterRateLimitException
     * @throws OpenRouterInvalidRequestException
     * @throws OpenRouterServerException
     */
    private function handleErrorResponse(ResponseInterface $response, int $statusCode): never
    {
        try {
            $errorData = $response->toArray(false);
            $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
        } catch (\Throwable $e) {
            $errorData = null;
            $errorMessage = 'Failed to parse error response';
        }

        match (true) {
            $statusCode === 401 => $this->handleAuthenticationError($errorMessage, $errorData),
            $statusCode === 429 => $this->handleRateLimitError($response, $errorMessage, $errorData),
            $statusCode === 400 => $this->handleInvalidRequestError($errorMessage, $errorData),
            $statusCode >= 500 => $this->handleServerError($statusCode, $errorMessage, $errorData),
            default => $this->handleUnknownError($statusCode, $errorMessage, $errorData),
        };
    }

    /**
     * Handle authentication error (401).
     *
     * @throws OpenRouterAuthenticationException
     */
    private function handleAuthenticationError(string $message, ?array $errorData): never
    {
        $this->logger->error('OpenRouter authentication failed', [
            'message' => $message,
        ]);

        throw new OpenRouterAuthenticationException(
            'Authentication failed. Please check your API key: ' . $message,
            401,
            $errorData
        );
    }

    /**
     * Handle rate limit error (429).
     *
     * @throws OpenRouterRateLimitException
     */
    private function handleRateLimitError(ResponseInterface $response, string $message, ?array $errorData): never
    {
        // Try to extract retry_after from headers or response body
        $retryAfter = null;
        $headers = $response->getHeaders(false);

        if (isset($headers['retry-after'][0])) {
            $retryAfter = (int) $headers['retry-after'][0];
        } elseif (isset($errorData['retry_after'])) {
            $retryAfter = (int) $errorData['retry_after'];
        }

        $this->logger->warning('OpenRouter rate limit exceeded', [
            'message' => $message,
            'retry_after' => $retryAfter,
        ]);

        $userMessage = 'Rate limit exceeded.';
        if ($retryAfter !== null) {
            $userMessage .= " Please try again in {$retryAfter} seconds.";
        }

        throw new OpenRouterRateLimitException(
            $userMessage,
            429,
            $errorData,
            $retryAfter
        );
    }

    /**
     * Handle invalid request error (400).
     *
     * @throws OpenRouterInvalidRequestException
     */
    private function handleInvalidRequestError(string $message, ?array $errorData): never
    {
        $this->logger->error('OpenRouter invalid request', [
            'message' => $message,
        ]);

        throw new OpenRouterInvalidRequestException(
            'Invalid request to OpenRouter API: ' . $message,
            400,
            $errorData
        );
    }

    /**
     * Handle server error (500+).
     *
     * @throws OpenRouterServerException
     */
    private function handleServerError(int $statusCode, string $message, ?array $errorData): never
    {
        $this->logger->error('OpenRouter server error', [
            'status_code' => $statusCode,
            'message' => $message,
        ]);

        throw new OpenRouterServerException(
            'OpenRouter server error. Please try again later: ' . $message,
            $statusCode,
            $errorData
        );
    }

    /**
     * Handle unknown error.
     *
     * @throws OpenRouterInvalidRequestException
     */
    private function handleUnknownError(int $statusCode, string $message, ?array $errorData): never
    {
        $this->logger->error('OpenRouter unknown error', [
            'status_code' => $statusCode,
            'message' => $message,
        ]);

        throw new OpenRouterInvalidRequestException(
            "Unexpected error from OpenRouter API (HTTP {$statusCode}): {$message}",
            $statusCode,
            $errorData
        );
    }
}
