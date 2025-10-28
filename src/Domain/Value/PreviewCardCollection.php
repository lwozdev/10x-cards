<?php

declare(strict_types=1);

namespace App\Domain\Value;

/**
 * Preview Card Collection Value Object
 *
 * Immutable collection of preview cards with business operations.
 * Manages card lifecycle during AI generation preview phase.
 */
final readonly class PreviewCardCollection
{
    /**
     * @param array<PreviewCard> $cards
     */
    private function __construct(
        private array $cards
    ) {
    }

    /**
     * Create empty collection
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Create from AI-generated cards
     *
     * @param array<array{front: string, back: string}> $aiCards
     */
    public static function fromAiGeneration(array $aiCards): self
    {
        $cards = array_map(
            fn(array $card) => PreviewCard::fromAiGeneration($card['front'], $card['back']),
            $aiCards
        );

        return new self($cards);
    }

    /**
     * Create from array (DB hydration)
     */
    public static function fromArray(array $data): self
    {
        $cards = array_map(
            fn(array $cardData) => PreviewCard::fromArray($cardData),
            $data
        );

        return new self($cards);
    }

    /**
     * Convert to array for DB persistence
     */
    public function toArray(): array
    {
        return array_map(
            fn(PreviewCard $card) => $card->toArray(),
            $this->cards
        );
    }

    /**
     * Edit a card by tmpId (returns new collection)
     */
    public function editCard(string $tmpId, string $front, string $back): self
    {
        $found = false;
        $newCards = [];

        foreach ($this->cards as $card) {
            if ($card->tmpId === $tmpId) {
                $newCards[] = $card->edit($front, $back);
                $found = true;
            } else {
                $newCards[] = $card;
            }
        }

        if (!$found) {
            throw new \DomainException("Card with tmp_id {$tmpId} not found");
        }

        return new self($newCards);
    }

    /**
     * Delete a card by tmpId (returns new collection)
     */
    public function deleteCard(string $tmpId): self
    {
        $found = false;
        $newCards = [];

        foreach ($this->cards as $card) {
            if ($card->tmpId === $tmpId) {
                $newCards[] = $card->delete();
                $found = true;
            } else {
                $newCards[] = $card;
            }
        }

        if (!$found) {
            throw new \DomainException("Card with tmp_id {$tmpId} not found");
        }

        return new self($newCards);
    }

    /**
     * Get total count of cards (including deleted)
     */
    public function count(): int
    {
        return count($this->cards);
    }

    /**
     * Get count of cards marked as edited
     */
    public function editedCount(): int
    {
        return count(array_filter($this->cards, fn(PreviewCard $card) => $card->isEdited()));
    }

    /**
     * Get count of cards marked as deleted
     */
    public function deletedCount(): int
    {
        return count(array_filter($this->cards, fn(PreviewCard $card) => $card->isDeleted()));
    }

    /**
     * Get count of cards kept (not deleted)
     */
    public function keptCount(): int
    {
        return count(array_filter($this->cards, fn(PreviewCard $card) => $card->isKept()));
    }

    /**
     * Get only non-deleted cards
     *
     * @return array<PreviewCard>
     */
    public function activeCards(): array
    {
        return array_values(array_filter($this->cards, fn(PreviewCard $card) => $card->isKept()));
    }

    /**
     * Check if collection is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->cards);
    }

    /**
     * Check if all cards are deleted
     */
    public function allDeleted(): bool
    {
        if ($this->isEmpty()) {
            return true;
        }

        return $this->deletedCount() === $this->count();
    }
}