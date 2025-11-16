<?php

declare(strict_types=1);

namespace App\UI\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for POST /api/generate endpoint.
 *
 * Validates source text before passing to application layer:
 * - Must not be blank
 * - Must be between 1000 and 10000 characters
 *
 * This DTO is used for Symfony validation at the HTTP layer.
 * Additional validation happens in SourceText Value Object.
 */
final class GenerateCardsRequest
{
    #[Assert\NotBlank(message: 'Source text cannot be empty')]
    #[Assert\Length(
        min: 1000,
        max: 10000,
        minMessage: 'Source text must be at least {{ limit }} characters long',
        maxMessage: 'Source text must not exceed {{ limit }} characters'
    )]
    public string $sourceText = '';

    public function getSourceText(): string
    {
        return $this->sourceText;
    }

    public function setSourceText(string $sourceText): void
    {
        $this->sourceText = $sourceText;
    }
}
