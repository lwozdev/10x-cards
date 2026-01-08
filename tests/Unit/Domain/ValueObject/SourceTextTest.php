<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use PHPUnit\Framework\TestCase;

/**
 * Example unit test for SourceText Value Object
 * Tests validation: 1000-10000 characters (as per test-plan.md: TC-AI-01, TC-AI-02)
 */
class SourceTextTest extends TestCase
{
    public function testCannotCreateWithTextBelowMinimumLength(): void
    {
        // Arrange: Text with 999 characters (below minimum of 1000)
        $text = str_repeat('a', 999);

        // Act & Assert: Should throw exception
        $this->markTestIncomplete(
            'SourceText value object not yet implemented. ' .
            'Expected to validate minimum 1000 characters.'
        );

        // Future implementation:
        // $this->expectException(\InvalidArgumentException::class);
        // new SourceText($text);
    }

    public function testCannotCreateWithTextAboveMaximumLength(): void
    {
        // Arrange: Text with 10001 characters (above maximum of 10000)
        $text = str_repeat('a', 10001);

        // Act & Assert: Should throw exception
        $this->markTestIncomplete(
            'SourceText value object not yet implemented. ' .
            'Expected to validate maximum 10000 characters.'
        );

        // Future implementation:
        // $this->expectException(\InvalidArgumentException::class);
        // new SourceText($text);
    }

    public function testCanCreateWithValidTextLength(): void
    {
        // Arrange: Valid text (1000 characters exactly)
        $text = str_repeat('a', 1000);

        // Act & Assert: Should create successfully
        $this->markTestIncomplete(
            'SourceText value object not yet implemented. ' .
            'Expected to accept 1000-10000 characters.'
        );

        // Future implementation:
        // $sourceText = new SourceText($text);
        // $this->assertSame($text, $sourceText->getValue());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validLengthProvider')]
    public function testAcceptsValidLengthRange(int $length): void
    {
        $text = str_repeat('a', $length);

        $this->markTestIncomplete(
            'SourceText value object not yet implemented. ' .
            'Expected to accept lengths: 1000, 5000, 10000.'
        );

        // Future implementation:
        // $sourceText = new SourceText($text);
        // $this->assertEquals($length, strlen($sourceText->getValue()));
    }

    public static function validLengthProvider(): array
    {
        return [
            'minimum_1000' => [1000],
            'mid_range_5000' => [5000],
            'maximum_10000' => [10000],
        ];
    }
}
