# API Endpoint Implementation Plan: POST /api/sets

## 1. Przegląd punktu końcowego

Endpoint służy do utworzenia nowego zestawu fiszek, który może być:

- **Pusty** – dla tworzenia manualnego (użytkownik doda karty później)
- **Z kartami** – dla zapisania zestawu wygenerowanego przez AI (lub manualnie utworzonego na froncie)

Endpoint obsługuje również linkowanie KPI poprzez opcjonalny parametr `job_id`, który aktualizuje rekord w tabeli
`ai_jobs` informacjami o zaakceptowanych i edytowanych kartach.

## 2. Szczegóły żądania

- **Metoda HTTP**: POST
- **Struktura URL**: `/api/sets`
- **Autoryzacja**: Wymagane uwierzytelnienie (użytkownik zalogowany)
- **Content-Type**: `application/json`

### Parametry żądania

#### Wymagane:

- `name` (string): Nazwa zestawu
    - Nie może być pusta
    - Unikalna w obrębie użytkownika (case-insensitive)
    - Walidowana jako CITEXT w bazie danych

#### Opcjonalne:

- `cards` (array of objects): Tablica kart do zapisania
    - `front` (string, wymagane): Przód karty (max 1000 znaków)
    - `back` (string, wymagane): Tył karty (max 1000 znaków)
    - `origin` (enum: 'ai' | 'manual', opcjonalne): Źródło karty, domyślnie 'manual'
    - `edited` (boolean, opcjonalne): Czy karta została edytowana przez użytkownika przed zapisem
- `job_id` (UUID, opcjonalne): Identyfikator pracy AI dla trackingu KPI

### Przykład Request Body:

```json
{
    "name": "Matematyka - Geometria",
    "cards": [
        {
            "front": "Co to jest trójkąt równoboczny?",
            "back": "Trójkąt, którego wszystkie boki mają jednakową długość",
            "origin": "ai",
            "edited": false
        },
        {
            "front": "Wzór na pole trójkąta",
            "back": "P = (a × h) / 2",
            "origin": "ai",
            "edited": true
        }
    ],
    "job_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

## 3. Wykorzystywane typy

### Domain Layer (`src/Domain/`)

#### Value Objects (`src/Domain/Value/`):

- `SetName`: Walidacja nazwy zestawu (niepusta, zgodna z regułami biznesowymi)
- `CardFront`: Walidacja przodu karty (niepusty, max 1000 znaków)
- `CardBack`: Walidacja tyłu karty (niepusty, max 1000 znaków)
- `UserId`: Identyfikator użytkownika (UUID)
- `AiJobId`: Identyfikator pracy AI (UUID)

#### Enums (`src/Domain/Value/`):

- `CardOrigin`: Enum z wartościami `AI`, `MANUAL`

#### Repository Interfaces (`src/Domain/Repository/`):

- `SetRepositoryInterface`: Metody `save(Set)`, `findByOwnerAndName(UserId, SetName)`
- `CardRepositoryInterface`: Metody `save(Card)`, `saveAll(array)`
- `AiJobRepositoryInterface`: Metody `findById(AiJobId)`, `save(AiJob)`

### Application Layer (`src/Application/`)

#### Command (`src/Application/Command/`):

```php
final readonly class CreateSetCommand
{
    public function __construct(
        public UserId $userId,
        public SetName $name,
        /** @var CreateSetCardDto[] */
        public array $cards,
        public ?AiJobId $jobId = null,
    ) {}
}

final readonly class CreateSetCardDto
{
    public function __construct(
        public CardFront $front,
        public CardBack $back,
        public CardOrigin $origin,
        public bool $wasEdited,
    ) {}
}
```

#### Handler (`src/Application/Handler/`):

```php
final readonly class CreateSetHandler
{
    public function __construct(
        private SetRepositoryInterface $setRepository,
        private CardRepositoryInterface $cardRepository,
        private ?AiJobRepositoryInterface $aiJobRepository,
        private ClockInterface $clock,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(CreateSetCommand $command): CreateSetResult
    {
        // Logika tworzenia zestawu i kart
    }
}
```

#### Handler Result:

```php
final readonly class CreateSetResult
{
    public function __construct(
        public string $setId,
        public string $name,
        public int $cardCount,
    ) {}
}
```

### UI Layer (`src/UI/Http/`)

#### Request DTO (`src/UI/Http/Request/`):

```php
final class CreateSetRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    public string $name;

    /** @var CreateSetCardRequestDto[] */
    #[Assert\Valid]
    public array $cards = [];

    #[Assert\Uuid]
    public ?string $job_id = null;
}

