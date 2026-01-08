# Plan Testów - AI Flashcard Generator (Hybrydowy)

## 1. Wprowadzenie i Cele Testowania

### 1.1 Cel dokumentu

Kompleksowy plan testów dla aplikacji AI Flashcard Generator - webowej platformy umożliwiającej automatyczne tworzenie
fiszek edukacyjnych z wykorzystaniem AI.

### 1.2 Główne cele testowania

- **Weryfikacja zgodności z wymaganiami funkcjonalnymi** określonymi w PRD
- **Zapewnienie jakości generowania fiszek przez AI** - osiągnięcie 75% wskaźnika akceptacji
- **Walidacja bezpieczeństwa aplikacji** - szczególnie Row-Level Security (RLS) w PostgreSQL
- **Potwierdzenie poprawności działania algorytmu spaced repetition**
- **Weryfikacja integracji z OpenRouter.ai**
- **Zapewnienie stabilności i wydajności** aplikacji

### 1.3 Kluczowe metryki sukcesu

1. **Wskaźnik akceptacji fiszek AI**: ≥75% wygenerowanych fiszek zaakceptowanych przez użytkowników
2. **Adopcja funkcji AI**: ≥75% wszystkich fiszek w systemie stworzonych z wykorzystaniem AI
3. **Pokrycie kodu testami**: minimum 80% dla warstwy domenowej i aplikacyjnej
4. **Wskaźnik wykrytych błędów krytycznych**: 0 przed wejściem do produkcji
5. **Czas odpowiedzi API generowania**: <15 sekund dla 95% żądań

---

## 2. Analiza Projektu

### 2.1 Kluczowe komponenty projektu

* **Warstwa Domenowa (Domain):** Encje (User, Set, Card, AiJob, ReviewEvent), Value Objects (UserId, Email, SourceText z
  walidacją 1000-10000 znaków), Interfejsy Repozytoriów
* **Warstwa Aplikacji (Application):** CQRS (Command/Query), Handlery (CreateSetHandler, GenerateCardsHandler), Events (
  SetCreatedEvent)
* **Warstwa Infrastruktury (Infrastructure):** Repozytoria Doctrine, OpenRouter (AI), EventSubscriber ustawiający RLS
* **Warstwa Prezentacji (UI/Frontend):** Twig + Symfony UX (Turbo & Stimulus), kontrolery Stimulus (
  generate_controller.js, edit_set_controller.js)
* **Baza Danych (PostgreSQL):** **Row Level Security (RLS)**, ENUM types, triggery (automatyczna aktualizacja
  card_count)

### 2.2 Specyfika stosu technologicznego a testowanie

* **Symfony 7.3 & PHP 8.2:** Wymaga silnych testów jednostkowych (PHPUnit 12.4)
* **PostgreSQL RLS:** **KRYTYCZNE** - testowanie scenariuszy wielodostępowych, weryfikacja czy `PostgresRLSSubscriber`
  poprawnie narzuca kontekst użytkownika
* **Symfony UX (Stimulus/Turbo):** Testy E2E (Panther/Playwright) dla weryfikacji interakcji JS z backendem
* **OpenRouter AI:** Mockowanie odpowiedzi (pozytywne, błędy 4xx/5xx, timeouty)

### 2.3 Priorytety testowe

#### Priorytet KRYTYCZNY (P0)

1. **Bezpieczeństwo danych (RLS)** - izolacja danych między użytkownikami
2. **Autentykacja i autoryzacja** - bezpieczeństwo dostępu
3. **Generowanie fiszek przez AI** - główna funkcjonalność produktu
4. **Zapis i pobieranie zestawów** - podstawowa funkcjonalność CRUD
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

#### Priorytet NISKI (P3)

1. **Sugestie nazw zestawów** - funkcja pomocnicza
2. **Motywy (dark mode)** - funkcja estetyczna

### 2.4 Obszary ryzyka

* **Postgres RLS:** Skomplikowana logika - jeśli sesja DB nie zostanie poprawnie zainicjowana, zapytania mogą zwrócić
  puste wyniki lub błędy
