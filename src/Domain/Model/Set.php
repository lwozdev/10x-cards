<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Value\SetName;
use App\Domain\Value\UserId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sets')]
#[ORM\Index(name: 'sets_owner_listing', columns: ['owner_id', 'deleted_at'])]
#[ORM\Index(name: 'sets_owner_updated_at', columns: ['owner_id', 'updated_at'])]
#[ORM\UniqueConstraint(name: 'sets_owner_name_unique', columns: ['owner_id', 'name'])]
class Set
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'owner_id', type: 'guid')]
    private string $ownerId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'card_count', type: 'integer')]
    private int $cardCount = 0;

    #[ORM\Column(name: 'generated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $generatedAt = null;

    #[ORM\Column(name: 'generated_model', type: 'text', nullable: true)]
    private ?string $generatedModel = null;

    #[ORM\Column(name: 'generated_tokens_in', type: 'integer', nullable: true)]
    private ?int $generatedTokensIn = null;

    #[ORM\Column(name: 'generated_tokens_out', type: 'integer', nullable: true)]
    private ?int $generatedTokensOut = null;

    #[ORM\Column(name: 'deleted_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        string $id,
        UserId $ownerId,
        SetName $name,
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = $id;
        $this->ownerId = $ownerId->toString();
        $this->name = $name->toString();
        $this->createdAt = $createdAt;
        $this->updatedAt = $createdAt;
    }

    public static function create(
        string $id,
        UserId $ownerId,
        SetName $name,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $ownerId, $name, $createdAt);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOwnerId(): UserId
    {
        return UserId::fromString($this->ownerId);
    }

    public function getName(): SetName
    {
        return SetName::fromString($this->name);
    }

    public function getCardCount(): int
    {
        return $this->cardCount;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function isAiGenerated(): bool
    {
        return null !== $this->generatedAt;
    }

    public function renameTo(SetName $newName, \DateTimeImmutable $updatedAt): void
    {
        $this->name = $newName->toString();
        $this->updatedAt = $updatedAt;
    }

    public function markAsGenerated(
        \DateTimeImmutable $generatedAt,
        string $modelName,
        int $tokensIn,
        int $tokensOut,
    ): void {
        $this->generatedAt = $generatedAt;
        $this->generatedModel = $modelName;
        $this->generatedTokensIn = $tokensIn;
        $this->generatedTokensOut = $tokensOut;
    }

    public function softDelete(\DateTimeImmutable $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function incrementCardCount(): void
    {
        ++$this->cardCount;
    }

    public function decrementCardCount(): void
    {
        if ($this->cardCount > 0) {
            --$this->cardCount;
        }
    }

    public function touch(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
