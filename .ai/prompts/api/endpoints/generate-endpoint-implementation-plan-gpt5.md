# API Endpoint Implementation Plan: POST /generate

## 1. Przegląd punktu końcowego

Punkt końcowy **POST `/generate`** przyjmuje długi tekst źródłowy użytkownika (1000–10000 znaków), tworzy rekord kolejki
**AI job** i **nieblokująco** uruchamia proces generowania fiszek przez pracownika (Symfony Messenger). Zwraca **202
Accepted** z uchwytem zadania (`job_id`, `status="queued"`), który UI wykorzysta do śledzenia postępu. Walidacja
długości wejścia jest zgodna z wymaganiami produktu i schematem bazy. fileciteturn0file1 fileciteturn0file3

Architektura pozostaje **monolityczna w Symfony** z widokami Twig, bez API Platform; endpoint realizujemy jako „ręczny”
kontroler JSON + Messenger. fileciteturn0file0

## 2. Szczegóły żądan ia

- **Metoda HTTP:** `POST`
- **URL:** `/generate`
- **Nagłówki:** `Content-Type: application/json`; `Accept: application/json`
- **Autoryzacja:** Wymagane zalogowanie (sesja lub token). Brak CSRF dla endpointu API.
- **Body (JSON):**
  ```json
  { "source_text": "<1000..10000 chars>" }
  ```
- **Parametry:**
    - **Wymagane:** `source_text` (string, 1000–10000 znaków, liczonych jako Unicode code points).
    - **Opcjonalne:** brak.
- **Walidacja:** `NotBlank`, `Length(min=1000, max=10000)`, odrzucenie tekstu zawierającego wyłącznie białe znaki;
  dodatkowo sprawdzenie `mb_strlen`. Limit spójny z `ai_jobs.request_prompt` (`CHECK ... BETWEEN 1000 AND 10000`).
  fileciteturn0file3

## 3. Wykorzystywane typy

**DTO (HTTP):**

- `GenerateRequestDto { string $sourceText }`
- `GenerateAcceptedDto { string $jobId, string $status = "queued" }`

**Command (warstwa aplikacyjna):**

- `EnqueueGenerateCommand { Uuid $jobId, Uuid $userId, string $sourceText }`

**Eventy domenowe (opcjonalnie):**

- `AiJobQueued { Uuid $jobId, Uuid $userId }`
- `AiJobFailed { Uuid $jobId, string $reason }`
- `AiJobSucceeded { Uuid $jobId, Uuid $setId, int $cardsCount }`

**Encje / tabele używane:**

- `ai_jobs` (status, error_message, request_prompt, response_raw, ...), z RLS po `user_id`. fileciteturn0file3
- (po stronie workera) `sets`, `cards` do zapisania wyniku generowania, zgodnie z zasadami długości i relacjami.
  fileciteturn0file2 fileciteturn0file3

## 4. Szczegóły odpowiedzi

- **202 Accepted** (po utworzeniu zadania):
  ```json
  { "job_id": "uuid", "status": "queued" }
  ```
- **422 Unprocessable Entity** (walidacja wejścia — zgodnie ze specyfikacją trasy):
  ```json
  { "errors": { "source_text": ["This value is too short. Minimum length is 1000 characters."] } }
  ```
- **401 Unauthorized** (brak sesji/tokena): `{ "error": "unauthorized" }`
- **429 Too Many Requests** (rate limiting): `{ "error": "rate_limited", "retry_after": 30 }`
- **500 Internal Server Error** (nieoczekiwany błąd): `{ "error": "internal_error", "job_id": "uuid" }`

> Uwaga: Specyfikacja trasy przewiduje **422** dla błędów walidacji (bardziej precyzyjne niż ogólne **400**).
> fileciteturn0file1

## 5. Przepływ danych

1. **HTTP Controller**: parse JSON → map to `GenerateRequestDto` → walidacja (Symfony Validator).
2. **Security**: uzyskaj `userId` z sesji bezpieczeństwa.
3. **DB (RLS)**: przed zapisem ustaw `SET app.current_user_id = :userId` (Doctrine listener). fileciteturn0file3
4. **Persist `ai_jobs`**: w transakcji `INSERT` z `status="queued"`, `request_prompt=source_text`, `user_id`.
5. **Dispatch** `EnqueueGenerateCommand` na `messenger.bus.default` (transport async).
6. **Kick worker (opcjonalnie dev/sync)**: środowisko lokalne może używać transportu `sync` dla natychmiastowego
   podglądu; prod → osobny proces workera.
