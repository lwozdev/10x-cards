<analiza_projektu>
**1. Kluczowe komponenty projektu:**

* **Warstwa Domenowa (Domain):** Oparta na wzorcach DDD. Zawiera Encje (User, Set, Card, AiJob, ReviewEvent), Obiekty
  Wartości (Value Objects: UserId, Email, SourceText - z walidacją 1000-10000 znaków) oraz Interfejsy Repozytoriów. To
  jądro logiki biznesowej.
* **Warstwa Aplikacji (Application):** Wzorce CQRS (Command/Query). Handlery (np. `CreateSetHandler`,
  `GenerateCardsHandler`) sterują przepływem danych, logiką biznesową i zdarzeniami (np. `SetCreatedEvent`).
* **Warstwa Infrastruktury (Infrastructure):** Implementacje repozytoriów Doctrine, integracja z OpenRouter (AI), oraz
  bardzo istotna warstwa bezpieczeństwa bazy danych (EventSubscriber ustawiający RLS).
* **Warstwa Prezentacji (UI/Frontend):** Server-Side Rendering (Twig) wspierany przez Symfony UX (Turbo & Stimulus).
  Kontrolery Stimulus (np. `generate_controller.js`, `edit_set_controller.js`) obsługują logikę interfejsu (liczniki
  znaków, walidację formularzy, paski postępu) bez przeładowania strony.
* **Baza Danych (PostgreSQL):** Zaawansowana konfiguracja. Wykorzystanie **Row Level Security (RLS)** do izolacji danych
  użytkowników, niestandardowe typy ENUM (`card_origin`, `ai_job_status`), rozszerzenie `citext` oraz triggery (
  automatyczna aktualizacja `card_count`).

**2. Specyfika stosu technologicznego a testowanie:**

* **Symfony 7.3 & PHP 8.2:** Wymaga silnych testów jednostkowych (PHPUnit 12.4) dla serwisów i handlerów.
* **PostgreSQL RLS:** Standardowe testy integracyjne często działają na jednym użytkowniku. Tutaj **krytyczne** jest
  testowanie scenariuszy wielodostępowych, aby upewnić się, że `PostgresRLSSubscriber` poprawnie narzuca kontekst
  użytkownika i polityki bezpieczeństwa działają.
* **Symfony UX (Stimulus/Turbo):** Nie jest to typowe SPA (React/Vue), więc testy E2E (np. Panther/Selenium) są
  niezbędne, aby zweryfikować czy zdarzenia JS (np. aktualizacja licznika znaków, wysyłanie formularzy przez Turbo)
  działają poprawnie z backendem.
* **OpenRouter AI:** Zewnętrzne API. Testy muszą uwzględniać mockowanie odpowiedzi (pozytywne, błędy 4xx/5xx, timeouty),
  aby nie generować kosztów i zapewnić determinizm.

**3. Priorytety testowe:**

1. **Bezpieczeństwo danych (RLS):** Ryzyko wycieku danych między użytkownikami jest zminimalizowane architektonicznie,
   ale błąd w konfiguracji (np. `PostgresRLSSubscriber`) byłby katastrofalny.
2. **Generowanie AI:** Główna wartość biznesowa. Testy muszą pokrywać walidację wejścia (`SourceText`), obsługę błędów
   API i poprawność parsowania odpowiedzi.
3. **Core Business Logic (Zestawy/Fiszki):** Tworzenie, edycja, usuwanie (soft delete), algorytm powtórek.
4. **UI Interactivity:** Walidacja formularzy po stronie klienta (Stimulus) vs backend.

**4. Obszary ryzyka:**

* **Postgres RLS:** Skomplikowana logika w migracjach i subskrybentach. Jeśli sesja DB nie zostanie poprawnie
  zainicjowana, zapytania mogą zwrócić puste wyniki lub błędy.
* **Asynchroniczność/Timeouty AI:** `GenerateCardsHandler` obsługuje timeouty (30s). Testy muszą sprawdzić, czy
  aplikacja "wdzięcznie" obsługuje długie czasy odpowiedzi.
* **Spójność danych (Triggery):** Licznik `card_count` jest w `sets`, ale zarządzany przez trigger w DB. Aplikacja
  polega na tym liczniku.
* **Zgodność front-back:** Walidacja JS (np. hasła, długość tekstu) musi być zsynchronizowana z walidacją w Entity/Value
  Objects.
  </analiza_projektu>

<plan_testów>

# Kompleksowy Plan Testów: AI Flashcard Generator

## 1. Wprowadzenie

Celem niniejszego planu jest zapewnienie jakości, bezpieczeństwa i stabilności aplikacji "AI Flashcard Generator".
Projekt jest monolitem opartym na Symfony 7.3 z silnym naciskiem na bezpieczeństwo danych na poziomie bazy danych (
PostgreSQL RLS) oraz interaktywność frontendu (Symfony UX). Ze względu na charakter MVP oraz integrację z płatnym API
AI, testy muszą balansować między dokładnością weryfikacji logiki biznesowej a optymalizacją kosztów (mockowanie AI).

## 2. Zakres Testów

### 2.1. W Zakesie (In-Scope)