* **Asynchroniczność/Timeouty AI:** GenerateCardsHandler obsługuje timeouty (30s)
* **Spójność danych (Triggery):** Licznik card_count zarządzany przez trigger w DB
* **Zgodność front-back:** Walidacja JS musi być zsynchronizowana z walidacją w Entity/Value Objects

---

## 3. Zakres Testów

### 3.1 W Zakresie (In-Scope)

* **Uwierzytelnianie i Autoryzacja:** Rejestracja, logowanie, reset hasła, weryfikacja izolacji danych (RLS)
* **Moduł Generowania AI:** Przetwarzanie tekstu, komunikacja z OpenRouter (mockowana), obsługa błędów i timeoutów,
  podgląd i edycja przed zapisem
* **Zarządzanie Zestawami (CRUD):** Tworzenie ręczne, edycja, usuwanie (Soft Delete), mechanizmy zliczania fiszek (DB
  Triggers)
* **Logika Domenowa:** Value Objects, walidacja danych wejściowych, algorytm powtórek (ReviewState)
* **Frontend (UX):** Działanie kontrolerów Stimulus (walidacja formularzy, paski postępu, modale), responsywność
* **API:** Endpointy wykorzystywane przez frontend (np. /api/generate, /api/sets)

### 3.2 Poza Zakresem (Out-of-Scope dla MVP)

* Testy obciążeniowe (Load Testing) dla dużej skali
* Testy natywnych aplikacji mobilnych
* Testy integracji z systemami LMS (Moodle itp.)
* Weryfikacja jakości merytorycznej treści generowanych przez rzeczywiste modele AI

---

## 4. Strategia i Typy Testów

### 4.1 Testy Jednostkowe (Unit Tests) - Backend

**Cel:** Weryfikacja logiki w izolacji

**Pokrycie:**

* **Value Objects:** SourceText (limity 1000-10000 znaków), Email, CardFront/Back
* **Entities:** Metody fabrykujące (create), logika zmiany stanu (ReviewState::updateAfterReview)
* **Services:** CreateSetHandler, GenerateCardsHandler (logika biznesowa bez zapisu do DB)

**Narzędzia:** PHPUnit 12.4, mockery dla dependencies

**Kryteria akceptacji:**

- Pokrycie kodu: minimum 90% dla warstwy domenowej
- Wszystkie edge cases obsłużone (null, empty, extreme values)
- Szybkość wykonania: <100ms dla całego suite

### 4.2 Testy Integracyjne (Integration Tests) - Backend & DB

**Cel:** Weryfikacja współpracy między komponentami

**Kluczowe obszary:**

#### PostgreSQL RLS

- Weryfikacja czy PostgresRLSSubscriber poprawnie ustawia kontekst sesji
- Próby dostępu do danych innego użytkownika muszą kończyć się niepowodzeniem na poziomie zapytania SQL
- Testy CRUD z różnymi użytkownikami

#### Triggery DB

- Sprawdzenie czy dodanie/usunięcie (soft delete) fiszki aktualizuje card_count w tabeli sets

#### AI Service

- Testy integracyjne z wykorzystaniem MockAiCardGenerator
- OpenRouterAiCardGenerator z nagranymi odpowiedziami (VCR)
- Obsługa timeoutów, rate limits, błędów autentykacji

**Narzędzia:**

- PHPUnit z Symfony KernelTestCase
- Testowa baza danych PostgreSQL (DATABASE_URL z sufixem _test)
- Fixtures do przygotowania danych testowych

**Kryteria akceptacji:**

- Wszystkie repozytoria działają poprawnie z PostgreSQL 16
- RLS skutecznie izoluje dane użytkowników
- Integracja z OpenRouter obsługuje wszystkie typy błędów
- Pokrycie kodu: minimum 80% dla warstwy infrastruktury

### 4.3 Testy Funkcjonalne (Functional Tests)

**Cel:** Weryfikacja działania aplikacji z perspektywy użytkownika końcowego, testowanie przepływów HTTP