final class CreateSetCardRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 1000)]
    public string $front;

    #[Assert\NotBlank]
    #[Assert\Length(max: 1000)]
    public string $back;

    #[Assert\Choice(choices: ['ai', 'manual'])]
    public string $origin = 'manual';

    public bool $edited = false;
}
```

#### Response DTO (`src/UI/Http/Response/`):

```php
final readonly class CreateSetResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public int $card_count,
    ) {}
}
```

#### Controller (`src/UI/Http/Controller/`):

```php
final class CreateSetController extends AbstractController
{
    #[Route('/api/sets', name: 'api_sets_create', methods: ['POST'])]
    public function __invoke(
        Request $request,
        CreateSetHandler $handler,
        ValidatorInterface $validator,
        SerializerInterface $serializer,
    ): JsonResponse {}
}
```

## 4. Szczegóły odpowiedzi

### Sukces (201 Created):

```json
{
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Matematyka - Geometria",
    "card_count": 15
}
```

**Headers**:

- `Location: /api/sets/{id}`
- `Content-Type: application/json`

### Błędy:

#### 400 Bad Request:

Nieprawidłowy format JSON lub brak wymaganych pól.

```json
{
    "error": "Invalid JSON format",
    "code": "bad_request"
}
```

#### 401 Unauthorized:

Użytkownik nieuwierzytelniony.

```json
{
    "error": "Authentication required",
    "code": "unauthorized"
}
```

#### 404 Not Found:

Podany `job_id` nie istnieje lub nie należy do użytkownika.

```json
{
    "error": "AI job not found",
    "code": "job_not_found"
}
```

#### 409 Conflict:

Zestaw o tej nazwie już istnieje dla danego użytkownika.

```json
{
    "error": "Set with this name already exists",
    "code": "duplicate_set_name",
    "field": "name"
}
```

#### 422 Unprocessable Entity:

Błędy walidacji danych wejściowych.

```json
{
    "error": "Validation failed",
    "code": "validation_error",
    "violations": [
        {
            "field": "name",
            "message": "Set name cannot be empty"
        },
        {
            "field": "cards[0].front",
            "message": "Card front text is too long (max 1000 characters)"
        }
    ]
}
```

#### 500 Internal Server Error:

Nieoczekiwany błąd serwera (logowany do systemu monitoringu).

```json
{
    "error": "Internal server error",
    "code": "internal_error"
}
```

## 5. Przepływ danych

### 5.1. Diagram przepływu:

```
┌──────────┐
│  Client  │
└────┬─────┘
     │ POST /api/sets
     │ {name, cards?, job_id?}
     ▼
┌────────────────────────────┐
│  CreateSetController       │
│  (UI/Http)                 │
│  - Deserializacja JSON     │
│  - Walidacja (Validator)   │
│  - Mapowanie na Command    │
└────────┬───────────────────┘
         │ CreateSetCommand
         ▼
┌────────────────────────────┐
│  CreateSetHandler          │
│  (Application)             │
│  1. Walidacja biznesowa    │
│  2. Sprawdź unikalność     │
│  3. Utwórz encję Set       │
│  4. Utwórz encje Card      │
│  5. Aktualizuj AiJob (opt) │
│  6. Zapisz w transakcji    │
│  7. Publikuj event         │
└────────┬───────────────────┘
         │ CreateSetResult
         ▼
┌────────────────────────────┐
│  Infrastructure/Doctrine   │
│  - Zapis Set do `sets`     │
│  - Zapis Cards do `cards`  │
│  - Update `ai_jobs`        │
│  - Commit transakcji       │
│  - RLS: weryfikacja owner  │
└────────┬───────────────────┘
         │
         ▼
┌────────────────────────────┐
│  PostgreSQL Database       │
│  - Constraint UNIQUE       │
│    (owner_id, name)        │
│  - Trigger: card_count++   │
│  - RLS policies            │
└────────┬───────────────────┘
         │ Success
         ▼
┌────────────────────────────┐
│  Controller                │
│  - Mapowanie Result→DTO    │
│  - Zwrot 201 + Location    │
└────────┬───────────────────┘
         │ JSON Response
         ▼