* **Uwierzytelnianie i Autoryzacja:** Rejestracja, logowanie, reset hasła, weryfikacja izolacji danych (RLS).
* **Moduł Generowania AI:** Przetwarzanie tekstu, komunikacja z OpenRouter (mockowana), obsługa błędów i timeoutów,
  podgląd i edycja przed zapisem.
* **Zarządzanie Zestawami (CRUD):** Tworzenie ręczne, edycja, usuwanie (Soft Delete), mechanizmy zliczania fiszek (DB
  Triggers).
* **Logika Domenowa:** Obiekty wartości (Value Objects), walidacja danych wejściowych, algorytm powtórek (ReviewState).
* **Frontend (UX):** Działanie kontrolerów Stimulus (walidacja formularzy, paski postępu, modale), responsywność (
  Tailwind CSS).
* **API:** Endpointy wykorzystywane przez frontend (np. `/api/generate`, `/api/sets`).

### 2.2. Poza Zakresem (Out-of-Scope)

* Testy obciążeniowe (Load Testing) dla dużej skali (na etapie MVP).
* Testy natywnych aplikacji mobilnych (aplikacja jest webowa).
* Testy integracji z systemami LMS (Moodle itp.).
* Weryfikacja jakości merytorycznej treści generowanych przez rzeczywiste modele AI (to zależy od dostawcy LLM).

## 3. Strategia i Typy Testów

### 3.1. Testy Jednostkowe (Unit Tests) - Backend

* **Cel:** Weryfikacja logiki w izolacji.
* **Pokrycie:**
    * **Value Objects:** `SourceText` (limity 1000-10000 znaków), `Email`, `CardFront`/`Back`.
    * **Entities:** Metody fabrykujące (`create`), logika zmiany stanu (np. `ReviewState::updateAfterReview`).
    * **Services:** `CreateSetHandler` (logika biznesowa bez zapisu do DB), transformacja DTO.
* **Narzędzia:** PHPUnit 12.4.

### 3.2. Testy Integracyjne (Integration Tests) - Backend & DB

* **Cel:** Weryfikacja współpracy komponentów z bazą danych i serwisami zewnętrznymi.
* **Kluczowe obszary:**
    * **PostgreSQL RLS:** Weryfikacja czy `PostgresRLSSubscriber` poprawnie ustawia kontekst sesji. Próby dostępu do
      danych innego użytkownika muszą kończyć się niepowodzeniem na poziomie zapytania SQL.
    * **Triggery DB:** Sprawdzenie czy dodanie/usunięcie (soft delete) fiszki aktualizuje `card_count` w tabeli `sets`.
    * **AI Service:** Testy integracyjne z wykorzystaniem `MockAiCardGenerator` oraz `OpenRouterAiCardGenerator` z
      nagranymi odpowiedziami (VCR), aby unikać kosztów.

### 3.3. Testy Frontendowe i E2E (End-to-End)

* **Cel:** Weryfikacja interakcji użytkownika i poprawnego działania kontrolerów Stimulus.
* **Kluczowe obszary:**
    * Formularz generowania: licznik znaków (real-time), blokada przycisku, pasek postępu (symulowany w JS).
    * Edycja zestawu: dynamiczne dodawanie/usuwanie fiszek w formularzu, walidacja JS.
    * Przepływ Turbo: nawigacja bez przeładowania strony.
* **Narzędzia:** Symfony Panther (bazujący na WebDriver/Selenium) lub Cypress (opcjonalnie).

## 4. Scenariusze Testowe (Kluczowe Funkcjonalności)

### 4.1. Moduł AI i Generowania

| ID    | Scenariusz                               | Oczekiwany Rezultat                                                                                           | Typ Testu             |
|-------|------------------------------------------|---------------------------------------------------------------------------------------------------------------|-----------------------|
| AI-01 | Walidacja wejścia (tekst < 1000 znaków)  | Blokada przycisku "Generuj", komunikat błędu (Stimulus). Błąd 422 przy próbie obejścia (API).                 | Unit/E2E              |
| AI-02 | Walidacja wejścia (tekst > 10000 znaków) | Blokada przycisku, licznik na czerwono.                                                                       | E2E                   |
| AI-03 | Poprawne wygenerowanie fiszek            | Otrzymanie JSON z listą fiszek, przekierowanie do edycji, utworzenie `AiJob` (status SUCCEEDED).              | Integration (Mock AI) |
| AI-04 | Timeout zewnętrznego API (>30s)          | Przechwycenie `AiTimeoutException`, wyświetlenie modalu z błędem i sugestiami, zapis `AiJob` (status FAILED). | Integration           |
| AI-05 | Zapis wygenerowanego zestawu             | Utworzenie zestawu w DB, powiązanie `AiJob` z `Set`, poprawne metryki (accepted/edited count).                | Integration           |

### 4.2. Bezpieczeństwo i RLS (Row Level Security)

