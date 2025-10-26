# API Endpoint Implementation Plan: POST /generate

## 1. Przegląd punktu końcowego

Endpoint `/generate` służy do utworzenia zadania AI (AiJob) do generowania fiszek z tekstu źródłowego dostarczonego
przez użytkownika. Endpoint działa asynchronicznie - natychmiast tworzy rekord zadania ze statusem "queued" i zwraca
jego identyfikator (job_id), umożliwiając UI śledzenie postępu generowania.

**Kluczowe cechy:**

- Asynchroniczne przetwarzanie (202 Accepted)
- Walidacja długości tekstu źródłowego (1000-10000 znaków)
- Wymaga uwierzytelnienia użytkownika
- Rejestracja zdarzeń analitycznych
- Przestrzeganie RLS (Row Level Security) na poziomie bazy danych

---

## 2. Szczegóły żądania

**Metoda HTTP:** POST

**Struktura URL:** `/generate`

**Nagłówki:**

- `Content-Type: application/json`
- `Authorization: Bearer <token>` lub sesja Symfony (jeśli używamy session-based auth)

**Request Body:**

```json
{
    "source_text": "<tekst o długości 1000-10000 znaków>"
}
```

**Parametry:**

- **Wymagane:**
    - `source_text` (string, 1000-10000 znaków) - tekst źródłowy do wygenerowania fiszek

- **Opcjonalne:** brak

---

## 3. Wykorzystywane typy

### Request DTO

```php
// src/UI/Http/Request/GenerateFlashcardsRequest.php
namespace App\UI\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class GenerateFlashcardsRequest
{
    #[Assert\NotBlank(message: 'Source text is required')]
    #[Assert\Type(type: 'string', message: 'Source text must be a string')]
    #[Assert\Length(
        min: 1000,
        max: 10000,
        minMessage: 'Source text must be at least {{ limit }} characters long',
        maxMessage: 'Source text cannot be longer than {{ limit }} characters'
    )]
    public readonly string $sourceText;
}
```

### Command Model

```php
// src/Application/Command/GenerateFlashcardsCommand.php
namespace App\Application\Command;

use App\Domain\Value\UserId;

final readonly class GenerateFlashcardsCommand
{
    public function __construct(
        public UserId $userId,
        public string $sourceText,
    ) {}
}
```

### Response DTO

```php
// src/UI/Http/Response/AiJobResponse.php
namespace App\UI\Http\Response;

final readonly class AiJobResponse
{
    public function __construct(
        public string $jobId,
        public string $status,
    ) {}

    public function toArray(): array
    {
        return [
            'job_id' => $this->jobId,
            'status' => $this->status,
        ];
    }
}
```

---

## 4. Szczegóły odpowiedzi

### Sukces (202 Accepted)

```json
{
    "job_id": "123e4567-e89b-12d3-a456-426614174000",
    "status": "queued"
}
```

### Błąd walidacji (422 Unprocessable Entity)

```json
{
    "error": "Validation failed",
    "details": {
        "source_text": [
            "Source text must be at least 1000 characters long"
        ]
    }
}
```

### Nieautoryzowany dostęp (401 Unauthorized)

```json
{
    "error": "Authentication required",
    "message": "You must be logged in to generate flashcards"
}
```

### Błąd serwera (500 Internal Server Error)

```json
{
    "error": "Internal server error",
    "message": "An unexpected error occurred. Please try again later."
}
```

---

## 5. Przepływ danych

### Architektura Clean Architecture (warstwy)

```
Request
  ↓
[Controller] (UI Layer)
  ↓ deserializacja + walidacja
[Request DTO]
  ↓ mapowanie do Command
[Command] → [Handler] (Application Layer)
  ↓
[Domain Service/Repository] (Domain/Infrastructure)
  ↓
[Database] (ai_jobs + analytics_events)
  ↓
[Response DTO]
  ↓
[JSON Response 202]
```

### Szczegółowy przepływ

1. **Request przyjęty przez kontroler** (`FlashcardGeneratorController::generate`)
    - Sprawdzenie uwierzytelnienia (Symfony Security)
    - Deserializacja JSON do `GenerateFlashcardsRequest`
    - Walidacja przez Symfony Validator

