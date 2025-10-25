<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Set;
use App\Domain\Value\UserId;

interface SetRepositoryInterface
{
    public function findById(string $id): ?Set;

    /**
     * @return Set[]
     */
    public function findOwnedBy(UserId $ownerId): array;

    /**
     * Find active (not soft-deleted) sets owned by user, ordered by updated_at DESC
     *
     * @return Set[]
     */
    public function findActiveOwnedBy(UserId $ownerId, int $limit = 100, int $offset = 0): array;

    public function save(Set $set): void;

    public function softDelete(Set $set): void;

    public function existsByOwnerAndName(UserId $ownerId, string $name): bool;
}