**Zakres:**

- Wszystkie kontrolery HTTP (SecurityController, GenerateViewController, GenerateCardsController, etc.)
- Przepływy użytkownika end-to-end (rejestracja, logowanie, generowanie, edycja, zapis)

**Narzędzia:**

- PHPUnit + Symfony WebTestCase
- BrowserKit do symulacji żądań HTTP
- CssSelector do weryfikacji HTML response

**Kryteria akceptacji:**

- Wszystkie user stories z PRD przechodzą testy end-to-end
- Walidacja zwraca odpowiednie komunikaty błędów
- Przekierowania działają poprawnie

### 4.4 Testy Frontend (JavaScript/Stimulus)

**Cel:** Weryfikacja poprawności działania interaktywnych komponentów po stronie klienta

**Zakres:**

- **generate_controller.js:** Licznik znaków, enable/disable przycisku, animacja ładowania, obsługa błędów
- **edit_set_controller.js:** Edycja front/back, usuwanie fiszek, walidacja, wykrywanie zmian
- **form_validation_controller.js:** Walidacja po stronie klienta
- **theme_controller.js:** Przełączanie dark/light mode
- **modal_controller.js, snackbar_controller.js:** Komponenty UI

**Narzędzia:**

- Jest (JavaScript testing framework)
- Testing Library (@testing-library/dom)
- Playwright dla testów E2E frontend

**Kryteria akceptacji:**

- Wszystkie Stimulus controllers mają pokrycie testami >80%
- UI działa poprawnie na urządzeniach mobilnych
- Brak błędów w console przeglądarki

### 4.5 Testy Bezpieczeństwa (Security Tests)

**Cel:** Zapewnienie odporności na powszechne zagrożenia bezpieczeństwa

**Zakres:**

1. **SQL Injection:** Próby wstrzykiwania SQL w polach formularzy
2. **XSS (Cross-Site Scripting):** Wstrzykiwanie skryptów w: source_text, set_name, card_front, card_back
3. **CSRF:** Weryfikacja tokenów CSRF na wszystkich POST/PUT/DELETE
4. **Authentication & Authorization:** Próba dostępu do chronionych endpoints bez logowania
5. **Row-Level Security (RLS):** Próba dostępu User A do danych User B, SQL injection próby ominięcia RLS
6. **Sensitive Data Exposure:** Hasła nie są logowane, OPENROUTER_API_KEY nie jest ujawniony

**Narzędzia:**

- OWASP ZAP (Zed Attack Proxy)
- Symfony Security Checker
- PHPStan dla statycznej analizy

**Kryteria akceptacji:**

- 0 krytycznych luk bezpieczeństwa przed produkcją
- Wszystkie OWASP Top 10 zagrożenia zmitigowane
- RLS skutecznie izoluje dane użytkowników

### 4.6 Testy Wydajnościowe (Performance Tests)

**Cel:** Weryfikacja spełnienia wymagań wydajnościowych

**Zakres:**

- **Database Queries:** N+1 query problem, indexy, query time <100ms dla 95% zapytań
- **API Response Time:**
    - GET /generate: <500ms
    - POST /api/generate: <15s (95 percentile) - obejmuje wywołanie AI
    - POST /api/sets: <1s
- **Frontend Performance:**
    - First Contentful Paint (FCP): <2s
    - Largest Contentful Paint (LCP): <2.5s
    - Time to Interactive (TTI): <3.5s

**Narzędzia:**

- Symfony Profiler dla profiling zapytań
- Lighthouse / WebPageTest dla frontend performance
- PostgreSQL EXPLAIN ANALYZE dla query optimization

**Kryteria akceptacji:**

- Lighthouse Performance score >90
- Brak N+1 queries w kluczowych endpointach
- API response time SLA: 95% <15s dla generowania

---

## 5. Scenariusze Testowe dla Kluczowych Funkcjonalności

### 5.1 Moduł AI i Generowania

