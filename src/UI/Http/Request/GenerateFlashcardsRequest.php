<?php

declare(strict_types=1);

namespace App\UI\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for flashcard generation from source text.
 *
 * Validates that source text meets the requirements:
 * - Must be a non-empty string
 * - Must be between 1000 and 10000 characters (inclusive)
 */
final class GenerateFlashcardsRequest
{
    #[Assert\NotBlank(message: 'Source text is required')]
    #[Assert\Type(type: 'string', message: 'Source text must be a string')]
    #[Assert\Length(
        min: 1000,
        max: 10000,
        minMessage: 'Source text must be at least {{ limit }} characters long',
        maxMessage: 'Source text cannot be longer than {{ limit }} characters'
    )]
    public readonly string $sourceText;

    public function __construct(string $sourceText)
    {
        $this->sourceText = $sourceText;
    }
}