2. **Utworzenie komendy**
    - Pobranie zalogowanego użytkownika (`$this->getUser()`)
    - Stworzenie `GenerateFlashcardsCommand` z `userId` i `sourceText`

3. **Delegacja do handlera** (`GenerateFlashcardsHandler`)
    - Rozpoczęcie transakcji bazy danych
    - Utworzenie encji `AiJob` przez fabrykę domenową
    - Zapis do bazy przez `AiJobRepositoryInterface`
    - Publikacja eventu analitycznego `ai_generate_started`
    - Commit transakcji
    - Zwrócenie `job_id`

4. **Przygotowanie odpowiedzi**
    - Mapowanie `job_id` i `status` do `AiJobResponse`
    - Serializacja do JSON
    - Zwrócenie HTTP 202 Accepted

5. **Rejestracja analytics**
    - Event: `ai_generate_started`
    - Payload: `{user_id, text_length, timestamp}`
    - Zapis do tabeli `analytics_events`

### Interakcje z bazą danych

**Tabela `ai_jobs`:**

```sql
INSERT INTO ai_jobs (id, user_id, status, request_prompt, created_at, updated_at)
VALUES (gen_random_uuid(),
        :user_id,
        'queued',
        :source_text,
        now(),
        now());
```

**Tabela `analytics_events`:**

```sql
INSERT INTO analytics_events (event_type, user_id, payload, occurred_at)
VALUES ('ai_generate_started',
        :user_id,
        '{"text_length": :length}'::jsonb,
        now());
```

**RLS (Row Level Security):**

- Przed wykonaniem zapytań: `SET app.current_user_id = :user_id`
- Polityka RLS na `ai_jobs`: `user_id = current_app_user()`
- Polityka RLS na `analytics_events`: `user_id = current_app_user()`

---

## 6. Względy bezpieczeństwa

### Uwierzytelnianie

- **Wymagane:** użytkownik musi być zalogowany
- **Mechanizm:** Symfony Security Guard/Authenticator
- **Atrybut kontrolera:** `#[IsGranted('IS_AUTHENTICATED_FULLY')]`
- **Błąd:** 401 Unauthorized jeśli niezalogowany

### Autoryzacja

- **Zasada:** użytkownik może tworzyć zadania AI tylko dla siebie
- **Implementacja:** `user_id` w `AiJob` ustawiany na podstawie `$this->getUser()->getId()`
- **RLS:** polityka bazy danych wymusza `ai_jobs.user_id = current_app_user()`
- **IDOR Prevention:** brak możliwości podania `user_id` przez użytkownika

### Walidacja danych wejściowych

- **Poziom 1 (Aplikacja):** Symfony Validator constraints
    - `@Assert\NotBlank` - pole wymagane
    - `@Assert\Type('string')` - typ string
    - `@Assert\Length(min: 1000, max: 10000)` - długość
- **Poziom 2 (Baza danych):** CHECK constraint
    - `CHECK (request_prompt IS NULL OR char_length(request_prompt) BETWEEN 1000 AND 10000)`

### CSRF Protection

- **Dla session-based auth:** dodać token CSRF w formularzu/nagłówku
- **Dla token-based API:** CSRF nie jest wymagany (stateless)
- **Rekomendacja:** jeśli endpoint używany z JS w ramach SSR app, użyć `csrf_token()`

### Rate Limiting (opcjonalnie, rozszerzenie MVP)

- **Cel:** zapobieganie abuse (DOS)
- **Mechanizm:** Symfony RateLimiter component
- **Limit:** np. 10 requestów/minutę na użytkownika
- **Błąd:** 429 Too Many Requests

### SQL Injection Prevention

- **Mechanizm:** Doctrine ORM używa parametryzowanych zapytań
- **Zasada:** nigdy nie interpolować `$sourceText` bezpośrednio w SQL
- **Status:** automatycznie zapewnione przez ORM

### XSS Prevention

