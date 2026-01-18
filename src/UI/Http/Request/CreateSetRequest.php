<?php

declare(strict_types=1);

namespace App\UI\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for POST /api/sets endpoint.
 *
 * Validates input for creating a flashcard set:
 * - Set name: required, max 255 characters
 * - Cards: optional array, max 100 cards (DoS protection)
 * - Job ID: optional UUID for AI job linkage
 *
 * This DTO handles HTTP-layer validation.
 * Additional domain validation happens in Value Objects.
 */
final class CreateSetRequest
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
     * @var CreateSetCardRequestDto[]
     */
    #[Assert\Valid]
    #[Assert\Count(
        max: 100,
        maxMessage: 'Too many cards. Maximum {{ limit }} cards allowed per request'
    )]
    public array $cards = [];

    #[Assert\Uuid(message: 'Invalid job ID format. Must be a valid UUID')]
    public ?string $job_id = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return CreateSetCardRequestDto[]
     */
    public function getCards(): array
    {
        return $this->cards;
    }

    /**
     * @param CreateSetCardRequestDto[] $cards
     */
    public function setCards(array $cards): void
    {
        $this->cards = $cards;
    }

    public function getJobId(): ?string
    {
        return $this->job_id;
    }

    public function setJobId(?string $job_id): void
    {
        $this->job_id = $job_id;
    }
}
