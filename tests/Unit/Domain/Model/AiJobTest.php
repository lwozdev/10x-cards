<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Model;

use App\Domain\Model\AiJob;
use App\Domain\Model\AiJobStatus;
use DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for AiJob Domain Model
 *
 * Critical business logic:
 * - Acceptance rate calculation (KPI metric: target 75%)
 * - Deleted count calculation
 * - Set linking validation
 */
final class AiJobTest extends TestCase
{
    // ===== Factory Methods Tests =====

    public function testCreateSucceededJobWithAllData(): void
    {
        $userId = Uuid::v4();
        $prompt = str_repeat('Sample text ', 100);
        $generatedCount = 12;
        $suggestedName = 'Biology Flashcards';
        $modelName = 'anthropic/claude-3.5-sonnet';
        $tokensIn = 1500;
        $tokensOut = 800;

        $job = AiJob::createSucceeded(
            userId: $userId,
            requestPrompt: $prompt,
            generatedCount: $generatedCount,
            suggestedName: $suggestedName,
            modelName: $modelName,
            tokensIn: $tokensIn,
            tokensOut: $tokensOut
        );

        $this->assertInstanceOf(AiJob::class, $job);
        $this->assertInstanceOf(Uuid::class, $job->getId());
        $this->assertEquals($userId, $job->getUserId());
        $this->assertSame($prompt, $job->getRequestPrompt());
        $this->assertSame($generatedCount, $job->getGeneratedCount());
        $this->assertSame($suggestedName, $job->getSuggestedName());
        $this->assertSame($modelName, $job->getModelName());
        $this->assertSame($tokensIn, $job->getTokensIn());
        $this->assertSame($tokensOut, $job->getTokensOut());
        $this->assertSame(AiJobStatus::SUCCEEDED, $job->getStatus());
        $this->assertTrue($job->isSuccessful());
        $this->assertFalse($job->isFailed());
        $this->assertNotNull($job->getCompletedAt());
        $this->assertNull($job->getSetId());
        $this->assertSame(0, $job->getAcceptedCount());
        $this->assertSame(0, $job->getEditedCount());
    }

    public function testCreateSucceededJobWithNullSuggestedName(): void
    {
        $userId = Uuid::v4();
        $job = AiJob::createSucceeded(
            userId: $userId,
            requestPrompt: 'Test prompt',
            generatedCount: 5,
            suggestedName: null,
            modelName: 'test-model',
            tokensIn: 100,
            tokensOut: 50
        );

        $this->assertNull($job->getSuggestedName());
        $this->assertTrue($job->isSuccessful());
    }

    public function testCreateFailedJobWithErrorMessage(): void
    {
        $userId = Uuid::v4();
        $prompt = 'Test prompt text';
        $errorMessage = 'AI service timeout after 30s';

        $job = AiJob::createFailed(
            userId: $userId,
            requestPrompt: $prompt,
            errorMessage: $errorMessage
        );

        $this->assertInstanceOf(AiJob::class, $job);
        $this->assertEquals($userId, $job->getUserId());
        $this->assertSame($prompt, $job->getRequestPrompt());
        $this->assertSame($errorMessage, $job->getErrorMessage());
        $this->assertSame(AiJobStatus::FAILED, $job->getStatus());
        $this->assertTrue($job->isFailed());
        $this->assertFalse($job->isSuccessful());
        $this->assertNotNull($job->getCompletedAt());
        $this->assertSame(0, $job->getGeneratedCount());
        $this->assertNull($job->getModelName());
        $this->assertNull($job->getTokensIn());
        $this->assertNull($job->getTokensOut());
    }

    // ===== Acceptance Rate Calculation Tests (CRITICAL KPI) =====

    public function testGetAcceptanceRateReturnsZeroWhenNoCardsGenerated(): void
    {
        $job = AiJob::createFailed(
            userId: Uuid::v4(),
            requestPrompt: 'Test',
            errorMessage: 'Error'
        );

        $this->assertSame(0.0, $job->getAcceptanceRate());
    }

    #[DataProvider('acceptanceRateProvider')]
    public function testGetAcceptanceRateCalculatesCorrectly(
        int $generatedCount,
        int $acceptedCount,
        float $expectedRate
    ): void {
        $userId = Uuid::v4();
        $setId = Uuid::v4();

        $job = AiJob::createSucceeded(
            userId: $userId,
            requestPrompt: 'Test prompt',
            generatedCount: $generatedCount,
            suggestedName: 'Test Set',
            modelName: 'test-model',
            tokensIn: 100,
            tokensOut: 50
        );

        $job->linkToSet($setId, $acceptedCount, 0);

        $this->assertSame($expectedRate, $job->getAcceptanceRate());
    }

    public static function acceptanceRateProvider(): array
    {
        return [
            'perfect_acceptance' => [10, 10, 1.0],
            'target_75_percent' => [10, 7, 0.7], // Target KPI: 75%
            'exact_75_percent' => [100, 75, 0.75],
            'half_accepted' => [10, 5, 0.5],
            'one_tenth_accepted' => [10, 1, 0.1],
            'none_accepted' => [10, 0, 0.0],
            'single_card_accepted' => [1, 1, 1.0],
            'single_card_rejected' => [1, 0, 0.0],
        ];
    }

