<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Value\Email;
use App\Domain\Value\UserId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 'users_email_unique', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(name: 'password_hash', type: 'text')]
    private string $passwordHash;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'last_login_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(name: 'is_verified', type: 'boolean')]
    private bool $isVerified = false;

    private function __construct(
        UserId $id,
        Email $email,
        string $passwordHash,
        DateTimeImmutable $createdAt,
        bool $isVerified = false
    ) {
        $this->id = $id->toString();
        $this->email = $email->toString();
        $this->passwordHash = $passwordHash;
        $this->createdAt = $createdAt;
        $this->isVerified = $isVerified;
    }

    public static function create(
        UserId $id,
        Email $email,
        string $passwordHash,
        DateTimeImmutable $createdAt,
        bool $isVerified = false
    ): self {
        if (strlen($passwordHash) < 60) {
            throw new \InvalidArgumentException('Password hash must be at least 60 characters');
        }

        return new self($id, $email, $passwordHash, $createdAt, $isVerified);
    }

    public function getId(): UserId
    {
        return UserId::fromString($this->id);
    }

    public function getEmail(): Email
    {
        return Email::fromString($this->email);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function updateLastLogin(DateTimeImmutable $lastLoginAt): void
    {
        $this->lastLoginAt = $lastLoginAt;
    }

    // Symfony Security UserInterface implementation
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase in this implementation
    }

    // PasswordAuthenticatedUserInterface implementation
    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    // Email verification methods
    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function markAsVerified(): void
    {
        $this->isVerified = true;
    }
}
