# Plan Testów - Generator Fiszek AI

## 1. Wprowadzenie i Cele Testowania

### 1.1 Cel dokumentu
Niniejszy dokument stanowi kompleksowy plan testów dla aplikacji Generator Fiszek AI - webowej platformy umożliwiającej uczniom automatyczne tworzenie fiszek edukacyjnych z wykorzystaniem sztucznej inteligencji.

### 1.2 Główne cele testowania
- **Weryfikacja zgodności z wymaganiami funkcjonalnymi** określonymi w PRD (Product Requirements Document)
- **Zapewnienie jakości generowania fiszek przez AI** - osiągnięcie 75% wskaźnika akceptacji
- **Walidacja bezpieczeństwa aplikacji** - ochrona danych użytkowników, autentykacja i autoryzacja
- **Potwierdzenie poprawności działania algorytmu spaced repetition**
- **Weryfikacja integracji z zewnętrznymi serwisami** (OpenRouter.ai)
- **Zapewnienie stabilności i wydajności** aplikacji w środowisku produkcyjnym
- **Walidacja poprawności implementacji Row-Level Security (RLS)** w PostgreSQL

### 1.3 Kluczowe metryki sukcesu
1. **Wskaźnik akceptacji fiszek AI**: ≥75% wygenerowanych fiszek zaakceptowanych przez użytkowników
2. **Adopcja funkcji AI**: ≥75% wszystkich fiszek w systemie stworzonych z wykorzystaniem AI
3. **Pokrycie kodu testami**: minimum 80% dla warstwy domenowej i aplikacyjnej
4. **Wskaźnik wykrytych błędów krytycznych**: 0 przed wejściem do produkcji
5. **Czas odpowiedzi API generowania**: <15 sekund dla 95% żądań

## 2. Zakres Testów

### 2.1 Elementy podlegające testowaniu

#### 2.1.1 Warstwa domenowa (Domain Layer)
- **Modele domenowe**: User, Set, Card (z różnymi CardOrigin), ReviewState, AnalyticsEvent, AiJobStatus
- **Value Objects**: Email, UserId, SetName, CardFront, CardBack, SourceText, SuggestedSetName, AiJobId
- **Events**: SetCreatedEvent
- **Exceptions**: AiJobNotFoundException, DuplicateSetNameException
- **Interfaces repozytoriów**: UserRepository, SetRepository, CardRepository, ReviewStateRepository, AnalyticsEventRepository, AiJobRepository

#### 2.1.2 Warstwa aplikacyjna (Application Layer)
- **Commands**: CreateSetCommand, GenerateCardsCommand, GenerateFlashcardsCommand (z DTO)
- **Handlers**: CreateSetHandler, GenerateCardsHandler, GenerateFlashcardsHandler
- **Results**: CreateSetResult, GenerateCardsHandlerResult
- **Event Listeners**: FlashcardGenerationExceptionListener

#### 2.1.3 Warstwa infrastruktury (Infrastructure Layer)
- **Repozytoria Doctrine**: DoctrineUserRepository, DoctrineSetRepository, DoctrineCardRepository, DoctrineReviewStateRepository, DoctrineAnalyticsEventRepository, DoctrineAiJobRepository
- **Integracja OpenRouter**: OpenRouterService, OpenRouterAiCardGenerator, DTO (Flashcard, FlashcardGenerationResult)
- **Exception Handling**: wszystkie wyjątki OpenRouter (Timeout, Authentication, RateLimit, Network, Parse, etc.)
- **Event Subscribers**: SetCreatedEventSubscriber, PostgresRLSSubscriber, SetCurrentUserForRlsSubscriber, UpdateLastLoginSubscriber
- **Security**: UserProvider, password hashing

#### 2.1.4 Warstwa UI (UI/HTTP Layer)
- **Controllers**: GenerateCardsController, CreateSetController, EditNewSetController, GenerateViewController, SecurityController
- **Request DTOs**: GenerateCardsRequest, CreateSetRequest, CreateSetCardRequestDto, GenerateFlashcardsRequest
- **Response DTOs**: GenerateCardsResponse, CreateSetResponse, AiJobResponse, CardPreviewDto
- **Twig Components**: AppScaffold, Button, Card, TextField, Modal, Snackbar, NavDrawer, NavRail, BottomNav, ListItem

#### 2.1.5 Warstwa frontendowa (Frontend/JavaScript)
- **Stimulus Controllers**:
  - `generate_controller.js` - generowanie fiszek, licznik znaków, walidacja
  - `edit_set_controller.js` - edycja wygenerowanych fiszek, usuwanie
  - `form_validation_controller.js` - walidacja formularzy po stronie klienta
  - `csrf_protection_controller.js` - ochrona CSRF
  - `theme_controller.js` - przełączanie motywów
  - `modal_controller.js` - modalne okna dialogowe
  - `snackbar_controller.js` - komunikaty dla użytkownika

#### 2.1.6 Baza danych
- **Migracje Doctrine**: wszystkie wersje w folderze migrations/
- **Row-Level Security (RLS)**: polityki bezpieczeństwa na poziomie wierszy PostgreSQL
- **Relacje między encjami**: User ↔ Set ↔ Card, ReviewState ↔ Card

#### 2.1.7 Infrastruktura i deployment
- **Konteneryzacja Docker**: backend, postgres, nginx
- **Konfiguracja nginx**: routing, proxy_pass, obsługa Asset Mapper
- **Zmienne środowiskowe**: DATABASE_URL, OPENROUTER_API_KEY, APP_SECRET

### 2.2 Elementy wyłączone z testowania w MVP

Zgodnie z granicami produktu określonymi w PRD:
- Import z plików (PDF, DOCX, CSV)
- Funkcje społecznościowe i współdzielenie zestawów
- Aplikacje mobilne natywne (iOS, Android)
- Fiszki multimedialne (obrazy, audio)
- Integracje z platformami edukacyjnymi (Google Classroom, Moodle)
- Własny zaawansowany algorytm spaced repetition (używamy gotowego)

### 2.3 Priorytety testowe

#### Priorytet KRYTYCZNY (P0)
1. **Autentykacja i autoryzacja** - bezpieczeństwo dostępu do danych
2. **Row-Level Security (RLS)** - izolacja danych między użytkownikami
3. **Generowanie fiszek przez AI** - główna funkcjonalność produktu
4. **Zapis i pobieranie zestawów fiszek** - podstawowa funkcjonalność CRUD
5. **Obliczanie metryk sukcesu** - wskaźnik akceptacji i adopcji AI

#### Priorytet WYSOKI (P1)
1. **Edycja wygenerowanych fiszek** - kluczowa dla UX
2. **Walidacja wejścia użytkownika** - 1000-10000 znaków, formaty danych
3. **Obsługa błędów API OpenRouter** - timeouty, limity, błędy parsowania
4. **Algorytm spaced repetition** - poprawność nauki
5. **Analityka** - śledzenie zdarzeń (usunięcia fiszek, źródło tworzenia)

#### Priorytet ŚREDNI (P2)
1. **Interfejs użytkownika** - poprawność renderowania Twig Components
2. **Interaktywność frontend** - Stimulus controllers
3. **Responsywność** - Material Design 3 na różnych urządzeniach
4. **Wydajność** - czas odpowiedzi, optymalizacja zapytań DB
5. **Resetowanie hasła** - funkcja wymagana przez PRD

#### Priorytet NISKI (P3)
1. **Sugestie nazw zestawów** - funkcja pomocnicza
2. **Motywy (dark mode)** - funkcja estetyczna
3. **Polskie znaki diakrytyczne** - poprawność wyświetlania
4. **Logowanie zdarzeń** - Monolog, debugging

## 3. Typy Testów do Przeprowadzenia

### 3.1 Testy Jednostkowe (Unit Tests)

**Cel**: Weryfikacja poprawności działania najmniejszych jednostek kodu w izolacji.

**Zakres**:
- **Value Objects** (src/Domain/Value/):
  - Email: walidacja formatu, obsługa błędnych danych
  - SetName: walidacja długości (3-100 znaków), obsługa znaków specjalnych
  - CardFront/CardBack: walidacja niepustości, obsługa długich tekstów
  - SourceText: walidacja długości (1000-10000 znaków)
  - AiJobId, UserId: walidacja UUID

- **Domain Models** (src/Domain/Model/):
  - User: tworzenie, walidacja email, hashowanie hasła
  - Set: tworzenie, dodawanie kart, walidacja nazwy
  - CardOrigin: enum AI vs MANUAL
  - ReviewState: ease_factor, interval, next_review_date
  - AnalyticsEvent: tworzenie zdarzeń, serializacja danych

- **Application Handlers**:
  - CreateSetHandler: przetwarzanie CreateSetCommand, walidacja kart, obsługa duplikatów nazw
  - GenerateCardsHandler: obsługa GenerateCardsCommand, walidacja source_text
  - GenerateFlashcardsHandler: przetwarzanie długich tekstów

- **Services**:
  - OpenRouterService: mockowanie HTTP Client, parsowanie odpowiedzi, obsługa błędów
  - OpenRouterAiCardGenerator: transformacja odpowiedzi API na domain models

- **Exceptions**:
  - Wszystkie custom exceptions: DuplicateSetNameException, AiJobNotFoundException
  - OpenRouter exceptions: parsowanie kodów błędów HTTP, komunikaty

**Narzędzia**: PHPUnit 12.4, mockery dla dependencies

**Kryteria akceptacji**:
- Pokrycie kodu: minimum 90% dla warstwy domenowej
- Wszystkie edge cases obsłużone (null, empty, extreme values)
- Szybkość wykonania: <100ms dla całego suite testów jednostkowych

### 3.2 Testy Integracyjne (Integration Tests)

**Cel**: Weryfikacja poprawności współpracy między komponentami.

**Zakres**:

#### 3.2.1 Integracja z bazą danych
- **Doctrine Repositories**:
  - DoctrineUserRepository: CRUD operations, findByEmail
  - DoctrineSetRepository: CRUD, findByUser, obsługa relacji User-Set-Card
  - DoctrineCardRepository: CRUD, findBySet, filtering by CardOrigin
  - DoctrineReviewStateRepository: update algorithms, filtering by next_review_date
  - DoctrineAnalyticsEventRepository: saving events, querying for metrics

- **Migracje**:
  - Wykonanie wszystkich migracji od zera
  - Rollback i ponowne wykonanie
  - Walidacja schema: `doctrine:schema:validate`

- **Row-Level Security (RLS)**:
  - PostgresRLSSubscriber: ustawienie current_user_id
  - SetCurrentUserForRlsSubscriber: propagacja ID użytkownika do sesji DB
  - Weryfikacja izolacji danych: user A nie widzi danych user B
  - Testy CRUD z różnymi użytkownikami

