# Plan Implementacji Usługi OpenRouter (Architektura i Strategia)

## 1. Opis usługi

### Cel i odpowiedzialność

Usługa `OpenRouterService` stanowi warstwę abstrakcji między aplikacją Generator Fiszek AI a API OpenRouter.ai. Głównym
zadaniem jest umożliwienie komunikacji z modelami językowymi (LLM) w celu generowania treści edukacyjnych.

### Główne zadania

1. **Komunikacja z API OpenRouter**
    - Konstruowanie żądań HTTP zgodnych ze specyfikacją OpenRouter
    - Wysyłanie zapytań typu chat completion
    - Odbieranie i parsowanie odpowiedzi

2. **Zarządzanie konfiguracją**
    - Obsługa różnych modeli AI (GPT-4, Claude, Gemini)
    - Konfiguracja parametrów modelu (temperature, max_tokens, etc.)
    - Przechowywanie domyślnych wartości

3. **Formatowanie komunikacji**
    - Konstrukcja komunikatów systemowych (instrukcje dla AI)
    - Przekazywanie komunikatów użytkownika
    - Wymuszanie ustrukturyzowanych odpowiedzi poprzez JSON Schema

4. **Obsługa cyklu życia żądania**
    - Walidacja danych wejściowych
    - Sanityzacja treści użytkownika
    - Parsowanie i walidacja odpowiedzi
    - Kompleksowa obsługa błędów

### Kontekst aplikacji

W projekcie Generator Fiszek AI usługa będzie wykorzystywana do:

- **Generowania fiszek edukacyjnych**: Przekształcenie tekstu źródłowego (1000-10000 znaków) na zestaw par
  pytanie-odpowiedź
- **Sugerowania nazw zestawów**: Automatyczna analiza treści i propozycja nazwy
- **Przyszłe rozszerzenia**: Potencjalne zadania wymagające przetwarzania NLP

## 2. Opis konstruktora

### Wymagane zależności

Usługa powinna otrzymywać następujące zależności przez dependency injection:

1. **HTTP Client** (HttpClientInterface)
    - Umożliwia wykonywanie żądań HTTP
    - Musi być konfigurowalny (timeouty, retry)
    - Mockwalny dla celów testowych

2. **API Key** (string)
    - Klucz autoryzacyjny do OpenRouter
    - Pochodzenie: zmienna środowiskowa
    - Walidowany przy konstrukcji (nie może być pusty)

3. **API URL** (string)
    - Endpoint API OpenRouter
    - Domyślnie: `https://openrouter.ai/api/v1/chat/completions`
    - Konfigurowalny dla różnych środowisk (produkcja, staging, testy)

4. **Logger** (LoggerInterface)
    - Do rejestrowania błędów i diagnostyki
    - Poziomy logowania: error, warning, info
    - Nie powinien logować wrażliwych danych (API key, dane osobowe)

5. **Domyślny model** (string, opcjonalny)
    - Nazwa modelu używana gdy nie podano inaczej
    - Przykłady: `openai/gpt-4-turbo-preview`, `anthropic/claude-3-sonnet`
    - Nadpisywalny przez opcje wywołania

6. **Domyślny timeout** (int, opcjonalny)
    - Maksymalny czas oczekiwania na odpowiedź (w sekundach)
    - Zapobiega zawieszaniu się aplikacji
    - Zalecana wartość: 60-120 sekund

### Walidacja przy konstrukcji

- Sprawdzenie czy API key nie jest pusty
- Opcjonalnie: test połączenia z API (health check)
- Rzucenie wyjątku przy nieprawidłowej konfiguracji

## 3. Publiczne metody i pola

### 3.1 Metoda `chatCompletion()`

**Sygnatura**: Przyjmuje tablicę messages i opcjonalne parametry, zwraca obiekt odpowiedzi

**Parametry wejściowe:**

- **messages**: Tablica wiadomości w formacie OpenRouter
    - Każda wiadomość: role (system/user/assistant) + content
    - Walidacja: niepusta tablica, poprawne role, obecność required fields

- **options**: Tablica opcjonalnych parametrów
    - model: nazwa modelu do użycia
    - temperature: randomness odpowiedzi (0.0-2.0)
    - max_tokens: limit długości odpowiedzi
    - top_p, frequency_penalty, presence_penalty
    - **response_format**: struktura JSON Schema dla ustrukturyzowanych odpowiedzi