┌──────────┐
│  Client  │
└──────────┘
```

### 5.2. Szczegóły kroków:

1. **Walidacja Request** (Controller):
    - Deserializacja JSON do `CreateSetRequest`
    - Walidacja Symfony Validator
    - Sprawdzenie autoryzacji (zalogowany użytkownik)

2. **Mapowanie na Command** (Controller):
    - Pobranie `UserId` z Security context
    - Utworzenie Value Objects (`SetName`, `CardFront`, `CardBack`)
    - Utworzenie `CreateSetCommand`

3. **Sprawdzenie konfliktów** (Handler):
    - Sprawdzenie czy istnieje zestaw o tej nazwie dla użytkownika
    - Jeśli tak → rzuć wyjątek `DuplicateSetNameException`

4. **Weryfikacja job_id** (Handler, jeśli podany):
    - Pobranie `AiJob` z repozytorium
    - Weryfikacja własności przez RLS
    - Jeśli nie istnieje → rzuć `AiJobNotFoundException`

5. **Utworzenie encji** (Handler):
    - Nowa encja `Set` z `owner_id` i `name`
    - Nowe encje `Card` dla każdego elementu w `cards[]`
    - Ustawienie `edited_by_user_at = now()` jeśli `edited = true`

6. **Aktualizacja ai_jobs** (Handler, jeśli job_id):
    - `set_id` = nowo utworzony set ID
    - `accepted_count` = liczba kart z `origin='ai'`
    - `edited_count` = liczba kart z `origin='ai' AND edited=true`
    - `updated_at` = now()

7. **Persystencja** (Infrastructure):
    - Rozpoczęcie transakcji DB
    - Zapis `Set`
    - Zapis wszystkich `Card`
    - Aktualizacja `AiJob` (jeśli dotyczy)
    - Commit transakcji
    - RLS automatycznie weryfikuje `owner_id`

8. **Publikacja eventów** (Handler):
    - Event: `SetCreated` z danymi: `set_id`, `owner_id`, `card_count`, `origin_counts`
    - Obsługa w AnalyticsEventSubscriber

9. **Zwrot odpowiedzi** (Controller):
    - Mapowanie `CreateSetResult` → `CreateSetResponse`
    - Status 201 Created
    - Header `Location: /api/sets/{id}`

### 5.3. Interakcja z bazą danych:

**Tabele zaangażowane:**

- `sets`: INSERT nowego rekordu
- `cards`: INSERT N rekordów (jeśli cards[] podane)
- `ai_jobs`: UPDATE rekordu (jeśli job_id podany)

**Constraints/Triggers wykonywane:**

- `UNIQUE (owner_id, name)` na `sets` - zapobiega duplikatom
- Trigger aktualizujący `sets.card_count` po INSERT do `cards`
- RLS policies weryfikują `owner_id = current_app_user()`

**Indeksy wykorzystywane:**

- `sets_owner_listing ON (owner_id, deleted_at)` - sprawdzenie unikalności
- `ai_jobs` primary key - update job_id

## 6. Względy bezpieczeństwa

### 6.1. Uwierzytelnianie i autoryzacja

- **Wymóg uwierzytelnienia**: Endpoint dostępny tylko dla zalogowanych użytkowników
- **Firewall Symfony**: Konfiguracja w `security.yaml`
- **RLS (Row Level Security)**:
    - Na początku żądania: `SET app.current_user_id = '<uuid>'`
    - Wszystkie operacje DB automatycznie filtrują po `owner_id`
    - Użytkownik nie może:
        - Utworzyć zestawu dla innego użytkownika
        - Podać cudzego `job_id`
        - Zobaczyć/zmodyfikować cudzych zestawów

### 6.2. Walidacja danych wejściowych

**Warstwy walidacji**:

1. **Deserializacja** (Symfony Serializer):
    - Weryfikacja struktury JSON
    - Mapowanie typów

2. **Symfony Validator** (Application layer):
    - Constraints na `CreateSetRequest`
    - Długości pól, format UUID, dozwolone wartości enum

3. **Value Objects** (Domain layer):
    - `SetName`: niepusta, reasonable length
    - `CardFront`, `CardBack`: max 1000 znaków, niepuste

4. **Database Constraints**:
    - `CHECK (name <> '')`
    - `CHECK (char_length(front) <= 1000)`
    - `CHECK (char_length(back) <= 1000)`
    - `UNIQUE (owner_id, name)`

### 6.3. Ochrona przed atakami

| Atak                | Mechanizm ochrony                                                                   |
|---------------------|-------------------------------------------------------------------------------------|
| **SQL Injection**   | Doctrine ORM parametryzuje zapytania; brak raw SQL                                  |
| **XSS**             | Twig auto-escape; walidacja długości pól                                            |
| **CSRF**            | Tokeny CSRF w formularzach (nie dotyczy API JSON, ale opcjonalnie SameSite cookies) |
| **IDOR**            | RLS weryfikuje własność zasobów; `job_id` musi należeć do użytkownika               |
| **Mass Assignment** | DTO z jawną definicją pól; `owner_id` nigdy nie z requestu, tylko z sesji           |
| **DoS**             | Rate limiting (do implementacji); timeout na transakcje DB                          |
| **Data Leakage**    | Brak wrażliwych danych w error messages; szczegóły błędów tylko w logach            |

### 6.4. Logowanie i audyt

**Co logować**:

- Każde utworzenie zestawu: `set_created` event
- Błędy 409/422: do analytics dla wykrywania problemów UX
- Błędy 500: do error tracking (Sentry/podobne)
- Aktualizacje `ai_jobs`: do KPI dashboardu

**Dane w logach**:

- `user_id`, `set_id`, `card_count`, `origin_counts` (ai vs manual)
- `job_id` jeśli podany
- Timestamp, request ID (correlation ID)

**Nie logować**:

- Treści kart (mogą zawierać dane osobowe uczniów)
- API keys, tokeny sesji

## 7. Obsługa błędów

### 7.1. Hierarchia wyjątków

```php
// Domain Exceptions
DomainException
├── DuplicateSetNameException (409)
├── InvalidSetNameException (422)
├── InvalidCardContentException (422)
└── AiJobNotFoundException (404)

// Application Exceptions
ApplicationException
├── ValidationException (422)
└── UnauthorizedException (401)