#### 3.2.2 Integracja z OpenRouter.ai
- **OpenRouterService**:
  - Rzeczywiste wywołania API (w środowisku testowym z limitem)
  - Mockowanie odpowiedzi API dla różnych scenariuszy
  - Obsługa timeoutów (AiTimeoutException)
  - Obsługa rate limits (OpenRouterRateLimitException)
  - Obsługa błędów autentykacji (OpenRouterAuthenticationException)
  - Parsowanie różnych formatów odpowiedzi

- **OpenRouterAiCardGenerator**:
  - Generowanie fiszek z różnych typów tekstów (notki, artykuły, Q&A)
  - Walidacja formatu wygenerowanych fiszek
  - Obsługa błędów generowania (AiGenerationException)

#### 3.2.3 Event System
- **Event Subscribers**:
  - SetCreatedEventSubscriber: reakcja na SetCreatedEvent
  - UpdateLastLoginSubscriber: aktualizacja last_login_at przy logowaniu
  - Weryfikacja asynchroniczności (jeśli używane Messenger)

**Narzędzia**:
- PHPUnit z Symfony KernelTestCase
- Testowa baza danych PostgreSQL (DATABASE_URL z sufixem _test)
- Fixtures do przygotowania danych testowych

**Kryteria akceptacji**:
- Wszystkie repozytoria działają poprawnie z PostgreSQL 16
- RLS skutecznie izoluje dane użytkowników
- Integracja z OpenRouter obsługuje wszystkie typy błędów
- Pokrycie kodu: minimum 80% dla warstwy infrastruktury

### 3.3 Testy Funkcjonalne (Functional Tests)

**Cel**: Weryfikacja działania aplikacji z perspektywy użytkownika końcowego, testowanie przepływów HTTP.

**Zakres**:

#### 3.3.1 Kontrolery HTTP
- **SecurityController**:
  - GET /login: wyświetlenie formularza logowania
  - POST /login: logowanie z poprawnymi danymi
  - POST /login: logowanie z błędnymi danymi (komunikat błędu)
  - GET /register: wyświetlenie formularza rejestracji
  - POST /register: rejestracja nowego użytkownika
  - POST /register: walidacja (email zajęty, hasła się różnią, słabe hasło)
  - POST /logout: wylogowanie użytkownika

- **GenerateViewController**:
  - GET /generate: wyświetlenie strony generowania (wymaga logowania)
  - Walidacja UI: pole textarea, licznik znaków, przycisk "Generuj"

- **GenerateCardsController**:
  - POST /api/generate: generowanie fiszek z prawidłowym tekstem (1000-10000 znaków)
  - POST /api/generate: walidacja długości tekstu (za krótki, za długi)
  - POST /api/generate: obsługa błędów API (timeout, rate limit)
  - POST /api/generate: zwrócenie job_id i przekierowanie do edycji

- **EditNewSetController**:
  - GET /sets/new/edit: wyświetlenie wygenerowanych fiszek z sesji
  - Walidacja: sprawdzenie, czy pending_set istnieje w sesji
  - UI: lista fiszek, edycja front/back, usuwanie fiszek

- **CreateSetController**:
  - POST /api/sets: zapisanie nowego zestawu
  - Walidacja: nazwa zestawu (3-100 znaków), minimum 1 fiszka
  - Weryfikacja CardOrigin (AI vs MANUAL)
  - Sprawdzenie, czy pending_set został usunięty z sesji

#### 3.3.2 Przepływy użytkownika (User Flows)
- **US-001: Rejestracja**:
  1. GET /register
  2. POST /register z prawidłowymi danymi
  3. Weryfikacja auto-login i przekierowania

- **US-002: Logowanie**:
  1. GET /login
  2. POST /login z prawidłowymi danymi
  3. Weryfikacja przekierowania do /generate lub /sets

- **US-003: Generowanie fiszek z tekstu**:
  1. GET /generate (zalogowany użytkownik)
  2. POST /api/generate z tekstem 5000 znaków
  3. Weryfikacja job_id i pending_set w sesji
  4. GET /sets/new/edit - wyświetlenie wygenerowanych fiszek

- **US-005: Edycja wygenerowanych fiszek**:
  1. Kontynuacja z US-003
  2. Edycja front/back wybranych fiszek
  3. Usunięcie niepotrzebnych fiszek
  4. Weryfikacja śledzenia usunięć (analytics)

- **US-006: Zapisywanie zestawu**:
  1. Kontynuacja z US-005
  2. POST /api/sets z nazwą zestawu i edytowanymi fiszkami
  3. Weryfikacja zapisu w bazie danych
  4. Sprawdzenie CardOrigin i flagi edited dla edytowanych kart

- **US-007: Obsługa błędów generowania**:
  1. POST /api/generate z tekstem powodującym błąd API
  2. Weryfikacja komunikatu błędu
  3. Sprawdzenie, czy użytkownik pozostaje na stronie /generate

- **US-008: Tworzenie pustego zestawu**:
  1. GET /sets/new (jeśli endpoint istnieje)
  2. POST /api/sets z ręcznie dodanymi fiszkami (CardOrigin=MANUAL)

**Narzędzia**:
- PHPUnit + Symfony WebTestCase
- BrowserKit do symulacji żądań HTTP
- CssSelector do weryfikacji HTML response

**Kryteria akceptacji**:
- Wszystkie user stories z PRD przechodzą testy end-to-end
- Walidacja zwraca odpowiednie komunikaty błędów
- Przekierowania działają poprawnie
- Sesje zarządzane prawidłowo (pending_set, user auth)

### 3.4 Testy Frontend (JavaScript/Stimulus)

**Cel**: Weryfikacja poprawności działania interaktywnych komponentów po stronie klienta.

**Zakres**:

#### 3.4.1 Stimulus Controllers
- **generate_controller.js**:
  - Licznik znaków: real-time update, range 1000-10000
  - Wizualna informacja zwrotna: kolor licznika (czerwony/zielony)
  - Enable/disable przycisku "Generuj" na podstawie limitu znaków
  - Animacja ładowania podczas generowania
  - Overlay z etapami: "Analizowanie tekstu..." → "Tworzenie fiszek..."
  - Obsługa błędów: wyświetlenie ErrorModal
  - Fetch POST /api/generate z JSON payload
  - Przekierowanie do /sets/new/edit po sukcesie

- **edit_set_controller.js**:
  - Edycja front/back w textarea (real-time)
  - Usuwanie fiszek: przycisk Delete, re-indexing DOM
  - Walidacja: niepuste karty, minimum 1 fiszka
  - Licznik statystyk: wygenerowane vs. zaoszczędzone
  - Wykrywanie zmian: original vs. modified (flaga edited)
  - Cancel z potwierdzeniem (CancelModal) jeśli są zmiany
  - Overlay ładowania podczas zapisu
  - POST /api/sets z JSON payload

- **form_validation_controller.js**:
  - Walidacja po stronie klienta (długość, format)
  - Komunikaty błędów w czasie rzeczywistym
  - Blokada submit przy błędach walidacji

- **csrf_protection_controller.js**:
  - Automatyczne dodawanie tokena CSRF do żądań AJAX
  - Weryfikacja tokena przed wysłaniem formularza

- **theme_controller.js**:
  - Przełączanie dark/light mode
  - Persistence w localStorage
  - Aktualizacja CSS custom properties

- **modal_controller.js**:
  - Otwieranie/zamykanie modalnych okien
  - Zarządzanie focus trap
  - Escape key handling

- **snackbar_controller.js**:
  - Wyświetlanie komunikatów sukcesu/błędu
  - Auto-dismiss po 5 sekundach
  - Kolejkowanie wielu komunikatów

#### 3.4.2 Testy UI/UX
- **Responsywność**: Material Design 3 na mobile/tablet/desktop
- **Accessibility**: ARIA attributes, keyboard navigation
- **Cross-browser**: Chrome, Firefox, Safari, Edge

**Narzędzia**:
- Jest (JavaScript testing framework)
- Testing Library (@testing-library/dom)
- Playwright/Cypress dla testów E2E frontend
- axe-core dla testów dostępności

**Kryteria akceptacji**:
- Wszystkie Stimulus controllers mają pokrycie testami >80%
- UI działa poprawnie na urządzeniach mobilnych
- Brak błędów w console przeglądarki
- Accessibility score >90 (Lighthouse)

### 3.5 Testy Bezpieczeństwa (Security Tests)

**Cel**: Zapewnienie, że aplikacja jest odporna na powszechne zagrożenia bezpieczeństwa.

**Zakres**:

#### 3.5.1 Testy OWASP Top 10
1. **SQL Injection**:
   - Próby wstrzykiwania SQL w polach formularzy
   - Weryfikacja parametryzowanych zapytań Doctrine ORM
   - Testy na endpointach: /api/generate, /api/sets, /login

2. **XSS (Cross-Site Scripting)**:
   - Wstrzykiwanie skryptów w: source_text, set_name, card_front, card_back
   - Weryfikacja auto-escapingu Twig
   - Testy na rendered HTML

3. **CSRF (Cross-Site Request Forgery)**:
   - Weryfikacja tokenów CSRF na wszystkich POST/PUT/DELETE
   - Testy csrf_protection_controller.js
   - Walidacja Symfony CSRF protection

4. **Authentication & Authorization**:
   - Próba dostępu do chronionych endpoints bez logowania
   - Weryfikacja haszowania haseł (bcrypt/argon2)
   - Testy strength hasła (min. 8 znaków)
   - Weryfikacja session management

5. **Row-Level Security (RLS)**:
   - Próba dostępu User A do danych User B
   - Weryfikacja PostgresRLSSubscriber
   - SQL injection próby ominięcia RLS
   - Direct DB access tests

6. **Sensitive Data Exposure**:
   - Weryfikacja, że hasła nie są logowane
   - OPENROUTER_API_KEY nie jest ujawniony w response/logs
   - Error messages nie ujawniają wrażliwych informacji

7. **Security Misconfiguration**:
   - Weryfikacja APP_ENV=prod w produkcji
   - Debug mode disabled
   - X-Frame-Options, X-Content-Type-Options headers

#### 3.5.2 Testy penetracyjne (Pen Testing)
- **API Endpoints**:
  - Rate limiting na /api/generate (zapobieganie abuse)
  - Input validation bypass attempts
  - JWT token manipulation (jeśli używane)

- **File Upload** (jeśli dodane w przyszłości):
  - Upload złośliwych plików
  - Path traversal attacks

**Narzędzia**:
- OWASP ZAP (Zed Attack Proxy)
- Symfony Security Checker
- PHPStan dla statycznej analizy
- Manual penetration testing

**Kryteria akceptacji**:
- 0 krytycznych luk bezpieczeństwa przed produkcją
- Wszystkie OWASP Top 10 zagrożenia zmitigowane
- Security headers poprawnie skonfigurowane
- RLS skutecznie izoluje dane użytkowników

