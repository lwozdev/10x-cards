<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Value\CardBack;
use App\Domain\Value\CardFront;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cards')]
#[ORM\Index(name: 'cards_set_active', columns: ['set_id', 'deleted_at'])]
#[ORM\Index(name: 'cards_set_updated', columns: ['set_id', 'updated_at'])]
class Card
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'set_id', type: 'guid')]
    private string $setId;

    #[ORM\Column(type: 'string', length: 20, enumType: CardOrigin::class)]
    private CardOrigin $origin;

    #[ORM\Column(type: 'text')]
    private string $front;

    #[ORM\Column(type: 'text')]
    private string $back;

    #[ORM\Column(name: 'edited_by_user_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $editedByUserAt = null;

    #[ORM\Column(name: 'deleted_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        string $id,
        string $setId,
        CardOrigin $origin,
        CardFront $front,
        CardBack $back,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->setId = $setId;
        $this->origin = $origin;
        $this->front = $front->toString();
        $this->back = $back->toString();
        $this->createdAt = $createdAt;
        $this->updatedAt = $createdAt;
    }

    public static function create(
        string $id,
        string $setId,
        CardOrigin $origin,
        CardFront $front,
        CardBack $back,
        DateTimeImmutable $createdAt,
        bool $wasEditedByUser = false
    ): self {
        $card = new self($id, $setId, $origin, $front, $back, $createdAt);

        if ($wasEditedByUser) {
            $card->editedByUserAt = $createdAt;
        }

        return $card;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSetId(): string
    {
        return $this->setId;
    }

    public function getOrigin(): CardOrigin
    {
        return $this->origin;
    }

    public function getFront(): CardFront
    {
        return CardFront::fromString($this->front);
    }

    public function getBack(): CardBack
    {
        return CardBack::fromString($this->back);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getEditedByUserAt(): ?DateTimeImmutable
    {
        return $this->editedByUserAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function wasEditedByUser(): bool
    {
        return $this->editedByUserAt !== null;
    }

    public function editFrontBack(
        CardFront $front,
        CardBack $back,
        DateTimeImmutable $editedAt
    ): void {
        $this->front = $front->toString();
        $this->back = $back->toString();
        $this->editedByUserAt = $editedAt;
        $this->updatedAt = $editedAt;
    }

    public function softDelete(DateTimeImmutable $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
        $this->updatedAt = $deletedAt;
    }
}