**Obsługa response_format:**

- Format: `{ type: 'json_schema', json_schema: { name: string, strict: true, schema: object } }`
- Wymusza zwracanie odpowiedzi zgodnej z podanym schematem
- Walidacja struktury przed wysłaniem
- Przykład zastosowania: generowanie fiszek w określonym formacie JSON

**Zwracana wartość:**

- Obiekt DTO zawierający:
    - Treść odpowiedzi (content)
    - Metadane (model, tokens usage, finish reason)
    - Opcjonalnie: surowa odpowiedź API dla debugowania

**Obsługa błędów:**

- Walidacja przed wysłaniem (InvalidArgumentException)
- Błędy sieci (NetworkException)
- Błędy API (AuthenticationException, RateLimitException, etc.)
- Retry logic dla błędów przejściowych

### 3.2 Metoda `generateFlashcards()`

**Cel**: Wysokopoziomowa metoda do generowania fiszek (wrapper nad chatCompletion)

**Parametry:**

- **sourceText**: Tekst źródłowy (walidacja: 1000-10000 znaków)
- **options**: Opcjonalne nadpisanie parametrów

**Logika:**

1. Walidacja długości tekstu
2. Sanityzacja input użytkownika
3. Konstrukcja predefiniowanego system message (instrukcje dla AI)
4. Konstrukcja response_format z JSON Schema dla fiszek
5. Wywołanie chatCompletion
6. Parsowanie JSON i konwersja na obiekty DTO (Flashcard)
7. Zwrócenie tablicy obiektów Flashcard

**System prompt:**

- Instrukcje dla AI: "twórz fiszki edukacyjne"
- Wymogi: prosty język, 10-20 fiszek, zwięzłe odpowiedzi
- Format: JSON z polami front/back

**Response format:**

- Schema: obiekt z polem "flashcards" (array)
- Każdy element: obiekt z polami "front" i "back" (string)
- Strict mode: true (wymuszenie zgodności)

### 3.3 Metoda `suggestSetName()`

**Cel**: Generowanie sugerowanej nazwy zestawu fiszek

**Parametry:**

- **sourceText**: Tekst źródłowy (może być ograniczony do pierwszych 500 znaków dla optymalizacji)
- **options**: Opcjonalne parametry

**Logika:**

1. Sanityzacja i ograniczenie długości tekstu
2. System prompt: "zaproponuj zwięzłą nazwę (3-8 słów)"
3. Niskie temperature (0.5) dla deterministycznych wyników
4. Limit tokens: 50 (krótka odpowiedź)
5. Zwrócenie stringa (nazwa)

**Optymalizacje:**

- Użycie szybszego/tańszego modelu
- Ograniczenie długości input (excerpt)
- Cache dla identycznych tekstów (opcjonalnie)

### 3.4 Metoda `validateApiConnection()`

**Cel**: Health check - sprawdzenie czy API jest dostępne i klucz jest prawidłowy

**Logika:**

1. Wysłanie minimalnego testowego żądania
2. Limit tokens: 5 (minimalizacja kosztów)
3. Obsługa wyjątków
4. Zwrócenie boolean: true (OK) / false (błąd)

**Zastosowanie:**

- Startup checks
- Monitoring endpoints
- Diagnostyka problemów z konfiguracją

## 4. Prywatne metody i pola

### 4.1 `buildRequestPayload()`

**Odpowiedzialność:**

- Konstrukcja obiektu JSON payload zgodnego z OpenRouter API
- Łączenie domyślnych wartości z przekazanymi opcjami
- Walidacja i normalizacja parametrów

**Kluczowe zadania:**

- Dodanie model name (domyślny lub z opcji)
- Przekazanie messages
- Dodanie opcjonalnych parametrów (temperature, max_tokens, response_format)
- Usunięcie nieobsługiwanych parametrów

### 4.2 `sendRequest()`

**Odpowiedzialność:**

- Wykonanie faktycznego żądania HTTP POST
- Ustawienie nagłówków (Authorization, Content-Type, HTTP-Referer)
- Obsługa timeoutów
- Obsługa błędów transportowych

**Nagłówki wymagane przez OpenRouter:**

- `Authorization: Bearer {api_key}`
- `Content-Type: application/json`
- `HTTP-Referer`: URL aplikacji (dla statystyk OpenRouter)
- `X-Title`: Nazwa aplikacji (opcjonalne)