### 3.6 Testy Wydajnościowe (Performance Tests)

**Cel**: Weryfikacja, że aplikacja spełnia wymagania wydajnościowe.

**Zakres**:

#### 3.6.1 Backend Performance
- **Database Queries**:
  - N+1 query problem (Doctrine eager/lazy loading)
  - Indexy na: user_id, set_id, CardOrigin, next_review_date
  - Query time <100ms dla 95% zapytań

- **API Response Time**:
  - GET /generate: <500ms
  - POST /api/generate: <15s (95 percentile) - obejmuje wywołanie AI
  - POST /api/sets: <1s
  - GET /sets/new/edit: <500ms

- **OpenRouter Integration**:
  - Timeout ustawiony na 30s
  - Retry logic dla transient errors
  - Monitoring czasu odpowiedzi API

#### 3.6.2 Frontend Performance
- **Page Load Time**:
  - First Contentful Paint (FCP): <2s
  - Largest Contentful Paint (LCP): <2.5s
  - Time to Interactive (TTI): <3.5s

- **JavaScript Bundle Size**:
  - Stimulus controllers: <50KB gzipped
  - Total JS: <200KB gzipped

- **Asset Optimization**:
  - Tailwind CSS: purge unused classes
  - Images: lazy loading, compression

#### 3.6.3 Load Testing
- **Concurrent Users**:
  - 10 concurrent users generating flashcards
  - 50 concurrent users browsing sets
  - 100 concurrent users learning

- **Database Load**:
  - 1000 sets, 10000 cards in DB
  - Query performance degradation test

**Narzędzia**:
- Apache JMeter / Locust dla load testing
- Symfony Profiler dla profiling zapytań
- Lighthouse / WebPageTest dla frontend performance
- PostgreSQL EXPLAIN ANALYZE dla query optimization

**Kryteria akceptacji**:
- Lighthouse Performance score >90
- Brak N+1 queries w kluczowych endpointach
- API response time SLA: 95% <15s dla generowania
- Database query time <100ms (95 percentile)

### 3.7 Testy Regresji (Regression Tests)

**Cel**: Weryfikacja, że nowe zmiany nie zepsuły istniejącej funkcjonalności.

**Zakres**:
- Automatyczne uruchomienie pełnego suite testów po każdym merge do main
- Testy smoke dla krytycznych user flows po deploy do staging
- Visual regression testing dla UI components (Twig Components)

**Narzędzia**:
- GitHub Actions CI/CD pipeline
- Percy/Chromatic dla visual regression
- Automated test suite: unit + integration + functional

**Kryteria akceptacji**:
- CI pipeline: 100% testów przechodzi przed merge
- Visual regression: 0 unexpected changes
- Smoke tests: wszystkie krytyczne flows działają po deploy

### 3.8 Testy Akceptacyjne Użytkownika (UAT)

**Cel**: Weryfikacja, że aplikacja spełnia oczekiwania użytkowników końcowych.

**Zakres**:
- **Grupa testowa**: 10-20 uczniów szkół podstawowych i średnich
- **Scenariusze testowe**:
  1. Rejestracja i pierwsze logowanie
  2. Generowanie zestawu fiszek z własnych notatek (1500-8000 znaków)
  3. Edycja i usuwanie wygenerowanych fiszek
  4. Zapisanie zestawu z własną nazwą
  5. Rozpoczęcie sesji nauki ze spaced repetition
  6. Ocena fiszek: "Wiem" / "Nie wiem"
  7. Zakończenie sesji i przegląd statystyk

- **Zbierane metryki**:
  - Wskaźnik akceptacji fiszek AI (cel: ≥75%)
  - Czas na stworzenie zestawu (vs. manualne tworzenie)
  - System Usability Scale (SUS): cel >70
  - Net Promoter Score (NPS): cel >50

**Narzędzia**:
- Ankiety online (Google Forms / Typeform)
- Session recordings (Hotjar / FullStory)
- Analytics (Plausible / Google Analytics)

**Kryteria akceptacji**:
- ≥75% testerów akceptuje AI-generated flashcards
- SUS score >70 (above average)
- Brak critical blockers zgłoszonych przez testerów
- ≥80% testerów pomyślnie ukończyło wszystkie scenariusze

## 4. Scenariusze Testowe dla Kluczowych Funkcjonalności

### 4.1 Moduł Autentykacji

#### TC-AUTH-001: Rejestracja nowego użytkownika
**Priorytet**: P0 (Krytyczny)
**User Story**: US-001

**Kroki testowe**:
1. Przejdź do GET /register
2. Wprowadź:
   - Email: `test@example.com`
   - Hasło: `SecurePass123!`
   - Potwierdzenie hasła: `SecurePass123!`
3. Kliknij "Zarejestruj się"

**Oczekiwany rezultat**:
- Status 302 (redirect)
- Użytkownik automatycznie zalogowany
- Przekierowanie do /generate lub /sets
- W bazie danych: nowy rekord w tabeli users
- Hasło zahashowane (bcrypt/argon2)

**Warunki brzegowe**:
- Email już istnieje → komunikat błędu
- Hasła się różnią → komunikat błędu
- Słabe hasło (<8 znaków) → komunikat błędu
- Nieprawidłowy format email → komunikat błędu

---

#### TC-AUTH-002: Logowanie użytkownika
**Priorytet**: P0 (Krytyczny)
**User Story**: US-002

**Kroki testowe**:
1. Przejdź do GET /login
2. Wprowadź:
   - Email: `test@example.com`
   - Hasło: `SecurePass123!`
3. Kliknij "Zaloguj się"

**Oczekiwany rezultat**:
- Status 302 (redirect)
- Sesja użytkownika utworzona
- Przekierowanie do /generate
- UpdateLastLoginSubscriber: aktualizacja last_login_at

**Warunki brzegowe**:
- Błędne hasło → komunikat "Nieprawidłowy login lub hasło"
- Nieistniejący email → komunikat "Nieprawidłowy login lub hasło"
- Próba dostępu do /generate bez logowania → redirect do /login

---

#### TC-AUTH-003: Row-Level Security (RLS)
**Priorytet**: P0 (Krytyczny)

**Kroki testowe**:
1. Zaloguj się jako User A
2. Stwórz zestaw "Set A"
3. Wyloguj się
4. Zaloguj się jako User B
5. Wykonaj zapytanie: SELECT * FROM sets

**Oczekiwany rezultat**:
- User B widzi tylko własne zestawy
- "Set A" nie jest widoczny dla User B
- PostgresRLSSubscriber ustawił current_user_id = User B
- Próba direct SQL access do Set A → brak wyników

**Warunki brzegowe**:
- Direct DB access (psql) → RLS nadal aktywne
- SQL injection próba ominięcia RLS → blocked
- Doctrine query User B → tylko własne dane

---

### 4.2 Moduł Generowania Fiszek AI

#### TC-GEN-001: Generowanie fiszek z prawidłowego tekstu
**Priorytet**: P0 (Krytyczny)
**User Story**: US-003

**Warunki wstępne**: Użytkownik zalogowany

**Kroki testowe**:
1. Przejdź do GET /generate
2. Wklej tekst o długości 5000 znaków (przykład: fragment podręcznika)
3. Zweryfikuj licznik znaków: pokazuje "5000 / 10000"
4. Zweryfikuj przycisk "Generuj": aktywny (zielony)
5. Kliknij "Generuj fiszki"

**Oczekiwany rezultat**:
- Overlay ładowania z komunikatem "Analizowanie tekstu..."
- POST /api/generate z JSON: `{source_text: "..."}`
- OpenRouterService wywołany z tekstem
- Response 200: `{job_id: "uuid", status: "completed"}`
- pending_set zapisany w sesji:
  ```json
  {
    "job_id": "uuid",
    "suggested_name": "Nazwa zestawu",
    "cards": [
      {"front": "Pytanie 1", "back": "Odpowiedź 1"},
      {"front": "Pytanie 2", "back": "Odpowiedź 2"}
    ],
    "source_text": "..."
  }
  ```
- Redirect 302 → /sets/new/edit

**Warunki brzegowe**:
- Tekst 1000 znaków (minimum) → sukces
- Tekst 10000 znaków (maksimum) → sukces
- Tekst 999 znaków → przycisk disabled
- Tekst 10001 znaków → przycisk disabled
- Puste pole → przycisk disabled

---

#### TC-GEN-002: Walidacja długości tekstu źródłowego
**Priorytet**: P0 (Krytyczny)
**User Story**: US-003

**Kroki testowe**:
1. Przejdź do GET /generate
2. Wklej tekst o długości 500 znaków
3. Obserwuj licznik znaków i przycisk "Generuj"

**Oczekiwany rezultat**:
- Licznik pokazuje: "500 / 10000" (czerwony kolor)
- Komunikat: "Minimum 1000 znaków wymagane"
- Przycisk "Generuj" disabled (szary)

**Test 2**:
1. Wklej tekst o długości 12000 znaków

**Oczekiwany rezultat**:
- Licznik pokazuje: "12000 / 10000" (czerwony kolor)
- Komunikat: "Maksimum 10000 znaków"
- Przycisk "Generuj" disabled

---

#### TC-GEN-003: Obsługa błędów API OpenRouter
**Priorytet**: P1 (Wysoki)
**User Story**: US-007

**Warunki wstępne**:
- Mock OpenRouterService zwraca timeout

**Kroki testowe**:
1. Przejdź do GET /generate
2. Wklej prawidłowy tekst (5000 znaków)
3. Kliknij "Generuj fiszki"
4. OpenRouterService rzuca AiTimeoutException

**Oczekiwany rezultat**:
- Overlay ładowania znika
- ErrorModal wyświetlony z komunikatem:
  "Nie udało się wygenerować fiszek. Spróbuj ponownie lub zmień tekst źródłowy."
- Sugestie: "Sprawdź połączenie internetowe" / "Spróbuj krótszego tekstu"
- Użytkownik pozostaje na /generate
- Tekst w polu textarea zachowany

**Inne przypadki błędów**:
- OpenRouterRateLimitException → "Za dużo żądań. Spróbuj za chwilę."
- OpenRouterAuthenticationException → "Błąd konfiguracji. Skontaktuj się z administratorem."
- OpenRouterParseException → "Nie udało się przetworzyć odpowiedzi AI."

---

#### TC-GEN-004: Jakość wygenerowanych fiszek
**Priorytet**: P0 (Krytyczny)
**Metryka**: Wskaźnik akceptacji ≥75%

**Warunki wstępne**: Integracja z rzeczywistym OpenRouter API

**Kroki testowe**:
1. Przygotuj 20 różnych tekstów źródłowych:
   - 5x notatki z matematyki (500-2000 znaków)
   - 5x notatki z historii (1000-3000 znaków)
   - 5x notatki z biologii (1500-4000 znaków)
   - 5x notatki z geografii (1000-2500 znaków)