7. **Response**: zwróć `202` + `{job_id,status}` natychmiast.
8. **Worker** (poza zakresem endpointu, ale istotne dla spójności):
    - Aktualizuje `ai_jobs.status` → `running` → `succeeded|failed`.
    - W sukcesie tworzy `sets` + `cards` (z ograniczeniami długości, soft-delete off), uzupełnia metadane generowania w
      `sets`. fileciteturn0file3
    - Rejestruje `analytics_events` (np. `ai_generation _started/succeeded/failed`). fileciteturn0file2

## 6. Względy bezpieczeństwa

- **Uwierzytelnianie:** endpoint wymaga zalogowanego użytkownika; kontroler zabezpieczony za pomocą `security.yaml` (
  firewall + access_control).
- **Autoryzacja i RLS:** wszystkie operacje DB przechodzą przez RLS (`ai_jobs_by_user`); aplikacja **musi** ustawiać GUC
  `app.current_user_id`. fileciteturn0file3
- **Walidacja danych wejściowych:** limity długości 1000–10000, normalizacja Unicode (NFC), odrzucenie niewidocznych
  znaków kontrolnych. fileciteturn0file1
- **Sekrety i AI:** klucz OpenRouter trzymany w `vault/.env`, worker używa „service account”; logowanie promptów z
  retencją i ewentualną anonimizacją. fileciteturn0file2
- **Brak CSRF dla JSON API:** ale endpoint nie jest dostępny anonimowo.
- **Audyt i monitoring:** Logi aplikacyjne z korelacją `job_id`.

## 7. Obsługa błędów

**Scenariusze i kody:**

- **Walidacja wejścia** → `422` z mapą błędów (Symfony `ConstraintViolationList` → JSON).
- **Brak autoryzacji** → `401`.
- **Błąd zapisu `ai_jobs` (RLS / DB)** → `500` (zachowaj `error_id` do korelacji).
- **Transport Messenger niedostępny** → `500`, ale job może pozostać w stanie „queued”; log + Sentry.
- **Worker failure** (poza requestem) → `ai_jobs.status="failed"`, `error_message` uzupełnione; osobny endpoint/UI
  odczyta status.
- **Payload > limitu** → `413 Payload Too Large`.

**Rejestracja błędów:**

- Aplikacyjnie: `monolog` (channel `api`).
- W DB: `ai_jobs.error_message` + `analytics_events(event_type="ai_generation_failed")`. fileciteturn0file3
  fileciteturn0file2

## 8. Rozważania dotyczące wydajności

- Asynchroniczność: natychmiastowe `202` skraca TTFB; ciężka praca w workerze.
- Indeksy pod listy jobów i monitorowanie: `ai_jobs_user_time`, `ai_jobs_status_time`. fileciteturn0file3
- Ograniczenie rozmiarów `response_raw` (JSON) i retencji — rozważ `GIN` tylko gdy potrzebne. fileciteturn0file3
- Po stronie wyników: szybkie listowanie `sets` dzięki `card_count` i filtrom soft delete. fileciteturn0file3

## 9. Kroki implementacji

1. **Routing**: dodaj trasę `POST /generate` do `config/routes.yaml` (prefiks API, JSON only).
2. **Kontroler**: `GenerateController::enqueue()`
    - Deserializacja JSON → `GenerateRequestDto`.
    - Walidacja (Validator). Błędy → `422`.
    - Pobierz `userId` z `Security`.
3. **RLS Context**: Doctrine DBAL `ConnectionSubscriber` ustawiający `SET app.current_user_id = :userId` na początku
   żądania. fileciteturn0file3
4. **Persist job**: `AiJob` repo → `INSERT` (`status="queued"`, `request_prompt`).
5. **Command + Bus**: utwórz i wyślij `EnqueueGenerateCommand`.
6. **Messenger**: skonfiguruj transport `async` (e.g. Doctrine/Redis/RabbitMQ). Osobny **worker** uruchamiany procesowo.
7. **Odpowiedź**: `JsonResponse(202, {job_id,status})`.
8. **Monitoring**: metryki czasu enqueue, error rate.
9. **Dokumentacja**: opis endpointu w README/API docs i w UI (opis limitów 1000–10000). fileciteturn0file1

## 10. Przykładowe odpowiedzi

**202 Accepted**

```json
{
    "job_id": "2f6f0b3e-7a0a-4c5d-9b3d-1a2b3c4d5e6f",
    "status": "queued"
}
```

**422 Unprocessable Entity**

```json
{
    "errors": {
        "source_text": [
            "This value is too short. Minimum length is 1000 characters."
        ]
    }
}
```

**401 Unauthorized**

```json
{
    "error": "unauthorized"
}
```