**Obsługa błędów:**

- Connection refused → NetworkException
- Timeout → TimeoutException
- Transport errors → NetworkException z oryginalnym błędem

### 4.3 `parseResponse()`

**Odpowiedzialność:**

- Parsowanie surowej odpowiedzi HTTP
- Sprawdzenie status code
- Dekodowanie JSON
- Walidacja struktury odpowiedzi
- Ekstrakcja treści z `choices[0].message.content`

**Mapowanie status codes:**

- 200: sukces → parsowanie contentu
- 400: błędne żądanie → InvalidRequestException
- 401: błędny API key → AuthenticationException
- 429: rate limit → RateLimitException (z retry_after)
- 500+: błąd serwera → ServerException

**Walidacja:**

- Sprawdzenie obecności `choices[0].message.content`
- Walidacja typu danych
- Obsługa pustych odpowiedzi

### 4.4 `validateMessages()`

**Odpowiedzialność:**

- Walidacja tablicy messages przed wysłaniem

**Sprawdzenia:**

- Tablica nie jest pusta
- Każdy element ma klucze 'role' i 'content'
- Role są z dozwolonego zbioru: 'system', 'user', 'assistant'
- Content nie jest pusty

**Rzucane wyjątki:**

- InvalidArgumentException z opisem błędu

### 4.5 `validateResponseFormat()`

**Odpowiedzialność:**

- Walidacja struktury response_format przed wysłaniem

**Sprawdzenia:**

- Obecność klucza `type` = 'json_schema'
- Obecność obiektu `json_schema`
- Wymagane pola w json_schema: `name`, `strict`, `schema`
- Schema jest poprawnym obiektem JSON Schema

**Cel:**

- Wczesne wykrycie błędów (fail-fast)
- Jasne komunikaty błędów dla dewelopera

### 4.6 `extractContent()`

**Odpowiedzialność:**

- Ekstrakcja treści z obiektu odpowiedzi
- Obsługa różnych formatów odpowiedzi

**Logika:**

- Pobranie `content` z odpowiedzi
- Obsługa pustych wartości
- Zwrócenie stringa

### 4.7 `handleRateLimit()`

**Odpowiedzialność:**

- Specjalizowana obsługa błędów HTTP 429

**Logika:**

- Odczytanie nagłówka `Retry-After` lub pola w JSON
- Logowanie informacji o rate limit
- Rzucenie RateLimitException z czasem oczekiwania
- Informacja dla użytkownika: "spróbuj za X sekund"

### 4.8 `sanitizeUserInput()`

**Odpowiedzialność:**

- Oczyszczanie tekstu dostarczonego przez użytkownika

**Operacje:**

- Usunięcie znaków kontrolnych (poza whitespace)
- Trim whitespace
- Sprawdzenie maksymalnej długości
- Zapobieganie prompt injection

**Bezpieczeństwo:**

- Nie usuwać znaków Unicode (wielojęzyczność)
- Zachować formatowanie (newlines, tabs)

## 5. Obsługa błędów

### 5.1 Hierarchia wyjątków

**Struktura:**

```
OpenRouterException (bazowy, extends RuntimeException)
├── OpenRouterNetworkException
├── OpenRouterTimeoutException
├── OpenRouterApiException
│   ├── OpenRouterAuthenticationException (401)
│   ├── OpenRouterRateLimitException (429)
│   ├── OpenRouterInvalidRequestException (400)
│   └── OpenRouterServerException (500+)
└── OpenRouterParseException
```

**Zalety hierarchii:**

- Możliwość catch specyficznych błędów
- Możliwość catch ogólnego typu (OpenRouterException)
- Jasna semantyka błędów
- Łatwość w testowaniu

### 5.2 Strategie obsługi błędów

#### 1. Błędy sieciowe (NetworkException, TimeoutException)

**Strategia:** Retry z exponential backoff

- Pierwsza próba: natychmiast
- Druga próba: po 1 sekundzie
- Trzecia próba: po 2 sekundach
- Maksymalnie 3 próby
- Po ostatniej nieudanej próbie: rzuć wyjątek

**Logowanie:** Warning level przy każdym retry

#### 2. Błędy autoryzacji (AuthenticationException)

**Strategia:** Fail immediately (brak retry)

- Logowanie: Error level
- Komunikat: "Nieprawidłowy klucz API, sprawdź konfigurację"
- Akcja: Weryfikacja zmiennej środowiskowej