2. Dla każdego tekstu wygeneruj fiszki
3. Analiza jakości:
   - Czy pytanie i odpowiedź są sensowne?
   - Czy język jest prosty i zrozumiały dla ucznia?
   - Czy fiszki odpowiadają treści źródłowej?
   - Czy nie ma duplikatów?

**Oczekiwany rezultat**:
- ≥75% wygenerowanych fiszek akceptowanych (nie usuniętych)
- Średnio 5-15 fiszek z tekstu 2000 znaków
- Brak fiszek z pustym front lub back
- Brak fiszek identycznych (duplikatów)

**Kryteria odrzucenia fiszki**:
- Nonsensowne pytanie/odpowiedź
- Zbyt długie (>500 znaków na stronę)
- Zbyt krótkie (<3 znaki)
- Duplikat

---

### 4.3 Moduł Edycji i Zapisywania Zestawu

#### TC-EDIT-001: Przeglądanie wygenerowanych fiszek
**Priorytet**: P1 (Wysoki)
**User Story**: US-005

**Warunki wstępne**:
- pending_set w sesji z 10 fiszkami

**Kroki testowe**:
1. Przejdź do GET /sets/new/edit
2. Obserwuj wyświetloną listę fiszek

**Oczekiwany rezultat**:
- Status 200
- Lista 10 fiszek wyświetlona
- Każda fiszka: front (textarea), back (textarea), przycisk Delete
- Pole "Nazwa zestawu" z sugestią (suggested_name)
- Stats bar: "Wygenerowano: 10 fiszek"
- Przycisk "Zapisz zestaw" (aktywny)
- Przycisk "Anuluj"

---

#### TC-EDIT-002: Edycja fiszki
**Priorytet**: P1 (Wysoki)
**User Story**: US-005

**Kroki testowe**:
1. Kontynuacja TC-EDIT-001
2. Kliknij w textarea "front" pierwszej fiszki
3. Zmień tekst: "Nowe pytanie"
4. Kliknij w textarea "back"
5. Zmień tekst: "Nowa odpowiedź"

**Oczekiwany rezultat**:
- Textarea edytowalne
- Zmiany zapisane w local state (Stimulus controller)
- Flaga edited=true dla tej fiszki
- Stats bar: "Wygenerowano: 10 | Edytowano: 1"

---

#### TC-EDIT-003: Usuwanie fiszki
**Priorytet**: P1 (Wysoki)
**User Story**: US-005

**Kroki testowe**:
1. Kontynuacja TC-EDIT-001
2. Kliknij przycisk "Usuń" przy 3 fiszkach
3. Potwierdź usunięcie w modal (jeśli jest)

**Oczekiwany rezultat**:
- Fiszki usunięte z DOM
- Re-indexing pozostałych fiszek (0-6)
- Stats bar: "Wygenerowano: 10 | Pozostało: 7"
- Analytics event: fiszka_usunięta_w_edycji (3 zdarzenia)

---

#### TC-EDIT-004: Zapisywanie zestawu
**Priorytet**: P0 (Krytyczny)
**User Story**: US-006

**Warunki wstępne**:
- pending_set z 10 fiszkami
- 2 fiszki edytowane
- 3 fiszki usunięte
- Pozostało 7 fiszek (2 edited, 5 original)

**Kroki testowe**:
1. Kontynuacja TC-EDIT-003
2. Wprowadź nazwę: "Mój zestaw testowy"
3. Kliknij "Zapisz zestaw"

**Oczekiwany rezultat**:
- Overlay ładowania
- POST /api/sets z JSON:
  ```json
  {
    "name": "Mój zestaw testowy",
    "cards": [
      {"front": "...", "back": "...", "origin": "ai", "edited": true},
      {"front": "...", "back": "...", "origin": "ai", "edited": true},
      {"front": "...", "back": "...", "origin": "ai", "edited": false},
      ...
    ]
  }
  ```
- Response 200: `{set_id: "uuid"}`
- W bazie danych:
  - Nowy rekord w sets: name="Mój zestaw testowy", user_id=current_user
  - 7 rekordów w cards: set_id=nowy_set_id
  - 2 karty z origin=AI, edited=true
  - 5 kart z origin=AI, edited=false
- SetCreatedEvent emitted
- AnalyticsEvent: set_created, flashcards_accepted (7), flashcards_deleted (3)
- pending_set usunięty z sesji
- Redirect 302 → /generate (lub /sets jeśli istnieje)

**Warunki brzegowe**:
- Pusta nazwa → walidacja error
- Nazwa <3 znaki → error
- Nazwa >100 znaków → error
- Duplikat nazwy → DuplicateSetNameException → error message
- Wszystkie fiszki usunięte → "Musisz mieć przynajmniej 1 fiszkę"
- Pusta fiszka (empty front/back) → walidacja error

---

### 4.4 Moduł Analityki

#### TC-ANALYTICS-001: Obliczanie wskaźnika akceptacji
**Priorytet**: P0 (Krytyczny)
**Metryka**: Acceptance rate

**Warunki wstępne**:
- 100 wygenerowanych fiszek
- 20 usuniętych podczas edycji
- 80 zapisanych (60 original + 20 edited)

**Kroki testowe**:
1. Query AnalyticsEventRepository:
   ```php
   $generated = $repo->countByEventType('flashcards_generated');
   $deleted = $repo->countByEventType('fiszka_usunięta_w_edycji');
   ```
2. Oblicz wskaźnik: `1 - (deleted / generated)`

**Oczekiwany rezultat**:
- generated = 100
- deleted = 20
- acceptance_rate = 1 - (20/100) = 0.80 = 80%
- Wskaźnik ≥75% ✅ (cel osiągnięty)

---

#### TC-ANALYTICS-002: Obliczanie adopcji AI
**Priorytet**: P0 (Krytyczny)
**Metryka**: AI adoption rate

**Warunki wstępne**:
- 100 fiszek w systemie
- 80 z origin=AI (wygenerowane)
- 20 z origin=MANUAL (ręcznie stworzone)

**Kroki testowe**:
1. Query CardRepository:
   ```php
   $total = $repo->count([]);
   $aiGenerated = $repo->count(['origin' => CardOrigin::AI]);
   ```
2. Oblicz wskaźnik: `ai_generated / total`

**Oczekiwany rezultat**:
- total = 100
- ai_generated = 80
- adoption_rate = 80/100 = 0.80 = 80%
- Wskaźnik ≥75% ✅ (cel osiągnięty)

---

### 4.5 Moduł Spaced Repetition

#### TC-REVIEW-001: Rozpoczęcie sesji nauki
**Priorytet**: P1 (Wysoki)
**User Story**: US-010, US-011

**Warunki wstępne**:
- Użytkownik ma zestaw z 10 fiszkami
- ReviewState dla każdej fiszki:
  - next_review_date: dzisiaj (5 fiszek) + przyszłość (5 fiszek)

**Kroki testowe**:
1. GET /sets/{set_id}/learn
2. Sprawdź query: pobierz fiszki gdzie next_review_date <= today

**Oczekiwany rezultat**:
- Status 200
- Wyświetlona 1. fiszka (tylko front)
- Przycisk "Pokaż odpowiedź"
- Counter: "1 / 5" (5 fiszek do powtórki dzisiaj)

---

#### TC-REVIEW-002: Ocena fiszki "Wiem"
**Priorytet**: P1 (Wysoki)
**User Story**: US-011

**Kroki testowe**:
1. Kontynuacja TC-REVIEW-001
2. Kliknij "Pokaż odpowiedź"
3. Wyświetlony back fiszki
4. Kliknij "Wiem"

**Oczekiwany rezultat**:
- POST /api/review: `{card_id: "uuid", rating: "know"}`
- ReviewState updated:
  - interval zwiększony (np. 1 → 4 dni)
  - ease_factor zwiększony
  - next_review_date = today + interval
- ReviewEvent saved: rating=KNOW, review_date=today
- Przejście do następnej fiszki

---

#### TC-REVIEW-003: Ocena fiszki "Nie wiem"
**Priorytet**: P1 (Wysoki)
**User Story**: US-011

**Kroki testowe**:
1. Kontynuacja TC-REVIEW-001
2. Kliknij "Pokaż odpowiedź"
3. Kliknij "Nie wiem"

**Oczekiwany rezultat**:
- POST /api/review: `{card_id: "uuid", rating: "dont_know"}`
- ReviewState updated:
  - interval reset do 1 dnia
  - ease_factor zmniejszony
  - next_review_date = today + 1
- ReviewEvent saved: rating=DONT_KNOW
- Fiszka pokazana ponownie w tej sesji (optional, depends on algorithm)

---

#### TC-REVIEW-004: Podsumowanie sesji nauki
**Priorytet**: P2 (Średni)
**User Story**: US-012

**Warunki wstępne**:
- Sesja ukończona: 5 fiszek ocenionych
- 3x "Wiem", 2x "Nie wiem"

**Kroki testowe**:
1. Po ostatniej fiszce: GET /sets/{set_id}/learn/summary

**Oczekiwany rezultat**:
- Status 200
- Wyświetlone statystyki:
  - "Poprawnych: 3 / 5 (60%)"
  - "Do powtórki: 2"
  - "Następna sesja: {date}"
- Przycisk "Powrót do zestawów"

---

## 5. Środowisko Testowe

### 5.1 Środowiska

#### 5.1.1 Lokalne (Development)
- **Backend**: Docker container `flashcards-backend` (PHP 8.2, Symfony 7.3)
- **Database**: Docker container `postgres` (PostgreSQL 16)
- **Webserver**: Docker container `nginx` (port 8000)
- **Frontend**: Stimulus controllers, Asset Mapper
- **AI**: Mock OpenRouterService (dev mode) lub rzeczywisty API (test key)

**Konfiguracja**:
```bash
APP_ENV=dev
DATABASE_URL=postgresql://user:pass@postgres:5432/flashcards_dev
OPENROUTER_API_KEY=<test-key>
```

#### 5.1.2 CI/CD (Continuous Integration)
- **Platform**: GitHub Actions
- **Triggers**: Push to main, Pull Requests
- **Services**: PostgreSQL 16 (service container)
- **PHP**: 8.2
- **Composer cache**: enabled

**Pipeline stages**:
1. Composer install
2. Database setup (migrations)
3. PHPUnit tests (unit + integration + functional)
4. PHPStan static analysis (level 8)
5. Code coverage report

#### 5.1.3 Staging
- **Hosting**: DigitalOcean Droplet (podobny do produkcji)
- **Database**: PostgreSQL 16 (managed database)
- **Domain**: staging.flashcards-app.com
- **AI**: OpenRouter API (staging key, quota limits)
- **Data**: Seed data dla UAT

