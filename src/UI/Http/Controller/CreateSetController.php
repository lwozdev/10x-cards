<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Command\CreateSetCardDto;
use App\Application\Command\CreateSetCommand;
use App\Application\Handler\CreateSetHandler;
use App\Domain\Exception\AiJobNotFoundException;
use App\Domain\Exception\DuplicateSetNameException;
use App\Domain\Model\CardOrigin;
use App\Domain\Value\AiJobId;
use App\Domain\Value\CardBack;
use App\Domain\Value\CardFront;
use App\Domain\Value\SetName;
use App\Domain\Value\UserId;
use App\UI\Http\Request\CreateSetRequest;
use App\UI\Http\Response\CreateSetResponse;
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
 * Controller for POST /api/sets endpoint.
 *
 * Handles creation of flashcard sets with optional cards:
 * 1. Deserialize and validate request
 * 2. Create Value Objects (SetName, CardFront, CardBack, etc.)
 * 3. Get current user from security context
 * 4. Create command and call handler
 * 5. Map result to response DTO
 * 6. Return JSON response with 201 Created + Location header
 *
 * Supports two use cases:
 * - Creating empty set for manual card addition later
 * - Creating set with AI-generated cards (or manually created cards)
 *
 * Error handling:
 * - 400: Invalid JSON format
 * - 401: Not authenticated (handled by Symfony Security)
 * - 404: AI job not found (job_id provided but doesn't exist)
 * - 409: Duplicate set name (set with this name already exists for user)
 * - 422: Validation errors (name too long, card content too long, invalid origin)
 * - 500: Unexpected errors
 */
#[Route('/api/sets', name: 'api_sets_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final class CreateSetController extends AbstractController
{
    public function __construct(
        private readonly CreateSetHandler $handler,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            // 1. Deserialize request body to DTO
            /** @var CreateSetRequest $requestDto */
            $requestDto = $this->serializer->deserialize(
                $request->getContent(),
                CreateSetRequest::class,
                'json'
            );

            // 2. Validate using Symfony Validator
            $violations = $this->validator->validate($requestDto);

            if (count($violations) > 0) {
                return $this->validationErrorResponse($violations);
            }

            // 3. Get current user ID from security context
            /** @var \App\Domain\Model\User|null $user */
            $user = $this->getUser();
            if (null === $user) {
                return $this->json([
                    'error' => 'Authentication required',
                    'code' => 'unauthorized',
                ], Response::HTTP_UNAUTHORIZED);
            }

            // TEMPORARY: Hardcoded user ID for testing (same as GenerateCardsController)
            // TODO: Replace with: UserId::fromString($user->getUserIdentifier())
            $userId = $user->getId();

            // 4. Create Value Objects and map to Command
            try {
                $setName = SetName::fromString($requestDto->getName());

                $cards = array_map(
                    fn ($cardDto) => new CreateSetCardDto(
                        front: CardFront::fromString($cardDto->getFront()),
                        back: CardBack::fromString($cardDto->getBack()),
                        origin: CardOrigin::from($cardDto->getOrigin()),
                        wasEdited: $cardDto->isEdited()
                    ),
                    $requestDto->getCards()
                );

                $jobId = null !== $requestDto->getJobId()
                    ? AiJobId::fromString($requestDto->getJobId())
                    : null;
            } catch (\InvalidArgumentException $e) {
                return $this->json([
                    'error' => 'Validation failed',
                    'code' => 'validation_error',
                    'message' => $e->getMessage(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // 5. Create command
            $command = new CreateSetCommand(
                userId: $userId,
                name: $setName,
                cards: $cards,
                jobId: $jobId
            );

            // 6. Call handler
            $result = ($this->handler)($command);

            // 7. Map to response DTO
            $response = new CreateSetResponse(
                id: $result->setId,
                name: $result->name,
                card_count: $result->cardCount
            );

            // 8. Clear pending_set from session (cleanup after successful save)
            $request->getSession()->remove('pending_set');

            // 9. Return 201 Created with Location header
            return $this->json(
                $response,
                Response::HTTP_CREATED,
                ['Location' => "/api/sets/{$result->setId}"]
            );
        } catch (DuplicateSetNameException $e) {
            // 409 Conflict - set name already exists for this user
            $this->logger->info('Duplicate set name attempt', [
                'message' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => $e->getMessage(),
                'code' => 'duplicate_set_name',
                'field' => 'name',
            ], Response::HTTP_CONFLICT);
        } catch (AiJobNotFoundException $e) {
            // 404 Not Found - job_id doesn't exist or doesn't belong to user
            $this->logger->warning('AI job not found', [
                'message' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => $e->getMessage(),
                'code' => 'job_not_found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            // 500 Internal Server Error - unexpected errors
            $this->logger->error('Unexpected error in create set endpoint', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json([
                'error' => 'Internal server error',
                'code' => 'internal_error',
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
            'error' => 'Validation failed',
            'code' => 'validation_error',
            'violations' => $errors,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