#### 3. Rate limiting (RateLimitException)

**Strategia:** Zależna od kontekstu

- W tle (batch jobs): czekaj i retry po wskazanym czasie
- Interaktywnie (user request): zwróć komunikat użytkownikowi
- Logowanie: Warning level z informacją o retry_after

**Informacja zwrotna:**

- "Przekroczono limit zapytań. Spróbuj ponownie za X sekund."

#### 4. Błędne żądanie (InvalidRequestException)

**Strategia:** Fail immediately (brak retry)

- Logowanie: Error level z payload (bez API key)
- Analiza błędu: co było nieprawidłowe?
- Akcja deweloperska: poprawa request structure

#### 5. Błędy serwera (ServerException)

**Strategia:** Retry z opóźnieniem

- 1-2 próby z 5-sekundowym opóźnieniem
- Logowanie: Error level
- Komunikat użytkownikowi: "Tymczasowy problem z usługą AI, spróbuj za chwilę"

#### 6. Błędy parsowania (ParseException)

**Strategia:** Fail immediately

- Logowanie: Error level + surowa odpowiedź (first 500 chars)
- Analiza: czy API zmieniło format odpowiedzi?
- Możliwość fallback: próba parsowania w alternatywny sposób

### 5.3 Logowanie

**Zasady:**

- Error level: błędy wymagające interwencji (auth, parse, server errors)
- Warning level: błędy przejściowe (network, rate limit)
- Info level: normalne operacje (successful requests)

**Struktura logu:**

- Exception type
- Message (opisowy)
- HTTP status code (jeśli dotyczy)
- Request metadata (model, message count) - BEZ treści
- Timestamp
- Stack trace (dla errors)

**Bezpieczeństwo logowania:**

- NIE logować API key
- NIE logować pełnej treści user input (RODO)
- NIE logować danych osobowych
- Sanityzacja payload przed logowaniem

### 5.4 Komunikaty użytkownikowi

**Zasady:**

- Przyjazne, nieznające językiem technicznym
- Actionable: co użytkownik może zrobić?
- Konkretne: podaj czas retry przy rate limit

**Przykłady:**

- Network error: "Nie można połączyć się z usługą AI. Sprawdź połączenie internetowe."
- Rate limit: "Przekroczono limit zapytań. Spróbuj ponownie za 30 sekund."
- Server error: "Usługa AI jest tymczasowo niedostępna. Spróbuj za kilka minut."
- Invalid input: "Tekst musi mieć od 1000 do 10000 znaków (podano: 847)."

## 6. Kwestie bezpieczeństwa

### 6.1 Przechowywanie i zarządzanie API Key

**Zasady:**

1. **Nigdy w kodzie źródłowym**
    - API key tylko w zmiennych środowiskowych
    - Nie commitować do repozytorium
    - `.env.local` w .gitignore

2. **Rotacja kluczy**
    - Plan wymiany kluczy w przypadku wycieku
    - Przechowywanie w secret management (produkcja)
    - Różne klucze dla różnych środowisk

3. **Dostęp ograniczony**
    - Tylko usługa OpenRouterService ma dostęp
    - Nie przekazywać klucza do innych komponentów
    - Nie logować w plaintext

4. **Konfiguracja przez DI**
    - Wstrzykiwanie przez Symfony service container
    - Parametry z `%env(...)%`
    - Walidacja przy starcie aplikacji

### 6.2 Walidacja i sanityzacja danych wejściowych

**Cele:**

1. Zapobieganie prompt injection
2. Zapobieganie zbyt długim requestom
3. Ochrona przed złośliwymi danymi

**Techniki:**

**1. Walidacja długości**

- Minimum i maximum dla każdego typu input
- Flashcards: 1000-10000 znaków
- Set name suggestion: 500 znaków (excerpt)
- Limit messages array: max 20 wiadomości

**2. Sanityzacja znaków kontrolnych**

- Usunięcie znaków \x00-\x1F (poza \n, \r, \t)
- Zachowanie znaków Unicode (obsługa języków)
- Normalizacja whitespace

**3. Separacja system/user messages**

- System message: zawsze kontrolowany przez aplikację
- User message: zawsze sanityzowany
- Nigdy nie interpoluj user input w system message

**4. Walidacja przed wysłaniem**

- Sprawdzenie typu danych
- Sprawdzenie required fields
- Fail-fast approach

