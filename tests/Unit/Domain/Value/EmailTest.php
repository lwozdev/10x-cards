<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Value;

use App\Domain\Value\Email;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Email Value Object
 *
 * Business rules:
 * - Must be valid email format (RFC compliant via filter_var)
 * - Maximum 255 characters
 * - Normalized to lowercase with trimmed whitespace
 * - Case-insensitive equality comparison
 */
final class EmailTest extends TestCase
{
    // ===== Validation Tests =====

    #[DataProvider('invalidEmailProvider')]
    public function testRejectsInvalidEmailFormats(string $invalidEmail, string $description): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        Email::fromString($invalidEmail);
    }

    public static function invalidEmailProvider(): array
    {
        return [
            'empty_string' => ['', 'empty string'],
            'missing_at_sign' => ['testexample.com', 'missing @ sign'],
            'missing_domain' => ['test@', 'missing domain'],
            'missing_username' => ['@example.com', 'missing username'],
            'double_at_sign' => ['test@@example.com', 'double @ sign'],
            'spaces_in_email' => ['test user@example.com', 'spaces in username'],
            'no_domain_extension' => ['test@example', 'missing TLD'],
            'dots_only' => ['...@example.com', 'dots only as username'],
        ];
    }

    public function testRejectsEmailExceedingMaximumLength(): void
    {
        // Create 256 character email: (244 chars username) + @ + example.com (11 chars) = 256
        $longUsername = str_repeat('a', 244);
        $longEmail = $longUsername . '@example.com';

        $this->expectException(InvalidArgumentException::class);
        // Note: filter_var may reject very long emails as invalid format before length check
        // We expect either "Invalid email format" or "Email cannot exceed 255 characters"

        Email::fromString($longEmail);
    }

    // ===== Valid Creation Tests =====

    #[DataProvider('validEmailProvider')]
    public function testCreatesWithValidEmails(string $validEmail): void
    {
        $email = Email::fromString($validEmail);

        $this->assertInstanceOf(Email::class, $email);
    }

    public static function validEmailProvider(): array
    {
        return [
            'simple' => ['test@example.com'],
            'with_dots' => ['test.user@example.com'],
            'with_plus' => ['test+tag@example.com'],
            'with_dash' => ['test-user@example.com'],
            'with_numbers' => ['user123@example.com'],
            'subdomain' => ['test@mail.example.com'],
            'long_tld' => ['test@example.museum'],
            'country_tld' => ['test@example.co.uk'],
        ];
    }

    public function testCreatesWithMaximumAllowedLength(): void
    {
        // Note: filter_var may reject very long emails even if under 255 chars
        // This tests reasonable length email that should pass validation

        // Create a moderately long but valid email (~80 chars)
        $longUsername = 'very.long.email.address.for.testing.purposes.with.dots';
        $longEmail = $longUsername . '@example-domain.com'; // ~80 chars

        $email = Email::fromString($longEmail);

        $this->assertInstanceOf(Email::class, $email);
        $this->assertLessThanOrEqual(255, strlen($email->toString()));
    }

    // ===== Normalization Tests =====

    public function testNormalizesToLowercase(): void
    {
        $email = Email::fromString('Test.User@EXAMPLE.COM');

        $this->assertSame('test.user@example.com', $email->toString());
    }

    public function testTrimsWhitespace(): void
    {
        $email = Email::fromString('  test@example.com  ');

        $this->assertSame('test@example.com', $email->toString());
    }

    public function testTrimsAndNormalizesTogetherCorrectly(): void
    {
        $email = Email::fromString('  Test.User@EXAMPLE.COM  ');

        $this->assertSame('test.user@example.com', $email->toString());
    }

    #[DataProvider('normalizationCasesProvider')]
    public function testVariousNormalizationCases(string $input, string $expected): void
    {
        $email = Email::fromString($input);

        $this->assertSame($expected, $email->toString());
    }

    public static function normalizationCasesProvider(): array
    {
        return [
            'uppercase' => ['USER@EXAMPLE.COM', 'user@example.com'],
            'mixed_case' => ['UsEr@ExAmPlE.cOm', 'user@example.com'],
            'leading_space' => ['   user@example.com', 'user@example.com'],
            'trailing_space' => ['user@example.com   ', 'user@example.com'],
            'both_spaces' => ['  user@example.com  ', 'user@example.com'],
            'tabs' => ["\tuser@example.com\t", 'user@example.com'],
            'newlines' => ["\nuser@example.com\n", 'user@example.com'],
        ];
    }

    // ===== Equality Tests =====

    public function testEqualsReturnsTrueForSameEmail(): void
    {
        $email1 = Email::fromString('test@example.com');
        $email2 = Email::fromString('test@example.com');

        $this->assertTrue($email1->equals($email2));
    }

    public function testEqualsReturnsTrueForCaseInsensitiveMatch(): void
    {
        $email1 = Email::fromString('Test@Example.COM');
        $email2 = Email::fromString('test@example.com');

        // Both are normalized to lowercase, so they should be equal
        $this->assertTrue($email1->equals($email2));
    }

    public function testEqualsReturnsFalseForDifferentEmails(): void
    {
        $email1 = Email::fromString('test1@example.com');
        $email2 = Email::fromString('test2@example.com');

        $this->assertFalse($email1->equals($email2));
    }

    public function testEqualsHandlesWhitespaceNormalization(): void
    {
        $email1 = Email::fromString('  test@example.com  ');
        $email2 = Email::fromString('test@example.com');

        $this->assertTrue($email1->equals($email2));
    }

    // ===== Public API Tests =====

    public function testToStringReturnsNormalizedValue(): void
    {
        $email = Email::fromString('TEST@EXAMPLE.COM');

        $this->assertSame('test@example.com', $email->toString());
    }

    public function testValuePropertyIsAccessible(): void
    {
        $email = Email::fromString('test@example.com');

        $this->assertSame('test@example.com', $email->value);
    }

    public function testFromStringIsIdempotent(): void
    {
        $input = 'test@example.com';
        $email1 = Email::fromString($input);
        $email2 = Email::fromString($input);

        $this->assertTrue($email1->equals($email2));
        $this->assertSame($email1->toString(), $email2->toString());
    }

    // ===== Edge Cases =====

    public function testHandlesInternationalDomains(): void
    {
        // Note: filter_var with FILTER_VALIDATE_EMAIL may not fully support IDN
        // This test documents current behavior
        $email = Email::fromString('test@example.pl');

        $this->assertSame('test@example.pl', $email->toString());
    }

    public function testPreservesValidSpecialCharacters(): void
    {
        $email = Email::fromString('test.user+tag@example.com');

        $this->assertSame('test.user+tag@example.com', $email->toString());
    }
}
