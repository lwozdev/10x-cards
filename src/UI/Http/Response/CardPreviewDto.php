<?php

declare(strict_types=1);

namespace App\UI\Http\Response;

/**
 * DTO for a single flashcard preview in API response.
 *
 * Used in POST /api/generate response to return generated cards.
 * This is serialized to JSON.
 */
final class CardPreviewDto
{
    public function __construct(
        public readonly string $front,
        public readonly string $back,
    ) {
    }
}