### 6.3 Ochrona przed Prompt Injection

**Strategia:**

**1. Stała struktura system message**

- Predefiniowane prompty w kodzie
- Brak możliwości nadpisania przez użytkownika
- Jasne oddzielenie instrukcji od danych

**2. Użytkownik = tylko user message**

- Cała treść od użytkownika w osobnej wiadomości
- Role='user' dla wszystkich user inputs
- Brak możliwości wstrzykiwania role='system'

**3. Response format jako guardrail**

- JSON Schema wymusza strukturę odpowiedzi
- AI nie może "złamać formatu" instrukcjami w user input
- Strict mode zapewnia zgodność

**4. Monitoring nietypowych odpowiedzi**

- Logowanie finish_reason != 'stop'
- Alerting przy podejrzanych patterns
- Analiza odrzuconych odpowiedzi

### 6.4 Rate Limiting po stronie aplikacji

**Cel:** Ochrona przed nadużyciami i przypadkowym przekroczeniem limitów OpenRouter

**Strategia:**

**1. Limit per user**

- Max 10 zapytań na minutę na użytkownika
- Identyfikacja: user ID lub session ID
- Storage: Symfony Cache lub Redis

**2. Limit globalny**

- Max 100 zapytań na minutę (cała aplikacja)
- Ochrona przed DDoS
- Storage: in-memory cache

**3. Informacja zwrotna**

- HTTP 429 Too Many Requests
- Retry-After header
- Komunikat: "Zbyt wiele zapytań, spróbuj za X sekund"

**4. Whitelisting**

- Możliwość wyłączenia limitu dla adminów
- Wyższe limity dla premium users
- Konfiguracja w bazie danych

### 6.5 Timeout i resource limits

**Konfiguracja:**

**1. Request timeout**

- Default: 60 sekund
- Max: 120 sekund (dla długich generacji)
- Configurable per request

**2. Connection timeout**

- Max czas nawiązywania połączenia: 10 sekund
- Zapobiega długiemu czekaniu przy problemach sieciowych

**3. Max duration**

- Całkowity czas żądania (z retry): 3 minuty
- Po tym czasie: hard fail

**4. Memory limits**

- Ograniczenie rozmiaru odpowiedzi
- Max response size: 1 MB
- Ochrona przed memory exhaustion

### 6.6 Logowanie i auditing

**Co logować:**

1. Wszystkie wywołania API (metadata)
2. Wszystkie błędy (z kontekstem)
3. Rate limiting events
4. Unusual patterns (długie odpowiedzi, błędne finish_reason)

**Czego NIE logować:**

1. API key (nigdy!)
2. Pełna treść user input (RODO)
3. Dane osobowe
4. Informacje wrażliwe

**Struktura audit log:**

- Timestamp
- User ID (hash lub pseudonim)
- Operation (generateFlashcards, suggestSetName)
- Model used
- Tokens consumed
- Success/Failure
- Duration

**Retencja:**

- Logi aplikacyjne: 30 dni
- Audit logs: 90 dni
- Error logs: 180 dni

### 6.7 HTTPS i szyfrowanie

**Wymagania:**

1. Wszystkie połączenia z OpenRouter przez HTTPS
2. Walidacja certyfikatów SSL
3. Brak fallback na HTTP
4. TLS 1.2 minimum

**Konfiguracja HTTP Client:**

- Weryfikacja peer
- Weryfikacja host
- Timeout SSL handshake

## 7. Plan wdrożenia krok po kroku

### Krok 1: Przygotowanie struktury projektu

**Działania:**

1. Utworzenie katalogów:
    - `src/Service/` - dla głównej usługi
    - `src/DTO/OpenRouter/` - dla obiektów transferu danych
    - `src/Exception/OpenRouter/` - dla wyjątków

2. Utworzenie plików konfiguracyjnych:
    - Dodanie zmiennych środowiskowych do `.env`
    - Utworzenie `.env.local` (lokalnie, nie commitowane)
    - Aktualizacja `.gitignore`

3. Weryfikacja zależności:
    - Symfony HttpClient zainstalowany
    - Logger dostępny
    - PSR interfaces

### Krok 2: Implementacja wyjątków

**Kolejność:**