// Infrastructure Exceptions
InfrastructureException
└── DatabaseException (500)
```

### 7.2. Mapowanie wyjątków na kody HTTP

| Exception                     | Status | Response Code        | User Message                                               |
|-------------------------------|--------|----------------------|------------------------------------------------------------|
| `DuplicateSetNameException`   | 409    | `duplicate_set_name` | "Masz już zestaw o tej nazwie. Wybierz inną nazwę."        |
| `AiJobNotFoundException`      | 404    | `job_not_found`      | "Nie znaleziono pracy AI o podanym ID."                    |
| `ValidationException`         | 422    | `validation_error`   | Lista błędów walidacji                                     |
| `InvalidSetNameException`     | 422    | `invalid_set_name`   | "Nazwa zestawu jest nieprawidłowa."                        |
| `InvalidCardContentException` | 422    | `invalid_card`       | "Treść karty przekracza maksymalną długość (1000 znaków)." |
| `UnauthorizedException`       | 401    | `unauthorized`       | "Musisz być zalogowany, aby wykonać tę operację."          |
| `DatabaseException`           | 500    | `internal_error`     | "Wystąpił błąd. Spróbuj ponownie później."                 |
| `\Throwable` (catch-all)      | 500    | `internal_error`     | "Wystąpił nieoczekiwany błąd."                             |

### 7.3. Exception Listener

```php
// src/UI/Http/EventListener/ExceptionListener.php
final readonly class ApiExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Mapowanie exception → JsonResponse
        $response = match (true) {
            $exception instanceof DuplicateSetNameException => new JsonResponse(
                ['error' => $exception->getMessage(), 'code' => 'duplicate_set_name', 'field' => 'name'],
                409
            ),
            // ... inne przypadki
            default => new JsonResponse(
                ['error' => 'Internal server error', 'code' => 'internal_error'],
                500
            ),
        };

        $event->setResponse($response);
    }
}
```

### 7.4. Logowanie błędów

- **409, 422**: Log level `INFO` (oczekiwane błędy użytkownika)
- **404**: Log level `WARNING` (podejrzane: job_id nie istnieje)
- **500**: Log level `ERROR` + zapis do error trackingu
- Każdy błąd: correlation ID dla śledzenia przez wiele systemów

## 8. Rozważania dotyczące wydajności

### 8.1. Potencjalne wąskie gardła

1. **INSERT wielu kart w jednej transakcji**:
    - Problem: Zestaw z 50+ kartami → 50+ INSERTów
    - Mitigacja: Batch INSERT (Doctrine `flush()` na końcu)

2. **Sprawdzenie unikalności nazwy**:
    - Problem: Zapytanie SELECT przed INSERT
    - Mitigacja: Unikalny index w DB; pozwolić na constraint violation i obsłużyć exception

3. **Aktualizacja ai_jobs**:
    - Problem: Dodatkowy UPDATE w transakcji
    - Mitigacja: Opcjonalny (tylko jeśli job_id podany); indeks na `ai_jobs.id`

4. **Trigger aktualizujący card_count**:
    - Problem: Trigger wykonywany dla każdego INSERT karty
    - Mitigacja: Opcjonalnie denormalizować card_count w aplikacji, a nie triggerem

### 8.2. Strategie optymalizacji

**Immediate (MVP)**:

- Doctrine batch insert: `$entityManager->flush()` raz po wszystkich `persist()`
- Indeksy:
    - `sets (owner_id, name)` - UNIQUE
    - `cards (set_id, deleted_at)` - dla szybkiego COUNT
    - `ai_jobs (id)` - PRIMARY KEY

**Future (post-MVP)**:

- Asynchroniczne przetwarzanie: Jeśli zestawy > 100 kart, kolejkuj tworzenie (Messenger)
- Cache: Brak potrzeby - operacja write
- Partycjonowanie: `cards` po `set_id` jeśli miliony kart (mało prawdopodobne w MVP)

### 8.3. Limity i rate limiting

**Limity biznesowe**:

- Max kart w jednym request: **100** (zabezpieczenie przed DoS)
- Max długość nazwy zestawu: **255 znaków**
- Max długość front/back: **1000 znaków** (zgodnie z DB schema)

**Rate limiting** (do implementacji):

- Symfony Rate Limiter: 30 requestów/minutę na użytkownika
- 429 Too Many Requests jeśli przekroczony

### 8.4. Monitoring

**Metryki do śledzenia**:

- Średni czas odpowiedzi endpointu (target: <500ms)
- 95-ty percentyl (target: <1s)
- Liczba błędów 409/422/500
- Rozkład liczby kart w zestawach (dla optymalizacji batch insert)
- Użycie `job_id` (% requestów z job_id vs bez)

## 9. Etapy wdrożenia

### Krok 1: Przygotowanie Value Objects (Domain Layer)

**Czas: 1-2h**

- [ ] Utworzyć `src/Domain/Value/SetName.php`:
  ```php
  final readonly class SetName
  {
      public function __construct(private string $value) {
          if (trim($value) === '') {
              throw new InvalidSetNameException('Set name cannot be empty');
          }
          if (mb_strlen($value) > 255) {
              throw new InvalidSetNameException('Set name too long');
          }
      }

      public function toString(): string { return $this->value; }
  }
  ```

- [ ] Utworzyć `src/Domain/Value/CardFront.php` i `CardBack.php`:
  ```php
  final readonly class CardFront
  {
      public function __construct(private string $value) {
          if (trim($value) === '') {
              throw new InvalidCardContentException('Card front cannot be empty');
          }
          if (mb_strlen($value) > 1000) {
              throw new InvalidCardContentException('Card front too long (max 1000 chars)');
          }
      }

      public function toString(): string { return $this->value; }
  }
  ```

- [ ] Utworzyć `src/Domain/Value/CardOrigin.php` (Enum):
  ```php
  enum CardOrigin: string
  {
      case AI = 'ai';
      case MANUAL = 'manual';
  }
  ```

- [ ] Utworzyć `src/Domain/Value/AiJobId.php` (UUID wrapper)

**Testy**: Unit testy dla Value Objects (walidacja długości, pustych wartości)

---

### Krok 2: Rozszerzenie encji Doctrine (Infrastructure Layer)

**Czas: 1-2h**

- [ ] Zweryfikować istniejącą encję `Set` w `src/Infrastructure/Doctrine/Entity/Set.php`:
    - Pole `owner_id` (UUID, FK do users)
    - Pole `name` (string, CITEXT w DB)
    - Pole `card_count` (int, default 0)
    - Pole `created_at`, `updated_at`
    - Metoda fabryczna: `Set::create(UserId $ownerId, SetName $name, \DateTimeImmutable $createdAt)`

- [ ] Zweryfikować encję `Card`:
    - Pole `set_id` (UUID, FK do sets)
    - Pole `origin` (enum: ai/manual)
    - Pole `front`, `back` (string)
    - Pole `edited_by_user_at` (DateTimeImmutable, nullable)
    - Metoda fabryczna: `Card::create(SetId, CardFront, CardBack, CardOrigin, bool $edited, Clock)`

- [ ] Zweryfikować encję `AiJob`:
    - Pole `set_id` (UUID, nullable, FK do sets)
    - Pola `accepted_count`, `edited_count`
    - Metoda: `linkToSet(SetId, int $acceptedCount, int $editedCount, \DateTimeImmutable $now)`

**Testy**: Integration testy z rzeczywistą bazą PostgreSQL (INSERT, relacje FK, RLS)

---

### Krok 3: Repository Interfaces i implementacje

**Czas: 1-2h**

- [ ] `src/Domain/Repository/SetRepositoryInterface.php`:
  ```php
  interface SetRepositoryInterface
  {
      public function save(Set $set): void;
      public function findByOwnerAndName(UserId $ownerId, SetName $name): ?Set;
  }
  ```

- [ ] Implementacja `src/Infrastructure/Doctrine/Repository/DoctrineSetRepository.php`:
    - Metoda `save()`: `persist()` + `flush()`
    - Metoda `findByOwnerAndName()`: DQL z filtrem `deleted_at IS NULL`

- [ ] `src/Domain/Repository/CardRepositoryInterface.php`:
  ```php
  interface CardRepositoryInterface
  {
      public function saveAll(array $cards): void;
  }
  ```

- [ ] Implementacja `DoctrineCardRepository`:
    - Metoda `saveAll()`: `foreach` + `persist()`, następnie jeden `flush()`

- [ ] `src/Domain/Repository/AiJobRepositoryInterface.php`:
  ```php
  interface AiJobRepositoryInterface
  {
      public function findById(AiJobId $id): ?AiJob;
      public function save(AiJob $job): void;
  }
  ```

**Testy**: Integration testy (save, findByOwnerAndName, RLS verification)

---

### Krok 4: Application Command i Handler

**Czas: 2-3h**

- [ ] `src/Application/Command/CreateSetCommand.php`:
  ```php
  final readonly class CreateSetCommand
  {
      /**
       * @param CreateSetCardDto[] $cards
       */
      public function __construct(
          public UserId $userId,
          public SetName $name,
          public array $cards,
          public ?AiJobId $jobId = null,
      ) {}
  }
  ```

- [ ] `src/Application/Command/CreateSetCardDto.php`:
  ```php
  final readonly class CreateSetCardDto
  {
      public function __construct(
          public CardFront $front,
          public CardBack $back,
          public CardOrigin $origin,
          public bool $wasEdited,
      ) {}
  }
  ```

- [ ] `src/Application/Handler/CreateSetHandler.php`:
  ```php
  final readonly class CreateSetHandler
  {
      public function __construct(
          private SetRepositoryInterface $setRepository,
          private CardRepositoryInterface $cardRepository,
          private ?AiJobRepositoryInterface $aiJobRepository,
          private ClockInterface $clock,
          private EventDispatcherInterface $eventDispatcher,
      ) {}

      public function __invoke(CreateSetCommand $command): CreateSetResult
      {
          // 1. Sprawdź czy zestaw o tej nazwie już istnieje
          $existing = $this->setRepository->findByOwnerAndName($command->userId, $command->name);
          if ($existing !== null) {
              throw new DuplicateSetNameException("Set with name '{$command->name->toString()}' already exists");
          }

          // 2. Jeśli job_id podany, pobierz i zweryfikuj
          $aiJob = null;
          if ($command->jobId !== null) {
              $aiJob = $this->aiJobRepository->findById($command->jobId);
              if ($aiJob === null) {
                  throw new AiJobNotFoundException("AI job not found");
              }
              // RLS automatycznie weryfikuje własność
          }

          // 3. Utwórz encję Set
          $now = $this->clock->now();
          $set = Set::create($command->userId, $command->name, $now);
          $this->setRepository->save($set);

          // 4. Utwórz encje Card
          $cards = [];
          $aiAcceptedCount = 0;
          $aiEditedCount = 0;

          foreach ($command->cards as $cardDto) {
              $card = Card::create(
                  $set->getId(),
                  $cardDto->front,
                  $cardDto->back,
                  $cardDto->origin,
                  $cardDto->wasEdited,
                  $this->clock,
              );
              $cards[] = $card;

              // Zliczaj dla KPI
              if ($cardDto->origin === CardOrigin::AI) {
                  $aiAcceptedCount++;
                  if ($cardDto->wasEdited) {
                      $aiEditedCount++;
                  }
              }
          }

          $this->cardRepository->saveAll($cards);

          // 5. Aktualizuj AiJob jeśli podany
          if ($aiJob !== null) {
              $aiJob->linkToSet($set->getId(), $aiAcceptedCount, $aiEditedCount, $now);
              $this->aiJobRepository->save($aiJob);
          }

          // 6. Publikuj event
          $this->eventDispatcher->dispatch(new SetCreatedEvent(
              $set->getId(),
              $command->userId,
              count($cards),
              $aiAcceptedCount,
              $aiEditedCount,
          ));

          // 7. Zwróć wynik
          return new CreateSetResult(
              $set->getId()->toString(),
              $set->getName()->toString(),
              count($cards),
          );
      }
  }
  ```

- [ ] `src/Application/Handler/CreateSetResult.php`

**Testy**: Unit testy Handlera (mock repositories) + Feature testy (end-to-end)

---

### Krok 5: UI Layer - Request/Response DTOs

**Czas: 1h**

- [ ] `src/UI/Http/Request/CreateSetRequest.php`:
  ```php
  final class CreateSetRequest
  {
      #[Assert\NotBlank(message: 'Set name is required')]
      #[Assert\Length(max: 255, maxMessage: 'Set name is too long')]
      public string $name;

      /**
       * @var CreateSetCardRequestDto[]
       */
      #[Assert\Valid]
      #[Assert\Count(max: 100, maxMessage: 'Too many cards (max 100)')]
      public array $cards = [];

      #[Assert\Uuid(message: 'Invalid job ID format')]
      public ?string $job_id = null;
  }
  ```

- [ ] `src/UI/Http/Request/CreateSetCardRequestDto.php`:
  ```php
  final class CreateSetCardRequestDto
  {
      #[Assert\NotBlank]
      #[Assert\Length(max: 1000, maxMessage: 'Card front is too long')]
      public string $front;

      #[Assert\NotBlank]
      #[Assert\Length(max: 1000, maxMessage: 'Card back is too long')]
      public string $back;

      #[Assert\Choice(choices: ['ai', 'manual'], message: 'Origin must be "ai" or "manual"')]
      public string $origin = 'manual';

      public bool $edited = false;
  }
  ```

- [ ] `src/UI/Http/Response/CreateSetResponse.php`:
  ```php
  final readonly class CreateSetResponse
  {
      public function __construct(
          public string $id,
          public string $name,
          public int $card_count,
      ) {}
  }
  ```

---

### Krok 6: Controller

**Czas: 1-2h**

- [ ] `src/UI/Http/Controller/CreateSetController.php`:
  ```php
  #[Route('/api/sets', name: 'api_sets_create', methods: ['POST'])]
  final class CreateSetController extends AbstractController
  {
      public function __invoke(
          Request $request,
          CreateSetHandler $handler,
          ValidatorInterface $validator,
          SerializerInterface $serializer,
      ): JsonResponse {
          // 1. Deserializuj request
          $dto = $serializer->deserialize(
              $request->getContent(),
              CreateSetRequest::class,
              'json'
          );

          // 2. Waliduj
          $errors = $validator->validate($dto);
          if (count($errors) > 0) {
              return $this->json([
                  'error' => 'Validation failed',
                  'code' => 'validation_error',
                  'violations' => array_map(
                      fn($error) => [
                          'field' => $error->getPropertyPath(),
                          'message' => $error->getMessage(),
                      ],
                      iterator_to_array($errors)
                  ),
              ], 422);
          }

          // 3. Pobierz UserId z Security
          $user = $this->getUser();
          if ($user === null) {
              return $this->json([
                  'error' => 'Authentication required',
                  'code' => 'unauthorized',
              ], 401);
          }
          $userId = UserId::fromString($user->getId());

          // 4. Mapuj na Command
          $command = new CreateSetCommand(
              $userId,
              new SetName($dto->name),
              array_map(
                  fn($cardDto) => new CreateSetCardDto(
                      new CardFront($cardDto->front),
                      new CardBack($cardDto->back),
                      CardOrigin::from($cardDto->origin),
                      $cardDto->edited,
                  ),
                  $dto->cards
              ),
              $dto->job_id ? AiJobId::fromString($dto->job_id) : null,
          );

          // 5. Wykonaj Handler
          try {
              $result = $handler($command);
          } catch (DuplicateSetNameException $e) {
              return $this->json([
                  'error' => $e->getMessage(),
                  'code' => 'duplicate_set_name',
                  'field' => 'name',
              ], 409);
          } catch (AiJobNotFoundException $e) {
              return $this->json([
                  'error' => $e->getMessage(),
                  'code' => 'job_not_found',
              ], 404);
          }

          // 6. Zwróć Response
          return $this->json(
              new CreateSetResponse(
                  $result->setId,
                  $result->name,
                  $result->cardCount,
              ),
              201,
              ['Location' => "/api/sets/{$result->setId}"]
          );
      }
  }
  ```

---

### Krok 7: Exception Handling i Error Responses

**Czas: 1h**

- [ ] Utworzyć custom exceptions:
    - `src/Domain/Exception/DuplicateSetNameException.php`
    - `src/Domain/Exception/InvalidSetNameException.php`
    - `src/Domain/Exception/InvalidCardContentException.php`
    - `src/Domain/Exception/AiJobNotFoundException.php`

- [ ] `src/UI/Http/EventListener/ApiExceptionListener.php`:
  ```php
  final readonly class ApiExceptionListener
  {
      public function onKernelException(ExceptionEvent $event): void
      {
          $exception = $event->getThrowable();

          $response = match (true) {
              $exception instanceof DuplicateSetNameException => new JsonResponse([
                  'error' => $exception->getMessage(),
                  'code' => 'duplicate_set_name',
                  'field' => 'name',
              ], 409),

              $exception instanceof AiJobNotFoundException => new JsonResponse([
                  'error' => 'AI job not found',
                  'code' => 'job_not_found',
              ], 404),

              // ... inne

              default => new JsonResponse([
                  'error' => 'Internal server error',
                  'code' => 'internal_error',
              ], 500),
          };

          $event->setResponse($response);
      }
  }
  ```

- [ ] Zarejestrować listener w `config/services.yaml`:
  ```yaml
  App\UI\Http\EventListener\ApiExceptionListener:
      tags:
          - { name: kernel.event_listener, event: kernel.exception }
  ```

---

### Krok 8: RLS Configuration i Database Setup

**Czas: 1h**

- [ ] Utworzyć Doctrine Event Subscriber dla ustawiania `app.current_user_id`:
  ```php
  // src/Infrastructure/Doctrine/EventSubscriber/RlsSubscriber.php
  final readonly class RlsSubscriber implements EventSubscriber
  {
      public function __construct(
          private Security $security,
      ) {}

      public function getSubscribedEvents(): array
      {
          return [Events::postConnect];
      }

      public function postConnect(ConnectionEventArgs $args): void
      {
          $user = $this->security->getUser();
          if ($user === null) {
              return;
          }

          $conn = $args->getConnection();
          $conn->executeStatement(
              "SET app.current_user_id = :user_id",
              ['user_id' => $user->getId()]
          );
      }
  }
  ```

- [ ] Zweryfikować polityki RLS w migracji (powinny już istnieć z wcześniejszych kroków)

- [ ] Przetestować RLS:
    - User A nie może utworzyć zestawu z job_id należącym do User B
    - Constraint UNIQUE (owner_id, name) działa poprawnie

---

### Krok 9: Event Handling i Analytics

**Czas: 1-2h**

- [ ] `src/Domain/Event/SetCreatedEvent.php`:
  ```php
  final readonly class SetCreatedEvent
  {
      public function __construct(
          public string $setId,
          public string $userId,
          public int $totalCardCount,
          public int $aiCardCount,
          public int $editedAiCardCount,
      ) {}
  }
  ```

- [ ] `src/Infrastructure/EventSubscriber/AnalyticsEventSubscriber.php`:
  ```php
  final readonly class AnalyticsEventSubscriber implements EventSubscriberInterface
  {
      public function __construct(
          private EntityManagerInterface $entityManager,
      ) {}

      public static function getSubscribedEvents(): array
      {
          return [SetCreatedEvent::class => 'onSetCreated'];
      }

      public function onSetCreated(SetCreatedEvent $event): void
      {
          $analyticsEvent = new AnalyticsEvent(
              'set_created',
              $event->userId,
              $event->setId,
              [
                  'total_cards' => $event->totalCardCount,
                  'ai_cards' => $event->aiCardCount,
                  'edited_ai_cards' => $event->editedAiCardCount,
              ]
          );

          $this->entityManager->persist($analyticsEvent);
          $this->entityManager->flush();
      }
  }
  ```

---

### Krok 10: Testy

**Czas: 3-4h**

- [ ] **Unit testy**:
    - Value Objects (SetName, CardFront, CardBack): walidacja długości, pustych wartości
    - CreateSetHandler (mockowane repozytoria): scenariusze sukcesu, duplikaty, brak job_id

- [ ] **Integration testy**:
    - Repositories z rzeczywistą bazą PostgreSQL
    - RLS policies: user nie może wstawić z cudzym owner_id
    - UNIQUE constraint (owner_id, name)
    - Trigger aktualizujący card_count

- [ ] **Feature testy (HTTP)**:
    - Sukces: POST /api/sets z kartami → 201 + poprawny JSON
    - Sukces: POST /api/sets bez kart → 201 + card_count=0
    - Sukces: POST /api/sets z job_id → ai_jobs zaktualizowany
    - Błąd 409: duplikat nazwy
    - Błąd 422: puste name, zbyt długie front/back, nieprawidłowy origin
    - Błąd 404: job_id nie istnieje
    - Błąd 401: użytkownik niezalogowany

- [ ] **Performance testy**:
    - Benchmark: utworzenie zestawu z 100 kartami (target < 1s)
    - Verify: brak N+1 queries

---

### Krok 11: Dokumentacja

**Czas: 30min**

- [ ] Dodać do `README.md` przykłady użycia endpointu:
  ```bash
  curl -X POST http://localhost:8000/api/sets \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer <token>" \
    -d '{
      "name": "Matematyka",
      "cards": [
        {"front": "2+2?", "back": "4", "origin": "manual"}
      ]
    }'
  ```

- [ ] Zaktualizować dokumentację API (jeśli istnieje plik OpenAPI/Swagger)

---

### Krok 12: Code Review i Deployment

**Czas: 1h**

- [ ] Utworzyć Pull Request z opisem zmian
- [ ] Code review (sprawdzenie: bezpieczeństwo, testy, czytelność)
- [ ] Uruchomić CI/CD pipeline:
    - PHPStan level max
    - Testy (unit, integration, feature)
    - Code style (PHP-CS-Fixer)
- [ ] Merge do main branch
- [ ] Deploy na staging
- [ ] Smoke test na staging
- [ ] Deploy na production

---

## Podsumowanie kroków

| Krok                     | Czas       | Priorytet          |
|--------------------------|------------|--------------------|
| 1. Value Objects         | 1-2h       | Wysoki             |
| 2. Encje Doctrine        | 1-2h       | Wysoki             |
| 3. Repositories          | 1-2h       | Wysoki             |
| 4. Command/Handler       | 2-3h       | Wysoki             |
| 5. Request/Response DTOs | 1h         | Wysoki             |
| 6. Controller            | 1-2h       | Wysoki             |
| 7. Exception Handling    | 1h         | Średni             |
| 8. RLS Setup             | 1h         | Wysoki (security!) |
| 9. Analytics Events      | 1-2h       | Średni             |
| 10. Testy                | 3-4h       | Wysoki             |
| 11. Dokumentacja         | 30min      | Niski              |
| 12. Review & Deploy      | 1h         | Wysoki             |
| **TOTAL**                | **15-22h** |                    |

**Minimalna ścieżka (MVP)**: Kroki 1-6, 8, 10 (testy podstawowe) = ~10-14h

**Zalecana kolejność**:

1. Najpierw: Value Objects → Encje → Repositories (fundament)
2. Następnie: Command/Handler → DTOs → Controller (logika biznesowa)
3. Na końcu: Exception handling → Analytics → Testy kompletne

---

## Załączniki

### A. Przykładowa migracja (fragmenty kluczowych constraints)

```sql
-- W migracji dla tabeli `sets`
CREATE UNIQUE INDEX sets_owner_name_unique
    ON sets (owner_id, name)
    WHERE deleted_at IS NULL;

