<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Handler;

use App\Application\Handler\GenerateCardsHandler;
use App\Domain\Repository\AiJobRepositoryInterface;
use App\Domain\Service\AiCardGeneratorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for GenerateCardsHandler.
 *
 * Focus: truncateErrorMessage() utility method
 * Critical for preventing DB constraint violations (255 char limit)
 */
final class GenerateCardsHandlerTest extends TestCase
{
    private GenerateCardsHandler $handler;

    protected function setUp(): void
    {
        // Create mock dependencies
        $aiGenerator = $this->createMock(AiCardGeneratorInterface::class);
        $aiJobRepository = $this->createMock(AiJobRepositoryInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->handler = new GenerateCardsHandler(
            $aiGenerator,
            $aiJobRepository,
            $logger
        );
    }

    // ===== truncateErrorMessage Tests =====

    public function testTruncateErrorMessageLeavesShortMessagesUnchanged(): void
    {
        $shortMessage = 'Short error message';

        $result = $this->invokeTruncateErrorMessage($shortMessage);

        $this->assertSame($shortMessage, $result);
    }

    public function testTruncateErrorMessageLeavesExactly255CharsUnchanged(): void
    {
        $message255 = str_repeat('a', 255);

        $result = $this->invokeTruncateErrorMessage($message255);

        $this->assertSame($message255, $result);
        $this->assertSame(255, mb_strlen($result, 'UTF-8'));
    }

    public function testTruncateErrorMessageTruncatesMessageExceeding255Chars(): void
    {
        $message256 = str_repeat('a', 256);

        $result = $this->invokeTruncateErrorMessage($message256);

        $this->assertSame(255, mb_strlen($result, 'UTF-8'));
        $this->assertStringEndsWith('...', $result);
        $this->assertStringStartsWith(str_repeat('a', 252), $result);
    }

    #[DataProvider('messageLengthProvider')]
    public function testTruncateErrorMessageHandlesVariousLengths(
        int $length,
        bool $shouldTruncate,
    ): void {
        $message = str_repeat('x', $length);

        $result = $this->invokeTruncateErrorMessage($message);

        if ($shouldTruncate) {
            $this->assertSame(255, mb_strlen($result, 'UTF-8'));
            $this->assertStringEndsWith('...', $result);
        } else {
            $this->assertSame($message, $result);
            $this->assertSame($length, mb_strlen($result, 'UTF-8'));
        }
    }

    public static function messageLengthProvider(): array
    {
        return [
            'empty' => [0, false],
            'single_char' => [1, false],
            'short_50' => [50, false],
            'medium_150' => [150, false],
            'exact_255' => [255, false],
            'over_by_one' => [256, true],
            'over_by_ten' => [265, true],
            'very_long_500' => [500, true],
            'extremely_long_1000' => [1000, true],
        ];
    }

    // ===== UTF-8 Handling Tests =====

    public function testTruncateErrorMessageHandlesMultibyteCharactersCorrectly(): void
    {
        // Create message with Polish characters (2 bytes each in UTF-8)
        $polishMessage = str_repeat('ą', 256); // 256 characters

        $result = $this->invokeTruncateErrorMessage($polishMessage);

        $this->assertSame(255, mb_strlen($result, 'UTF-8'));
        $this->assertStringEndsWith('...', $result);
        // Should be 252 Polish chars + "..."
        $this->assertSame(str_repeat('ą', 252).'...', $result);
    }

    public function testTruncateErrorMessageHandlesEmojiCorrectly(): void
    {
        // Emoji can be multiple bytes but count as 1 character
        $emojiMessage = str_repeat('😀', 256);

        $result = $this->invokeTruncateErrorMessage($emojiMessage);

        $this->assertSame(255, mb_strlen($result, 'UTF-8'));
        $this->assertStringEndsWith('...', $result);
    }

    public function testTruncateErrorMessageHandlesMixedAsciiAndMultibyte(): void
    {
        // 200 ASCII + 56 Polish chars = 256 total chars
        $mixedMessage = str_repeat('a', 200).str_repeat('ą', 56);

        $result = $this->invokeTruncateErrorMessage($mixedMessage);

        $this->assertSame(255, mb_strlen($result, 'UTF-8'));
        $this->assertStringEndsWith('...', $result);
    }

    public function testTruncateErrorMessageHandlesCyrillicCharacters(): void
    {
        $cyrillicMessage = str_repeat('Я', 256); // Russian character

        $result = $this->invokeTruncateErrorMessage($cyrillicMessage);

        $this->assertSame(255, mb_strlen($result, 'UTF-8'));
        $this->assertStringEndsWith('...', $result);
    }

    public function testTruncateErrorMessageHandlesChineseCharacters(): void
    {
        $chineseMessage = str_repeat('中', 256);

        $result = $this->invokeTruncateErrorMessage($chineseMessage);

        $this->assertSame(255, mb_strlen($result, 'UTF-8'));
        $this->assertStringEndsWith('...', $result);
    }

    // ===== Edge Cases =====

    public function testTruncateErrorMessageHandlesEmptyString(): void
    {
        $result = $this->invokeTruncateErrorMessage('');

        $this->assertSame('', $result);
    }

    public function testTruncateErrorMessageHandlesWhitespace(): void
    {
        $whitespaceMessage = str_repeat(' ', 300);

        $result = $this->invokeTruncateErrorMessage($whitespaceMessage);

        $this->assertSame(255, mb_strlen($result, 'UTF-8'));
        $this->assertStringEndsWith('...', $result);
    }

    public function testTruncateErrorMessageHandlesNewlines(): void
    {
        $messageWithNewlines = str_repeat("Line\n", 60); // ~300 chars

        $result = $this->invokeTruncateErrorMessage($messageWithNewlines);

        $this->assertSame(255, mb_strlen($result, 'UTF-8'));
        $this->assertStringEndsWith('...', $result);
    }

    public function testTruncateErrorMessageHandlesTabCharacters(): void
    {
        $messageWithTabs = str_repeat("Text\t", 60); // ~300 chars

        $result = $this->invokeTruncateErrorMessage($messageWithTabs);

        $this->assertSame(255, mb_strlen($result, 'UTF-8'));
        $this->assertStringEndsWith('...', $result);
    }

    public function testTruncateErrorMessageHandlesSpecialCharacters(): void
    {
        $specialChars = str_repeat('!@#$%^&*()', 30); // 300 chars

        $result = $this->invokeTruncateErrorMessage($specialChars);

        $this->assertSame(255, mb_strlen($result, 'UTF-8'));
        $this->assertStringEndsWith('...', $result);
    }

    // ===== Realistic Error Message Tests =====

    public function testTruncateErrorMessageHandlesRealisticApiError(): void
    {
        $apiError = 'OpenRouter API Error: The model "anthropic/claude-3.5-sonnet" is currently '.
            'unavailable due to high demand. Please try again in a few minutes or use an alternative '.
            'model. Error code: 503. Request ID: req_abc123def456. Timestamp: 2024-01-15T10:30:45Z. '.
            'Additional context: This error typically occurs during peak usage hours. Consider implementing '.
            'exponential backoff or using a different model tier for better availability.';

        $result = $this->invokeTruncateErrorMessage($apiError);

        $this->assertSame(255, mb_strlen($result, 'UTF-8'));
        $this->assertStringEndsWith('...', $result);
        $this->assertStringStartsWith('OpenRouter API Error:', $result);
    }

    public function testTruncateErrorMessageHandlesStackTrace(): void
    {
        $stackTrace = "Fatal error in GenerateCardsHandler\n".
            "Stack trace:\n".
            "#0 /app/src/Handler/GenerateCardsHandler.php(45): App\\Service\\AiService->generate()\n".
            "#1 /app/src/Controller/ApiController.php(123): App\\Handler\\GenerateCardsHandler->handle()\n".
            "#2 /app/vendor/symfony/http-kernel/HttpKernel.php(456): App\\Controller\\ApiController->generateAction()\n".
            '#3 Additional frames omitted for brevity...';

        $result = $this->invokeTruncateErrorMessage($stackTrace);

        $this->assertSame(255, mb_strlen($result, 'UTF-8'));
        $this->assertStringEndsWith('...', $result);
    }

    public function testTruncateErrorMessageHandlesJsonErrorResponse(): void
    {
        $jsonError = json_encode([
            'error' => 'invalid_request',
            'message' => 'The request payload is malformed',
            'details' => str_repeat('x', 300),
            'timestamp' => '2024-01-15T10:30:45Z',
        ]);

        $result = $this->invokeTruncateErrorMessage($jsonError);

        $this->assertSame(255, mb_strlen($result, 'UTF-8'));
        $this->assertStringEndsWith('...', $result);
    }

    // ===== Exact Boundary Tests =====

    public function testTruncateErrorMessageAtBoundary252Chars(): void
    {
        $message252 = str_repeat('a', 252);

        $result = $this->invokeTruncateErrorMessage($message252);

        $this->assertSame($message252, $result);
        $this->assertSame(252, mb_strlen($result, 'UTF-8'));
    }

    public function testTruncateErrorMessageAtBoundary253Chars(): void
    {
        $message253 = str_repeat('a', 253);

        $result = $this->invokeTruncateErrorMessage($message253);

        $this->assertSame($message253, $result);
        $this->assertSame(253, mb_strlen($result, 'UTF-8'));
    }

    public function testTruncateErrorMessageAtBoundary254Chars(): void
    {
        $message254 = str_repeat('a', 254);

        $result = $this->invokeTruncateErrorMessage($message254);

        $this->assertSame($message254, $result);
        $this->assertSame(254, mb_strlen($result, 'UTF-8'));
    }

    public function testTruncateErrorMessageResult252CharsPlus3DotsEquals255(): void
    {
        $message256 = str_repeat('a', 256);

        $result = $this->invokeTruncateErrorMessage($message256);

        // Result should be exactly: 252 'a' + '...' = 255 total
        $expectedResult = str_repeat('a', 252).'...';
        $this->assertSame($expectedResult, $result);
        $this->assertSame(255, mb_strlen($result, 'UTF-8'));
    }

    // ===== Helper Method =====

    /**
     * Use reflection to access private truncateErrorMessage method.
     */
    private function invokeTruncateErrorMessage(string $message): string
    {
        $reflection = new \ReflectionMethod(
            GenerateCardsHandler::class,
            'truncateErrorMessage'
        );
        $reflection->setAccessible(true);

        return $reflection->invoke($this->handler, $message);
    }
}
