<?php

declare(strict_types=1);

namespace App\UI\Http\Response;

/**
 * Response DTO for POST /api/sets endpoint.
 *
 * JSON structure (HTTP 201 Created):
 * {
 *   "id": "550e8400-e29b-41d4-a716-446655440000",
 *   "name": "Matematyka - Geometria",
 *   "card_count": 15
 * }
 *
 * Headers:
 * - Location: /api/sets/{id}
 * - Content-Type: application/json
 */
final readonly class CreateSetResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public int $card_count,
    ) {
    }
}
