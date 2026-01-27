<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Model\CardOrigin;
use App\Domain\Value\CardBack;
use App\Domain\Value\CardFront;

/**
 * DTO for a single card within UpdateSetCommand.
 *
 * Represents card data to be created or updated:
 * - id: existing card ID (null for new cards)
 * - front: card front content
 * - back: card back content
 * - origin: 'ai' or 'manual'
 */
final readonly class UpdateSetCardDto
{
    public function __construct(
        public ?string $id,
        public CardFront $front,
        public CardBack $back,
        public CardOrigin $origin,
    ) {
    }
}