**Konfiguracja**:
```bash
APP_ENV=prod
APP_DEBUG=0
DATABASE_URL=postgresql://...:5432/flashcards_staging
```

#### 5.1.4 Produkcja
- **Hosting**: DigitalOcean Droplet
- **Database**: PostgreSQL 16 (managed, backups enabled)
- **Domain**: flashcards-app.com
- **AI**: OpenRouter API (production key)
- **Monitoring**: Uptime monitoring, error tracking

### 5.2 Dane Testowe

#### 5.2.1 Fixtures dla testów automatycznych
**Users**:
```php
// tests/Fixtures/UserFixtures.php
User::create('test@example.com', 'password123');
User::create('user2@example.com', 'password456');
```

**Sets & Cards**:
```php
// Set wygenerowany AI: 10 fiszek, 2 edited
Set::create('Matematyka - Geometria', user: $user1, cards: [
    Card::ai('Pytanie 1', 'Odpowiedź 1', edited: false),
    Card::ai('Pytanie 2', 'Odpowiedź 2', edited: true),
    ...
]);

// Set manualny: 5 fiszek
Set::create('Historia - Starożytność', user: $user1, cards: [
    Card::manual('Kim był Juliusz Cezar?', 'Dyktator rzymski'),
    ...
]);
```

**AnalyticsEvents**:
```php
AnalyticsEvent::create('flashcards_generated', ['count' => 100]);
AnalyticsEvent::create('fiszka_usunięta_w_edycji', ['count' => 20]);
```

#### 5.2.2 Seed data dla UAT (staging)
- 5 przykładowych użytkowników
- 20 zestawów fiszek (mix AI + manual)
- 200 fiszek z różnych dziedzin
- ReviewStates z różnymi next_review_date

**Generowanie seed data**:
```bash
php bin/console doctrine:fixtures:load --env=staging --append
```

### 5.3 Dostęp do środowisk

| Środowisko | URL | Database | Dostęp |
|------------|-----|----------|--------|
| Lokalne | http://localhost:8000 | localhost:5432 | Wszyscy deweloperzy |
| CI/CD | GitHub Actions | Ephemeral container | Automatyczny |
| Staging | https://staging.flashcards-app.com | DigitalOcean Managed DB | QA Team + Developers |
| Produkcja | https://flashcards-app.com | DigitalOcean Managed DB | Admin only |

## 6. Narzędzia do Testowania

### 6.1 Backend Testing

#### 6.1.1 PHPUnit 12.4
**Zastosowanie**: Unit, Integration, Functional tests

**Konfiguracja**: `phpunit.dist.xml`
- Strict mode: fail on deprecations, notices, warnings
- Testsuite: `tests/` directory
- Coverage: `--coverage-html var/coverage`

**Przykład użycia**:
```bash
# Wszystkie testy
php vendor/bin/phpunit

# Konkretny test
php vendor/bin/phpunit tests/Unit/Domain/Value/EmailTest.php

# Z coverage
php vendor/bin/phpunit --coverage-html var/coverage
```

#### 6.1.2 Symfony Test Framework
**Komponenty**:
- `KernelTestCase`: dla testów integracyjnych (services, repositories)
- `WebTestCase`: dla testów funkcjonalnych (controllers, HTTP)
- `BrowserKit`: symulacja HTTP requests
- `DomCrawler`: parsowanie HTML responses

**Przykład**:
```php
class GenerateCardsControllerTest extends WebTestCase
{
    public function testGenerateFlashcards(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/generate', [
            'source_text' => str_repeat('test ', 250) // 1250 znaków
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('job_id', $data);
    }
}
```

#### 6.1.3 PHPStan
**Zastosowanie**: Static analysis, type checking

**Konfiguracja**: Level 8 (strictest)
```neon
# phpstan.neon
parameters:
    level: 8
    paths:
        - src
    excludePaths:
        - src/Kernel.php
```

**Uruchomienie**:
```bash
vendor/bin/phpstan analyse
```

#### 6.1.4 Doctrine Fixtures
**Zastosowanie**: Przygotowanie danych testowych

**Instalacja**:
```bash
composer require --dev doctrine/doctrine-fixtures-bundle
```

**Przykład**:
```php
class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword($this->hasher->hash('password'));
        $manager->persist($user);
        $manager->flush();
    }
}
```

### 6.2 Frontend Testing

#### 6.2.1 Jest
**Zastosowanie**: Unit tests dla JavaScript (Stimulus controllers)

**Instalacja**:
```bash
npm install --save-dev jest @testing-library/dom
```

**Konfiguracja**: `jest.config.js`
```javascript
module.exports = {
    testEnvironment: 'jsdom',
    testMatch: ['**/tests/javascript/**/*.test.js'],
    coverageDirectory: 'var/coverage-js'
};
```

**Przykład testu**:
```javascript
// tests/javascript/generate_controller.test.js
import { Application } from '@hotwired/stimulus';
import GenerateController from '../../assets/controllers/generate_controller.js';

describe('GenerateController', () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <div data-controller="generate">
                <textarea data-generate-target="sourceText"></textarea>
                <span data-generate-target="charCount"></span>
                <button data-generate-target="submitBtn"></button>
            </div>
        `;

        application = Application.start();
        application.register('generate', GenerateController);
    });

    test('updates character count on input', () => {
        const textarea = document.querySelector('[data-generate-target="sourceText"]');
        const charCount = document.querySelector('[data-generate-target="charCount"]');

        textarea.value = 'A'.repeat(1500);
        textarea.dispatchEvent(new Event('input'));

        expect(charCount.textContent).toBe('1500 / 10000');
    });

    test('enables submit button when valid length', () => {
        const textarea = document.querySelector('[data-generate-target="sourceText"]');
        const submitBtn = document.querySelector('[data-generate-target="submitBtn"]');

        textarea.value = 'A'.repeat(1500);
        textarea.dispatchEvent(new Event('input'));

        expect(submitBtn.disabled).toBe(false);
    });
});
```

#### 6.2.2 Playwright / Cypress
**Zastosowanie**: E2E tests, browser automation

**Wybór**: Playwright (zalecane dla nowych projektów)

**Instalacja**:
```bash
npm install --save-dev @playwright/test
```

**Przykład testu E2E**:
```javascript
// tests/e2e/generate-flashcards.spec.js
import { test, expect } from '@playwright/test';

test('complete flashcard generation flow', async ({ page }) => {
    // Logowanie
    await page.goto('http://localhost:8000/login');
    await page.fill('#email', 'test@example.com');
    await page.fill('#password', 'password123');
    await page.click('button[type="submit"]');

    // Generowanie
    await page.goto('http://localhost:8000/generate');
    const longText = 'Sample text '.repeat(100); // ~1200 znaków
    await page.fill('textarea[name="source_text"]', longText);

    // Sprawdź licznik
    const charCount = await page.textContent('[data-testid="char-count"]');
    expect(charCount).toContain('1200');

    // Kliknij generuj
    await page.click('button[data-action="generate"]');

    // Czekaj na redirect
    await page.waitForURL('**/sets/new/edit');

    // Sprawdź wygenerowane fiszki
    const flashcards = await page.locator('.flashcard-item').count();
    expect(flashcards).toBeGreaterThan(0);
});
```

### 6.3 Database Testing

#### 6.3.1 PostgreSQL Test Database
**Konfiguracja**: Osobna baza `flashcards_test`

**Setup w PHPUnit**:
```php
// tests/bootstrap.php
putenv('DATABASE_URL=postgresql://user:pass@localhost:5432/flashcards_test');
```

**Migracje przed testami**:
```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction
```

#### 6.3.2 Database Transactions w testach
**Pattern**: Rollback po każdym teście

```php
use Doctrine\DBAL\Connection;

class RepositoryTest extends KernelTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = self::getContainer()->get(Connection::class);
        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->connection->rollBack();
        parent::tearDown();
    }
}
```

### 6.4 API Testing

#### 6.4.1 HTTP Client (Manual Testing)
**Narzędzie**: VS Code REST Client extension

**Przykład**: `test-generate-endpoint.http`
```http
### Login
POST http://localhost:8000/login
Content-Type: application/x-www-form-urlencoded

email=test@example.com&password=password123

### Generate Flashcards
POST http://localhost:8000/api/generate
Content-Type: application/json

{
    "source_text": "{{$randomLoremParagraph}}"
}
```

#### 6.4.2 Postman / Insomnia
**Zastosowanie**: Manual API testing, collection sharing

**Collections**:
- Authentication (login, register, logout)
- Flashcard Generation (generate, edit, save)
- Learning (start session, review, summary)

### 6.5 Security Testing

#### 6.5.1 OWASP ZAP
**Zastosowanie**: Automated security scanning

**Uruchomienie**:
```bash
docker run -t owasp/zap2docker-stable zap-baseline.py \
    -t http://localhost:8000 \
    -r zap-report.html
```

**Sprawdza**:
- SQL Injection
- XSS
- CSRF
- Security headers

#### 6.5.2 Symfony Security Checker
**Zastosowanie**: Skanowanie zależności pod kątem znanych luk

**Uruchomienie**:
```bash
symfony security:check
# lub
composer audit
```

### 6.6 Performance Testing

#### 6.6.1 Apache JMeter
**Zastosowanie**: Load testing, stress testing

**Scenariusz testowy**:
1. 10 concurrent users
2. Each user: login → generate → edit → save
3. Ramp-up: 10s
4. Duration: 5 min

**Metryki**:
- Average response time
- 95th percentile
- Throughput (requests/sec)
- Error rate

#### 6.6.2 Symfony Profiler
**Zastosowanie**: Database query profiling, performance bottlenecks

**Dostęp**: `http://localhost:8000/_profiler`

**Analiza**:
- N+1 query detection
- Slow queries (>100ms)
- Memory usage
- Event listeners execution time

#### 6.6.3 Lighthouse
**Zastosowanie**: Frontend performance, accessibility, SEO

**Uruchomienie**:
```bash
lighthouse http://localhost:8000 --output html --output-path ./lighthouse-report.html
```

**Metryki**:
- Performance score >90
- Accessibility score >90
- FCP <2s, LCP <2.5s

### 6.7 CI/CD

#### 6.7.1 GitHub Actions
**Konfiguracja**: `.github/workflows/tests.yml`

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_PASSWORD: postgres
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo_pgsql

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run migrations
        run: php bin/console doctrine:migrations:migrate --no-interaction --env=test

      - name: Run tests
        run: php vendor/bin/phpunit

      - name: PHPStan
        run: vendor/bin/phpstan analyse