1. Bazowy wyjątek `OpenRouterException`
2. Wyjątki poziomu transportu (Network, Timeout)
3. Wyjątek bazowy API (OpenRouterApiException z dodatkowym payload)
4. Wyjątki specyficzne dla błędów API (Authentication, RateLimit, InvalidRequest, Server)
5. Wyjątek parsowania (ParseException)

**Testowanie:**

- Każdy wyjątek: unit test sprawdzający message i inheritance
- ApiException: test ekstrakcji HTTP code i API response

### Krok 3: Implementacja DTO

**Kolejność:**

1. `OpenRouterResponse` - główny obiekt odpowiedzi
    - Properties: id, model, content, tokens, etc.
    - Factory method: `fromApiResponse(array $response)`
    - Metoda pomocnicza: `getJsonContent()` dla response_format

2. `Flashcard` - reprezentacja pojedynczej fiszki
    - Properties: front, back
    - Factory method: `fromArray(array $data)`
    - Walidacja w konstruktorze

**Testowanie:**

- Factory methods z różnymi strukturami danych
- Edge cases: brakujące pola, nieprawidłowe typy
- JSON parsing w OpenRouterResponse

### Krok 4: Implementacja interfejsu usługi

**Działania:**

1. Utworzenie `OpenRouterServiceInterface`
2. Zdefiniowanie sygnatur publicznych metod:
    - `chatCompletion()`
    - `generateFlashcards()`
    - `suggestSetName()`
    - `validateApiConnection()`

3. Dodanie dokumentacji PHPDoc:
    - Opis każdej metody
    - @param z typami
    - @return z typami
    - @throws z możliwymi wyjątkami

**Cel:**

- Kontrakt dla implementacji
- Możliwość mockowania w testach
- Dokumentacja API

### Krok 5: Implementacja konstruktora i podstawowych metod prywatnych

**Działania:**

1. Implementacja konstruktora z dependency injection
2. Walidacja API key przy konstrukcji
3. Implementacja metod pomocniczych:
    - `validateMessages()`
    - `validateResponseFormat()`
    - `sanitizeUserInput()`

**Testowanie:**

- Konstruktor z różnymi konfiguracjami
- Walidacja: pozytywne i negatywne przypadki
- Sanityzacja: edge cases (znaki kontrolne, długość)

### Krok 6: Implementacja warstwy HTTP

**Działania:**

1. Implementacja `buildRequestPayload()`
    - Łączenie defaults z options
    - Walidacja parametrów

2. Implementacja `sendRequest()`
    - Ustawienie nagłówków
    - Obsługa timeoutów
    - Error handling dla błędów transportowych

3. Implementacja `parseResponse()`
    - Parsowanie JSON
    - Mapowanie status codes
    - Tworzenie DTO

**Testowanie:**

- Mock HTTP Client z różnymi odpowiedziami
- Test każdego status code (200, 400, 401, 429, 500)
- Test timeout handling

### Krok 7: Implementacja retry logic

**Działania:**

1. Dodanie stałych: MAX_RETRIES, RETRY_DELAY
2. Implementacja pętli retry w `chatCompletion()`
3. Exponential backoff dla kolejnych prób
4. Logowanie każdej próby

**Strategia:**

- Retry tylko dla Network i Timeout errors
- Brak retry dla API errors (poza Server 500+)
- Maksymalnie 3 próby

**Testowanie:**

- Mock Client zwracający błąd przy pierwszych próbach
- Weryfikacja liczby prób
- Weryfikacja opóźnień

### Krok 8: Implementacja obsługi rate limiting

**Działania:**

1. Implementacja `handleRateLimit()`
    - Ekstrakcja retry_after
    - Logowanie
    - Rzucenie RateLimitException

2. Opcjonalnie: lokalny rate limiter
    - Symfony Cache
    - Counter per user
    - TTL 60 sekund

**Testowanie:**

- Mock odpowiedzi 429 z retry_after
- Test lokalnego limitera (jeśli zaimplementowany)

### Krok 9: Implementacja publicznych metod wysokopoziomowych

**Działania:**

1. Implementacja `chatCompletion()` jako głównej metody
    - Walidacja
    - Payload building
    - Send + parse
    - Retry logic

2. Implementacja `generateFlashcards()`
    - Walidacja długości tekstu
    - Konstrukcja system prompt
    - Konstrukcja JSON Schema
    - Wywołanie chatCompletion
    - Parsowanie i mapowanie na Flashcard DTO

