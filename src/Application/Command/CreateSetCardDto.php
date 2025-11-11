<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Model\CardOrigin;
use App\Domain\Value\CardBack;
use App\Domain\Value\CardFront;

/**
 * Data Transfer Object representing a card to be created within a set.
 *
 * Used by CreateSetCommand to encapsulate card data with proper value objects.
 */
final readonly class CreateSetCardDto
{
    /**
     * @param CardFront $front Front side of the card (max 1000 chars)
     * @param CardBack $back Back side of the card (max 1000 chars)
     * @param CardOrigin $origin Source of the card (AI or MANUAL)
     * @param bool $wasEdited Whether the user edited this card before saving
     */
    public function __construct(
        public CardFront $front,
        public CardBack $back,
        public CardOrigin $origin,
        public bool $wasEdited,
    ) {
    }
}
