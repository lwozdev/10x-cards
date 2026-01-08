# Testing Guide - AI Flashcard Generator

Comprehensive testing setup for the AI Flashcard Generator application following the test plan outlined in `.ai/test-plan.md`.

## Table of Contents

- [Test Strategy Overview](#test-strategy-overview)
- [Environment Setup](#environment-setup)
- [Running Tests](#running-tests)
- [Test Structure](#test-structure)
- [Writing Tests](#writing-tests)
- [Coverage Reports](#coverage-reports)
- [CI/CD Integration](#cicd-integration)

---

## Test Strategy Overview

This project uses a **hybrid testing approach** with multiple test types:

| Test Type | Tool | Purpose | Coverage Goal |
|-----------|------|---------|---------------|
| **Unit Tests (PHP)** | PHPUnit 12.4 | Domain logic, value objects, entities | ≥90% (domain layer) |
| **Integration Tests (PHP)** | PHPUnit + Doctrine | Database, repositories, RLS | ≥80% (infrastructure) |
| **Functional Tests (PHP)** | Symfony WebTestCase | Controllers, HTTP flows | All user stories |
| **Unit Tests (JS)** | Vitest | Stimulus controllers | ≥80% (frontend logic) |
| **E2E Tests** | Playwright | Complete user journeys | Critical paths |
| **Static Analysis** | PHPStan (level 8) | Type safety, code quality | 0 errors |

### Priority Test Areas (from test-plan.md)

**P0 (Critical):**
- PostgreSQL Row-Level Security (RLS) isolation
- AI flashcard generation (75% acceptance rate target)
- Authentication and authorization
- Data integrity and CRUD operations

**P1 (High):**
- Form validation (1000-10000 character limits)
- OpenRouter API error handling
- Flashcard editing workflow
- Spaced repetition algorithm

---

## Environment Setup

### Prerequisites

- Docker & Docker Compose (for PostgreSQL)
- PHP 8.2+
- Composer
- Node.js 18+ & npm

### Installation

```bash
# 1. Install PHP dependencies
composer install

# 2. Install Node.js dependencies
npm install

# 3. Install Playwright browsers (Chromium only)
npm run playwright:install

# 4. Create test database
docker-compose up -d postgres
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction

# 5. Verify setup
composer test
npm test
```

### Test Database Configuration

Test database is configured in `.env.test`:

```env
DATABASE_URL="postgresql://flashcards:flashcards@postgres:5432/flashcards_test?serverVersion=16&charset=utf8"
```

The test database is automatically created with `_test` suffix and isolated from development data.

---

## Running Tests

### PHP Tests (PHPUnit)

```bash
# Run all PHP tests
composer test

# Run specific test suite
composer test:unit           # Unit tests only
composer test:integration    # Integration tests only
composer test:functional     # Functional tests only

# Run with coverage report
composer test:coverage       # Generates HTML report in var/coverage/

# Run single test file
vendor/bin/phpunit tests/Unit/Domain/ValueObject/SourceTextTest.php

# Run tests with filter
vendor/bin/phpunit --filter testCannotCreateWithTextBelowMinimumLength
```

### JavaScript Tests (Vitest)

```bash
# Run all frontend tests
npm test

# Watch mode (re-run on file changes)
npm run test:watch

# UI mode (visual test explorer)
npm run test:ui

# Coverage report
npm run test:coverage        # Generates report in var/coverage/frontend/
```

### E2E Tests (Playwright)

```bash
# Run all E2E tests (headless)
npm run test:e2e

# Run with UI mode (visual test runner)
npm run test:e2e:ui

# Run in headed mode (see browser)
npm run test:e2e:headed

# Debug mode (step through tests)
npm run test:e2e:debug

# Generate test code with Codegen
npm run playwright:codegen
```

### Static Analysis (PHPStan)

```bash
# Run PHPStan analysis (level 8)
composer phpstan

# Analyze with baseline
vendor/bin/phpstan analyse --generate-baseline
```

---

## Test Structure

```
tests/
├── Unit/                          # PHP unit tests
│   └── Domain/
│       └── ValueObject/
│           └── SourceTextTest.php # Example: 1000-10000 char validation
│
├── Integration/                   # PHP integration tests
│   └── Security/
│       └── RowLevelSecurityTest.php # CRITICAL: RLS isolation tests
│
├── Functional/                    # PHP functional tests (HTTP)
│   └── Controller/
│       └── GenerateFlashcardsFlowTest.php # Complete generation flow
│
├── frontend/                      # Vitest tests
│   ├── setup.ts                   # Global test configuration
│   └── controllers/
│       └── generate_controller.test.ts # Stimulus controller tests
│
├── e2e/                          # Playwright E2E tests
│   ├── auth.setup.ts             # Authentication state
│   └── flashcard-generation.spec.ts # Complete user journey
│
├── Fixtures/                     # Data fixtures for tests
│
├── bootstrap.php                 # PHPUnit bootstrap
└── console-application-loader.php # PHPStan console loader
```

---

## Writing Tests

### Unit Test Example (PHP)

```php
<?php

namespace App\Tests\Unit\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use App\Domain\ValueObject\SourceText;

class SourceTextTest extends TestCase
{
    /**
     * @dataProvider validLengthProvider
     */
    public function testAcceptsValidLengthRange(int $length): void
    {
        $text = str_repeat('a', $length);
        $sourceText = new SourceText($text);

        $this->assertEquals($length, strlen($sourceText->getValue()));
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
```

### Integration Test Example (RLS)

```php
<?php

namespace App\Tests\Integration\Security;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RowLevelSecurityTest extends KernelTestCase
{
    public function testUserCannotAccessAnotherUsersData(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        // Create User A and User B
        // User A creates a flashcard set
        // Authenticate as User B
        // Try to access User A's set
        // Assert: null (filtered by RLS)
    }
}
```

### Vitest Test Example (Stimulus)

```typescript
import { describe, it, expect, vi } from 'vitest';
import { screen } from '@testing-library/dom';

describe('GenerateController', () => {
  it('should disable button when text is below 1000 characters', () => {
    const textarea = screen.getByRole('textbox');
    const button = screen.getByRole('button', { name: /generuj/i });

    textarea.value = 'a'.repeat(999);
    textarea.dispatchEvent(new Event('input'));

    expect(button).toBeDisabled();
  });
});
```

### Playwright Test Example (E2E)

```typescript
import { test, expect } from '@playwright/test';

test('should generate flashcards from valid text', async ({ page }) => {
  await page.goto('/generate');

  const textarea = page.locator('textarea[name="source_text"]');
  await textarea.fill('Educational content. '.repeat(100));

  const button = page.locator('button:has-text("Generuj fiszki")');
  await expect(button).toBeEnabled();
  await button.click();

  // Wait for AI processing and redirect
  await page.waitForURL('/sets/new/edit', { timeout: 30000 });

  // Verify flashcards are displayed
  await expect(page.locator('[data-edit-set-target="cardItem"]')).toHaveCount(10);
});
```

---

## Coverage Reports

### Viewing Coverage Reports

After running tests with coverage:

```bash
# PHP Coverage (HTML)
composer test:coverage
open var/coverage/html/index.html

# Frontend Coverage (HTML)
npm run test:coverage
open var/coverage/frontend/index.html
```

### Coverage Thresholds

Configured in `vitest.config.ts` and enforced in CI:

- **Domain Layer (PHP):** 90% minimum
- **Application Layer (PHP):** 80% minimum
- **Frontend Controllers (JS):** 80% minimum

---

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  php-tests:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_DB: flashcards_test
          POSTGRES_USER: flashcards
          POSTGRES_PASSWORD: flashcards
        ports:
          - 5432:5432

    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: xdebug

      - run: composer install
      - run: php bin/console doctrine:migrations:migrate --env=test --no-interaction
      - run: composer test:coverage
      - run: composer phpstan

  frontend-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '18'

      - run: npm install
      - run: npm run test:coverage

  e2e-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '18'

      - run: npm install
      - run: npm run playwright:install
      - run: npm run test:e2e
```

---

## Test Data Management

### Using Fixtures

```php
<?php

namespace App\Tests\Fixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Faker\Factory;

class UserFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail($faker->email());
            $user->setPassword('hashed_password');
            $manager->persist($user);
        }

        $manager->flush();
    }
}
```

Load fixtures in tests:

```bash
php bin/console doctrine:fixtures:load --env=test --no-interaction
```

---

## Critical Test Scenarios (from test-plan.md)

### Must-Test Before Production

1. **RLS Isolation (SEC-01, SEC-02, SEC-03)**
   - User A cannot access User B's data
   - SQL injection cannot bypass RLS
   - `current_app_user()` returns correct ID

2. **AI Generation (TC-GEN-001, TC-AI-01, TC-AI-02)**
   - Character validation (1000-10000)
   - Timeout handling (30s limit)
   - Error recovery and user feedback

3. **Analytics (TC-ANALYTICS-001)**
   - Acceptance rate calculation: `1 - (deleted/generated)`
   - Target: ≥75% acceptance rate

4. **Authentication (TC-AUTH-001, TC-AUTH-003)**
   - Registration flow
   - Login/logout
   - Password security

---

## Troubleshooting

### Test Database Connection Issues

```bash
# Verify PostgreSQL is running
docker-compose ps

# Recreate test database
php bin/console doctrine:database:drop --env=test --force
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction
```

### Playwright Installation Issues

```bash
# Reinstall browsers
npx playwright install --with-deps chromium

# Clear Playwright cache
rm -rf ~/.cache/ms-playwright
npm run playwright:install
```

### PHPUnit Memory Issues

```bash
# Increase PHP memory limit
php -d memory_limit=512M vendor/bin/phpunit
```

---

## References

- **Complete Test Plan:** `.ai/test-plan.md`
- **Tech Stack Rationale:** `.ai/tech-stack.md`
- **Vitest Best Practices:** `.claude/skills/vitest/SKILL.md`
- **Playwright Best Practices:** `.claude/skills/playwright/SKILL.md`
- **PHPUnit Documentation:** https://phpunit.de/documentation.html
- **Symfony Testing:** https://symfony.com/doc/current/testing.html

---

## Next Steps

1. **Install dependencies:**
   ```bash
   composer install
   npm install
   npm run playwright:install
   ```

2. **Run initial test suite:**
   ```bash
   composer test
   npm test
   ```

3. **Implement first tests:**
   - Start with RLS tests (Priority P0)
   - Then AI generation tests
   - Finally E2E critical paths

4. **Set up CI/CD:**
   - Configure GitHub Actions
   - Enforce coverage thresholds
   - Add pre-commit hooks

---

**Testing Status:** Environment configured, example tests created, ready for implementation.
