<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Command\UpdateSetCardDto;
use App\Application\Command\UpdateSetCommand;
use App\Application\Handler\UpdateSetHandler;
use App\Domain\Exception\DuplicateSetNameException;
use App\Domain\Exception\SetNotFoundException;
use App\Domain\Exception\UnauthorizedSetAccessException;
use App\Domain\Model\CardOrigin;
use App\Domain\Value\CardBack;
use App\Domain\Value\CardFront;
use App\Domain\Value\SetName;
use App\UI\Http\Request\UpdateSetRequest;
use App\UI\Http\Response\UpdateSetResponse;
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
 * Controller for PUT /api/sets/{id} endpoint.
 *
 * Handles updating flashcard sets:
 * 1. Deserialize and validate request
 * 2. Create Value Objects
 * 3. Get current user from security context
 * 4. Create command and call handler
 * 5. Return JSON response
 *
 * Error handling:
 * - 400: Invalid JSON format
 * - 401: Not authenticated
 * - 403: Not authorized to access set
 * - 404: Set not found
 * - 409: Duplicate set name
 * - 422: Validation errors
 * - 500: Unexpected errors
 */
#[Route('/api/sets/{id}', name: 'api_sets_update', methods: ['PUT'])]
#[IsGranted('ROLE_USER')]
final class UpdateSetController extends AbstractController
{
    public function __construct(
        private readonly UpdateSetHandler $handler,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(string $id, Request $request): JsonResponse
    {
        try {
            // 1. Deserialize request body to DTO
            /** @var UpdateSetRequest $requestDto */
            $requestDto = $this->serializer->deserialize(
                $request->getContent(),
                UpdateSetRequest::class,
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

            $userId = $user->getId();

            // 4. Create Value Objects and map to Command
            try {
                $setName = SetName::fromString($requestDto->getName());

                $cards = array_map(
                    fn ($cardDto) => new UpdateSetCardDto(
                        id: $cardDto->getId(),
                        front: CardFront::fromString($cardDto->getFront()),
                        back: CardBack::fromString($cardDto->getBack()),
                        origin: CardOrigin::from($cardDto->getOrigin())
                    ),
                    $requestDto->getCards()
                );
            } catch (\InvalidArgumentException $e) {
                return $this->json([
                    'error' => 'Validation failed',
                    'code' => 'validation_error',
                    'message' => $e->getMessage(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // 5. Create command
            $command = new UpdateSetCommand(
                setId: $id,
                userId: $userId,
                name: $setName,
                cards: $cards,
                deletedCardIds: $requestDto->getDeletedCardIds()
            );

            // 6. Call handler
            $result = ($this->handler)($command);

            // 7. Map to response DTO
            $response = new UpdateSetResponse(
                id: $result->setId,
                name: $result->name,
                card_count: $result->cardCount
            );

            // 8. Return 200 OK
            return $this->json($response, Response::HTTP_OK);
        } catch (SetNotFoundException $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'code' => 'set_not_found',
            ], Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedSetAccessException $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'code' => 'forbidden',
            ], Response::HTTP_FORBIDDEN);
        } catch (DuplicateSetNameException $e) {
            $this->logger->info('Duplicate set name attempt', [
                'message' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => $e->getMessage(),
                'code' => 'duplicate_set_name',
                'field' => 'name',
            ], Response::HTTP_CONFLICT);
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error in update set endpoint', [
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