| ID     | Scenariusz                                  | Oczekiwany Rezultat                                                              | Typ Testu        |
|--------|---------------------------------------------|----------------------------------------------------------------------------------|------------------|
| SEC-01 | Dostęp do zestawu innego użytkownika (UUID) | Repozytorium zwraca `null` (filtrowanie przez RLS), brak błędu SQL, brak danych. | Integration (DB) |
| SEC-02 | Próba edycji fiszki innego użytkownika      | Blokada na poziomie bazy danych (Policy Violation) lub `NotFound` (dzięki RLS).  | Integration (DB) |
| SEC-03 | Izolacja sesji DB                           | Zapytanie SQL `current_app_user()` zwraca poprawne ID zalogowanego użytkownika.  | Integration      |
| SEC-04 | Rejestracja z istniejącym e-mailem          | Błąd walidacji, brak duplikatu w DB (`citext` unique).                           | Unit/Integration |

### 4.3. Zarządzanie Zestawami i Nauka

| ID       | Scenariusz                   | Oczekiwany Rezultat                                                                     | Typ Testu        |
|----------|------------------------------|-----------------------------------------------------------------------------------------|------------------|
| SET-01   | Soft Delete zestawu          | Ustawienie `deleted_at`, zestaw znika z listy "Moje zestawy", ale pozostaje w bazie.    | Integration      |
| SET-02   | Trigger licznika fiszek      | Dodanie 2 fiszek zwiększa `card_count` zestawu o 2. Usunięcie (soft) zmniejsza licznik. | Integration (DB) |
| LEARN-01 | Algorytm powtórek (Wiem)     | `next_review_date` ustawione w przyszłości, wzrost `interval`.                          | Unit             |
| LEARN-02 | Algorytm powtórek (Nie wiem) | `next_review_date` ustawione na "teraz" lub bliską przyszłość, reset `interval`.        | Unit             |

## 5. Środowisko Testowe

* **Lokalne (Developer):** Docker Compose (zgodnie z `docker-compose.yml`).
    * PHP Container: Xdebug włączony.
    * Baza Danych: PostgreSQL 16 (taka sama jak na prod, kluczowe dla RLS).
* **Testowe (CI/CD):**
    * Baza danych testowa (`flashcards_test`) tworzona od zera przy każdym uruchomieniu potoku.
    * Zmienne środowiskowe: `APP_ENV=test`, `OPENROUTER_API_KEY` ustawiony na mock/stub.

## 6. Narzędzia i Konfiguracja

1. **PHPUnit:** Główny framework testowy. Konfiguracja w `phpunit.dist.xml`.
    * Użycie `DAMA\DoctrineTestBundle` (jeśli dostępny) lub transakcji do wycofywania zmian w DB po każdym teście.
2. **Symfony WebProfiler:** Do debugowania zapytań SQL i RLS w środowisku dev.
3. **Mockery / PHPUnit Mocks:** Do izolacji serwisu `OpenRouterService`.
4. **Static Analysis:** PHPStan (do wykrywania błędów typów przed uruchomieniem testów).

## 7. Harmonogram Testów

1. **Etap 1: Unit & Domain (Natychmiast):** Testy Encji, Value Objects i walidacji. Kluczowe dla stabilności logiki.
2. **Etap 2: DB & RLS (Krytyczne):** Weryfikacja migracji i polityk bezpieczeństwa. Bez tego nie można iść dalej.
3. **Etap 3: Handlery & Integracja:** Testy tworzenia zestawów i przepływu generowania (z mockowanym AI).
4. **Etap 4: UI/Frontend:** Testy kontrolerów Stimulus (walidacja JS, interakcje).

## 8. Kryteria Akceptacji

* **Unit Tests:** 100% sukcesu, pokrycie kodu (Code Coverage) dla warstwy Domain > 90%.
* **Bezpieczeństwo:** 100% testów RLS zakończonych sukcesem. Żaden test nie może mieć dostępu do danych innego
  użytkownika testowego.
* **Regresja:** Wszystkie istniejące testy przechodzą przed merge'owaniem nowych funkcji.
* **Analiza Statyczna:** Brak błędów na poziomie 5 w PHPStan.

## 9. Procedury Raportowania Błędów

Zgłoszenia błędów (Issues) w systemie kontroli wersji powinny zawierać:

1. **Środowisko:** (np. Docker, Localhost).
2. **Kroki do reprodukcji:** Dokładna ścieżka (np. Wklej tekst > Kliknij generuj > Czekaj 35s).
3. **Oczekiwany vs Rzeczywisty rezultat.**
4. **Logi:** Fragmenty z `var/log/dev.log` lub output z konsoli przeglądarki (dla błędów Stimulus/JS).
5. **Kontekst AI:** Jeśli dotyczy generowania - czy błąd pochodzi z API (kod błędu OpenRouter) czy z parsowania w
   aplikacji.

---
**Uwaga specjalna dla QA:** Ze względu na zastosowanie **PostgreSQL RLS**, podczas pisania testów integracyjnych w
PHPUnit, należy upewnić się, że testy wykorzystują mechanizm autoryzacji (np. `actingAs($user)`) lub ręcznie ustawiają
zmienną sesyjną DB, w przeciwnym razie zapytania będą zwracać puste wyniki, co może prowadzić do fałszywych negatywów (
false negatives).
</plan_testów>
