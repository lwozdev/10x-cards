<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Value;

use App\Domain\Value\SetName;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SetName Value Object
 *
 * Business rules:
 * - Cannot be empty after trimming whitespace
 * - Maximum 255 characters
 * - Case-insensitive equality comparison (mimics CITEXT behavior)
 */
final class SetNameTest extends TestCase
{
    // ===== Validation Tests =====

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Set name cannot be empty');

        SetName::fromString('');
    }

    public function testRejectsWhitespaceOnlyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Set name cannot be empty');

        SetName::fromString('   ');
    }

    public function testRejectsVariousWhitespacePatterns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Set name cannot be empty');

        SetName::fromString("  \t\n\r  ");
    }

    public function testRejectsNameExceedingMaximumLength(): void
    {
        $longName = str_repeat('a', 256);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Set name cannot exceed 255 characters');

        SetName::fromString($longName);
    }

    public function testRejectsNameExceedingMaximumLengthAfterTrim(): void
    {
        // 257 total chars: 1 space + 256 'a's
        $longName = ' ' . str_repeat('a', 256);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Set name cannot exceed 255 characters');

        SetName::fromString($longName);
    }

    // ===== Valid Creation Tests =====

    public function testCreatesWithValidName(): void
    {
        $name = SetName::fromString('My Flashcard Set');

        $this->assertInstanceOf(SetName::class, $name);
        $this->assertSame('My Flashcard Set', $name->toString());
    }

    public function testCreatesWithSingleCharacter(): void
    {
        $name = SetName::fromString('A');

        $this->assertSame('A', $name->toString());
    }

    public function testCreatesWithMaximumAllowedLength(): void
    {
        $maxName = str_repeat('a', 255);
        $name = SetName::fromString($maxName);

        $this->assertSame(255, mb_strlen($name->toString()));
        $this->assertSame($maxName, $name->toString());
    }

    #[DataProvider('validNamesProvider')]
    public function testCreatesWithVariousValidNames(string $validName): void
    {
        $name = SetName::fromString($validName);

        $this->assertInstanceOf(SetName::class, $name);
    }

    public static function validNamesProvider(): array
    {
        return [
            'simple' => ['Biology Flashcards'],
            'with_numbers' => ['Chapter 1 - Introduction'],
            'with_special_chars' => ['Math: Algebra & Geometry'],
            'with_polish_chars' => ['Historia Polski - Średniowiecze'],
            'with_emoji' => ['Science 🔬 Chemistry'],
            'single_word' => ['Physics'],
            'long_name' => [str_repeat('Long Set Name ', 15)], // ~210 chars
        ];
    }

    // ===== Trimming Behavior =====

    public function testTrimsLeadingWhitespace(): void
    {
        $name = SetName::fromString('   Biology');

        $this->assertSame('   Biology', $name->toString());
    }

    public function testTrimsTrailingWhitespace(): void
    {
        $name = SetName::fromString('Biology   ');

        $this->assertSame('Biology   ', $name->toString());
    }

    public function testTrimsBothEndsWhitespace(): void
    {
        $name = SetName::fromString('   Biology   ');

        $this->assertSame('   Biology   ', $name->toString());
    }

    public function testPreservesInternalWhitespace(): void
    {
        $name = SetName::fromString('My    Flashcard    Set');

        // Internal whitespace should be preserved
        $this->assertSame('My    Flashcard    Set', $name->toString());
    }

    // ===== Equality Tests (Case-Insensitive) =====

    public function testEqualsReturnsTrueForIdenticalNames(): void
    {
        $name1 = SetName::fromString('Biology');
        $name2 = SetName::fromString('Biology');

        $this->assertTrue($name1->equals($name2));
    }

    public function testEqualsReturnsTrueForCaseInsensitiveMatch(): void
    {
        $name1 = SetName::fromString('Biology');
        $name2 = SetName::fromString('BIOLOGY');

        $this->assertTrue($name1->equals($name2));
    }

    public function testEqualsReturnsTrueForMixedCaseMatch(): void
    {
        $name1 = SetName::fromString('BiOLoGy');
        $name2 = SetName::fromString('biology');

        $this->assertTrue($name1->equals($name2));
    }

    public function testEqualsReturnsFalseForDifferentNames(): void
    {
        $name1 = SetName::fromString('Biology');
        $name2 = SetName::fromString('Chemistry');

        $this->assertFalse($name1->equals($name2));
    }

    #[DataProvider('caseInsensitiveMatchProvider')]
    public function testCaseInsensitiveEqualityVariations(string $name1, string $name2, bool $shouldMatch): void
    {
        $setName1 = SetName::fromString($name1);
        $setName2 = SetName::fromString($name2);

        $this->assertSame($shouldMatch, $setName1->equals($setName2));
    }

    public static function caseInsensitiveMatchProvider(): array
    {
        return [
            'exact_match' => ['Biology', 'Biology', true],
            'all_uppercase' => ['BIOLOGY', 'biology', true],
            'all_lowercase' => ['biology', 'BIOLOGY', true],
            'mixed_case' => ['BiOLoGy', 'bIoLoGy', true],
            'different_names' => ['Biology', 'Chemistry', false],
            'substring_not_equal' => ['Bio', 'Biology', false],
            'with_spaces_match' => ['My Set', 'MY SET', true],
            'with_spaces_different' => ['My Set', 'My  Set', false], // different number of spaces
        ];
    }

    // ===== UTF-8 Handling =====

    public function testHandlesPolishCharactersCorrectly(): void
    {
        $polishName = 'Zestaw fiszek: Język Polski - Średniowiecze';
        $name = SetName::fromString($polishName);

        $this->assertSame($polishName, $name->toString());
    }

    public function testCaseInsensitiveComparisonWithPolishCharacters(): void
    {
        $name1 = SetName::fromString('Język Polski');
        $name2 = SetName::fromString('JĘZYK POLSKI');

        // mb_strtolower should handle Polish chars correctly
        $this->assertTrue($name1->equals($name2));
    }

    public function testHandlesEmojiInSetName(): void
    {
        $nameWithEmoji = 'Science 🔬 Biology 🧬';
        $name = SetName::fromString($nameWithEmoji);

        $this->assertSame($nameWithEmoji, $name->toString());
    }

    public function testRejectsNameWith256MultibyteCharacters(): void
    {
        $longPolishName = str_repeat('ą', 256); // 256 Polish characters

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Set name cannot exceed 255 characters');

        SetName::fromString($longPolishName);
    }

    public function testAccepts255MultibyteCharacters(): void
    {
        $polishName = str_repeat('ł', 255);
        $name = SetName::fromString($polishName);

        $this->assertSame(255, mb_strlen($name->toString()));
    }

    // ===== Public API Tests =====

    public function testToStringReturnsOriginalValue(): void
    {
        $original = 'My Flashcard Set';
        $name = SetName::fromString($original);

        $this->assertSame($original, $name->toString());
    }

    public function testValuePropertyIsAccessible(): void
    {
        $name = SetName::fromString('Biology');

        $this->assertSame('Biology', $name->value);
    }

    public function testFromStringIsIdempotent(): void
    {
        $input = 'Physics 101';
        $name1 = SetName::fromString($input);
        $name2 = SetName::fromString($input);

        $this->assertTrue($name1->equals($name2));
        $this->assertSame($name1->toString(), $name2->toString());
    }

    // ===== Edge Cases =====

    public function testHandlesSpecialCharacters(): void
    {
        $specialChars = 'Math: Algebra & Geometry (2024) - Part #1';
        $name = SetName::fromString($specialChars);

        $this->assertSame($specialChars, $name->toString());
    }

    public function testHandlesNewlineCharacters(): void
    {
        $nameWithNewline = "Biology\nChapter 1";
        $name = SetName::fromString($nameWithNewline);

        // Newlines are preserved (not trimmed from middle)
        $this->assertSame($nameWithNewline, $name->toString());
    }

    public function testPreservesCaseInStoredValue(): void
    {
        $mixedCase = 'BiOLoGy';
        $name = SetName::fromString($mixedCase);

        // Original case should be preserved in storage
        $this->assertSame('BiOLoGy', $name->toString());
        // But comparison should be case-insensitive
        $this->assertTrue($name->equals(SetName::fromString('biology')));
    }
}