    public function testGetAcceptanceRateBeforeLinkingToSet(): void
    {
        $job = AiJob::createSucceeded(
            userId: Uuid::v4(),
            requestPrompt: 'Test',
            generatedCount: 10,
            suggestedName: 'Test',
            modelName: 'model',
            tokensIn: 100,
            tokensOut: 50
        );

        // Before linking, acceptedCount is 0
        $this->assertSame(0.0, $job->getAcceptanceRate());
    }

    // ===== Deleted Count Calculation Tests =====

    #[DataProvider('deletedCountProvider')]
    public function testGetDeletedCountCalculatesCorrectly(
        int $generatedCount,
        int $acceptedCount,
        int $expectedDeletedCount
    ): void {
        $userId = Uuid::v4();
        $setId = Uuid::v4();

        $job = AiJob::createSucceeded(
            userId: $userId,
            requestPrompt: 'Test',
            generatedCount: $generatedCount,
            suggestedName: 'Test',
            modelName: 'model',
            tokensIn: 100,
            tokensOut: 50
        );

        $job->linkToSet($setId, $acceptedCount, 0);

        $this->assertSame($expectedDeletedCount, $job->getDeletedCount());
    }

    public static function deletedCountProvider(): array
    {
        return [
            'none_deleted' => [10, 10, 0],
            'half_deleted' => [10, 5, 5],
            'all_deleted' => [10, 0, 10],
            'single_card_deleted' => [10, 9, 1],
        ];
    }

    // ===== Link to Set Validation Tests (CRITICAL) =====

    public function testLinkToSetSuccessfullyLinksValidJob(): void
    {
        $userId = Uuid::v4();
        $setId = Uuid::v4();
        $generatedCount = 10;
        $acceptedCount = 8;
        $editedCount = 3;

        $job = AiJob::createSucceeded(
            userId: $userId,
            requestPrompt: 'Test',
            generatedCount: $generatedCount,
            suggestedName: 'Test',
            modelName: 'model',
            tokensIn: 100,
            tokensOut: 50
        );

        $job->linkToSet($setId, $acceptedCount, $editedCount);

        $this->assertEquals($setId, $job->getSetId());
        $this->assertSame($acceptedCount, $job->getAcceptedCount());
        $this->assertSame($editedCount, $job->getEditedCount());
    }

    public function testLinkToSetThrowsExceptionWhenAlreadyLinked(): void
    {
        $userId = Uuid::v4();
        $setId1 = Uuid::v4();
        $setId2 = Uuid::v4();

        $job = AiJob::createSucceeded(
            userId: $userId,
            requestPrompt: 'Test',
            generatedCount: 10,
            suggestedName: 'Test',
            modelName: 'model',
            tokensIn: 100,
            tokensOut: 50
        );

        $job->linkToSet($setId1, 5, 0);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Job already linked to a set');

        $job->linkToSet($setId2, 5, 0);
    }

