<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Command\GenerateCardsCommand;
use App\Application\Handler\GenerateCardsHandler;
use App\Domain\Value\SourceText;
use App\Infrastructure\Integration\Ai\Exception\AiGenerationException;
use App\Infrastructure\Integration\Ai\Exception\AiTimeoutException;
use App\UI\Http\Request\GenerateCardsRequest;
use App\UI\Http\Response\CardPreviewDto;
use App\UI\Http\Response\GenerateCardsResponse;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Controller for POST /api/generate endpoint.
 *
 * Handles synchronous AI flashcard generation:
 * 1. Deserialize and validate request
 * 2. Create SourceText Value Object (additional validation)
 * 3. Get current user from security context
 * 4. Call GenerateCardsHandler
 * 5. Map result to response DTO
 * 6. Return JSON response
 *
 * Error handling:
 * - 422: Validation errors (length, empty text)
 * - 401: Not authenticated (handled by Symfony Security)
 * - 504: AI timeout (from AiTimeoutException)
 * - 500: AI service error (from AiGenerationException)
 */
#[Route('/api/generate', name: 'api_generate_cards', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final class GenerateCardsController extends AbstractController
{
    public function __construct(
        private readonly GenerateCardsHandler $handler,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            // 1. Deserialize request body to DTO
            /** @var GenerateCardsRequest $requestDto */
            $requestDto = $this->serializer->deserialize(
                $request->getContent(),
                GenerateCardsRequest::class,
                'json'
            );

            // 2. Validate using Symfony Validator
            $violations = $this->validator->validate($requestDto);

            if (count($violations) > 0) {
                return $this->validationErrorResponse($violations);
            }

            // 3. Create SourceText Value Object (additional domain validation)
            try {
                $sourceText = SourceText::fromString($requestDto->getSourceText());
            } catch (\InvalidArgumentException $e) {
                return $this->json([
                    'error' => 'validation_failed',
                    'message' => $e->getMessage(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // 4. Get current user ID from security context
            /** @var \App\Domain\Model\User|null $user */
            $user = $this->getUser();
            if (null === $user) {
                return $this->json([
                    'error' => 'unauthorized',
                    'message' => 'Authentication required',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $userId = $user->getId();

            // 5. Create command and call handler
            $command = new GenerateCardsCommand(
                sourceText: $sourceText,
                userId: $userId
            );

            $result = $this->handler->handle($command);

            // 6. Map to response DTO
            $response = new GenerateCardsResponse(
                jobId: $result->jobId->toString(),
                suggestedName: $result->suggestedName->toString(),
                cards: array_map(
                    fn ($card) => new CardPreviewDto($card->front, $card->back),
                    $result->cards
                ),
                generatedCount: $result->generatedCount
            );

            // 7. Save data to session for next view (/sets/new/edit)
            $request->getSession()->set('pending_set', [
                'job_id' => $response->jobId,
                'suggested_name' => $response->suggestedName,
                'cards' => array_map(
                    fn ($card) => [
                        'front' => $card->front,
                        'back' => $card->back,
                    ],
                    $response->cards
                ),
                'source_text' => $sourceText->toString(),
                'generated_count' => $response->generatedCount,
            ]);

            // 8. Serialize and return JSON response
            return $this->json($response, Response::HTTP_OK);
        } catch (AiTimeoutException $e) {
            // AI timeout (>30s) - return 504 Gateway Timeout
            $this->logger->warning('AI generation timeout', [
                'message' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => 'ai_timeout',
                'message' => 'Generowanie fiszek przekroczyło limit czasu (30s). Spróbuj ponownie z krótszym tekstem.',
            ], Response::HTTP_GATEWAY_TIMEOUT);
        } catch (AiGenerationException $e) {
            // AI service error - return 500 Internal Server Error
            $this->logger->error('AI generation error', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return $this->json([
                'error' => 'ai_service_error',
                'message' => 'Wystąpił błąd podczas generowania fiszek. Spróbuj ponownie później.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Throwable $e) {
            // Unexpected error - log and return generic 500
            $this->logger->error('Unexpected error in generate cards endpoint', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json([
                'error' => 'internal_error',
                'message' => 'Wystąpił nieoczekiwany błąd. Spróbuj ponownie później.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Format validation errors for JSON response.
     */
    private function validationErrorResponse($violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $this->json([
            'error' => 'validation_failed',
            'message' => 'Dane wejściowe są nieprawidłowe',
            'violations' => $errors,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