```

## 7. Harmonogram Testów

### 7.1 Fazy testowania

#### Faza 1: Development (Ongoing)
**Czas trwania**: Cały czas developmentu

**Aktywności**:
- Unit tests pisane równolegle z kodem (TDD optional)
- Integration tests dla nowych features
- Local testing w środowisku Docker

**Odpowiedzialny**: Developers

**Częstotliwość**: Przy każdym commicie

---

#### Faza 2: Feature Complete (2 tygodnie przed MVP)
**Czas trwania**: 1 tydzień

**Aktywności**:
- Functional tests dla wszystkich user stories
- Security testing (OWASP ZAP, manual pen testing)
- Performance baseline (JMeter, Lighthouse)
- Code coverage analysis (cel: 80%)

**Odpowiedzialny**: QA Engineer + Developers

**Deliverables**:
- Test execution report
- Bug list (critical/high/medium/low)
- Coverage report

---

#### Faza 3: Alpha Testing (1 tydzień przed MVP)
**Czas trwania**: 3 dni

**Aktywności**:
- Internal UAT (team members)
- Full regression testing
- Cross-browser testing (Chrome, Firefox, Safari, Edge)
- Mobile responsiveness testing
- Bug fixing (critical + high priority)

**Odpowiedzialny**: Całość zespołu

**Deliverables**:
- Alpha test report
- Updated bug list
- Go/No-go decision for Beta

---

#### Faza 4: Beta Testing (UAT)
**Czas trwania**: 1 tydzień

**Aktywności**:
- UAT z 10-20 real users (uczniowie)
- Metrics collection:
  - Wskaźnik akceptacji fiszek AI (cel: ≥75%)
  - Wskaźnik adopcji AI (cel: ≥75%)
  - SUS score (cel: >70)
- Session recordings analysis
- Feedback collection (surveys)
- Bug fixing (critical only)

**Odpowiedzialny**: QA Engineer + Product Owner

**Deliverables**:
- UAT report
- Metrics dashboard
- User feedback summary
- Final bug list

---

#### Faza 5: Production Release
**Czas trwania**: 1 dzień

**Aktywności**:
- Smoke testing na produkcji
- Monitoring setup (uptime, errors, performance)
- Rollback plan prepared

**Odpowiedzialny**: DevOps + Developers

**Deliverables**:
- Production deployment checklist
- Smoke test results
- Monitoring dashboard

---

#### Faza 6: Post-Release (Continuous)
**Czas trwania**: Ongoing

**Aktywności**:
- Regression testing przy każdym release
- Performance monitoring
- User feedback monitoring
- A/B testing (optional, dla optymalizacji)

**Odpowiedzialny**: QA Engineer + DevOps

**Częstotliwość**: Co 2 tygodnie (sprint cycle)

### 7.2 Harmonogram szczegółowy (przykład dla 4-tygodniowego sprintu przed MVP)

| Tydzień | Dni | Aktywność | Odpowiedzialny |
|---------|-----|-----------|----------------|
| W-4 | Pn-Pt | Development + Unit tests | Developers |
| W-3 | Pn-Śr | Feature completion + Integration tests | Developers |
| W-3 | Czw-Pt | Functional tests + Security tests | QA + Developers |
| W-2 | Pn-Wt | Performance testing + Bug fixing | QA + Developers |
| W-2 | Śr-Czw | Alpha testing (internal) | Całość zespołu |
| W-2 | Pt | Alpha review + Bug triage | Product Owner + QA |
| W-1 | Pn | Beta setup + User recruitment | Product Owner |
| W-1 | Wt-Czw | Beta UAT (z użytkownikami) | QA + Product Owner |
| W-1 | Pt | Beta review + Critical bug fixing | Developers |
| W0 | Pn | Final regression + Smoke tests | QA |
| W0 | Wt | Production deployment | DevOps |
| W0 | Śr | Post-release monitoring | DevOps + Developers |

### 7.3 Entry/Exit Criteria

#### Entry Criteria dla każdej fazy:

**Faza 2 (Feature Complete)**:
- ✅ Wszystkie user stories z PRD zaimplementowane
- ✅ Unit tests coverage ≥70%
- ✅ Brak critical bugs w development

**Faza 3 (Alpha Testing)**:
- ✅ Wszystkie functional tests passed
- ✅ Security scan completed (0 critical vulnerabilities)
- ✅ Performance baseline established

**Faza 4 (Beta UAT)**:
- ✅ Alpha testing completed
- ✅ Brak critical + high bugs
- ✅ 10-20 beta testers recruited

**Faza 5 (Production Release)**:
- ✅ UAT metrics achieved (75% acceptance rate, 75% AI adoption)
- ✅ SUS score >70
- ✅ Brak critical bugs
- ✅ Deployment checklist completed

#### Exit Criteria dla każdej fazy:

**Faza 2**:
- ✅ Test execution report delivered
- ✅ All tests documented
- ✅ Bug list prioritized

**Faza 3**:
- ✅ Alpha test report approved
- ✅ Go decision for Beta
- ✅ Critical bugs fixed

**Faza 4**:
- ✅ UAT report approved
- ✅ Metrics goals achieved
- ✅ User feedback positive (majority)

**Faza 5**:
- ✅ Production smoke tests passed
- ✅ Monitoring active
- ✅ Rollback plan tested

## 8. Kryteria Akceptacji Testów

### 8.1 Kryteria funkcjonalne

#### 8.1.1 User Stories
- ✅ Wszystkie user stories (US-001 do US-012) z PRD zaimplementowane
- ✅ Wszystkie kryteria akceptacji dla każdej US spełnione
- ✅ Edge cases obsłużone (błędne dane, timeouty, edge values)

#### 8.1.2 Funkcjonalność AI
- ✅ Wskaźnik akceptacji fiszek AI ≥ 75%
- ✅ Wskaźnik adopcji AI ≥ 75%
- ✅ Średni czas generowania < 15s (95 percentile)
- ✅ Obsługa wszystkich typów błędów OpenRouter

#### 8.1.3 Bezpieczeństwo
- ✅ Autentykacja i autoryzacja działają poprawnie
- ✅ RLS skutecznie izoluje dane między użytkownikami
- ✅ Brak luk OWASP Top 10
- ✅ CSRF protection enabled
- ✅ Hasła hashowane (bcrypt/argon2)

#### 8.1.4 Spaced Repetition
- ✅ Algorytm poprawnie oblicza next_review_date
- ✅ ease_factor i interval aktualizowane zgodnie z algorytmem
- ✅ ReviewEvents zapisywane poprawnie
- ✅ Fiszki wyświetlane w poprawnej kolejności

### 8.2 Kryteria niefunkcjonalne

#### 8.2.1 Wydajność
- ✅ Page load time (FCP) < 2s
- ✅ API response time (excluding AI) < 1s (95 percentile)
- ✅ AI generation time < 15s (95 percentile)
- ✅ Database queries < 100ms (95 percentile)
- ✅ Brak N+1 queries w critical paths

#### 8.2.2 Skalowalność
- ✅ System handle 10 concurrent AI generations
- ✅ System handle 50 concurrent browsing users
- ✅ Database performance degradation < 20% przy 10k fiszek

#### 8.2.3 Niezawodność
- ✅ Uptime ≥ 99% (cel dla produkcji)
- ✅ Error rate < 1%
- ✅ Graceful degradation przy błędach AI (fallback do manual)

#### 8.2.4 Użyteczność (UX)
- ✅ SUS score > 70
- ✅ Task completion rate > 90% (UAT)
- ✅ Accessibility score > 90 (Lighthouse)
- ✅ Mobile responsiveness: działa na urządzeniach 320px+ width

#### 8.2.5 Utrzymywalność
- ✅ Code coverage ≥ 80% (domain + application layers)
- ✅ PHPStan level 8: 0 errors
- ✅ Documentation: README, CLAUDE.md, PRD aktualne
- ✅ CI/CD pipeline: testy przechodzą automatycznie

### 8.3 Kryteria akceptacji per typ testu

#### Unit Tests
- ✅ Coverage ≥ 90% dla warstwy domenowej
- ✅ Coverage ≥ 80% dla warstwy aplikacyjnej
- ✅ Wszystkie edge cases pokryte
- ✅ Execution time < 100ms dla całego suite

#### Integration Tests
- ✅ Wszystkie repozytoria przetestowane z PostgreSQL
- ✅ RLS przetestowane (izolacja danych)
- ✅ OpenRouter integration przetestowane (mock + real API)
- ✅ Event system przetestowany

#### Functional Tests
- ✅ Wszystkie endpointy HTTP przetestowane
- ✅ Wszystkie user flows end-to-end przetestowane
- ✅ Walidacja zwraca poprawne komunikaty błędów
- ✅ Sesje zarządzane poprawnie

#### Security Tests
- ✅ OWASP ZAP scan: 0 critical/high vulnerabilities
- ✅ Manual pen testing: brak exploitów
- ✅ Symfony security:check: 0 known vulnerabilities
- ✅ RLS bypass attempts: wszystkie zablokowane

#### Performance Tests
- ✅ Lighthouse Performance score ≥ 90
- ✅ JMeter load test: 10 concurrent users, 0 errors
- ✅ Database queries profiled: brak N+1
- ✅ API SLA: 95% requests < target time

#### UAT
- ✅ ≥ 75% akceptacji fiszek AI (beta users)
- ✅ ≥ 75% adopcji AI (beta users)
- ✅ SUS score > 70
- ✅ Task completion rate > 90%
- ✅ Brak critical blockers zgłoszonych

### 8.4 Definition of Done dla testowania

**Feature jest "Done" gdy**:
1. ✅ Unit tests napisane i przechodzą (coverage ≥80%)
2. ✅ Integration tests napisane i przechodzą
3. ✅ Functional tests napisane i przechodzą
4. ✅ Code review przeprowadzony
5. ✅ PHPStan level 8: 0 errors
6. ✅ Manual testing wykonany przez QA
7. ✅ Documentation zaktualizowana
8. ✅ CI/CD pipeline: green

**Sprint jest "Done" gdy**:
1. ✅ Wszystkie features "Done"
2. ✅ Regression testing przeprowadzony
3. ✅ Security scan wykonany (0 critical/high)
4. ✅ Performance testing wykonane (SLA met)
5. ✅ Demo dla stakeholders przeprowadzone
6. ✅ Deployment do staging successful
7. ✅ Smoke tests na staging passed

**MVP jest "Done" gdy**:
1. ✅ Wszystkie user stories z PRD zaimplementowane
2. ✅ UAT completed (metrics goals achieved)
3. ✅ Security audit passed
4. ✅ Performance benchmarks met
5. ✅ Production deployment successful
6. ✅ Smoke tests na produkcji passed
7. ✅ Monitoring i alerting aktywne
8. ✅ Rollback plan przetestowany

## 9. Role i Odpowiedzialności w Procesie Testowania

### 9.1 Role

#### 9.1.1 QA Engineer (Quality Assurance Engineer)
**Główne odpowiedzialności**:
- Planowanie strategii testów
- Tworzenie test cases i scenariuszy testowych
- Wykonywanie functional, integration, E2E tests
- Security testing (OWASP ZAP, manual pen testing)
- Performance testing (JMeter, Lighthouse)
- UAT coordination (rekrutacja beta testerów, analiza feedbacku)
- Bug triage i prioritization
- Test reporting i metrics tracking

**Deliverables**:
- Test plan (ten dokument)
- Test cases documentation
- Test execution reports
- Bug reports
- UAT report
- Metrics dashboard (acceptance rate, adoption rate, SUS)

**Wymagane umiejętności**:
- Znajomość Symfony + PHPUnit
- Security testing (OWASP Top 10)
- Performance testing tools
- SQL (PostgreSQL)
- Podstawowa znajomość JavaScript (Stimulus)

---

#### 9.1.2 Developers
**Główne odpowiedzialności**:
- Unit tests (TDD optional)
- Integration tests dla własnego kodu
- Code review (także testów)
- Bug fixing
- Test automation (CI/CD setup)
- Współpraca z QA przy reprodukcji bugów

**Deliverables**:
- Unit tests (coverage ≥80%)
- Integration tests
- Fixtures dla testów
- Bug fixes
- CI/CD pipeline configuration

**Wymagane umiejętności**:
- PHPUnit, Symfony Test Framework
- Doctrine, PostgreSQL
- Jest (dla JavaScript)
- Git, GitHub Actions

---

#### 9.1.3 Product Owner
**Główne odpowiedzialności**:
- Definiowanie kryteriów akceptacji dla user stories
- Priorytetyzacja bugów (business impact)
- Uczestnictwo w UAT (obserwacja użytkowników)
- Go/No-go decision dla release
- Stakeholder communication

**Deliverables**:
- PRD z kryteriami akceptacji
- Bug priority decisions
- UAT feedback analysis
- Release approval

---

#### 9.1.4 DevOps Engineer
**Główne odpowiedzialności**:
- Setup środowisk testowych (staging)
- CI/CD pipeline maintenance
- Database migrations w środowiskach testowych
- Performance monitoring setup
- Production deployment
- Rollback procedures

**Deliverables**:
- CI/CD pipeline (GitHub Actions)
- Staging environment
- Monitoring dashboards
- Deployment checklist
- Rollback plan

---

### 9.2 Matryca RACI (Responsible, Accountable, Consulted, Informed)

| Aktywność | QA | Developers | Product Owner | DevOps |
|-----------|-----|-----------|---------------|--------|
| **Planning** |
| Test Plan tworzenie | A/R | C | C | I |
| Test Cases writing | A/R | C | C | I |
| **Execution** |
| Unit Tests | I | A/R | I | I |
| Integration Tests | C | A/R | I | I |
| Functional Tests | A/R | C | I | I |
| Security Tests | A/R | C | I | C |
| Performance Tests | A/R | C | I | C |
| UAT | A/R | I | C | I |
| **Management** |
| Bug Triage | A/R | C | C | I |
| Bug Fixing | C | A/R | I | I |
| Test Reporting | A/R | I | C | I |
| **Infrastructure** |
| CI/CD Setup | C | C | I | A/R |
| Staging Setup | C | I | I | A/R |
| Production Deploy | C | C | C | A/R |
| **Approval** |
| Test Sign-off | R | I | A | I |
| Release Decision | C | C | A | C |

**Legenda**:
- **R** (Responsible): Wykonuje pracę
- **A** (Accountable): Ostateczna odpowiedzialność, approval
- **C** (Consulted): Konsultowany, input potrzebny
- **I** (Informed): Informowany o postępach

### 9.3 Komunikacja i Reporting

#### 9.3.1 Daily Standup (dla zespołu)
**Uczestnicy**: Developers, QA, Product Owner (optional)
**Czas**: 15 min, codziennie
**Agenda**:
- Co zrobiliśmy wczoraj (testing)?
- Co robimy dziś?
- Czy są blockery?

#### 9.3.2 Test Status Meeting (weekly)
**Uczestnicy**: QA, Developers, Product Owner
**Czas**: 30 min, raz w tygodniu
**Agenda**:
- Test execution status (% completed)
- Critical bugs review
- Test coverage status
- Risks and mitigation

**Deliverable**: Test status report (dashboard)

#### 9.3.3 Bug Triage Meeting (bi-weekly lub on-demand)
**Uczestnicy**: QA, Developers, Product Owner
**Czas**: 1 godzina
**Agenda**:
- Review nowych bugów
- Priorytetyzacja (Critical/High/Medium/Low)
- Assignment do developers
- Deadlines dla critical/high bugs

**Deliverable**: Prioritized bug backlog

#### 9.3.4 UAT Review Meeting
**Uczestnicy**: QA, Product Owner, Developers (optional)
**Czas**: 1 godzina, po zakończeniu UAT
**Agenda**:
- UAT metrics review (acceptance rate, adoption rate, SUS)
- User feedback summary
- Critical bugs z UAT
- Go/No-go decision

**Deliverable**: UAT report + Release decision

### 9.4 Eskalacja problemów

#### Severity levels:
1. **Critical (P0)**: Blocker dla release, app crashed, data loss
   - **Eskalacja**: Natychmiast do całego zespołu (Slack alert)
   - **SLA**: Fix w ciągu 24h

2. **High (P1)**: Major feature broken, workaround exists
   - **Eskalacja**: Do Product Owner w ciągu 4h
   - **SLA**: Fix przed release

3. **Medium (P2)**: Minor feature issue, non-critical
   - **Eskalacja**: Standardowy proces (bug backlog)
   - **SLA**: Fix w następnym sprincie

4. **Low (P3)**: Cosmetic, nice-to-have
   - **Eskalacja**: Brak, backlog
   - **SLA**: Best effort

## 10. Procedury Raportowania Błędów

### 10.1 Bug Report Template

#### Obowiązkowe pola:

**1. Tytuł (Title)**
- Format: `[Moduł] Krótki opis problemu`
- Przykład: `[Generowanie] Przycisk "Generuj" nie odblokowuje się przy 1000 znaków`

**2. Severity / Priority**
- **Severity**: Critical / High / Medium / Low
- **Priority**: P0 / P1 / P2 / P3

**3. Środowisko (Environment)**
- Wersja: commit hash lub tag
- Środowisko: Local / Staging / Production
- Browser: Chrome 120 / Firefox 121 / Safari 17
- OS: Ubuntu 22.04 / macOS 14 / Windows 11
- Device: Desktop / Mobile (iPhone 14, Android Galaxy S23)

**4. Kroki reprodukcji (Steps to Reproduce)**
```
1. Przejdź do GET /generate
2. Zaloguj się jako test@example.com
3. Wklej tekst o długości dokładnie 1000 znaków
4. Obserwuj przycisk "Generuj fiszki"
```

**5. Oczekiwany rezultat (Expected Result)**
```
Przycisk "Generuj fiszki" powinien być aktywny (enabled) i zielony,
ponieważ 1000 znaków to minimalny dozwolony limit.
```

**6. Rzeczywisty rezultat (Actual Result)**
```
Przycisk pozostaje nieaktywny (disabled) i szary.
Licznik pokazuje "1000 / 10000" w czerwonym kolorze.
```

**7. Screenshoty / Nagrania (Attachments)**
- Screenshot błędu
- Console log (Developer Tools)
- Network tab (dla błędów API)
- Video recording (dla kompleksowych flow)

**8. Logi (Logs)**
```
// Browser console
Uncaught TypeError: Cannot read property 'value' of null
    at GenerateController.validateLength (generate_controller.js:45)

