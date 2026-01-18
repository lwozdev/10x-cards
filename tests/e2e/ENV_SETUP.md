# Environment Variables Setup for E2E Tests

## 📋 Overview

Playwright E2E tests ładują zmienne środowiskowe z następujących plików (w kolejności):

1. **`.env.test`** - Główne zmienne testowe (commitowane do git)
2. **`.env.test.local`** - Lokalne nadpisania (gitignored, dla danych wrażliwych)

Jeśli zmienna jest zdefiniowana w obu plikach, `.env.test.local` ma pierwszeństwo.

## 🔧 Konfiguracja

### Automatyczne Ładowanie

Zmienne są automatycznie ładowane przez `playwright.config.ts`:

```typescript
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Load .env.test
dotenv.config({ path: path.resolve(__dirname, '.env.test') });

// Load .env.test.local (overrides .env.test)
dotenv.config({ path: path.resolve(__dirname, '.env.test.local'), override: true });
```

### Setup dla Lokalnego Developmentu

1. **Skopiuj przykładowy plik:**
   ```bash
   cp .env.test.local.example .env.test.local
   ```

2. **Edytuj `.env.test.local`** z własnymi wartościami:
   ```env
   # Test user credentials
   E2E_USERNAME=your-test-email@example.com
   E2E_PASSWORD=YourSecurePassword123!

   # Override BASE_URL if needed
   BASE_URL=http://localhost:8000

   # Database (if testing against local DB)
   DATABASE_URL=postgresql://user:pass@localhost:6432/flashcards_test
   ```

3. **Nigdy nie commituj `.env.test.local`** - jest gitignored!

## 📝 Dostępne Zmienne

### Z `.env.test` (commitowane)

```env
KERNEL_CLASS='App\Kernel'
APP_SECRET='$ecretf0rt3st'
DATABASE_URL=postgresql://flashcards_user:flashcards_pass@flashcards-postgres:5432/flashcards_test
```

### Dla E2E Tests (w `.env.test.local`)

```env
# Credentials testowego użytkownika
E2E_USERNAME_ID=test-user-uuid-123
E2E_USERNAME=test@example.com
E2E_PASSWORD=SecureTestPassword123!

# Base URL (domyślnie http://localhost:8000)
BASE_URL=http://nginx  # Gdy uruchamiasz w Docker

# API Keys (jeśli testujemy integracje)
OPENROUTER_API_KEY=sk-test-key-here

# Debug
PWDEBUG=1              # Enable Playwright Inspector
DEBUG=pw:api           # Enable Playwright debug logs
```

## 🧪 Używanie w Testach

### Przykład: Dostęp do zmiennych środowiskowych

```typescript
import { test, expect } from '@playwright/test';

test('login with env credentials', async ({ page }) => {
  // Get credentials from environment
  const username = process.env.E2E_USERNAME || 'fallback@example.com';
  const password = process.env.E2E_PASSWORD || 'fallbackpassword';

  await page.goto('/login');
  await page.fill('input[name="email"]', username);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]');

  await expect(page).toHaveURL(/\/(generate|sets)/);
});
```

### Przykład: Walidacja środowiska

```typescript
import { test, expect } from '@playwright/test';

test.beforeAll(() => {
  // Ensure required env vars are set
  if (!process.env.E2E_USERNAME) {
    throw new Error('E2E_USERNAME not set! Create .env.test.local');
  }
});
```

### Przykład: Różne środowiska

```typescript
import { test } from '@playwright/test';

const isCI = process.env.CI === 'true';
const baseURL = process.env.BASE_URL || 'http://localhost:8000';

test('adjust test for environment', async ({ page }) => {
  if (isCI) {
    // Use mock data in CI
    await page.route('/api/**', route => route.fulfill({ ... }));
  }

  await page.goto('/');
  // ... test continues
});
```

## 🐳 Docker Environment

Gdy uruchamiasz testy w Docker, zmienne z `.env.test` są automatycznie ładowane.

### Nadpisywanie w Docker

```bash
# Metoda 1: Przez docker-compose exec
docker compose exec -e BASE_URL=http://nginx -e E2E_USERNAME=test@example.com node npm run test:e2e

# Metoda 2: Przez Makefile (BASE_URL już ustawione)
make test-e2e

# Metoda 3: Edytuj docker-compose.yml
services:
  node:
    environment:
      BASE_URL: http://nginx
      E2E_USERNAME: ${E2E_USERNAME}  # From host .env.test.local
```

## ✅ Weryfikacja Setup

Uruchom test sprawdzający konfigurację:

```bash
# Lokalnie
npx playwright test env-check.spec.ts --reporter=list

# W Docker
docker compose exec -e BASE_URL=http://nginx node npx playwright test env-check.spec.ts --reporter=list
```

**Oczekiwany output:**
```
✓ should load environment variables from .env.test
✓ should have BASE_URL configured
✓ should allow .env.test.local to override .env.test
```

## 🔒 Bezpieczeństwo

### ✅ DO:
- Commituj `.env.test` z wartościami placeholderami (###)
- Używaj `.env.test.local` dla prawdziwych credentials
- Dodaj `.env.test.local` do `.gitignore`
- Używaj różnych credentials dla CI i local dev

### ❌ DON'T:
- Nigdy nie commituj prawdziwych haseł do `.env.test`
- Nie używaj production credentials w testach
- Nie sharuj `.env.test.local` publicznie

## 🚀 CI/CD

### GitHub Actions

```yaml
- name: Run E2E tests
  env:
    BASE_URL: http://localhost:8000
    E2E_USERNAME: ci-test@example.com
    E2E_PASSWORD: ${{ secrets.E2E_TEST_PASSWORD }}
  run: npm run test:e2e
```

Zmienne środowiskowe przekazane przez GitHub Actions mają pierwszeństwo nad `.env.test`.

## 📚 Przydatne Linki

- [dotenv Documentation](https://github.com/motdotla/dotenv)
- [Playwright Environment Variables](https://playwright.dev/docs/test-configuration#environment-variables)
- [Playwright Parametrize Tests](https://playwright.dev/docs/test-parameterize)

## 🐛 Troubleshooting

### Problem: "Environment variable not defined"

**Rozwiązanie:**
1. Sprawdź czy plik `.env.test.local` istnieje
2. Sprawdź czy zmienna jest zdefiniowana w pliku
3. Sprawdź czy nie ma literówki w nazwie zmiennej
4. Zrestartuj Playwright jeśli modyfikowałeś .env

### Problem: "Values from .env.test.local not loading"

**Rozwiązanie:**
1. Sprawdź czy `.env.test.local` jest w root projektu (obok `playwright.config.ts`)
2. Sprawdź czy plik ma poprawny format (KEY=value)
3. Zrestartuj testy (dotenv ładuje zmienne przy starcie)

### Problem: "Different values in Docker vs local"

**Rozwiązanie:**
- W Docker zmienne są ładowane z plików w kontenerze
- Upewnij się że wolumen jest poprawnie zmontowany
- Użyj `docker compose exec -e VAR=value` aby nadpisać

---

**Przykład `.env.test.local` (gitignored):**

```env
# My local E2E test configuration
E2E_USERNAME=lukasz@example.com
E2E_PASSWORD=MyLocalTestPassword123!
BASE_URL=http://localhost:8000

# Uncomment for debugging
# PWDEBUG=1
# DEBUG=pw:api
```

---

✅ **Setup Complete!** Zmienne środowiskowe są ładowane automatycznie przy każdym uruchomieniu Playwright.