| ID    | Scenariusz                               | Oczekiwany Rezultat                                                                                       | Typ Testu             |
|-------|------------------------------------------|-----------------------------------------------------------------------------------------------------------|-----------------------|
| AI-01 | Walidacja wejścia (tekst < 1000 znaków)  | Blokada przycisku "Generuj", komunikat błędu (Stimulus). Błąd 422 przy próbie obejścia (API).             | Unit/E2E              |
| AI-02 | Walidacja wejścia (tekst > 10000 znaków) | Blokada przycisku, licznik na czerwono.                                                                   | E2E                   |
| AI-03 | Poprawne wygenerowanie fiszek            | Otrzymanie JSON z listą fiszek, przekierowanie do edycji, utworzenie AiJob (status SUCCEEDED).            | Integration (Mock AI) |
| AI-04 | Timeout zewnętrznego API (>30s)          | Przechwycenie AiTimeoutException, wyświetlenie modalu z błędem i sugestiami, zapis AiJob (status FAILED). | Integration           |
| AI-05 | Zapis wygenerowanego zestawu             | Utworzenie zestawu w DB, powiązanie AiJob z Set, poprawne metryki (accepted/edited count).                | Integration           |

### 5.2 Bezpieczeństwo i RLS (Row Level Security)

| ID     | Scenariusz                                  | Oczekiwany Rezultat                                                            | Typ Testu        |
|--------|---------------------------------------------|--------------------------------------------------------------------------------|------------------|
| SEC-01 | Dostęp do zestawu innego użytkownika (UUID) | Repozytorium zwraca null (filtrowanie przez RLS), brak błędu SQL, brak danych. | Integration (DB) |
| SEC-02 | Próba edycji fiszki innego użytkownika      | Blokada na poziomie bazy danych (Policy Violation) lub NotFound (dzięki RLS).  | Integration (DB) |
| SEC-03 | Izolacja sesji DB                           | Zapytanie SQL current_app_user() zwraca poprawne ID zalogowanego użytkownika.  | Integration      |
| SEC-04 | Rejestracja z istniejącym e-mailem          | Błąd walidacji, brak duplikatu w DB (citext unique).                           | Unit/Integration |

### 5.3 Zarządzanie Zestawami i Nauka

| ID       | Scenariusz                   | Oczekiwany Rezultat                                                                   | Typ Testu        |
|----------|------------------------------|---------------------------------------------------------------------------------------|------------------|
| SET-01   | Soft Delete zestawu          | Ustawienie deleted_at, zestaw znika z listy "Moje zestawy", ale pozostaje w bazie.    | Integration      |
| SET-02   | Trigger licznika fiszek      | Dodanie 2 fiszek zwiększa card_count zestawu o 2. Usunięcie (soft) zmniejsza licznik. | Integration (DB) |
| LEARN-01 | Algorytm powtórek (Wiem)     | next_review_date ustawione w przyszłości, wzrost interval.                            | Unit             |
| LEARN-02 | Algorytm powtórek (Nie wiem) | next_review_date ustawione na "teraz" lub bliską przyszłość, reset interval.          | Unit             |

### 5.4 Szczegółowe Scenariusze Testowe

#### TC-AUTH-001: Rejestracja nowego użytkownika

**Priorytet:** P0 (Krytyczny)
**User Story:** US-001

**Kroki testowe:**

1. Przejdź do GET /register
2. Wprowadź: Email (test@example.com), Hasło (SecurePass123!), Potwierdzenie hasła
3. Kliknij "Zarejestruj się"

**Oczekiwany rezultat:**

- Status 302 (redirect)
- Użytkownik automatycznie zalogowany
- Przekierowanie do /generate lub /sets
- W bazie danych: nowy rekord w tabeli users, hasło zahashowane

**Warunki brzegowe:**

- Email już istnieje → komunikat błędu
- Hasła się różnią → komunikat błędu
- Słabe hasło (<8 znaków) → komunikat błędu

---

#### TC-AUTH-003: Row-Level Security (RLS)

**Priorytet:** P0 (Krytyczny)

**Kroki testowe:**

