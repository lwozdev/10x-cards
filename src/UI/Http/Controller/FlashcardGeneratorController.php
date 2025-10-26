<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Command\GenerateFlashcardsCommand;
use App\Application\Handler\GenerateFlashcardsHandler;
use App\Domain\Model\User;
use App\UI\Http\Request\GenerateFlashcardsRequest;
use App\UI\Http\Response\AiJobResponse;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Thin controller for AI-powered flashcard generation.
 *
 * Responsibilities (thin controller principle):
 * - Deserialize and validate request
 * - Map request to command
 * - Delegate business logic to application handler
 * - Return response
 */
#[Route('/generate', name: 'flashcard_generate', methods: ['POST'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class FlashcardGeneratorController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly GenerateFlashcardsHandler $handler,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Generate flashcards from source text using AI.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Deserialize request
        $generateRequest = $this->deserializeRequest($request);
        if ($generateRequest instanceof JsonResponse) {
            return $generateRequest;
        }

        // Validate request
        $violations = $this->validator->validate($generateRequest);
        if (count($violations) > 0) {
            return $this->validationErrorResponse($violations);
        }

        // Get authenticated user
        /** @var User $user */
        $user = $this->getUser();

        try {
            // Create command and delegate to handler
            $command = new GenerateFlashcardsCommand(
                userId: $user->getId(),
                sourceText: $generateRequest->sourceText,
            );

            $jobId = $this->handler->handle($command);

            // Return success response
            $response = new AiJobResponse(
                jobId: $jobId,
                status: 'queued',
            );

            return new JsonResponse(
                $response->toArray(),
                Response::HTTP_ACCEPTED
            );

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Validation error in flashcard generation', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()->toString(),
            ]);

            return new JsonResponse(
                [
                    'error' => 'Validation failed',
                    'message' => $e->getMessage(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to create AI job', [
                'exception' => $e->getMessage(),
                'user_id' => $user->getId()->toString(),
            ]);

            return new JsonResponse(
                [
                    'error' => 'Internal server error',
                    'message' => 'An unexpected error occurred. Please try again later.',
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Deserialize request body to DTO.
     */
    private function deserializeRequest(Request $request): GenerateFlashcardsRequest|JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['source_text'])) {
                return new JsonResponse(
                    ['error' => 'Bad Request', 'message' => 'Missing required field: source_text'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            return new GenerateFlashcardsRequest(
                sourceText: (string) $data['source_text']
            );

        } catch (\JsonException $e) {
            $this->logger->warning('Invalid JSON in request', ['error' => $e->getMessage()]);

            return new JsonResponse(
                ['error' => 'Bad Request', 'message' => 'Invalid JSON format'],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Format validation errors into JSON response.
     */
    private function validationErrorResponse($violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $errors[$propertyPath][] = $violation->getMessage();
        }

        return new JsonResponse(
            [
                'error' => 'Validation failed',
                'details' => $errors,
            ],
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}