- **Kontekst:** `source_text` nie jest renderowany w Twig na tym etapie
- **Przyszłość:** przy wyświetlaniu użyć auto-escapingu Twig (`{{ variable }}`)

### Logowanie i audyt

- **Co logować:**
    - Utworzenie zadania AI (sukces)
    - Błędy walidacji (analytics)
    - Błędy systemowe (application logs)
- **Co NIE logować:**
    - Pełna treść `source_text` w logach aplikacji (tylko w `ai_jobs.request_prompt`)
    - Wrażliwe dane użytkownika

---

## 7. Obsługa błędów

### Scenariusze błędów i kody statusu

| Kod     | Scenariusz            | Przyczyna                                | Odpowiedź                                          |
|---------|-----------------------|------------------------------------------|----------------------------------------------------|
| **202** | Sukces                | Zadanie utworzone pomyślnie              | `{"job_id": "...", "status": "queued"}`            |
| **400** | Bad Request           | Nieprawidłowy JSON, brak `source_text`   | `{"error": "Invalid request", "message": "..."}`   |
| **401** | Unauthorized          | Użytkownik niezalogowany                 | `{"error": "Authentication required"}`             |
| **422** | Unprocessable Entity  | `source_text` < 1000 lub > 10000 znaków  | `{"error": "Validation failed", "details": {...}}` |
| **500** | Internal Server Error | Błąd bazy danych, niespodziewany wyjątek | `{"error": "Internal server error"}`               |

### Implementacja obsługi błędów

**Kontroler:**

```php
try {
    // Walidacja
    $violations = $validator->validate($request);
    if (count($violations) > 0) {
        return new JsonResponse([
            'error' => 'Validation failed',
            'details' => $this->formatValidationErrors($violations),
        ], 422);
    }

    // Wykonanie komendy
    $jobId = $this->handler->handle($command);

    return new JsonResponse([
        'job_id' => $jobId,
        'status' => 'queued',
    ], 202);

} catch (UniqueConstraintViolationException $e) {
    // Rzadki przypadek: konflikt UUID (praktycznie niemożliwe)
    $this->logger->error('UUID collision in ai_jobs', ['exception' => $e]);
    return new JsonResponse(['error' => 'Internal server error'], 500);

} catch (\Exception $e) {
    // Ogólny błąd
    $this->logger->error('Failed to create AI job', [
        'exception' => $e,
        'user_id' => $this->getUser()->getId(),
    ]);

    // Publikacja eventu analytics
    $this->analyticsService->track('ai_generate_failed', [
        'user_id' => $this->getUser()->getId(),
        'error_code' => $e->getCode(),
        'error_message' => $e->getMessage(),
    ]);

    return new JsonResponse([
        'error' => 'Internal server error',
        'message' => 'An unexpected error occurred',
    ], 500);
}
```

### Rejestracja błędów

**Analytics Events:**

- Event: `ai_generate_failed`
- Payload: `{user_id, error_code, error_message, timestamp}`

**Application Logs (Monolog):**

- Level: ERROR
- Context: exception, user_id, request_data

---

## 8. Etapy wdrożenia

### Krok 1: Utworzenie Request DTO

**Ścieżka:** `src/UI/Http/Request/GenerateFlashcardsRequest.php`

**Zadania:**

- Utworzyć klasę z polem `sourceText`
- Dodać Symfony Validator constraints:
    - `@Assert\NotBlank`
    - `@Assert\Type('string')`
    - `@Assert\Length(min: 1000, max: 10000)`

**Test:**

- Unit test sprawdzający walidację dla różnych długości tekstu

---

### Krok 2: Utworzenie Command i Handler (Application Layer)

**Command:** `src/Application/Command/GenerateFlashcardsCommand.php`

- Readonly class z `UserId` i `string $sourceText`

**Handler:** `src/Application/Handler/GenerateFlashcardsHandler.php`

- Metoda `handle(GenerateFlashcardsCommand): string` (zwraca job_id)
- Wstrzyknięcie dependencies:
    - `AiJobRepositoryInterface`
    - `AnalyticsServiceInterface` (lub EventDispatcher)
    - `ClockInterface` (PSR-20, opcjonalnie)
