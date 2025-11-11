<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\CreateSetCommand;
use App\Domain\Event\SetCreatedEvent;
use App\Domain\Exception\AiJobNotFoundException;
use App\Domain\Exception\DuplicateSetNameException;
use App\Domain\Model\Card;
use App\Domain\Model\CardOrigin;
use App\Domain\Model\Set;
use App\Domain\Repository\AiJobRepositoryInterface;
use App\Domain\Repository\CardRepositoryInterface;
use App\Domain\Repository\SetRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Handler for creating a new flashcard set with optional cards.
 *
 * Flow:
 * 1. Validate set name uniqueness (per user, case-insensitive)
 * 2. Verify AI job exists and belongs to user (if job_id provided)
 * 3. Create Set entity
 * 4. Create Card entities with proper origin and edited tracking
 * 5. Calculate KPI metrics (accepted count, edited count)
 * 6. Link AI job to set (if job_id provided)
 * 7. Persist all changes in a single transaction
 * 8. Dispatch analytics event
 * 9. Return result
 */
final readonly class CreateSetHandler
{
    public function __construct(
        private SetRepositoryInterface $setRepository,
        private CardRepositoryInterface $cardRepository,
        private AiJobRepositoryInterface $aiJobRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws DuplicateSetNameException When set name already exists for this user
     * @throws AiJobNotFoundException When job_id is provided but job not found
     */
    public function __invoke(CreateSetCommand $command): CreateSetResult
    {
        // Step 1: Check for duplicate set name (case-insensitive, per user)
        $setNameString = $command->name->toString();
        if ($this->setRepository->existsByOwnerAndName($command->userId, $setNameString)) {
            throw DuplicateSetNameException::forName($setNameString);
        }

        // Step 2: Verify AI job exists and belongs to user (if provided)
        $aiJob = null;
        if ($command->jobId !== null) {
            $aiJob = $this->aiJobRepository->findById($command->jobId->toString());

            if ($aiJob === null) {
                throw AiJobNotFoundException::forId($command->jobId->toString());
            }

            // RLS automatically ensures job belongs to current user
            // If user doesn't own it, findById returns null due to RLS policy
        }

        $now = new \DateTimeImmutable();
        $setId = Uuid::v4()->toString();

        // Step 3: Create Set entity
        $set = Set::create(
            $setId,
            $command->userId,
            $command->name,
            $now
        );

        // Step 4: Create Card entities and calculate KPI metrics
        $cards = [];
        $aiAcceptedCount = 0;
        $aiEditedCount = 0;

        foreach ($command->cards as $cardDto) {
            $card = Card::create(
                Uuid::v4()->toString(),
                $setId,
                $cardDto->origin,
                $cardDto->front,
                $cardDto->back,
                $now,
                $cardDto->wasEdited
            );

            $cards[] = $card;

            // Track KPI metrics for AI-generated cards
            if ($cardDto->origin === CardOrigin::AI) {
                $aiAcceptedCount++;

                if ($cardDto->wasEdited) {
                    $aiEditedCount++;
                }
            }
        }

        // Step 5: Persist Set
        $this->setRepository->save($set);

        // Step 6: Persist all Cards in batch (single flush after all persists)
        if (count($cards) > 0) {
            $this->cardRepository->saveAll($cards);
        }

        // Step 7: Link AI job to set (if provided)
        if ($aiJob !== null) {
            $aiJob->linkToSet(
                Uuid::fromString($setId),
                $aiAcceptedCount,
                $aiEditedCount
            );

            $this->aiJobRepository->save($aiJob);
        }

        // Step 8: Dispatch analytics event
        $this->eventDispatcher->dispatch(
            new SetCreatedEvent(
                setId: $setId,
                userId: $command->userId->toString(),
                totalCardCount: count($cards),
                aiCardCount: $aiAcceptedCount,
                editedAiCardCount: $aiEditedCount,
                jobId: $command->jobId?->toString()
            )
        );

        // Step 9: Return result
        return new CreateSetResult(
            setId: $setId,
            name: $setNameString,
            cardCount: count($cards)
        );
    }
}
