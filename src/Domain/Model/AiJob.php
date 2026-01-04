<?php

declare(strict_types=1);

namespace App\Domain\Model;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * AI Job - tracks AI flashcard generation for KPI metrics
 *
 * Purpose: Optional KPI tracking only. No server-side preview.
 * Flow:
 *   1. POST /api/generate creates AiJob with status=SUCCEEDED/FAILED
 *   2. Frontend manages card editing/deletion locally
 *   3. POST /api/sets updates AiJob with set_id, accepted_count, edited_count
 *
 * KPI Metrics:
 *   - Acceptance rate = accepted_count / generated_count (target: 75%)
 *   - Deleted count = generated_count - accepted_count
 *   - Edit rate = edited_count / accepted_count
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

    /**
     * Set ID - filled when user saves the set (POST /api/sets)
     * NULL until then
     */
    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $setId = null;

    #[ORM\Column(type: 'ai_job_status')]
    private AiJobStatus $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $requestPrompt = null;

    /**
     * How many cards AI generated
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $generatedCount = 0;

    /**
     * How many cards user saved (filled when POST /api/sets)
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $acceptedCount = 0;

    /**
     * How many saved cards were edited before saving (filled when POST /api/sets)
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $editedCount = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $suggestedName = null;

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
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Create new successful AI generation job
     */
    public static function createSucceeded(
        Uuid $userId,
        string $requestPrompt,
        int $generatedCount,
        ?string $suggestedName,
        string $modelName,
        int $tokensIn,
        int $tokensOut
    ): self {
        $job = new self();
        $job->userId = $userId;
        $job->requestPrompt = $requestPrompt;
        $job->status = AiJobStatus::SUCCEEDED;
        $job->generatedCount = $generatedCount;
        $job->suggestedName = $suggestedName;
        $job->modelName = $modelName;
        $job->tokensIn = $tokensIn;
        $job->tokensOut = $tokensOut;
        $job->completedAt = new \DateTimeImmutable();

        return $job;
    }

    /**
     * Create new failed AI generation job
     */
    public static function createFailed(
        Uuid $userId,
        string $requestPrompt,
        string $errorMessage
    ): self {
        $job = new self();
        $job->userId = $userId;
        $job->requestPrompt = $requestPrompt;
        $job->status = AiJobStatus::FAILED;
        $job->errorMessage = $errorMessage;
        $job->completedAt = new \DateTimeImmutable();

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

    public function getGeneratedCount(): int
    {
        return $this->generatedCount;
    }

    public function getAcceptedCount(): int
    {
        return $this->acceptedCount;
    }

    public function getEditedCount(): int
    {
        return $this->editedCount;
    }

    public function getDeletedCount(): int
    {
        return $this->generatedCount - $this->acceptedCount;
    }

    public function getSuggestedName(): ?string
    {
        return $this->suggestedName;
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

    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }

    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }

    /**
     * Calculate acceptance rate (target: 75%)
     */
    public function getAcceptanceRate(): float
    {
        if ($this->generatedCount === 0) {
            return 0.0;
        }

        return $this->acceptedCount / $this->generatedCount;
    }

    // ===== Intentional Methods (Business Operations) =====

    /**
     * Link this job to a saved Set and record KPI metrics
     * Called when user saves cards via POST /api/sets
     *
     * @param Uuid $setId
     * @param int $acceptedCount Number of cards user saved
     * @param int $editedCount Number of saved cards that were edited
     */
    public function linkToSet(Uuid $setId, int $acceptedCount, int $editedCount): void
    {
        if ($this->setId !== null) {
            throw new \DomainException('Job already linked to a set');
        }

        if (!$this->isSuccessful()) {
            throw new \DomainException('Can only link successful jobs to sets');
        }

        if ($acceptedCount > $this->generatedCount) {
            throw new \DomainException('Cannot accept more cards than generated');
        }

        if ($editedCount > $acceptedCount) {
            throw new \DomainException('Cannot have more edited cards than accepted');
        }

        $this->setId = $setId;
        $this->acceptedCount = $acceptedCount;
        $this->editedCount = $editedCount;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
