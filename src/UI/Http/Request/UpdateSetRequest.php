<?php

declare(strict_types=1);

namespace App\UI\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for PUT /api/sets/{id} endpoint.
 *
 * Validates input for updating a flashcard set:
 * - Set name: required, max 255 characters
 * - Cards: optional array, max 100 cards
 * - Deleted card IDs: optional array of UUIDs for cards to delete
 */
final class UpdateSetRequest
{
    #[Assert\NotBlank(message: 'Set name is required')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'Set name must be at least {{ limit }} character long',
        maxMessage: 'Set name must not exceed {{ limit }} characters'
    )]
    public string $name = '';

    /**
     * @var UpdateSetCardRequestDto[]
     */
    #[Assert\Valid]
    #[Assert\Count(
        max: 100,
        maxMessage: 'Too many cards. Maximum {{ limit }} cards allowed per request'
    )]
    public array $cards = [];

    /**
     * @var string[]
     */
    #[Assert\All([
        new Assert\Uuid(message: 'Invalid deleted card ID format')
    ])]
    public array $deleted_card_ids = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return UpdateSetCardRequestDto[]
     */
    public function getCards(): array
    {
        return $this->cards;
    }

    /**
     * @param UpdateSetCardRequestDto[] $cards
     */
    public function setCards(array $cards): void
    {
        $this->cards = $cards;
    }

    /**
     * @return string[]
     */
    public function getDeletedCardIds(): array
    {
        return $this->deleted_card_ids;
    }

    /**
     * @param string[] $deleted_card_ids
     */
    public function setDeletedCardIds(array $deleted_card_ids): void
    {
        $this->deleted_card_ids = $deleted_card_ids;
    }
}