    public function testLinkToSetThrowsExceptionWhenJobFailed(): void
    {
        $userId = Uuid::v4();
        $setId = Uuid::v4();

        $job = AiJob::createFailed(
            userId: $userId,
            requestPrompt: 'Test',
            errorMessage: 'Generation failed'
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Can only link successful jobs to sets');

        $job->linkToSet($setId, 0, 0);
    }

    public function testLinkToSetThrowsExceptionWhenAcceptedExceedsGenerated(): void
    {
        $userId = Uuid::v4();
        $setId = Uuid::v4();

        $job = AiJob::createSucceeded(
            userId: $userId,
            requestPrompt: 'Test',
            generatedCount: 10,
            suggestedName: 'Test',
            modelName: 'model',
            tokensIn: 100,
            tokensOut: 50
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot accept more cards than generated');

        $job->linkToSet($setId, 11, 0); // Trying to accept 11 when only 10 generated
    }

    public function testLinkToSetThrowsExceptionWhenEditedExceedsAccepted(): void
    {
        $userId = Uuid::v4();
        $setId = Uuid::v4();

        $job = AiJob::createSucceeded(
            userId: $userId,
            requestPrompt: 'Test',
            generatedCount: 10,
            suggestedName: 'Test',
            modelName: 'model',
            tokensIn: 100,
            tokensOut: 50
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot have more edited cards than accepted');

        $job->linkToSet($setId, 5, 6); // Trying to edit 6 when only 5 accepted
    }

    public function testLinkToSetAllowsZeroAcceptedCount(): void
    {
        $userId = Uuid::v4();
        $setId = Uuid::v4();

        $job = AiJob::createSucceeded(
            userId: $userId,
            requestPrompt: 'Test',
            generatedCount: 10,
            suggestedName: 'Test',
            modelName: 'model',
            tokensIn: 100,
            tokensOut: 50
        );

        $job->linkToSet($setId, 0, 0); // User deleted all cards

        $this->assertSame(0, $job->getAcceptedCount());
        $this->assertSame(10, $job->getDeletedCount());
        $this->assertSame(0.0, $job->getAcceptanceRate());
    }

    public function testLinkToSetAllowsAcceptedEqualsGenerated(): void
    {
        $userId = Uuid::v4();
        $setId = Uuid::v4();

        $job = AiJob::createSucceeded(
            userId: $userId,
            requestPrompt: 'Test',
            generatedCount: 10,
            suggestedName: 'Test',
            modelName: 'model',
            tokensIn: 100,
            tokensOut: 50
        );

        $job->linkToSet($setId, 10, 0); // User accepted all cards

        $this->assertSame(10, $job->getAcceptedCount());
        $this->assertSame(0, $job->getDeletedCount());
        $this->assertSame(1.0, $job->getAcceptanceRate());
    }

    public function testLinkToSetAllowsEditedEqualsAccepted(): void
    {
        $userId = Uuid::v4();
        $setId = Uuid::v4();

        $job = AiJob::createSucceeded(
            userId: $userId,
            requestPrompt: 'Test',
            generatedCount: 10,
            suggestedName: 'Test',
            modelName: 'model',
            tokensIn: 100,
            tokensOut: 50
        );

        $job->linkToSet($setId, 8, 8); // User edited all accepted cards

        $this->assertSame(8, $job->getAcceptedCount());
        $this->assertSame(8, $job->getEditedCount());
    }

    // ===== Timestamp Tests =====

    public function testCreatedAtIsSetOnCreation(): void
    {
        $job = AiJob::createSucceeded(
            userId: Uuid::v4(),
            requestPrompt: 'Test',
            generatedCount: 5,
            suggestedName: 'Test',
            modelName: 'model',
            tokensIn: 100,
            tokensOut: 50
        );

        $this->assertInstanceOf(\DateTimeImmutable::class, $job->getCreatedAt());
    }

    public function testCompletedAtIsSetForSucceededJob(): void
    {
        $job = AiJob::createSucceeded(
            userId: Uuid::v4(),
            requestPrompt: 'Test',
            generatedCount: 5,
            suggestedName: 'Test',
            modelName: 'model',
            tokensIn: 100,
            tokensOut: 50
        );

        $this->assertInstanceOf(\DateTimeImmutable::class, $job->getCompletedAt());
    }

    public function testCompletedAtIsSetForFailedJob(): void
    {
        $job = AiJob::createFailed(
            userId: Uuid::v4(),
            requestPrompt: 'Test',
            errorMessage: 'Error'
        );

        $this->assertInstanceOf(\DateTimeImmutable::class, $job->getCompletedAt());
    }

    public function testUpdatedAtIsUpdatedAfterLinkingToSet(): void
    {
        $userId = Uuid::v4();
        $setId = Uuid::v4();

        $job = AiJob::createSucceeded(
            userId: $userId,
            requestPrompt: 'Test',
            generatedCount: 10,
            suggestedName: 'Test',
            modelName: 'model',
            tokensIn: 100,
            tokensOut: 50
        );

        $originalUpdatedAt = $job->getUpdatedAt();

        // Small delay to ensure timestamp changes
        usleep(1000);

        $job->linkToSet($setId, 5, 0);

        $this->assertGreaterThan($originalUpdatedAt, $job->getUpdatedAt());
    }

    // ===== Edge Cases =====

    public function testJobIdIsUniqueForEachJob(): void
    {
        $userId = Uuid::v4();

        $job1 = AiJob::createSucceeded(
            userId: $userId,
            requestPrompt: 'Test',
            generatedCount: 5,
            suggestedName: 'Test',
            modelName: 'model',
            tokensIn: 100,
            tokensOut: 50
        );

        $job2 = AiJob::createSucceeded(
            userId: $userId,
            requestPrompt: 'Test',
            generatedCount: 5,
            suggestedName: 'Test',
            modelName: 'model',
            tokensIn: 100,
            tokensOut: 50
        );

        $this->assertNotEquals($job1->getId(), $job2->getId());
    }

    public function testHandlesZeroTokenCounts(): void
    {
        $job = AiJob::createSucceeded(
            userId: Uuid::v4(),
            requestPrompt: 'Test',
            generatedCount: 5,
            suggestedName: 'Test',
            modelName: 'model',
            tokensIn: 0,
            tokensOut: 0
        );

        $this->assertSame(0, $job->getTokensIn());
        $this->assertSame(0, $job->getTokensOut());
    }

    public function testHandlesLargeTokenCounts(): void
    {
        $job = AiJob::createSucceeded(
            userId: Uuid::v4(),
            requestPrompt: 'Test',
            generatedCount: 5,
            suggestedName: 'Test',
            modelName: 'model',
            tokensIn: 100000,
            tokensOut: 50000
        );

        $this->assertSame(100000, $job->getTokensIn());
        $this->assertSame(50000, $job->getTokensOut());
    }
}