- Logika:
    1. Rozpoczęcie transakcji (Doctrine UnitOfWork robi to automatycznie)
    2. Utworzenie `AiJob` przez fabrykę: `AiJob::createForGeneration($userId, $sourceText)`
    3. Zapis: `$repository->save($aiJob)`
    4. Publikacja eventu: `ai_generate_started`
    5. Zwrócenie `$aiJob->getId()->toString()`

**Test:**

- Integration test z in-memory/test database
- Mock dla analytics service
- Weryfikacja zapisu do `ai_jobs` z poprawnymi danymi

---

### Krok 3: Rozszerzenie Domain Model AiJob o fabrykę

**Ścieżka:** `src/Domain/Model/AiJob.php`

**Zadania:**

- Dodać metodę statyczną lub named constructor:
  ```php
  public static function createForGeneration(UserId $userId, string $sourceText): self
  {
      return new self(
          id: AiJobId::generate(), // lub null jeśli DB generuje
          userId: $userId,
          setId: null,
          status: AiJobStatus::QUEUED,
          errorMessage: null,
          requestPrompt: $sourceText,
          responseRaw: null,
          modelName: null,
          tokensIn: null,
          tokensOut: null,
          createdAt: new \DateTimeImmutable(),
          updatedAt: new \DateTimeImmutable(),
          completedAt: null,
      );
  }
  ```

**Test:**

- Unit test sprawdzający poprawność inicjalizacji
- Weryfikacja statusu "queued"
- Weryfikacja timestamps

---

### Krok 4: Utworzenie Response DTO

**Ścieżka:** `src/UI/Http/Response/AiJobResponse.php`

**Zadania:**

- Readonly class z `jobId` (string) i `status` (string)
- Metoda `toArray(): array` zwracająca tablicę dla JSON

**Test:**

- Unit test sprawdzający serializację do array

---

### Krok 5: Utworzenie kontrolera (UI Layer)

**Ścieżka:** `src/UI/Http/Controller/FlashcardGeneratorController.php`

**Zadania:**

- Utworzyć kontroler z metodą `generate(Request $request)`
- Attributes:
    - `#[Route('/generate', name: 'flashcard_generate', methods: ['POST'])]`
    - `#[IsGranted('IS_AUTHENTICATED_FULLY')]`
- Wstrzyknięcie dependencies:
    - `ValidatorInterface`
    - `GenerateFlashcardsHandler`
    - `SerializerInterface` (do deserializacji JSON)
    - `LoggerInterface`
- Logika:
    1. Deserializacja JSON do `GenerateFlashcardsRequest`
    2. Walidacja przez Validator
    3. Jeśli błędy walidacji → zwrot 422 z details
    4. Utworzenie `GenerateFlashcardsCommand` z `getUser()->getId()` i `$request->sourceText`
    5. Wywołanie `$handler->handle($command)`
    6. Utworzenie `AiJobResponse`
    7. Zwrot JSON 202

**Test:**

- Feature test (HTTP):
    - POST z prawidłowym JSON → 202
    - POST z za krótkim tekstem → 422
    - POST z za długim tekstem → 422
    - POST bez uwierzytelnienia → 401
    - Weryfikacja utworzenia rekordu w `ai_jobs`

---

### Krok 6: Konfiguracja routingu

**Ścieżka:** `config/routes.yaml` lub annotations w kontrolerze

**Zadania:**

- Jeśli używamy annotations/attributes (zalecane), routing jest już w kontrolerze
- Opcjonalnie: utworzyć grupę `/api` dla wszystkich API endpoints

**Test:**

- `bin/console debug:router` - sprawdzić czy route jest zarejestrowany

---

### Krok 7: Konfiguracja Security

**Ścieżka:** `config/packages/security.yaml`

**Zadania:**

- Upewnić się, że endpoint `/generate` wymaga uwierzytelnienia:
  ```yaml
  access_control:
    - { path: ^/generate, roles: ROLE_USER }
  ```
- Lub użyć `#[IsGranted]` w kontrolerze (już zrobione w kroku 5)

