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

- **Docker & Docker Compose** (wszystkie testy uruchamiamy w kontenerach)
- **Make** (opcjonalne, ale zdecydowanie zalecane)

**⚠️ WAŻNE:** Wszystkie środowiska testowe (PHP, Node.js, PostgreSQL, Chromium) działają w kontenerach Docker. Nie potrzebujesz lokalnego PHP ani Node.js!

### Installation

```bash
# Metoda 1: Użyj Makefile (ZALECANE)
make docker-up           # Start all Docker containers
make install            # Install dependencies in containers
make setup-test-db      # Create test database

# Metoda 2: Ręcznie z Docker Compose
docker compose up -d                                    # Start containers
docker compose exec backend composer install            # PHP deps
docker compose exec node npm install                    # Node deps (już done podczas build)
docker compose exec backend php bin/console doctrine:database:create --env=test
docker compose exec backend php bin/console doctrine:migrations:migrate --env=test --no-interaction

# 5. Verify setup
make test               # Run all tests (fast: PHP + JS)
make test-all          # Run ALL tests (including E2E)
```

### Test Database Configuration

Test database is configured in `.env.test`:

```env
DATABASE_URL="postgresql://flashcards:flashcards@postgres:5432/flashcards_test?serverVersion=16&charset=utf8"
```

The test database is automatically created with `_test` suffix and isolated from development data.

---

## Docker Architecture for Testing

Wszystkie testy uruchamiamy w kontenerach Docker dla spójności środowiska.

### Kontenery

```yaml
services:
  postgres:        # PostgreSQL 16 - baza danych testowa
  backend:         # PHP 8.2 + Symfony - testy PHPUnit
  node:            # Node.js 20 + Chromium - testy Vitest + Playwright
  nginx:           # Serwer web dla testów E2E
```

### Kontener Node.js

Specjalnie skonfigurowany dla testów frontend i E2E:

- **Obraz**: Node.js 20 Alpine
- **Chromium**: Zainstalowany systemowo (dla Playwright)
- **Środowisko**: Wszystkie zmienne dla testów E2E
- **Wolumen**: `node_modules` zmontowany jako osobny volume

```bash
# Sprawdź status kontenerów
docker compose ps

# Zobacz logi Node.js
make docker-logs-node

# Wejdź do kontenera Node.js
docker compose exec node sh

# Wejdź do kontenera Backend
docker compose exec backend bash
```

### Dlaczego Docker?

✅ **Spójność środowiska**: Wszyscy developerzy i CI używają identycznej konfiguracji
✅ **Izolacja**: Testy nie kolidują z lokalnym środowiskiem
✅ **Chromium preinstalowany**: Nie trzeba pobierać przeglądarki lokalnie
✅ **PostgreSQL RLS**: Wymaga prawdziwego PostgreSQL 16
✅ **Łatwe cleanup**: `docker compose down` czyści wszystko

---

## Running Tests

**💡 TIP:** Używaj komend `make` dla uproszczenia. Zobacz `make help` dla pełnej listy.

### PHP Tests (PHPUnit)

```bash
# ✅ ZALECANE: Użyj Makefile
make test-php                  # All PHP tests in Docker
make test-unit                 # Unit tests only
make test-integration          # Integration tests only
make test-functional           # Functional tests only

# Alternatywnie: Docker Compose bezpośrednio
docker compose exec backend vendor/bin/phpunit
docker compose exec backend vendor/bin/phpunit --testsuite=Unit

# Run with coverage report
make coverage-php              # Generates HTML report in var/coverage/
```

### JavaScript Tests (Vitest)

```bash
# ✅ ZALECANE: Użyj Makefile
make test-js                   # Run frontend tests in Docker
make test-js-watch             # Watch mode (re-run on changes)
make test-js-ui                # UI mode (visual test explorer)

# Alternatywnie: Docker Compose bezpośrednio
docker compose exec node npm run test:unit
docker compose exec node npm run test:watch
docker compose exec node npm run test:ui

# Coverage report
make coverage-js               # Generates report in var/coverage/frontend/
```

### E2E Tests (Playwright)

```bash
# ✅ ZALECANE: Użyj Makefile
make test-e2e                  # Run E2E tests (headless) in Docker
make test-e2e-ui               # UI mode (visual test runner)
make test-e2e-headed           # Headed mode (see browser)
make test-e2e-debug            # Debug mode (step through tests)

# Alternatywnie: Docker Compose bezpośrednio
docker compose exec -e BASE_URL=http://nginx node npm run test:e2e
docker compose exec -e BASE_URL=http://nginx node npm run test:e2e:ui

# Generate test code with Codegen
docker compose exec node npm run playwright:codegen
```

### Wszystkie Testy Naraz

```bash
make test                      # PHP + JS (fast, no E2E)
make test-all                  # PHP + JS + E2E (complete suite)
make ci                        # Full CI pipeline (setup + test + analyze)
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

## 🎯 Quick Reference - Makefile Commands

```bash
# ═══ Setup ═══
make install                  # Install all dependencies (PHP + Node in Docker)
make setup-test-db           # Create and migrate test database
make docker-up               # Start all Docker containers
make docker-down             # Stop all Docker containers

# ═══ Run Tests ═══
make test                    # PHP + JS (fast, no E2E)
make test-all               # PHP + JS + E2E (complete suite)
make test-php               # All PHP tests (Unit + Integration + Functional)
make test-unit              # PHP unit tests only
make test-integration       # PHP integration tests only
make test-functional        # PHP functional tests only
make test-js                # Frontend tests (Vitest)
make test-js-watch          # Vitest watch mode
make test-js-ui             # Vitest UI mode
make test-e2e               # E2E tests (Playwright)
make test-e2e-ui            # Playwright UI mode
make test-e2e-headed        # Playwright headed mode
make test-e2e-debug         # Playwright debug mode

# ═══ Coverage ═══
make coverage               # Generate all coverage reports
make coverage-php           # PHP coverage → var/coverage/html/index.html
make coverage-js            # JS coverage → var/coverage/frontend/index.html

# ═══ Code Quality ═══
make phpstan                # Run PHPStan static analysis (level 8)

# ═══ Docker ═══
make docker-up              # Start all containers
make docker-down            # Stop all containers
make docker-logs            # Show logs for all containers
make docker-logs-node       # Show Node.js container logs

# ═══ Cleanup ═══
make clean                  # Clean cache and temp files
make clean-test             # Clean test artifacts (coverage, reports)

# ═══ CI/CD ═══
make ci                     # Full CI pipeline (setup + test + analyze)

# ═══ Help ═══
make help                   # Show all available commands
```

---

**Testing Status:** ✅ Environment fully configured with Docker, Vitest, Playwright, and PHPUnit ready for implementation.

**Next:** Start implementing tests based on [.ai/test-plan.md](.ai/test-plan.md) priorities (RLS first, then AI generation).