-- Trigger dla card_count (jeśli nie istnieje)
CREATE OR REPLACE FUNCTION update_set_card_count()
    RETURNS TRIGGER AS
$$
BEGIN
    IF TG_OP = 'INSERT' AND NEW.deleted_at IS NULL THEN
        UPDATE sets SET card_count = card_count + 1 WHERE id = NEW.set_id;
    ELSIF TG_OP = 'UPDATE' AND OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
        UPDATE sets SET card_count = card_count - 1 WHERE id = NEW.set_id;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER cards_update_set_count
    AFTER INSERT OR UPDATE
    ON cards
    FOR EACH ROW
EXECUTE FUNCTION update_set_card_count();
```

### B. Przykład request/response (pełny scenariusz)

**Request**:

```http
POST /api/sets HTTP/1.1
Host: localhost:8000
Content-Type: application/json
Authorization: Bearer eyJhbGc...

{
  "name": "Biologia - Komórka",
  "cards": [
    {
      "front": "Co to jest mitochondrium?",
      "back": "Organellum komórkowe odpowiedzialne za produkcję energii (ATP)",
      "origin": "ai",
      "edited": false
    },
    {
      "front": "Czym różni się komórka roślinna od zwierzęcej?",
      "back": "Komórka roślinna ma ścianę komórkową, wakuolę i chloroplasty",
      "origin": "ai",
      "edited": true
    }
  ],
  "job_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890"
}
```

**Response (201 Created)**:

```http
HTTP/1.1 201 Created
Content-Type: application/json
Location: /api/sets/f47ac10b-58cc-4372-a567-0e02b2c3d479