3. Implementacja `suggestSetName()`
    - Excerpt tekstu (500 chars)
    - System prompt
    - Low temperature
    - Limit tokens

4. Implementacja `validateApiConnection()`
    - Minimal test request
    - Catch exceptions
    - Return boolean

**Testowanie:**

- Integration tests z mock HTTP Client
- Test każdej metody z różnymi inputs
- Test error scenarios

### Krok 10: Konfiguracja Symfony

**Działania:**

1. Aktualizacja `config/services.yaml`:
    - Definicja usługi z argumentami
    - Binding zmiennych środowiskowych
    - Alias interfejsu do implementacji

2. Aktualizacja `.env`:
    - Domyślne wartości (URL, model)
    - Placeholder dla API key

3. Utworzenie `.env.local` lokalnie:
    - Prawdziwy API key
    - Weryfikacja ignorowania przez git

**Testowanie:**

- Uruchomienie aplikacji
- Debug:container - sprawdzenie czy usługa jest zarejestrowana
- Test dependency injection w kontrolerze

### Krok 11: Implementacja testów jednostkowych

**Zakres:**

1. Testy wyjątków
2. Testy DTO (factory methods, getters)
3. Testy metod walidacji
4. Testy sanityzacji
5. Testy payload building
6. Testy parsowania odpowiedzi

**Mockowanie:**

- Mock HttpClient dla różnych scenariuszy
- Mock Logger do weryfikacji logowania

**Coverage:**

- Minimum 80% code coverage
- 100% dla krytycznych paths (error handling)

### Krok 12: Implementacja testów integracyjnych

**Zakres:**

1. Test pełnego flow z mock API responses
2. Test retry logic
3. Test rate limiting
4. Test timeout handling

**Uwaga:**

- Brak testów przeciwko prawdziwemu API (koszty, flakiness)
- Używać mock HTTP Client
- Opcjonalnie: manualne testy z prawdziwym API (dokumentacja)

### Krok 13: Przykładowe użycie w kontrolerze

**Działania:**

1. Utworzenie kontrolera `FlashcardGeneratorController`
2. Dependency injection OpenRouterServiceInterface
3. Endpoint POST `/api/generate`:
    - Przyjęcie source_text
    - Wywołanie generateFlashcards()
    - Wywołanie suggestSetName()
    - Zwrócenie JSON response

4. Obsługa błędów:
    - InvalidArgumentException → 400
    - RateLimitException → 429
    - Inne OpenRouterException → 500

**Testowanie:**

- Functional test z WebTestCase
- Mock usługi w teście
- Test wszystkich error cases

### Krok 14: Health check endpoint (opcjonalny)

**Działania:**

1. Endpoint GET `/health/openrouter`
2. Wywołanie `validateApiConnection()`
3. Zwrócenie JSON: `{ service: 'OpenRouter', status: 'healthy'/'unhealthy' }`
4. HTTP 200 (healthy) lub 503 (unhealthy)

**Zastosowanie:**

- Kubernetes liveness/readiness probes
- Monitoring (Prometheus, Datadog)
- Diagnostyka

### Krok 15: Dokumentacja i przewodniki

**Działania:**

1. Utworzenie `docs/openrouter-usage.md`:
    - Podstawowe użycie
    - Przykłady kodu
    - Obsługa błędów
    - Konfiguracja

2. Aktualizacja README.md:
    - Instrukcje ustawienia API key
    - Link do dokumentacji

3. Komentarze w kodzie:
    - PHPDoc dla wszystkich publicznych metod
    - Komentarze dla złożonej logiki

**Checklisty:**

- [ ] Jak skonfigurować API key
- [ ] Jak używać generateFlashcards()
- [ ] Jak obsłużyć błędy
- [ ] Jak testować lokalnie
- [ ] Jak monitorować usage

### Krok 16: Logging i monitoring

**Działania:**

1. Konfiguracja Monolog (jeśli jeszcze nie skonfigurowany)
2. Dodanie dedykowanego kanału dla OpenRouter
3. Implementacja logowania w usłudze:
    - Info: successful requests
    - Warning: retries, rate limits
    - Error: failures

4. Opcjonalnie: metryki:
    - Counter: liczba zapytań
    - Histogram: czas odpowiedzi
    - Counter: błędy (per typ)

**Narzędzia:**

- Monolog dla logów
- Symfony Metrics dla metryk (jeśli dostępne)
- Integracja z zewnętrznymi systemami (Sentry, Datadog)