**Test:**

- Feature test sprawdzający 401 dla niezalogowanych użytkowników

---

### Krok 8: Analytics Service/Event Publisher

**Ścieżka:** `src/Application/Service/AnalyticsService.php` lub EventDispatcher

**Zadania:**

- Utworzyć service do publikowania eventów analitycznych
- Metoda `track(string $eventType, array $payload, UserId $userId)`
- Zapis do tabeli `analytics_events` przez `AnalyticsEventRepositoryInterface`

**Alternatywa:**

- Użyć Symfony EventDispatcher + Listener/Subscriber

**Test:**

- Integration test sprawdzający zapis do `analytics_events`

---

### Krok 9: RLS (Row Level Security) - konfiguracja sesji

**Ścieżka:** Event Subscriber lub Middleware

**Zadania:**

- Utworzyć Symfony Event Subscriber nasłuchujący `kernel.request`
- Dla zalogowanych użytkowników:
    - Wykonać `SET app.current_user_id = :user_id` na połączeniu Doctrine
- Implementacja:
  ```php
  $this->entityManager->getConnection()->executeStatement(
      'SET app.current_user_id = :user_id',
      ['user_id' => $this->security->getUser()->getId()]
  );
  ```

**Test:**

- Integration test sprawdzający czy RLS policy działa
- Próba dostępu do `ai_jobs` innego użytkownika powinna zwrócić 0 wyników

---

### Krok 10: Obsługa błędów i logowanie

**Ścieżka:** Exception handling w kontrolerze + Monolog

**Zadania:**

- Try-catch w kontrolerze
- Logowanie błędów przez `LoggerInterface`
- Publikacja eventu `ai_generate_failed` przy błędach
- Zwrot przyjaznych komunikatów błędów (nie ujawniać stack trace w produkcji)

**Test:**

- Feature test symulujący błąd bazy danych (np. mock)
- Weryfikacja zwrotu 500 i zapisu do logów

---

### Krok 11: Walidacja na poziomie bazy danych

**Ścieżka:** Migracja Doctrine

**Zadania:**

- Sprawdzić czy migracja zawiera CHECK constraint:
  ```sql
  CHECK (request_prompt IS NULL OR char_length(request_prompt) BETWEEN 1000 AND 10000)
  ```
- Jeśli nie, utworzyć nową migrację dodającą constraint

**Test:**

- Integration test próbujący zapisać `AiJob` z nieprawidłową długością `request_prompt`
- Oczekiwany exception z bazy danych

---

### Krok 12: Testy integracyjne i feature testy

**Ścieżka:** `tests/Feature/FlashcardGeneratorTest.php`

**Zadania:**

- Test happy path:
    - POST `/generate` z prawidłowym tekstem
    - Weryfikacja 202 response
    - Weryfikacja `job_id` w odpowiedzi
    - Weryfikacja zapisu w `ai_jobs` z statusem "queued"
    - Weryfikacja zapisu w `analytics_events` z typem "ai_generate_started"

- Test walidacji:
    - Tekst 999 znaków → 422
    - Tekst 10001 znaków → 422
    - Brak pola `source_text` → 400/422
    - Puste `source_text` → 422

- Test bezpieczeństwa:
    - Niezalogowany użytkownik → 401
    - RLS: użytkownik A nie widzi zadań użytkownika B

**Test:**

- `bin/phpunit tests/Feature/FlashcardGeneratorTest.php`

---

### Krok 13: Dokumentacja

**Ścieżka:** README lub `docs/api/generate-endpoint.md`

**Zadania:**

- Opisać endpoint `/generate`
- Przykłady requestów i responses (curl/HTTPie)
- Dokumentacja błędów i kodów statusu

---

### Krok 14: Code review i refactoring

**Zadania:**

