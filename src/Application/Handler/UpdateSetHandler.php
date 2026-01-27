<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\UpdateSetCommand;
use App\Domain\Exception\DuplicateSetNameException;
use App\Domain\Exception\SetNotFoundException;
use App\Domain\Exception\UnauthorizedSetAccessException;
use App\Domain\Model\Card;
use App\Domain\Repository\CardRepositoryInterface;
use App\Domain\Repository\SetRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Handler for updating an existing flashcard set.
 *
 * Flow:
 * 1. Find set and verify ownership
 * 2. Check for duplicate set name (if name changed)
 * 3. Update set name
 * 4. Delete cards marked for deletion
 * 5. Update existing cards
 * 6. Create new cards
 * 7. Return result
 */
final readonly class UpdateSetHandler
{
    public function __construct(
        private SetRepositoryInterface $setRepository,
        private CardRepositoryInterface $cardRepository,
    ) {
    }

    /**
     * @throws SetNotFoundException          When set doesn't exist
     * @throws UnauthorizedSetAccessException When user doesn't own the set
     * @throws DuplicateSetNameException     When new name already exists for user
     */
    public function __invoke(UpdateSetCommand $command): UpdateSetResult
    {
        $now = new \DateTimeImmutable();

        // Step 1: Find set
        $set = $this->setRepository->findById($command->setId);

        if (null === $set || $set->isDeleted()) {
            throw SetNotFoundException::forId($command->setId);
        }

        // Step 2: Verify ownership
        if (!$set->getOwnerId()->equals($command->userId)) {
            throw UnauthorizedSetAccessException::forSet($command->setId);
        }

        // Step 3: Check for duplicate name (if name changed)
        $currentName = $set->getName()->toString();
        $newName = $command->name->toString();

        if (strtolower($currentName) !== strtolower($newName)) {
            if ($this->setRepository->existsByOwnerAndName($command->userId, $newName)) {
                throw DuplicateSetNameException::forName($newName);
            }

            // Rename the set
            $set->renameTo($command->name, $now);
        }

        // Step 4: Delete cards marked for deletion
        foreach ($command->deletedCardIds as $cardId) {
            $card = $this->cardRepository->findById($cardId);

            if (null !== $card && $card->getSetId() === $command->setId && !$card->isDeleted()) {
                $this->cardRepository->softDelete($card);
                $set->decrementCardCount();
            }
        }

        // Step 5: Process cards (update existing, create new)
        $existingCardIds = [];
        $cardsToSave = [];

        foreach ($command->cards as $cardDto) {
            if (null !== $cardDto->id) {
                // Update existing card
                $existingCardIds[] = $cardDto->id;
                $card = $this->cardRepository->findById($cardDto->id);

                if (null !== $card && $card->getSetId() === $command->setId && !$card->isDeleted()) {
                    // Check if content changed
                    $frontChanged = $card->getFront()->toString() !== $cardDto->front->toString();
                    $backChanged = $card->getBack()->toString() !== $cardDto->back->toString();

                    if ($frontChanged || $backChanged) {
                        $card->editFrontBack($cardDto->front, $cardDto->back, $now);
                        $cardsToSave[] = $card;
                    }
                }
            } else {
                // Create new card
                $card = Card::create(
                    Uuid::v4()->toString(),
                    $command->setId,
                    $cardDto->origin,
                    $cardDto->front,
                    $cardDto->back,
                    $now
                );

                $cardsToSave[] = $card;
                $set->incrementCardCount();
            }
        }

        // Step 6: Save all modified/new cards
        if (count($cardsToSave) > 0) {
            $this->cardRepository->saveAll($cardsToSave);
        }

        // Step 7: Update set timestamp
        $set->touch($now);
        $this->setRepository->save($set);

        // Step 8: Get actual card count
        $finalCardCount = $this->cardRepository->countActiveBySetId($command->setId);

        return new UpdateSetResult(
            setId: $command->setId,
            name: $newName,
            cardCount: $finalCardCount
        );
    }
}
