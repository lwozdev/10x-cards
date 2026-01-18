<?php

declare(strict_types=1);

namespace App\UI\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for a single card within POST /api/sets endpoint.
 *
 * Validates individual card data:
 * - Front: required, max 1000 characters
 * - Back: required, max 1000 characters
 * - Origin: must be 'ai' or 'manual' (defaults to 'manual')
 * - Edited: boolean flag (defaults to false)
 */
final class CreateSetCardRequestDto
{
    #[Assert\NotBlank(message: 'Card front cannot be empty')]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'Card front is too long (maximum {{ limit }} characters)'
    )]
    public string $front = '';

    #[Assert\NotBlank(message: 'Card back cannot be empty')]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'Card back is too long (maximum {{ limit }} characters)'
    )]
    public string $back = '';

    #[Assert\Choice(
        choices: ['ai', 'manual'],
        message: 'Origin must be either "ai" or "manual"'
    )]
    public string $origin = 'manual';

    public bool $edited = false;

    public function getFront(): string
    {
        return $this->front;
    }

    public function setFront(string $front): void
    {
        $this->front = $front;
    }

    public function getBack(): string
    {
        return $this->back;
    }

    public function setBack(string $back): void
    {
        $this->back = $back;
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function setOrigin(string $origin): void
    {
        $this->origin = $origin;
    }

    public function isEdited(): bool
    {
        return $this->edited;
    }

    public function setEdited(bool $edited): void
    {
        $this->edited = $edited;
    }
}