{
  "id": "f47ac10b-58cc-4372-a567-0e02b2c3d479",
  "name": "Biologia - Komórka",
  "card_count": 2
}
```

**Response (409 Conflict)**:

```http
HTTP/1.1 409 Conflict
Content-Type: application/json

{
  "error": "Masz już zestaw o nazwie 'Biologia - Komórka'. Wybierz inną nazwę.",
  "code": "duplicate_set_name",
  "field": "name"
}
```

### C. Service Configuration (services.yaml)

```yaml
services:
    # Repositories
    App\Domain\Repository\SetRepositoryInterface:
        alias: App\Infrastructure\Doctrine\Repository\DoctrineSetRepository

    App\Domain\Repository\CardRepositoryInterface:
        alias: App\Infrastructure\Doctrine\Repository\DoctrineCardRepository

    App\Domain\Repository\AiJobRepositoryInterface:
        alias: App\Infrastructure\Doctrine\Repository\DoctrineAiJobRepository

    # Clock (dla testów i spójności czasu)
    App\Domain\Service\ClockInterface:
        class: App\Infrastructure\Service\SystemClock

    # Event Subscribers
    App\Infrastructure\Doctrine\EventSubscriber\RlsSubscriber:
        tags:
            - { name: doctrine.event_subscriber }

    App\Infrastructure\EventSubscriber\AnalyticsEventSubscriber:
        tags:
            - { name: kernel.event_subscriber }
```

---

**Koniec planu implementacji.**
