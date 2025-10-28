<?php

declare(strict_types=1);

namespace App\Domain\Value;

use Symfony\Component\Uid\Uuid;

/**
 * Preview Card Value Object
 *
 * Represents a single AI-generated flashcard before user saves it as a Set.
 * Immutable - any modification returns a new instance.
 */
final readonly class PreviewCard
{
    private function __construct(
        public string $tmpId,
        public string $front,
        public string $back,
        public bool $edited,
        public bool $deleted
    ) {
        if (mb_strlen($front) > 1000) {
            throw new \DomainException('Card front cannot exceed 1000 characters');
        }
        if (mb_strlen($back) > 1000) {
            throw new \DomainException('Card back cannot exceed 1000 characters');
        }
    }

    /**
     * Create new preview card from AI generation
     */
    public static function fromAiGeneration(string $front, string $back): self
    {
        return new self(
            tmpId: Uuid::v4()->toRfc4122(),
            front: $front,
            back: $back,
            edited: false,
            deleted: false
        );
    }

    /**
     * Create from array representation (for hydration from DB)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tmpId: $data['tmp_id'],
            front: $data['front'],
            back: $data['back'],
            edited: $data['edited'] ?? false,
            deleted: $data['deleted'] ?? false
        );
    }

    /**
     * Convert to array for DB persistence
     */
    public function toArray(): array
    {
        return [
            'tmp_id' => $this->tmpId,
            'front' => $this->front,
            'back' => $this->back,
            'edited' => $this->edited,
            'deleted' => $this->deleted,
        ];
    }

    /**
     * Edit card content (returns new instance)
     */
    public function edit(string $front, string $back): self
    {
        if ($this->deleted) {
            throw new \DomainException('Cannot edit deleted card');
        }

        $hasChanges = $this->front !== $front || $this->back !== $back;

        return new self(
            tmpId: $this->tmpId,
            front: $front,
            back: $back,
            edited: $this->edited || $hasChanges,
            deleted: $this->deleted
        );
    }

    /**
     * Mark card as deleted (returns new instance)
     */
    public function delete(): self
    {
        if ($this->deleted) {
            return $this; // Already deleted, return same instance
        }

        return new self(
            tmpId: $this->tmpId,
            front: $this->front,
            back: $this->back,
            edited: $this->edited,
            deleted: true
        );
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function isEdited(): bool
    {
        return $this->edited;
    }

    public function isKept(): bool
    {
        return !$this->deleted;
    }
}