// Backend log (var/log/dev.log)
[2025-01-07 10:15:32] request.CRITICAL: Uncaught PHP Exception...
```

**9. Dodatkowe informacje (Additional Info)**
- Czy problem występuje zawsze? (Always / Sometimes / Rarely)
- Workaround: Czy istnieje obejście problemu?
- Related bugs: Linki do podobnych bugów
- User impact: Ilu użytkowników dotyczy?

### 10.2 Przykłady Bug Reports

#### Przykład 1: Critical Bug

```markdown
# [Autentykacja] RLS nie izoluje danych między użytkownikami

**Severity**: Critical
**Priority**: P0

**Środowisko**:
- Wersja: commit abc123def
- Środowisko: Staging
- Database: PostgreSQL 16.1

**Kroki reprodukcji**:
1. Zaloguj się jako user_a@example.com
2. Stwórz zestaw "Set A" z 5 fiszkami
3. Zanotuj set_id (np. "uuid-123")
4. Wyloguj się
5. Zaloguj się jako user_b@example.com
6. W przeglądarce: wykonaj GET /api/sets/uuid-123

**Oczekiwany rezultat**:
Response 403 Forbidden lub 404 Not Found.
User B nie powinien mieć dostępu do zestawu User A.

**Rzeczywisty rezultat**:
Response 200 OK z pełnymi danymi zestawu User A, włącznie z fiszkami.
```json
{
  "set_id": "uuid-123",
  "name": "Set A",
  "cards": [...]
}
```

**Root cause**:
PostgresRLSSubscriber nie ustawia `current_user_id` dla zapytań Doctrine.

**User impact**: CRITICAL - wszyscy użytkownicy (data breach risk)

**Workaround**: Brak

**Fix**: Immediate
```

---

#### Przykład 2: High Priority Bug

```markdown
# [Generowanie] Timeout 30s zbyt krótki dla długich tekstów

**Severity**: High
**Priority**: P1

**Środowisko**:
- Wersja: v1.0.0-beta
- Środowisko: Production
- OpenRouter: model deepseek/deepseek-chat-v3

**Kroki reprodukcji**:
1. Przejdź do /generate
2. Wklej tekst o długości 9500 znaków (prawie maksimum)
3. Kliknij "Generuj fiszki"
4. Czekaj...

**Oczekiwany rezultat**:
Po 10-15 sekundach: redirect do /sets/new/edit z wygenerowanymi fiszkami.

**Rzeczywisty rezultat**:
Po 30 sekundach: ErrorModal z komunikatem "Timeout. Spróbuj ponownie."
Logi backendu:
```
[2025-01-07 11:23:45] app.ERROR: OpenRouterTimeoutException:
Request timed out after 30000ms
```

**Analiza**:
- Średni czas generowania dla 9500 znaków: ~35-40s (based on 10 samples)
- Timeout ustawiony na 30s w OpenRouterService:72

**Propozycja fix**:
Zwiększyć timeout do 60s dla długich tekstów (>8000 znaków),
lub implementować async job queue z polling.

**User impact**: High - 20% użytkowników wkleja teksty >8000 znaków

