# E2E Tests with Playwright

Playwright end-to-end tests for AI Flashcard Generator application.

## 📁 Structure

```
tests/e2e/
├── pages/                    # Page Object Model
│   ├── BasePage.ts          # Base page with common functionality
│   ├── GeneratePage.ts      # /generate page object
│   ├── EditSetPage.ts       # /sets/new/edit page object
│   ├── LoginPage.ts         # /login page object
│   └── index.ts             # Page objects export
├── fixtures/                 # Test fixtures and data
│   ├── test-data.ts         # Reusable test data (users, texts, sets)
│   └── custom-fixtures.ts   # Custom Playwright fixtures
├── helpers/                  # Test utilities
│   └── api-mocks.ts         # API mocking helpers
├── auth.setup.ts            # Authentication setup (TODO)
├── flashcard-generation.spec.ts          # Original E2E tests
├── flashcard-generation-pom.spec.ts      # Refactored with POM
└── edit-set.spec.ts         # Edit set flow tests
```

## 🚀 Running Tests

### Prerequisites

**✅ Używaj Dockera!** Wszystko jest już skonfigurowane w kontenerze `flashcards-node`.

```bash
# Uruchom kontenery Docker
make docker-up

# Zainstaluj zależności w kontenerze (jeśli jeszcze nie zrobiono)
docker compose exec node npm install

# Chromium jest już zainstalowany w kontenerze Node.js!
# Nie trzeba uruchamiać playwright install - przeglądarka jest w obrazie Docker
```

### Run Tests

**✅ ZALECANE: Użyj Makefile**

```bash
make test-e2e               # Run all E2E tests in Docker
make test-e2e-ui            # UI mode (interactive debugger)
make test-e2e-headed        # Headed mode (see browser)
make test-e2e-debug         # Debug mode (step-by-step)
```

**Alternatywnie: Docker Compose bezpośrednio**

```bash
# Run all E2E tests
docker compose exec -e BASE_URL=http://nginx node npm run test:e2e

# Run with UI mode
docker compose exec -e BASE_URL=http://nginx node npm run test:e2e:ui

# Run specific test file
docker compose exec node npx playwright test flashcard-generation-pom.spec.ts

# Run tests matching pattern
docker compose exec node npx playwright test --grep "should generate flashcards"
```

### Generate Tests with Codegen

```bash
# Record new test (requires app running on localhost:8000)
npm run playwright:codegen
```

## 📖 Writing Tests with Page Object Model

### Example: Using Page Objects

```typescript
import { test, expect } from './fixtures/custom-fixtures';
import { TestTexts } from './fixtures/test-data';

test('generate flashcards', async ({ generatePage, editSetPage }) => {
  // Navigate to generate page
  await generatePage.navigate();

  // Enter text and generate
  await generatePage.enterSourceText(TestTexts.validGenerationText);
  await generatePage.clickGenerate();

  // Wait for redirect
  await generatePage.waitForRedirectToEdit();

  // Verify cards generated
  const cardCount = await editSetPage.getCardCount();
  expect(cardCount).toBeGreaterThan(0);

  // Save set
  await editSetPage.completeEditAndSave('My Test Set');
});
```

### Example: Using API Mocks

```typescript
import { mockSuccessfulGeneration, mockTimeoutError } from './helpers/api-mocks';

test('handle API timeout', async ({ page, generatePage }) => {
  // Mock timeout response
  await mockTimeoutError(page);

  await generatePage.navigate();
  await generatePage.enterSourceText(TestTexts.validGenerationText);
  await generatePage.clickGenerate();

  // Verify error handling
  await generatePage.verifyErrorModalVisible();
});
```

## 🎯 Best Practices

Following [playwright-expert skill guidelines](.claude/skills/playwright/SKILL.md):

### ✅ DO

- **Use Page Object Model** for all tests
- **Use browser contexts** for test isolation (handled automatically)
- **Use locators** with data-* attributes for resilience
- **Leverage custom fixtures** for page objects
- **Mock API responses** for controlled testing
- **Use expect assertions** with specific matchers
- **Implement visual regression** tests with `toHaveScreenshot()`
- **Use trace viewer** for debugging (`--trace on`)

### ❌ DON'T

- Don't use CSS selectors that can easily break (prefer data-* attributes)
- Don't hardcode test data (use `test-data.ts` fixtures)
- Don't duplicate page logic across tests (use Page Objects)
- Don't test multiple browsers for MVP (Chromium only per guidelines)

## 🔍 Debugging

### View Test Report

```bash
# Open HTML report after test run
npx playwright show-report var/playwright-report
```

### Use Trace Viewer

```bash
# Enable tracing for all tests
npx playwright test --trace on

# View trace for failed test
npx playwright show-trace var/traces/trace.zip
```

### Use VS Code Extension

Install [Playwright Test for VSCode](https://marketplace.visualstudio.com/items?itemName=ms-playwright.playwright) for:
- Run/debug individual tests
- View test results
- Record new tests
- Time travel debugging

## 📊 Coverage

Test coverage mapped to [test-plan.md](../../.ai/test-plan.md):

- **TC-GEN-001**: Generate flashcards from valid text ✓
- **TC-AI-01**: Validation - text below 1000 chars ✓
- **TC-AI-02**: Validation - text above 10000 chars ✓
- **TC-GEN-003**: Error handling - API timeout ✓
- **TC-EDIT-004**: Save set after editing and deleting cards ✓

Additional test scenarios:
- Rate limit handling
- Authentication errors
- Duplicate set name validation
- Boundary conditions (exactly 1000/10000 chars)
- Visual regression tests

## 🛠️ Configuration

See [playwright.config.ts](../../playwright.config.ts) for:
- Base URL: `http://localhost:8000`
- Browser: Chromium/Desktop Chrome only
- Retries: 2 on CI, 0 locally
- Screenshots: On failure
- Videos: On failure
- Traces: On first retry
- Reports: HTML + List

## 📝 Notes

### Current Status

All tests are marked as `.skip` because controllers are not yet implemented.

When implementing features:
1. Remove `.skip` from relevant tests
2. Update locators to match actual implementation
3. Run tests to verify functionality
4. Add new test cases as needed

### Authentication

`auth.setup.ts` contains placeholder for authentication flow.
Update when authentication is implemented to:
1. Login with test credentials
2. Save authenticated state to `var/.auth/user.json`
3. Reuse state across tests for faster execution

### CI/CD Integration

Tests are configured for GitHub Actions (see `playwright.config.ts`):
- Uses single worker on CI for stability
- Retries failed tests twice
- Generates HTML report artifact
- Runs on `npx playwright test`

## 📚 Resources

- [Playwright Documentation](https://playwright.dev/)
- [Best Practices](https://playwright.dev/docs/best-practices)
- [Page Object Model](https://playwright.dev/docs/pom)
- [Test Fixtures](https://playwright.dev/docs/test-fixtures)
- [API Testing](https://playwright.dev/docs/api-testing)
