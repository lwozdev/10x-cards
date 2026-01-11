<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Value;

use App\Domain\Value\SourceText;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SourceText Value Object
 *
 * Business rules:
 * - Minimum 1000 characters
 * - Maximum 10000 characters
 * - Cannot be empty after trimming whitespace
 * - Must handle UTF-8 characters correctly
 */
final class SourceTextTest extends TestCase
{
    // ===== Validation Tests =====

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source text cannot be empty');

        SourceText::fromString('');
    }

    public function testRejectsWhitespaceOnlyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source text cannot be empty');

        SourceText::fromString("   \n\t  ");
    }

    public function testRejectsTextBelowMinimumLength(): void
    {
        $text = str_repeat('a', 999);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source text must be at least 1000 characters long, got 999');

        SourceText::fromString($text);
    }

    public function testRejectsTextExceedingMaximumLength(): void
    {
        $text = str_repeat('a', 10001);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source text must not exceed 10000 characters, got 10001');

        SourceText::fromString($text);
    }

    public function testRejectsTextWithWhitespaceAtBoundaries(): void
    {
        // Text appears to be 1002 chars, but after trim is only 1000
        // Should pass since trimmed length equals minimum
        $text = '  ' . str_repeat('a', 1000) . '  ';
        $sourceText = SourceText::fromString($text);

        $this->assertInstanceOf(SourceText::class, $sourceText);
    }

    public function testRejectsTextThatIsTooShortAfterTrim(): void
    {
        // Text appears to be 1002 chars, but after trim is only 998
        $text = '  ' . str_repeat('a', 998) . '  ';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source text must be at least 1000 characters long, got 998');

        SourceText::fromString($text);
    }

    // ===== Valid Creation Tests =====

    public function testCreatesWithExactMinimumLength(): void
    {
        $text = str_repeat('a', 1000);
        $sourceText = SourceText::fromString($text);

        $this->assertInstanceOf(SourceText::class, $sourceText);
        $this->assertSame($text, $sourceText->toString());
        $this->assertSame(1000, $sourceText->length());
    }

    public function testCreatesWithExactMaximumLength(): void
    {
        $text = str_repeat('a', 10000);
        $sourceText = SourceText::fromString($text);

        $this->assertInstanceOf(SourceText::class, $sourceText);
        $this->assertSame($text, $sourceText->toString());
        $this->assertSame(10000, $sourceText->length());
    }

    #[DataProvider('validLengthProvider')]
    public function testCreatesWithValidLengthRange(int $length): void
    {
        $text = str_repeat('x', $length);
        $sourceText = SourceText::fromString($text);

        $this->assertSame($length, $sourceText->length());
        $this->assertSame($text, $sourceText->toString());
    }

    public static function validLengthProvider(): array
    {
        return [
            'minimum_1000' => [1000],
            'mid_range_2500' => [2500],
            'mid_range_5000' => [5000],
            'mid_range_7500' => [7500],
            'maximum_10000' => [10000],
        ];
    }

    // ===== UTF-8 Handling Tests =====

    public function testHandlesMultibyteCharactersCorrectly(): void
    {
        // Polish characters: ą, ć, ę, ł, ń, ó, ś, ź, ż (each is 2 bytes in UTF-8)
        // Using pattern without trailing space to avoid trim issues
        $polishText = str_repeat('ąćęłńóśźż', 111) . 'ą'; // 9 * 111 + 1 = 1000 chars

        $sourceText = SourceText::fromString($polishText);

        $this->assertSame(1000, $sourceText->length());
        $this->assertSame($polishText, $sourceText->toString());
    }

    public function testHandlesEmojiCorrectly(): void
    {
        // Emoji can be 1-4 bytes in UTF-8, but count as 1 character
        $baseText = str_repeat('a', 995);
        $textWithEmoji = $baseText . '😀😁😂😃😄'; // 995 + 5 = 1000 chars

        $sourceText = SourceText::fromString($textWithEmoji);

        $this->assertSame(1000, $sourceText->length());
    }

    public function testRejectsMultibyteTextExceedingLength(): void
    {
        // 10001 Polish characters should be rejected
        $polishText = str_repeat('ą', 10001);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source text must not exceed 10000 characters, got 10001');

        SourceText::fromString($polishText);
    }

    // ===== Edge Cases =====

    public function testHandlesNewlinesAndSpecialCharacters(): void
    {
        $text = str_repeat("Line 1\nLine 2\tTabbed\r\n", 50); // Should be ~1000 chars

        // Ensure it's at least 1000 chars
        if (mb_strlen($text) < 1000) {
            $text .= str_repeat('x', 1000 - mb_strlen($text));
        }

        $sourceText = SourceText::fromString($text);

        $this->assertGreaterThanOrEqual(1000, $sourceText->length());
        $this->assertLessThanOrEqual(10000, $sourceText->length());
    }

    public function testPreservesOriginalContent(): void
    {
        $text = str_repeat('Test content with spaces.   ', 40); // ~1120 chars

        $sourceText = SourceText::fromString($text);

        // Should preserve exact content (no trimming of the text itself, only for validation)
        $this->assertSame($text, $sourceText->toString());
    }

    // ===== Public API Tests =====

    public function testLengthMethodReturnsCorrectValue(): void
    {
        $text = str_repeat('a', 5000);
        $sourceText = SourceText::fromString($text);

        $this->assertSame(5000, $sourceText->length());
        $this->assertSame(mb_strlen($text, 'UTF-8'), $sourceText->length());
    }

    public function testToStringMethodReturnsOriginalValue(): void
    {
        $text = str_repeat('Sample text for testing. ', 50); // ~1250 chars
        $sourceText = SourceText::fromString($text);

        $this->assertSame($text, $sourceText->toString());
    }

    public function testFromStringIsIdempotent(): void
    {
        $text = str_repeat('a', 3000);
        $sourceText1 = SourceText::fromString($text);
        $sourceText2 = SourceText::fromString($text);

        $this->assertEquals($sourceText1->toString(), $sourceText2->toString());
        $this->assertEquals($sourceText1->length(), $sourceText2->length());
    }
}
