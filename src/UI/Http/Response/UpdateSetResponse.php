<?php

declare(strict_types=1);

namespace App\UI\Http\Response;

/**
 * Response DTO for PUT /api/sets/{id} endpoint.
 */
final readonly class UpdateSetResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public int $card_count,
    ) {
    }
}
