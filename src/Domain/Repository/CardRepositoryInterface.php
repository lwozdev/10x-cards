<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Card;

interface CardRepositoryInterface
{
    public function findById(string $id): ?Card;

    /**
     * @return Card[]
     */
    public function findActiveBySetId(string $setId): array;

    public function save(Card $card): void;

    /**
     * Save multiple cards in a single transaction (batch persist + flush).
     *
     * @param Card[] $cards
     */
    public function saveAll(array $cards): void;

    public function softDelete(Card $card): void;

    public function countActiveBySetId(string $setId): int;
}