### Krok 17: Code review i refactoring

**Checklist:**

- [ ] Kod zgodny z PSR-12
- [ ] Brak duplikacji
- [ ] Wszystkie edge cases obsłużone
- [ ] Error messages są jasne
- [ ] Logowanie bez wrażliwych danych
- [ ] Testy pokrywają krytyczne ścieżki
- [ ] Dokumentacja aktualna

**Narzędzia:**

- PHPStan (level 8)
- PHP CS Fixer
- Code review w PR

### Krok 18: Deploy na staging i testy manualne

**Działania:**

1. Deploy aplikacji na środowisko staging
2. Konfiguracja prawdziwego API key
3. Testy manualne:
    - Generowanie fiszek z różnymi tekstami
    - Test error cases (nieprawidłowy key, rate limit)
    - Test timeout (długie generacje)

4. Monitoring:
    - Sprawdzenie logów
    - Weryfikacja metryk
    - Test health check endpoint

**Weryfikacja:**

- [ ] API key działa
- [ ] Odpowiedzi są poprawne
- [ ] Błędy są odpowiednio obsługiwane
- [ ] Logi nie zawierają wrażliwych danych
- [ ] Performance jest akceptowalne

### Krok 19: Optymalizacje (opcjonalne)

**Możliwe usprawnienia:**

1. **Caching:**
    - Cache dla identycznych tekstów
    - TTL: 1 godzina
    - Klucz: hash tekstu źródłowego

2. **Batch processing:**
    - Kolejka dla nieinteraktywnych generacji
    - Background jobs

3. **Model selection:**
    - Tańszy model dla suggest name
    - Szybszy model dla prostych zadań
    - Premium model dla płacących użytkowników

4. **Streaming:**
    - Server-Sent Events dla real-time generation
    - Lepsze UX (pokazywanie progress)

5. **A/B testing:**
    - Różne prompty
    - Różne modele
    - Analiza jakości

---

## Podsumowanie kluczowych decyzji architektonicznych

### 1. Dependency Injection

- Usługa otrzymuje wszystkie zależności przez konstruktor
- Łatwe mockowanie w testach
- Zgodne z SOLID principles

### 2. Interface-based design

- OpenRouterServiceInterface definiuje kontrakt
- Możliwość łatwej zamiany implementacji
- Mockowanie w testach

### 3. DTO pattern

- Typowane obiekty zamiast tablic asocjacyjnych
- Walidacja przy konstrukcji
- Immutability (readonly properties w PHP 8.2)

### 4. Hierarchia wyjątków

- Możliwość catch specyficznych błędów
- Jasna semantyka
- Dodatkowe informacje w wyjątkach API

### 5. Retry logic z exponential backoff

- Odporność na błędy przejściowe
- Ograniczona liczba prób (max 3)
- Tylko dla błędów sieciowych

### 6. Separacja concerns

- HTTP layer (buildRequest, sendRequest, parseResponse)
- Business logic (generateFlashcards, suggestSetName)
- Validation (validateMessages, validateResponseFormat)

### 7. Security by design

- API key tylko z env variables
- Sanityzacja wszystkich user inputs
- Separacja system/user messages (prompt injection prevention)
- Rate limiting
- Secure logging (bez wrażliwych danych)

### 8. Observability

- Comprehensive logging
- Error tracking
- Health check endpoint
- Metrics (opcjonalnie)

---

## Następne kroki po implementacji

1. **Monitoring produkcyjny:**
    - Alerting przy wysokim error rate
    - Dashboard z metrics (requests, errors, latency)
    - Budżet na API costs

2. **Optymalizacja kosztów:**
    - Analiza usage per model
    - Eksperymentowanie z tańszymi modelami
    - Caching dla identycznych requestów

3. **Rozszerzenia funkcjonalności:**
    - Batch generation (wiele zestawów)
    - Multi-language support
    - Custom prompts per użytkownik
    - Fine-tuning (jeśli OpenRouter obsługuje)

4. **Iteracja na podstawie feedbacku:**
    - Analiza jakości generowanych fiszek
    - User acceptance rate
    - Poprawa system prompts
    - A/B testing różnych strategii

---

Ten plan zapewnia kompleksową, bezpieczną i skalowałną integrację z OpenRouter API, gotową do produkcyjnego użycia w
aplikacji Generator Fiszek AI.
