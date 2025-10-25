<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Value\UserId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ai_jobs')]
#[ORM\Index(name: 'ai_jobs_user_time', columns: ['user_id', 'created_at'])]
#[ORM\Index(name: 'ai_jobs_status_time', columns: ['status', 'created_at'])]
#[ORM\Index(name: 'ai_jobs_set', columns: ['set_id'])]
class AiJob
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'guid')]
    private string $userId;

    #[ORM\Column(name: 'set_id', type: 'guid', nullable: true)]
    private ?string $setId = null;

    #[ORM\Column(type: 'string', length: 20, enumType: AiJobStatus::class)]
    private AiJobStatus $status;

    #[ORM\Column(name: 'error_message', type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(name: 'request_prompt', type: 'text', nullable: true)]
    private ?string $requestPrompt = null;

    #[ORM\Column(name: 'response_raw', type: 'json', nullable: true)]
    private ?array $responseRaw = null;

    #[ORM\Column(name: 'model_name', type: 'text', nullable: true)]
    private ?string $modelName = null;

    #[ORM\Column(name: 'tokens_in', type: 'integer', nullable: true)]
    private ?int $tokensIn = null;

    #[ORM\Column(name: 'tokens_out', type: 'integer', nullable: true)]
    private ?int $tokensOut = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'completed_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    private function __construct(
        string $id,
        UserId $userId,
        string $requestPrompt,
        DateTimeImmutable $createdAt
    ) {
        $promptLength = mb_strlen($requestPrompt);
        if ($promptLength < 1000 || $promptLength > 10000) {
            throw new \InvalidArgumentException('Request prompt must be between 1000 and 10000 characters');
        }

        $this->id = $id;
        $this->userId = $userId->toString();
        $this->requestPrompt = $requestPrompt;
        $this->status = AiJobStatus::QUEUED;
        $this->createdAt = $createdAt;
        $this->updatedAt = $createdAt;
    }

    public static function create(
        string $id,
        UserId $userId,
        string $requestPrompt,
        DateTimeImmutable $createdAt
    ): self {
        return new self($id, $userId, $requestPrompt, $createdAt);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }

    public function getSetId(): ?string
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

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function markAsRunning(DateTimeImmutable $updatedAt): void
    {
        if ($this->status !== AiJobStatus::QUEUED) {
            throw new \LogicException('Can only mark queued jobs as running');
        }

        $this->status = AiJobStatus::RUNNING;
        $this->updatedAt = $updatedAt;
    }

    public function markAsSucceeded(
        ?string $setId,
        array $responseRaw,
        string $modelName,
        int $tokensIn,
        int $tokensOut,
        DateTimeImmutable $completedAt
    ): void {
        if (!$this->status->isTerminal()) {
            $this->status = AiJobStatus::SUCCEEDED;
            $this->setId = $setId;
            $this->responseRaw = $responseRaw;
            $this->modelName = $modelName;
            $this->tokensIn = $tokensIn;
            $this->tokensOut = $tokensOut;
            $this->completedAt = $completedAt;
            $this->updatedAt = $completedAt;
        }
    }

    public function markAsFailed(string $errorMessage, DateTimeImmutable $completedAt): void
    {
        if (!$this->status->isTerminal()) {
            $this->status = AiJobStatus::FAILED;
            $this->errorMessage = $errorMessage;
            $this->completedAt = $completedAt;
            $this->updatedAt = $completedAt;
        }
    }
}