**Workaround**: Podziel tekst na mniejsze fragmenty i generuj osobno.
```

---

#### Przykład 3: Medium Priority Bug

```markdown
# [UI] Licznik znaków nie aktualizuje się przy Ctrl+V

**Severity**: Medium
**Priority**: P2

**Środowisko**:
- Browser: Chrome 120.0.6099.109
- OS: Windows 11
- Wersja: commit def456ghi

**Kroki reprodukcji**:
1. Przejdź do /generate
2. Skopiuj tekst 5000 znaków do schowka
3. W polu textarea: Ctrl+V (wklej)
4. Obserwuj licznik znaków

**Oczekiwany rezultat**:
Licznik natychmiast pokazuje "5000 / 10000" i staje się zielony.

**Rzeczywisty rezultat**:
Licznik pozostaje na "0 / 10000" (czerwony).
Dopiero gdy klikniesz w textarea lub zaczniesz pisać, licznik się aktualizuje.

**Root cause**:
Stimulus controller słuchał tylko na event `input`,
ale `Ctrl+V` nie triggeruje `input` w Chrome na Windows.

**Propozycja fix**:
Dodać listener na event `paste`:
```javascript
connect() {
    this.sourceTextTarget.addEventListener('paste', this.updateCount.bind(this));
}
```

**User impact**: Medium - irytujące, ale nie blokuje funkcjonalności

**Workaround**: Kliknij w textarea po wklejeniu lub dodaj spację.
```

---

### 10.3 Bug Tracking System

#### Opcje narzędzi:

**1. GitHub Issues (zalecane dla małych zespołów)**
- Integracja z kodem (commits, PRs)
- Labels: `bug`, `critical`, `high`, `medium`, `low`, `security`, `performance`
- Milestones: `MVP`, `Sprint 1`, `Sprint 2`
- Assignees: @developer1, @developer2
- Projects: Kanban board (To Do / In Progress / Done)

**Przykład labeling**:
```
bug, critical, P0, security, RLS
bug, high, P1, AI-integration
bug, medium, P2, UI
bug, low, P3, cosmetic
```

**2. Jira (dla większych zespołów)**
- Advanced workflows
- Custom fields (Severity, Browser, OS)
- Time tracking
- Reporting i dashboards

**3. Linear (nowoczesna alternatywa)**
- Szybki UI
- Git integration
- Keyboard shortcuts

### 10.4 Bug Lifecycle

```
New → Assigned → In Progress → Fixed → Testing → Closed
                                ↓
                              Reopened (if not fixed)
```

**Statusy**:
1. **New**: Bug zgłoszony, czeka na triage
2. **Assigned**: Przypisany do developera
3. **In Progress**: Developer pracuje nad fixem
4. **Fixed**: Fix zaimplementowany, czeka na code review
5. **Testing**: QA testuje fix
6. **Closed**: Verified fixed, zamknięty
7. **Reopened**: Fix nie zadziałał, problem wrócił

### 10.5 Bug Metrics & Reporting

#### Kluczowe metryki:

**1. Bug Count by Severity**
```
Critical (P0): 0
High (P1): 3
Medium (P2): 12
Low (P3): 25
Total: 40
```

**2. Bug Resolution Time (SLA)**
```
P0: 24h (actual: 18h avg) ✅
P1: 1 week (actual: 4 days avg) ✅
P2: 1 sprint (actual: 1.5 sprint avg) ⚠️
P3: Best effort
```

**3. Bug Trend**
- Wykres: liczba bugów w czasie (new vs. closed)
- Cel: downward trend (więcej closed niż new)

**4. Bug Density**
- Bugs per 1000 lines of code
- Industry standard: 15-50 bugs/KLOC (development), <1 bug/KLOC (production)

**5. Defect Removal Efficiency (DRE)**
- Formula: `Bugs found before release / Total bugs found`
- Cel: >95% (im więcej bugów znalezionych przed produkcją, tym lepiej)

#### Weekly Bug Report Template:

```markdown
# Bug Report - Week of 2025-01-07

## Summary
- New bugs: 15
- Fixed bugs: 18
- Open bugs: 40 (↓ from 43 last week)

## By Severity
| Severity | New | Fixed | Open |
|----------|-----|-------|------|
| Critical | 0   | 1     | 0    |
| High     | 2   | 3     | 3    |
| Medium   | 6   | 8     | 12   |
| Low      | 7   | 6     | 25   |

## Top 3 Critical Areas
1. **AI Integration**: 5 bugs (timeouts, parsing errors)
2. **Frontend Validation**: 4 bugs (licznik znaków, button states)
3. **Database RLS**: 2 bugs (data isolation issues)

## Action Items
- [ ] Fix remaining 3 High priority bugs before Friday
- [ ] Investigate AI timeout issue (recurring)
- [ ] Add more unit tests for frontend validation

## Notes
- Bug density: 12 bugs/KLOC (acceptable for development phase)
- DRE: 96% (excellent - only 4% escaped to production)
```

---

## 11. Załączniki i Dodatkowe Zasoby

### 11.1 Linki do dokumentacji

1. **Project Documentation**:
   - PRD: `.ai/prd.md`
   - Tech Stack Analysis: `.ai/tech-stack.md`
   - CLAUDE.md: `/CLAUDE.md`

2. **Symfony Testing**:
   - Official docs: https://symfony.com/doc/current/testing.html
   - PHPUnit Bridge: https://symfony.com/doc/current/components/phpunit_bridge.html
   - WebTestCase: https://symfony.com/doc/current/testing.html#functional-tests

3. **Security**:
   - OWASP Top 10: https://owasp.org/www-project-top-ten/
   - Symfony Security: https://symfony.com/doc/current/security.html
   - PostgreSQL RLS: https://www.postgresql.org/docs/16/ddl-rowsecurity.html

4. **Tools**:
   - PHPUnit: https://phpunit.de/documentation.html
   - Jest: https://jestjs.io/docs/getting-started
   - Playwright: https://playwright.dev/docs/intro
   - JMeter: https://jmeter.apache.org/usermanual/index.html

### 11.2 Checklist przed release

```markdown
## Pre-Release Checklist

### Code Quality
- [ ] PHPStan level 8: 0 errors
- [ ] Code coverage ≥80% (domain + application)
- [ ] All unit tests pass
- [ ] All integration tests pass
- [ ] All functional tests pass

### Security
- [ ] OWASP ZAP scan completed (0 critical/high)
- [ ] Manual penetration testing completed
- [ ] RLS tested and verified
- [ ] CSRF protection enabled
- [ ] Security headers configured
- [ ] Composer audit: 0 known vulnerabilities

### Performance
- [ ] Lighthouse score ≥90
- [ ] API SLA met (95% <15s for AI generation)
- [ ] Database queries profiled (no N+1)
- [ ] JMeter load test passed (10 concurrent users)

### UAT
- [ ] UAT completed with ≥10 beta users
- [ ] Acceptance rate ≥75%
- [ ] AI adoption rate ≥75%
- [ ] SUS score >70
- [ ] No critical bugs reported

### Infrastructure
- [ ] Production environment configured
- [ ] Database migrations tested
- [ ] Backups enabled and tested
- [ ] Monitoring setup (uptime, errors, performance)
- [ ] Rollback plan documented and tested

### Documentation
- [ ] README.md updated
- [ ] CLAUDE.md updated
- [ ] API documentation (if applicable)
- [ ] User guide created (optional for MVP)

### Final Checks
- [ ] Smoke tests on production passed
- [ ] All critical bugs fixed
- [ ] Product Owner approval
- [ ] Stakeholder demo completed
- [ ] Go decision confirmed
```

### 11.3 Glossary (Słownik terminów)

**A**
- **Acceptance Rate**: Procent fiszek AI zaakceptowanych przez użytkowników (nie usuniętych podczas edycji)
- **AI Adoption Rate**: Udział fiszek stworzonych przez AI w ogólnej liczbie fiszek w systemie
- **Analytics Event**: Zdarzenie śledzone w systemie (np. `fiszka_usunięta_w_edycji`)

**C**
- **CardOrigin**: Enum określający źródło fiszki (AI vs MANUAL)
- **CSRF**: Cross-Site Request Forgery - atak polegający na wymuszeniu akcji bez wiedzy użytkownika

**D**
- **DRE**: Defect Removal Efficiency - procent bugów znalezionych przed release

**F**
- **Fixture**: Dane testowe przygotowane do testów automatycznych

**M**
- **MVP**: Minimum Viable Product - pierwsza wersja produktu z minimalnym zestawem funkcji

**N**
- **N+1 Query Problem**: Anti-pattern w ORM, gdzie wykonywane jest N+1 zapytań zamiast 1 (z JOIN)

**R**
- **RLS**: Row-Level Security - zabezpieczenie na poziomie wierszy w bazie danych PostgreSQL
- **ReviewState**: Encja przechowująca stan nauki fiszki (ease_factor, interval, next_review_date)

**S**
- **Spaced Repetition**: Metoda nauki oparta na powtarzaniu materiału w rosnących odstępach czasu
- **SUS**: System Usability Scale - standardowy kwestionariusz do oceny użyteczności (0-100)

**U**
- **UAT**: User Acceptance Testing - testy akceptacyjne z prawdziwymi użytkownikami

**Polskie terminy**:
- **Fiszka**: Flashcard - karta do nauki z pytaniem (front) i odpowiedzią (back)
- **Zestaw**: Set - kolekcja fiszek
- **Awers/Rewers**: Front/Back fiszki

---

## Podsumowanie

Niniejszy plan testów obejmuje wszystkie kluczowe aspekty testowania aplikacji Generator Fiszek AI, od testów jednostkowych po UAT. Główne cele to:

1. **Zapewnienie jakości funkcjonalnej**: Wszystkie user stories z PRD zaimplementowane i przetestowane
2. **Osiągnięcie metryk sukcesu**: ≥75% acceptance rate i ≥75% AI adoption
3. **Gwarancja bezpieczeństwa**: RLS, OWASP Top 10, penetration testing
4. **Wydajność i skalowalność**: SLA dla API, performance benchmarks
5. **Użyteczność**: SUS >70, pozytywny feedback użytkowników

Dzięki systematycznemu podejściu do testowania, zdefiniowanym rolom i procedurom raportowania błędów, zespół będzie w stanie dostarczyć wysokiej jakości MVP zgodny z wymaganiami biznesowymi i technicznymi.

**Następne kroki**:
1. Review planu testów przez cały zespół
2. Setup środowisk testowych (local, CI/CD, staging)
3. Rozpoczęcie pisania testów równolegle z developmentem
4. Regularne test status meetings
5. Continuous improvement procesu testowania na podstawie metryk

---

**Wersja dokumentu**: 1.0
**Data utworzenia**: 2025-01-07
**Autorzy**: Claude Code (na podstawie analizy projektu)
**Zatwierdzenie**: [Do uzupełnienia przez Product Owner]
