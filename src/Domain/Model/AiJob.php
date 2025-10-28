<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Value\PreviewCardCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * AI Job - tracks AI flashcard generation requests
 *
 * Lifecycle: queued -> running -> succeeded|failed
 * Stores preview cards before user saves them as a Set
 */
#[ORM\Entity]
#[ORM\Table(name: 'ai_jobs')]
#[ORM\Index(name: 'ai_jobs_user_time', columns: ['user_id', 'created_at'])]
#[ORM\Index(name: 'ai_jobs_status_time', columns: ['status', 'created_at'])]
#[ORM\Index(name: 'ai_jobs_set', columns: ['set_id'])]
class AiJob
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $userId;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $setId = null;

    #[ORM\Column(type: 'string', length: 20, enumType: AiJobStatus::class)]
    private AiJobStatus $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $requestPrompt = null;

    /**
     * Preview cards collection (stored as JSONB in DB)
     */
    #[ORM\Column(type: Types::JSON)]
    private array $cardsData = [];

    private ?PreviewCardCollection $cards = null;

    /**
     * Denormalized counters for query performance
     * Kept in sync with PreviewCardCollection
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $generatedCount = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $editedCount = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $deletedCount = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $suggestedName = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $responseRaw = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $modelName = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $tokensIn = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $tokensOut = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    private function __construct()
    {
        $this->id = Uuid::v4();
        $this->status = AiJobStatus::QUEUED;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->cards = PreviewCardCollection::empty();
    }

    /**
     * Create new AI generation job
     */
    public static function create(Uuid $userId, string $requestPrompt): self
    {
        $job = new self();
        $job->userId = $userId;
        $job->requestPrompt = $requestPrompt;
        return $job;
    }

    // ===== Getters =====

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getSetId(): ?Uuid
    {
        return $this->setId;
    }

    public function getStatus(): AiJobStatus
    {
        return $this->status;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getRequestPrompt(): ?string
    {
        return $this->requestPrompt;
    }

    public function getCards(): PreviewCardCollection
    {
        if ($this->cards === null) {
            $this->cards = PreviewCardCollection::fromArray($this->cardsData);
        }

        return $this->cards;
    }

    public function getGeneratedCount(): int
    {
        return $this->generatedCount;
    }

    public function getEditedCount(): int
    {
        return $this->editedCount;
    }

    public function getDeletedCount(): int
    {
        return $this->deletedCount;
    }

    public function getKeptCount(): int
    {
        return $this->generatedCount - $this->deletedCount;
    }

    public function getSuggestedName(): ?string
    {
        return $this->suggestedName;
    }

    public function getResponseRaw(): ?array
    {
        return $this->responseRaw;
    }

    public function getModelName(): ?string
    {
        return $this->modelName;
    }

    public function getTokensIn(): ?int
    {
        return $this->tokensIn;
    }

    public function getTokensOut(): ?int
    {
        return $this->tokensOut;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function isCompleted(): bool
    {
        return $this->status->isTerminal();
    }

    // ===== Intentional Methods (Business Operations) =====

    /**
     * Start processing this job
     */
    public function start(): void
    {
        if ($this->status !== AiJobStatus::QUEUED) {
            throw new \DomainException('Can only start queued jobs');
        }

        $this->status = AiJobStatus::RUNNING;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Mark job as succeeded with AI-generated cards
     *
     * @param array<array{front: string, back: string}> $aiGeneratedCards
     */
    public function succeedWithCards(
        array $aiGeneratedCards,
        ?string $suggestedName,
        array $responseRaw,
        string $modelName,
        int $tokensIn,
        int $tokensOut
    ): void {
        if ($this->status !== AiJobStatus::RUNNING) {
            throw new \DomainException('Can only succeed running jobs');
        }

        $this->cards = PreviewCardCollection::fromAiGeneration($aiGeneratedCards);
        $this->syncCardsToDatabase();

        $this->generatedCount = $this->cards->count();
        $this->suggestedName = $suggestedName;
        $this->responseRaw = $responseRaw;
        $this->modelName = $modelName;
        $this->tokensIn = $tokensIn;
        $this->tokensOut = $tokensOut;

        $this->status = AiJobStatus::SUCCEEDED;
        $this->completedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Mark job as failed with error message
     */
    public function failWithError(string $errorMessage): void
    {
        if ($this->status->isTerminal()) {
            throw new \DomainException('Cannot fail already completed job');
        }

        $this->status = AiJobStatus::FAILED;
        $this->errorMessage = $errorMessage;
        $this->completedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Edit a preview card (front/back content)
     */
    public function editCard(string $tmpId, string $front, string $back): void
    {
        $this->cards = $this->getCards()->editCard($tmpId, $front, $back);
        $this->syncCardsToDatabase();

        // Update denormalized counter
        $this->editedCount = $this->cards->editedCount();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Mark a preview card as deleted
     */
    public function deleteCard(string $tmpId): void
    {
        $this->cards = $this->getCards()->deleteCard($tmpId);
        $this->syncCardsToDatabase();

        // Update denormalized counter
        $this->deletedCount = $this->cards->deletedCount();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Link this job to a saved Set after user accepts preview
     */
    public function linkToSet(Uuid $setId): void
    {
        if ($this->setId !== null) {
            throw new \DomainException('Job already linked to a set');
        }

        if ($this->getCards()->allDeleted()) {
            throw new \DomainException('Cannot save set - all cards are deleted');
        }

        $this->setId = $setId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Sync in-memory cards collection to database representation
     */
    private function syncCardsToDatabase(): void
    {
        $this->cardsData = $this->cards->toArray();
    }
}