1. Zaloguj się jako User A
2. Stwórz zestaw "Set A"
3. Wyloguj się
4. Zaloguj się jako User B
5. Wykonaj zapytanie: SELECT * FROM sets

**Oczekiwany rezultat:**

- User B widzi tylko własne zestawy
- "Set A" nie jest widoczny dla User B
- PostgresRLSSubscriber ustawił current_user_id = User B
- Próba direct SQL access do Set A → brak wyników

**Warunki brzegowe:**

- Direct DB access (psql) → RLS nadal aktywne
- SQL injection próba ominięcia RLS → blocked

---

#### TC-GEN-001: Generowanie fiszek z prawidłowego tekstu

**Priorytet:** P0 (Krytyczny)
**User Story:** US-003

**Warunki wstępne:** Użytkownik zalogowany

**Kroki testowe:**

1. Przejdź do GET /generate
2. Wklej tekst o długości 5000 znaków
3. Zweryfikuj licznik znaków: pokazuje "5000 / 10000"
4. Zweryfikuj przycisk "Generuj": aktywny (zielony)
5. Kliknij "Generuj fiszki"

**Oczekiwany rezultat:**

- Overlay ładowania z komunikatem "Analizowanie tekstu..."
- POST /api/generate z JSON: {source_text: "..."}
- OpenRouterService wywołany z tekstem
- Response 200: {job_id: "uuid", status: "completed"}
- pending_set zapisany w sesji z job_id, suggested_name, cards, source_text
- Redirect 302 → /sets/new/edit

**Warunki brzegowe:**

- Tekst 1000 znaków (minimum) → sukces
- Tekst 10000 znaków (maksimum) → sukces
- Tekst 999 znaków → przycisk disabled
- Tekst 10001 znaków → przycisk disabled

---

#### TC-GEN-003: Obsługa błędów API OpenRouter

**Priorytet:** P1 (Wysoki)
**User Story:** US-007

**Warunki wstępne:** Mock OpenRouterService zwraca timeout

**Kroki testowe:**

1. Przejdź do GET /generate
2. Wklej prawidłowy tekst (5000 znaków)
3. Kliknij "Generuj fiszki"
4. OpenRouterService rzuca AiTimeoutException

**Oczekiwany rezultat:**

- Overlay ładowania znika
- ErrorModal wyświetlony z komunikatem: "Nie udało się wygenerować fiszek. Spróbuj ponownie."
- Sugestie: "Sprawdź połączenie internetowe" / "Spróbuj krótszego tekstu"
- Użytkownik pozostaje na /generate, tekst w polu textarea zachowany

**Inne przypadki błędów:**

- OpenRouterRateLimitException → "Za dużo żądań. Spróbuj za chwilę."
- OpenRouterAuthenticationException → "Błąd konfiguracji. Skontaktuj się z administratorem."

---

#### TC-EDIT-004: Zapisywanie zestawu

**Priorytet:** P0 (Krytyczny)
**User Story:** US-006

**Warunki wstępne:**

- pending_set z 10 fiszkami
- 2 fiszki edytowane
- 3 fiszki usunięte
- Pozostało 7 fiszek (2 edited, 5 original)

**Kroki testowe:**

1. Wprowadź nazwę: "Mój zestaw testowy"
2. Kliknij "Zapisz zestaw"

**Oczekiwany rezultat:**

- Overlay ładowania
- POST /api/sets z JSON: name, cards (origin: "ai", edited: true/false)
- Response 200: {set_id: "uuid"}
- W bazie danych: Nowy rekord w sets, 7 rekordów w cards (2 z edited=true)
- SetCreatedEvent emitted
- AnalyticsEvent: set_created, flashcards_accepted (7), flashcards_deleted (3)
- pending_set usunięty z sesji
- Redirect 302 → /generate

**Warunki brzegowe:**

- Pusta nazwa → walidacja error
- Nazwa <3 znaki → error
- Nazwa >100 znaków → error
- Duplikat nazwy → DuplicateSetNameException
- Wszystkie fiszki usunięte → "Musisz mieć przynajmniej 1 fiszkę"

---

