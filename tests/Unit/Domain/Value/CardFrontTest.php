<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Value;

use App\Domain\Value\CardFront;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CardFront Value Object.
 *
 * Business rules:
 * - Cannot be empty after trimming whitespace
 * - Maximum 1000 characters
 * - Case-sensitive equality comparison
 */
final class CardFrontTest extends TestCase
{
    // ===== Validation Tests =====

    public function testRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Card front cannot be empty');

        CardFront::fromString('');
    }

    public function testRejectsWhitespaceOnlyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Card front cannot be empty');

        CardFront::fromString('   ');
    }

    public function testRejectsVariousWhitespacePatterns(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Card front cannot be empty');

        CardFront::fromString("  \t\n\r  ");
    }

    public function testRejectsContentExceedingMaximumLength(): void
    {
        $longContent = str_repeat('a', 1001);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Card front cannot exceed 1000 characters');

        CardFront::fromString($longContent);
    }

    public function testRejectsContentExceedingMaximumLengthWithMultibyte(): void
    {
        $longContent = str_repeat('ą', 1001);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Card front cannot exceed 1000 characters');

        CardFront::fromString($longContent);
    }

    // ===== Valid Creation Tests =====

    public function testCreatesWithValidContent(): void
    {
        $content = 'What is photosynthesis?';
        $front = CardFront::fromString($content);

        $this->assertInstanceOf(CardFront::class, $front);
        $this->assertSame($content, $front->toString());
    }

    public function testCreatesWithSingleCharacter(): void
    {
        $front = CardFront::fromString('A');

        $this->assertSame('A', $front->toString());
    }

    public function testCreatesWithMaximumAllowedLength(): void
    {
        $maxContent = str_repeat('a', 1000);
        $front = CardFront::fromString($maxContent);

        $this->assertSame(1000, mb_strlen($front->toString()));
        $this->assertSame($maxContent, $front->toString());
    }

    #[DataProvider('validContentProvider')]
    public function testCreatesWithVariousValidContent(string $content): void
    {
        $front = CardFront::fromString($content);

        $this->assertInstanceOf(CardFront::class, $front);
        $this->assertSame($content, $front->toString());
    }

    public static function validContentProvider(): array
    {
        return [
            'simple_question' => ['What is DNA?'],
            'with_numbers' => ['Calculate: 2 + 2 = ?'],
            'with_special_chars' => ['H₂O chemical formula?'],
            'with_polish_chars' => ['Co to jest mitochondrium?'],
            'with_emoji' => ['What does 🌍 represent?'],
            'multiline' => ["Line 1\nLine 2\nLine 3"],
            'long_content' => [str_repeat('Question ', 100)], // ~900 chars
        ];
    }

    // ===== Content Preservation =====

    public function testPreservesLeadingWhitespace(): void
    {
        $content = '   Question with spaces';
        $front = CardFront::fromString($content);

        $this->assertSame($content, $front->toString());
    }

    public function testPreservesTrailingWhitespace(): void
    {
        $content = 'Question with spaces   ';
        $front = CardFront::fromString($content);

        $this->assertSame($content, $front->toString());
    }

    public function testPreservesInternalWhitespace(): void
    {
        $content = 'Question    with    multiple    spaces';
        $front = CardFront::fromString($content);

        $this->assertSame($content, $front->toString());
    }

    public function testPreservesNewlines(): void
    {
        $content = "Question:\nWhat is photosynthesis?";
        $front = CardFront::fromString($content);

        $this->assertSame($content, $front->toString());
    }

    // ===== Equality Tests (Case-Sensitive) =====

    public function testEqualsReturnsTrueForIdenticalContent(): void
    {
        $front1 = CardFront::fromString('What is DNA?');
        $front2 = CardFront::fromString('What is DNA?');

        $this->assertTrue($front1->equals($front2));
    }

    public function testEqualsReturnsFalseForDifferentCase(): void
    {
        $front1 = CardFront::fromString('What is DNA?');
        $front2 = CardFront::fromString('what is dna?');

        // Case-sensitive comparison
        $this->assertFalse($front1->equals($front2));
    }

    public function testEqualsReturnsFalseForDifferentContent(): void
    {
        $front1 = CardFront::fromString('What is DNA?');
        $front2 = CardFront::fromString('What is RNA?');

        $this->assertFalse($front1->equals($front2));
    }

    public function testEqualsIsCaseSensitive(): void
    {
        $front1 = CardFront::fromString('Biology');
        $front2 = CardFront::fromString('BIOLOGY');

        $this->assertFalse($front1->equals($front2));
    }

    // ===== UTF-8 Handling =====

    public function testHandlesPolishCharacters(): void
    {
        $polishContent = 'Co to jest fotosyntezy?';
        $front = CardFront::fromString($polishContent);

        $this->assertSame($polishContent, $front->toString());
    }

    public function testHandlesEmoji(): void
    {
        $contentWithEmoji = 'What does 🔬 represent in science?';
        $front = CardFront::fromString($contentWithEmoji);

        $this->assertSame($contentWithEmoji, $front->toString());
    }

    public function testAccepts1000MultibyteCharacters(): void
    {
        $content = str_repeat('ł', 1000);
        $front = CardFront::fromString($content);

        $this->assertSame(1000, mb_strlen($front->toString()));
    }

    public function testMultibyteCharacterCounting(): void
    {
        // Mix of ASCII and multibyte characters
        $content = str_repeat('aą', 500); // 500 * 2 = 1000 characters

        $front = CardFront::fromString($content);

        $this->assertSame(1000, mb_strlen($front->toString(), 'UTF-8'));
    }

    // ===== Public API Tests =====

    public function testToStringReturnsOriginalValue(): void
    {
        $content = 'Original question content';
        $front = CardFront::fromString($content);

        $this->assertSame($content, $front->toString());
    }

    public function testValuePropertyIsAccessible(): void
    {
        $content = 'Question content';
        $front = CardFront::fromString($content);

        $this->assertSame($content, $front->value);
    }

    public function testFromStringIsIdempotent(): void
    {
        $content = 'What is biology?';
        $front1 = CardFront::fromString($content);
        $front2 = CardFront::fromString($content);

        $this->assertTrue($front1->equals($front2));
        $this->assertSame($front1->toString(), $front2->toString());
    }

    // ===== Edge Cases =====

    public function testHandlesSpecialCharacters(): void
    {
        $specialChars = 'Formula: E=mc² & F=ma (Physics)';
        $front = CardFront::fromString($specialChars);

        $this->assertSame($specialChars, $front->toString());
    }

    public function testHandlesHTMLEntities(): void
    {
        $htmlContent = '<strong>Bold question</strong>';
        $front = CardFront::fromString($htmlContent);

        // Should preserve HTML as-is (no escaping at value object level)
        $this->assertSame($htmlContent, $front->toString());
    }

    public function testHandlesTabCharacters(): void
    {
        $contentWithTabs = "Question:\tAnswer option A\tOption B";
        $front = CardFront::fromString($contentWithTabs);

        $this->assertSame($contentWithTabs, $front->toString());
    }

    public function testHandlesVeryShortContent(): void
    {
        $short = 'Q?';
        $front = CardFront::fromString($short);

        $this->assertSame('Q?', $front->toString());
    }
}
