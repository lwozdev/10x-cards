<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Value;

use App\Domain\Value\CardBack;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CardBack Value Object.
 *
 * Business rules:
 * - Cannot be empty after trimming whitespace
 * - Maximum 1000 characters
 * - Case-sensitive equality comparison
 */
final class CardBackTest extends TestCase
{
    // ===== Validation Tests =====

    public function testRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Card back cannot be empty');

        CardBack::fromString('');
    }

    public function testRejectsWhitespaceOnlyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Card back cannot be empty');

        CardBack::fromString('   ');
    }

    public function testRejectsVariousWhitespacePatterns(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Card back cannot be empty');

        CardBack::fromString("  \t\n\r  ");
    }

    public function testRejectsContentExceedingMaximumLength(): void
    {
        $longContent = str_repeat('a', 1001);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Card back cannot exceed 1000 characters');

        CardBack::fromString($longContent);
    }

    public function testRejectsContentExceedingMaximumLengthWithMultibyte(): void
    {
        $longContent = str_repeat('ą', 1001);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Card back cannot exceed 1000 characters');

        CardBack::fromString($longContent);
    }

    // ===== Valid Creation Tests =====

    public function testCreatesWithValidContent(): void
    {
        $content = 'Photosynthesis is the process by which plants convert light energy into chemical energy.';
        $back = CardBack::fromString($content);

        $this->assertInstanceOf(CardBack::class, $back);
        $this->assertSame($content, $back->toString());
    }

    public function testCreatesWithSingleCharacter(): void
    {
        $back = CardBack::fromString('A');

        $this->assertSame('A', $back->toString());
    }

    public function testCreatesWithMaximumAllowedLength(): void
    {
        $maxContent = str_repeat('a', 1000);
        $back = CardBack::fromString($maxContent);

        $this->assertSame(1000, mb_strlen($back->toString()));
        $this->assertSame($maxContent, $back->toString());
    }

    #[DataProvider('validContentProvider')]
    public function testCreatesWithVariousValidContent(string $content): void
    {
        $back = CardBack::fromString($content);

        $this->assertInstanceOf(CardBack::class, $back);
        $this->assertSame($content, $back->toString());
    }

    public static function validContentProvider(): array
    {
        return [
            'simple_answer' => ['DNA stands for Deoxyribonucleic Acid'],
            'with_numbers' => ['The answer is 4'],
            'with_special_chars' => ['H₂O = Water molecule'],
            'with_polish_chars' => ['Mitochondrium to elektrownia komórki'],
            'with_emoji' => ['🌍 represents Earth'],
            'multiline' => ["Answer:\n- Point 1\n- Point 2\n- Point 3"],
            'long_content' => [str_repeat('Detailed answer. ', 50)], // ~850 chars
        ];
    }

    // ===== Content Preservation =====

    public function testPreservesLeadingWhitespace(): void
    {
        $content = '   Answer with spaces';
        $back = CardBack::fromString($content);

        $this->assertSame($content, $back->toString());
    }

    public function testPreservesTrailingWhitespace(): void
    {
        $content = 'Answer with spaces   ';
        $back = CardBack::fromString($content);

        $this->assertSame($content, $back->toString());
    }

    public function testPreservesInternalWhitespace(): void
    {
        $content = 'Answer    with    multiple    spaces';
        $back = CardBack::fromString($content);

        $this->assertSame($content, $back->toString());
    }

    public function testPreservesNewlines(): void
    {
        $content = "Answer:\nPhotosynthesis is a biological process.";
        $back = CardBack::fromString($content);

        $this->assertSame($content, $back->toString());
    }

    // ===== Equality Tests (Case-Sensitive) =====

    public function testEqualsReturnsTrueForIdenticalContent(): void
    {
        $back1 = CardBack::fromString('Deoxyribonucleic Acid');
        $back2 = CardBack::fromString('Deoxyribonucleic Acid');

        $this->assertTrue($back1->equals($back2));
    }

    public function testEqualsReturnsFalseForDifferentCase(): void
    {
        $back1 = CardBack::fromString('Deoxyribonucleic Acid');
        $back2 = CardBack::fromString('deoxyribonucleic acid');

        // Case-sensitive comparison
        $this->assertFalse($back1->equals($back2));
    }

    public function testEqualsReturnsFalseForDifferentContent(): void
    {
        $back1 = CardBack::fromString('DNA');
        $back2 = CardBack::fromString('RNA');

        $this->assertFalse($back1->equals($back2));
    }

    public function testEqualsIsCaseSensitive(): void
    {
        $back1 = CardBack::fromString('Answer');
        $back2 = CardBack::fromString('ANSWER');

        $this->assertFalse($back1->equals($back2));
    }

    // ===== UTF-8 Handling =====

    public function testHandlesPolishCharacters(): void
    {
        $polishContent = 'Fotosynteza to proces przetwarzania energii świetlnej.';
        $back = CardBack::fromString($polishContent);

        $this->assertSame($polishContent, $back->toString());
    }

    public function testHandlesEmoji(): void
    {
        $contentWithEmoji = '🔬 represents a microscope used in biology';
        $back = CardBack::fromString($contentWithEmoji);

        $this->assertSame($contentWithEmoji, $back->toString());
    }

    public function testAccepts1000MultibyteCharacters(): void
    {
        $content = str_repeat('ł', 1000);
        $back = CardBack::fromString($content);

        $this->assertSame(1000, mb_strlen($back->toString()));
    }

    public function testMultibyteCharacterCounting(): void
    {
        // Mix of ASCII and multibyte characters
        $content = str_repeat('aą', 500); // 500 * 2 = 1000 characters

        $back = CardBack::fromString($content);

        $this->assertSame(1000, mb_strlen($back->toString(), 'UTF-8'));
    }

    // ===== Public API Tests =====

    public function testToStringReturnsOriginalValue(): void
    {
        $content = 'Original answer content';
        $back = CardBack::fromString($content);

        $this->assertSame($content, $back->toString());
    }

    public function testValuePropertyIsAccessible(): void
    {
        $content = 'Answer content';
        $back = CardBack::fromString($content);

        $this->assertSame($content, $back->value);
    }

    public function testFromStringIsIdempotent(): void
    {
        $content = 'Biology is the study of life';
        $back1 = CardBack::fromString($content);
        $back2 = CardBack::fromString($content);

        $this->assertTrue($back1->equals($back2));
        $this->assertSame($back1->toString(), $back2->toString());
    }

    // ===== Edge Cases =====

    public function testHandlesSpecialCharacters(): void
    {
        $specialChars = 'Formula: E=mc² means Energy equals mass times speed of light squared';
        $back = CardBack::fromString($specialChars);

        $this->assertSame($specialChars, $back->toString());
    }

    public function testHandlesHTMLEntities(): void
    {
        $htmlContent = '<p>Answer with <strong>bold</strong> text</p>';
        $back = CardBack::fromString($htmlContent);

        // Should preserve HTML as-is (no escaping at value object level)
        $this->assertSame($htmlContent, $back->toString());
    }

    public function testHandlesTabCharacters(): void
    {
        $contentWithTabs = "Answer:\tDetail A\tDetail B";
        $back = CardBack::fromString($contentWithTabs);

        $this->assertSame($contentWithTabs, $back->toString());
    }

    public function testHandlesBulletPointLists(): void
    {
        $listContent = "Answer:\n• Point 1\n• Point 2\n• Point 3";
        $back = CardBack::fromString($listContent);

        $this->assertSame($listContent, $back->toString());
    }

    public function testHandlesNumberedLists(): void
    {
        $listContent = "Steps:\n1. First step\n2. Second step\n3. Third step";
        $back = CardBack::fromString($listContent);

        $this->assertSame($listContent, $back->toString());
    }
}