- Sprawdzić zgodność z PSR-12
- Uruchomić PHPStan/Psalm (poziom max lub 8)
- Code review zgodnie z checklistą z `symfony.md`:
    - [ ] Testy (unit/integration/feature) przechodzą
    - [ ] Migracje + indeksy (sprawdzić czy potrzebne nowe)
    - [ ] Walidacje (Form/Validator + DB) kompletne
    - [ ] RLS respektowany (test integracyjny)
    - [ ] Log błędów + event analityczny dodany
    - [ ] Brak N+1 / profilowanie wykonane
    - [ ] Security Quick-Check:
        - [ ] Sprawdzenie własności zasobu (IDOR)
        - [ ] CSRF (jeśli dotyczy)
        - [ ] Escaping w odpowiedziach
        - [ ] Brak sekretów w logach

---

### Krok 15: Deployment i monitoring

**Zadania:**

- Uruchomić migracje na środowisku staging/production
- Skonfigurować monitoring (logs, errors, metrics)
- Testować endpoint na staging
- Deploy na production
- Monitorować logi i metryki przez pierwsze 24h

---

## 10. Checklisty jakości

### Definition of Done (DoD)

- [ ] Request DTO utworzone z walidacją
- [ ] Command i Handler zaimplementowane
- [ ] Fabryka domenowa `AiJob::createForGeneration()` dodana
- [ ] Response DTO utworzone
- [ ] Kontroler z obsługą błędów zaimplementowany
- [ ] Routing skonfigurowany
- [ ] Security (uwierzytelnianie) skonfigurowane
- [ ] Analytics service publikuje eventy
- [ ] RLS session configuration działająca
- [ ] Testy unit napisane i przechodzą
- [ ] Testy integration napisane i przechodzą
- [ ] Testy feature (HTTP) napisane i przechodzą
- [ ] Migracje sprawdzone (CHECK constraint istnieje)
- [ ] PHPStan/Psalm przechodzi (poziom 8+)
- [ ] Dokumentacja endpoint'a napisana
- [ ] Code review wykonany

### Security Checklist

- [ ] Endpoint wymaga uwierzytelnienia (`#[IsGranted]`)
- [ ] `user_id` ustawiany z sesji, nie z requesta (IDOR prevention)
- [ ] RLS policy na `ai_jobs` wymusza `user_id = current_app_user()`
- [ ] Walidacja długości `source_text` (1000-10000) na dwóch poziomach
- [ ] Doctrine ORM używa parametryzowanych zapytań (SQL injection prevention)
- [ ] Brak logowania pełnego `source_text` w application logs (tylko w DB)
- [ ] Obsługa błędów nie ujawnia stack trace w production
- [ ] Rate limiting rozważone (opcjonalnie dla MVP)

---

## 11. Metryki sukcesu

Po wdrożeniu należy monitorować:

1. **Functional metrics:**
    - Liczba utworzonych zadań AI (`ai_jobs` ze statusem "queued")
    - Stosunek sukcesów do błędów walidacji (422)
    - Średni czas odpowiedzi endpointa (target: < 200ms)

2. **Error metrics:**
    - Liczba błędów 500 (powinno być 0 lub bardzo niskie)
    - Liczba błędów 401 (użytkownicy próbujący bez logowania)
    - Liczba błędów walidacji 422 (normalne, ale warto śledzić wzorce)

3. **Security metrics:**
    - Próby dostępu bez uwierzytelnienia (401)
    - Anomalie w długości `source_text` (potencjalne ataki)

4. **Business metrics:**
    - Adoption rate endpointa (ile użytkowników używa)
    - Konwersja z `ai_generate_started` do `ai_generate_succeeded`

---

## 12. Potencjalne rozszerzenia (poza MVP)

1. **Rate limiting:** ograniczenie liczby requestów na użytkownika
2. **Webhook/callback:** notyfikacja użytkownika po zakończeniu generowania
3. **Batch generation:** wiele tekstów źródłowych w jednym requeście
4. **Preview mode:** podgląd pierwszych 3 fiszek przed zapisaniem całego zestawu
5. **Custom AI model selection:** użytkownik wybiera model AI (GPT-4, Claude, etc.)
6. **Text preprocessing:** automatyczne czyszczenie/formatowanie tekstu źródłowego
7. **Quota management:** limity na liczbę generacji dziennie/miesięcznie