#### TC-ANALYTICS-001: Obliczanie wskaźnika akceptacji

**Priorytet:** P0 (Krytyczny)
**Metryka:** Acceptance rate

**Warunki wstępne:**

- 100 wygenerowanych fiszek
- 20 usuniętych podczas edycji
- 80 zapisanych (60 original + 20 edited)

**Kroki testowe:**

1. Query AnalyticsEventRepository: countByEventType('flashcards_generated'), countByEventType('
   fiszka_usunięta_w_edycji')
2. Oblicz wskaźnik: 1 - (deleted / generated)

**Oczekiwany rezultat:**

- generated = 100
- deleted = 20
- acceptance_rate = 1 - (20/100) = 0.80 = 80%
- Wskaźnik ≥75% ✅ (cel osiągnięty)

---

## 6. Środowisko Testowe

### 6.1 Środowiska

#### Lokalne (Development)

- Docker Compose (zgodnie z docker-compose.yml)
- PHP Container: Xdebug włączony
- Baza Danych: PostgreSQL 16 (taka sama jak na prod, kluczowe dla RLS)

#### Testowe (CI/CD)

- Baza danych testowa (flashcards_test) tworzona od zera przy każdym uruchomieniu
- Zmienne środowiskowe: APP_ENV=test, OPENROUTER_API_KEY ustawiony na mock/stub

#### Staging

- Środowisko podobne do produkcji
- Seed data dla UAT
- OpenRouter API (staging key, quota limits)

### 6.2 Dane Testowe

#### Fixtures dla testów automatycznych

- **Users:** test@example.com, user2@example.com
- **Sets & Cards:** Mix AI (edited/original) + manual
- **AnalyticsEvents:** flashcards_generated, fiszka_usunięta_w_edycji

#### Seed data dla UAT (staging)

- 5 przykładowych użytkowników
- 20 zestawów fiszek (mix AI + manual)
- 200 fiszek z różnych dziedzin
- ReviewStates z różnymi next_review_date

---

## 7. Narzędzia do Testowania

### 7.1 Backend Testing

- **PHPUnit 12.4:** Unit, Integration, Functional tests
- **Symfony Test Framework:** KernelTestCase, WebTestCase, BrowserKit, DomCrawler
- **PHPStan:** Static analysis, type checking (level 8)
- **Doctrine Fixtures:** Przygotowanie danych testowych

### 7.2 Frontend Testing

- **Jest:** Unit tests dla JavaScript (Stimulus controllers)
- **Testing Library (@testing-library/dom):** Testing framework
- **Playwright:** E2E tests, browser automation

### 7.3 Database Testing

- PostgreSQL Test Database (flashcards_test)
- Database Transactions w testach (rollback po każdym teście)

### 7.4 API Testing

- HTTP Client (Manual Testing): VS Code REST Client extension
- Postman / Insomnia: Manual API testing, collection sharing

### 7.5 Security Testing

- **OWASP ZAP:** Automated security scanning
- **Symfony Security Checker:** Skanowanie zależności pod kątem znanych luk

### 7.6 Performance Testing

- **Symfony Profiler:** Database query profiling, performance bottlenecks
- **Lighthouse:** Frontend performance, accessibility, SEO
- PostgreSQL EXPLAIN ANALYZE: Query optimization

### 7.7 CI/CD

- **GitHub Actions:** Automated test pipeline
- Pipeline stages: Composer install → Database setup → PHPUnit tests → PHPStan → Coverage report

---

## 8. Harmonogram Testów

### 8.1 Fazy testowania

#### Etap 1: Unit & Domain (Natychmiast)

Testy Encji, Value Objects i walidacji. Kluczowe dla stabilności logiki.

#### Etap 2: DB & RLS (Krytyczne)

Weryfikacja migracji i polityk bezpieczeństwa. Bez tego nie można iść dalej.

#### Etap 3: Handlery & Integracja

Testy tworzenia zestawów i przepływu generowania (z mockowanym AI).

#### Etap 4: UI/Frontend

Testy kontrolerów Stimulus (walidacja JS, interakcje).

#### Etap 5: UAT (User Acceptance Testing)

- Grupa testowa: 10-20 uczniów
- Zbierane metryki: Wskaźnik akceptacji (≥75%), SUS score (>70), NPS (>50)

### 8.2 Entry/Exit Criteria

#### Entry Criteria dla UAT:

- ✅ Wszystkie functional tests passed
- ✅ Security scan completed (0 critical vulnerabilities)
- ✅ Brak critical + high bugs
- ✅ 10-20 beta testers recruited

#### Exit Criteria dla UAT:

- ✅ UAT metrics achieved (75% acceptance rate, 75% AI adoption)
- ✅ SUS score >70
- ✅ Brak critical bugs
- ✅ User feedback positive (majority)

---

## 9. Kryteria Akceptacji Testów

### 9.1 Kryteria funkcjonalne

- ✅ Wszystkie user stories (US-001 do US-012) z PRD zaimplementowane
- ✅ Wskaźnik akceptacji fiszek AI ≥ 75%
- ✅ Wskaźnik adopcji AI ≥ 75%
- ✅ Średni czas generowania < 15s (95 percentile)
- ✅ RLS skutecznie izoluje dane między użytkownikami
- ✅ Brak luk OWASP Top 10
- ✅ Algorytm spaced repetition poprawnie oblicza next_review_date

### 9.2 Kryteria niefunkcjonalne

#### Wydajność

- ✅ Page load time (FCP) < 2s
- ✅ API response time (excluding AI) < 1s (95 percentile)
- ✅ AI generation time < 15s (95 percentile)
- ✅ Database queries < 100ms (95 percentile)
- ✅ Brak N+1 queries w critical paths

#### Niezawodność

- ✅ Error rate < 1%
- ✅ Graceful degradation przy błędach AI

#### Użyteczność (UX)

- ✅ SUS score > 70
- ✅ Task completion rate > 90% (UAT)
- ✅ Accessibility score > 90 (Lighthouse)
- ✅ Mobile responsiveness: działa na urządzeniach 320px+ width

#### Utrzymywalność

- ✅ Code coverage ≥ 80% (domain + application layers)
- ✅ PHPStan level 8: 0 errors
- ✅ Documentation: README, CLAUDE.md, PRD aktualne
- ✅ CI/CD pipeline: testy przechodzą automatycznie

### 9.3 Kryteria akceptacji per typ testu

#### Unit Tests

- ✅ Coverage ≥ 90% dla warstwy domenowej
- ✅ Coverage ≥ 80% dla warstwy aplikacyjnej
- ✅ Wszystkie edge cases pokryte
- ✅ Execution time < 100ms dla całego suite

#### Integration Tests

- ✅ Wszystkie repozytoria przetestowane z PostgreSQL
- ✅ RLS przetestowane (izolacja danych)
- ✅ OpenRouter integration przetestowane (mock + real API)

#### Security Tests

- ✅ OWASP ZAP scan: 0 critical/high vulnerabilities
- ✅ Symfony security:check: 0 known vulnerabilities
- ✅ RLS bypass attempts: wszystkie zablokowane

#### UAT

- ✅ ≥ 75% akceptacji fiszek AI (beta users)
- ✅ ≥ 75% adopcji AI (beta users)
- ✅ SUS score > 70
- ✅ Task completion rate > 90%
- ✅ Brak critical blockers zgłoszonych

### 9.4 Definition of Done

**Feature jest "Done" gdy:**

1. ✅ Unit tests napisane i przechodzą (coverage ≥80%)
2. ✅ Integration tests napisane i przechodzą
3. ✅ Functional tests napisane i przechodzą
4. ✅ Code review przeprowadzony
5. ✅ PHPStan level 8: 0 errors
6. ✅ Manual testing wykonany przez QA
7. ✅ Documentation zaktualizowana
8. ✅ CI/CD pipeline: green

**MVP jest "Done" gdy:**

1. ✅ Wszystkie user stories z PRD zaimplementowane
2. ✅ UAT completed (metrics goals achieved)
3. ✅ Security audit passed
4. ✅ Performance benchmarks met
5. ✅ Production deployment successful
6. ✅ Smoke tests na produkcji passed
7. ✅ Monitoring i alerting aktywne
8. ✅ Rollback plan przetestowany

---

## 10. Procedury Raportowania Błędów

### 10.1 Bug Report Template

#### Obowiązkowe pola:

**1. Tytuł (Title)**

- Format: `[Moduł] Krótki opis problemu`
- Przykład: `[Generowanie] Przycisk "Generuj" nie odblokowuje się przy 1000 znaków`

**2. Severity / Priority**

- **Severity:** Critical / High / Medium / Low
- **Priority:** P0 / P1 / P2 / P3

**3. Środowisko (Environment)**

- Wersja: commit hash lub tag
- Środowisko: Local / Staging / Production
- Browser: Chrome 120 / Firefox 121 / Safari 17
- OS: Ubuntu 22.04 / macOS 14 / Windows 11

**4. Kroki reprodukcji (Steps to Reproduce)**

**5. Oczekiwany rezultat (Expected Result)**

**6. Rzeczywisty rezultat (Actual Result)**

**7. Logi / Screenshots**

- Fragmenty z var/log/dev.log
- Output z konsoli przeglądarki (dla błędów Stimulus/JS)
- Screenshots (jeśli dotyczy UI)

**8. Kontekst AI (jeśli dotyczy)**

- Czy błąd pochodzi z API (kod błędu OpenRouter)?
- Czy z parsowania w aplikacji?

### 10.2 Severity Levels i Eskalacja

1. **Critical (P0):** Blocker dla release, app crashed, data loss
    - **Eskalacja:** Natychmiast do całego zespołu
    - **SLA:** Fix w ciągu 24h

2. **High (P1):** Major feature broken, workaround exists
    - **Eskalacja:** Do Product Owner w ciągu 4h
    - **SLA:** Fix przed release

3. **Medium (P2):** Minor feature issue, non-critical
    - **Eskalacja:** Standardowy proces (bug backlog)
    - **SLA:** Fix w następnym sprincie

4. **Low (P3):** Cosmetic, nice-to-have
    - **Eskalacja:** Brak, backlog
    - **SLA:** Best effort

---

## 11. Uwaga Specjalna dla QA: PostgreSQL RLS

**⚠️ KRYTYCZNA INFORMACJA:**

Ze względu na zastosowanie **PostgreSQL Row-Level Security (RLS)**, podczas pisania testów integracyjnych w PHPUnit,
należy upewnić się, że testy wykorzystują mechanizm autoryzacji (np. `actingAs($user)`) lub ręcznie ustawiają zmienną
sesyjną DB.

**W przeciwnym razie:**

- Zapytania będą zwracać puste wyniki
- Może prowadzić do fałszywych negatywów (false negatives)
- Testy mogą przechodzić mimo błędów w logice RLS

**Zalecenia:**

1. Zawsze testuj z dwoma różnymi użytkownikami (User A, User B)
2. Weryfikuj, że User A **nie widzi** danych User B
3. Testuj próby SQL injection ominięcia RLS
4. Sprawdź, czy `current_app_user()` zwraca poprawne ID

---

## 12. Podsumowanie

Ten hybrydowy plan testów łączy:

- **Praktyczną zwięzłość** i fokus na RLS z planu google.md
- **Gotowe scenariusze testowe** i strukturę z planu comprehensive.md

Priorytetem jest **bezpieczeństwo danych (RLS)** i **jakość generowania AI (75% akceptacji)**.

Implementacja testów powinna rozpocząć się od:

1. Testów RLS (SEC-01, SEC-02, SEC-03) - KRYTYCZNE
2. Testów generowania AI (AI-01 do AI-05)
3. Testów analityki (ANALYTICS-001, ANALYTICS-002)

Ten plan zapewnia solidne fundamenty dla testowania MVP przy jednoczesnym zachowaniu elastyczności i pragmatyzmu.
