<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\User;
use App\Domain\Value\Email;
use App\Domain\Value\UserId;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function save(User $user): void;

    public function exists(Email $email): bool;
}
