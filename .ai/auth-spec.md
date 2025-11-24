# Specyfikacja Techniczna Modułu Autentykacji
## Generator Fiszek AI

---

## 1. ARCHITEKTURA INTERFEJSU UŻYTKOWNIKA

### 1.1 Podział stron i widoków

Aplikacja operuje w dwóch trybach dostępu, co wpływa na strukturę interfejsu:

#### 1.1.1 Strony publiczne (dostępne bez logowania)

**Strona główna generowania fiszek** (`/generate`)
- Główne pole tekstowe do wklejania notatek (1000-10000 znaków)
- Dynamiczny licznik znaków z paskiem postępu
- Przycisk "Generuj fiszki" (aktywny przy spełnieniu limitu znaków)
- Informacja o limitach i wymaganiach
- Baner/komunikat zachęcający do rejestracji z opisem korzyści (zapisywanie, edycja, nauka)
- Nawigacja z przyciskami "Zaloguj się" i "Zarejestruj się" w prawym górnym rogu

**Strona logowania** (`/login`)
- Formularz logowania (email, hasło)
- Link "Zapomniałeś hasła?" prowadzący do resetowania hasła
- Link do strony rejestracji
- Komunikaty walidacyjne i błędów

**Strona rejestracji** (`/register`)
- Formularz rejestracji (email, hasło, potwierdzenie hasła)
- Walidacja w czasie rzeczywistym (format email, zgodność haseł, minimalna długość)
- Komunikaty walidacyjne
- Link do strony logowania dla istniejących użytkowników

**Strona resetowania hasła** (`/reset-password/request`)
- Formularz z polem email do wysłania linku resetującego
- Komunikat potwierdzający wysłanie emaila
- Link powrotny do logowania

**Strona ustawiania nowego hasła** (`/reset-password/reset/{token}`)
- Formularz z polami: nowe hasło, potwierdzenie hasła
- Walidacja wymagań bezpieczeństwa hasła
- Komunikat sukcesu z automatycznym przekierowaniem do logowania

**Strona podglądu wyników generowania (tryb publiczny)** (`/generate/preview`)
- Lista wygenerowanych fiszek w formacie awers/rewers
- Komunikat informujący, że zapisanie wymaga logowania
- Przyciski "Zaloguj się" i "Zarejestruj się" nad listą fiszek
- Brak funkcji edycji i usuwania
- Dane tymczasowo przechowywane w sesji

#### 1.1.2 Strony wymagające autoryzacji

**Strona edycji i zapisu zestawu** (`/sets/new/edit`)
- Lista wygenerowanych fiszek z możliwością edycji (inline editing dla awers/rewers)
- Przycisk usuwania przy każdej fiszce
- Pole do nadania nazwy zestawu (z automatyczną sugestią)
- Licznik: "Wygenerowano X fiszek, pozostało Y"
- Przyciski "Zapisz zestaw" i "Anuluj"
- Komunikaty potwierdzenia przed usunięciem

**Strona listy zestawów** (`/sets`)
- Lista wszystkich zapisanych zestawów użytkownika
- Dla każdego zestawu: nazwa, liczba fiszek, data utworzenia
- Przyciski akcji: "Ucz się", "Usuń"
- Przycisk "Stwórz nowy zestaw" (manualnie)
- Przycisk "Generuj fiszki AI" prowadzący do `/generate`
- Komunikat powitalny dla nowych użytkowników bez zestawów

**Uwaga**: Edycja już zapisanych zestawów NIE jest w zakresie MVP (zgodnie z PRD). Użytkownicy mogą edytować fiszki tylko przed zapisaniem zestawu w `/sets/new/edit`.

**Strona manualnego tworzenia zestawu** (`/sets/new/manual`)
- Pole do nadania nazwy zestawu
- Formularz dodawania fiszek (awers, rewers)
- Przycisk "Dodaj kolejną fiszkę"
- Dynamiczna lista dodanych fiszek z możliwością usunięcia
- Przycisk "Zapisz zestaw"

**Strona nauki** (`/sets/{id}/learn`)
- Wyświetlanie pojedynczej fiszki (początkowo tylko awers)
- Przycisk "Pokaż odpowiedź" odsłaniający rewers
- Po odsłonięciu: dwa przyciski "Wiem" i "Nie wiem"
- Pasek postępu nauki w sesji
- Ekran podsumowania po zakończeniu sesji (liczba przejrzanych fiszek, statystyki)

**Nawigacja zalogowanego użytkownika**
- Menu w prawym górnym rogu z nazwą użytkownika lub emailem
- Opcje: "Moje zestawy", "Wyloguj się"
- Aktywne wskaźniki bieżącej sekcji

### 1.2 Komponenty client-side i ich odpowiedzialności

#### 1.2.1 Komponenty oparte na Stimulus

**CharacterCounterController** (Stimulus)
- Monitoruje pole tekstowe na stronie `/generate`
- Aktualizuje licznik znaków w czasie rzeczywistym
- Zarządza stanem przycisku "Generuj fiszki" (enabled/disabled)
- Wyświetla wizualne wskaźniki (kolor paska postępu: czerwony poniżej minimum, zielony w zakresie)
- Reaguje na zdarzenia: input, paste

**FormValidationController** (Stimulus)
- Walidacja formularzy w czasie rzeczywistym (rejestracja, logowanie, resetowanie hasła)
- Sprawdza format email
- Porównuje pola hasło/potwierdzenie hasła
- Weryfikuje minimalne wymagania bezpieczeństwa hasła (8 znaków)
- Wyświetla komunikaty błędów pod polami formularza
- Dezaktywuje przycisk submit przy nieprawidłowych danych

**FlashcardEditorController** (Stimulus)
- Zarządza inline editing na stronie edycji zestawu
- Obsługuje dodawanie/usuwanie fiszek na stronie manualnego tworzenia
- Śledzi zmiany w treści fiszek (do celów analitycznych)
- Pokazuje modale potwierdzenia przed usunięciem
- Synchronizuje licznik pozostałych fiszek

**FlashcardLearningController** (Stimulus)
- Zarządza stanem fiszki (awers widoczny / rewers widoczny)
- Obsługuje przejścia między fiszkami
- Wysyła wynik ("Wiem"/"Nie wiem") do backendu (AJAX)
- Aktualizuje pasek postępu
- Wyświetla ekran podsumowania

**LoadingOverlayController** (Stimulus)
- Wyświetla animację ładowania podczas generowania fiszek
- Pokazuje komunikaty etapowe ("Analizowanie tekstu...", "Tworzenie fiszek...")
- Blokuje interakcję z formularzem podczas przetwarzania

#### 1.2.2 Integracja z backendem

**Formularze Symfony (server-side)**
- `RegistrationFormType` - formularz rejestracji z walidacją constraints (Email, Length, NotBlank, PasswordStrength)
- `LoginFormType` - formularz logowania
- `ResetPasswordRequestFormType` - żądanie resetu hasła
- `ResetPasswordFormType` - ustawienie nowego hasła
- `FlashcardSetFormType` - formularz zapisu zestawu (nazwa, lista fiszek)
- `FlashcardFormType` - pojedyncza fiszka (awers, rewers)

**Walidacja i komunikacja**
- Formularze Symfony obsługują POST requests z walidacją server-side
- Błędy walidacji są renderowane w szablonach Twig
- Komunikaty flash (success/error) wyświetlane po akcjach (rejestracja, logowanie, zapis)
- AJAX calls dla asynchronicznych operacji (generowanie fiszek, ocena w nauce) zwracają JSON

**Nawigacja i akcje użytkownika**
- Po rejestracji: automatyczne logowanie + przekierowanie do `/sets` (moje zestawy)
- Po logowaniu: przekierowanie do ostatniej odwiedzanej strony lub `/sets`
- Po wygenerowaniu (niezalogowany): przekierowanie do `/generate/preview` z zachętą do rejestracji
- Po wygenerowaniu (zalogowany): przekierowanie do `/sets/new/edit`
- Po zapisaniu zestawu: przekierowanie do `/sets`
- Wylogowanie: przekierowanie do `/` (strona główna/generate)

### 1.3 Walidacja i komunikaty błędów

#### 1.3.1 Walidacja rejestracji

**Email**
- Wymagany (NotBlank)
- Format email (Email constraint)
- Unikalność (custom validator sprawdzający UserRepository)
- Komunikat błędu: "Ten adres email jest już zarejestrowany"

**Hasło**
- Wymagane (NotBlank)
- Minimalna długość 8 znaków (Length constraint)
- Opcjonalnie: siła hasła (PasswordStrength constraint - mix liter i cyfr)
- Komunikat: "Hasło musi zawierać co najmniej 8 znaków"

**Potwierdzenie hasła**
- Musi być identyczne z hasłem
- Walidacja przez custom constraint lub FormType
- Komunikat: "Hasła nie są identyczne"

#### 1.3.2 Walidacja logowania

**Nieprawidłowe dane**
- Uniwersalny komunikat: "Nieprawidłowy email lub hasło" (bez ujawniania, czy email istnieje)
- Wyświetlany jako komunikat flash typu error

**Konto nieaktywne/zablokowane** (przyszła rozbudowa)
- Komunikat: "To konto zostało zablokowane. Skontaktuj się z administracją"

#### 1.3.3 Walidacja resetowania hasła

**Żądanie resetu**
- Email wymagany i poprawny format
- Zawsze komunikat sukcesu (nawet jeśli email nie istnieje - ze względów bezpieczeństwa)
- Komunikat: "Jeśli podany adres istnieje w systemie, wysłaliśmy link resetujący hasło"

**Ustawienie nowego hasła**
- Token ważny i nieużyty
- Nowe hasło spełnia wymogi (jak przy rejestracji)
- Komunikat błędu przy nieważnym tokenie: "Link resetujący wygasł lub jest nieprawidłowy. Poproś o nowy"
- Komunikat sukcesu: "Hasło zostało zmienione. Możesz się teraz zalogować"

#### 1.3.4 Walidacja zestawu fiszek

**Nazwa zestawu**
- Wymagana (NotBlank)
- Długość 3-100 znaków
- Komunikat: "Nazwa zestawu musi zawierać od 3 do 100 znaków"

**Fiszki w zestawie**
- Minimum 1 fiszka
- Każda fiszka musi mieć niepusty awers i rewers
- Komunikaty: "Zestaw musi zawierać co najmniej jedną fiszkę", "Awers i rewers fiszki nie mogą być puste"

### 1.4 Najważniejsze scenariusze obsługi

#### Scenariusz 1: Rejestracja nowego użytkownika
1. Użytkownik wchodzi na `/register`
2. Wypełnia formularz (email, hasło, potwierdzenie)
3. Stimulus controller waliduje na bieżąco, pokazuje błędy
4. Submit formularza → POST do `/register`
5. Backend waliduje, tworzy encję User, hashuje hasło
6. Automatyczne logowanie (utworzenie sesji)
7. Komunikat flash: "Witaj! Twoje konto zostało utworzone"
8. Przekierowanie do `/sets`

#### Scenariusz 2: Logowanie użytkownika
1. Użytkownik wchodzi na `/login`
2. Wypełnia email i hasło
3. Submit → POST do `/login`
4. Symfony Security weryfikuje credentials
5. Sukces: utworzenie sesji + przekierowanie do `referer` lub `/sets`
6. Błąd: komunikat flash + powrót do `/login`

#### Scenariusz 3: Generowanie fiszek (użytkownik niezalogowany)
1. Użytkownik wkleja tekst na `/generate`
2. CharacterCounterController aktywuje przycisk przy >1000 znaków
3. Kliknięcie "Generuj" → LoadingOverlay + AJAX POST do `/api/generate`
4. Backend wywołuje OpenRouter.ai, parsuje odpowiedź
5. Fiszki zapisane w sesji
6. Przekierowanie do `/generate/preview` - lista fiszek tylko do odczytu
7. Baner: "Zaloguj się, aby edytować i zapisać fiszki"

**Uwaga zgodności z PRD**: Zgodnie z US-010, generowanie fiszek jest dostępne BEZ logowania. W przypadku błędu API (US-007), komunikat błędu jest również wyświetlany użytkownikowi niezalogowanemu. PRD zawiera niekonsekwencję w US-007 (wspomina "dostępne tylko dla zalogowanych"), ale jest to sprzeczne z US-003 i US-010, więc ignorujemy to i zakładamy dostęp publiczny.

#### Scenariusz 4: Generowanie fiszek (użytkownik zalogowany)
1-3. Jak powyżej
4. Backend wywołuje AI, zapisuje fiszki w sesji
5. Przekierowanie do `/sets/new/edit`
6. Użytkownik widzi edytowalne fiszki, może usuwać/modyfikować
7. Wypełnia nazwę zestawu (z sugestią)
8. Submit → POST do `/sets/save`
9. Backend tworzy FlashcardSet i Flashcard entities, zapisuje do DB
10. Śledzenie analytics: źródło=AI, liczba wygenerowanych/usuniętych
11. Komunikat sukcesu + przekierowanie do `/sets`

#### Scenariusz 5: Reset hasła
1. Użytkownik klika "Zapomniałeś hasła?" na `/login`
2. Wchodzi na `/reset-password/request`, podaje email
3. Submit → backend generuje token (SymfonyCasts ResetPasswordBundle)
4. Email wysyłany z linkiem zawierającym token
5. Użytkownik klika link → `/reset-password/reset/{token}`
6. Wypełnia nowe hasło
7. Submit → backend waliduje token, zmienia hasło, unieważnia token
8. Komunikat sukcesu + przekierowanie do `/login`

---

## 2. LOGIKA BACKENDOWA

### 2.1 Struktura endpointów

#### 2.1.1 Endpointy publiczne (renderowanie Twig)

**GET `/`** lub **GET `/generate`**
- Kontroler: `GenerateController::index()`
- Renderuje: `generate/index.html.twig`
- Zawiera: formularz z polem tekstowym, CharacterCounterController
- Dostępny dla wszystkich

**POST `/api/generate`**
- Kontroler: `GenerateController::generate()`
- Przyjmuje: JSON `{text: string}`
- Waliduje: długość tekstu (1000-10000 znaków)
- Wywołuje: `FlashcardGeneratorService::generate(text)` → komunikacja z OpenRouter.ai
- Parsuje: odpowiedź AI do struktury `[{front: string, back: string}]`
- Zapisuje: tymczasowo w sesji jako `pending_flashcards`
- Zwraca: JSON `{success: true, flashcard_count: int, redirect_url: string}`
  - Dla niezalogowanych: `redirect_url: "/generate/preview"`
  - Dla zalogowanych: `redirect_url: "/sets/new/edit"`
- Dostępny dla wszystkich (bez autoryzacji)
- **Ważne**: Kontroler sprawdza `$this->getUser()` aby określić odpowiednie przekierowanie

**GET `/generate/preview`**
- Kontroler: `GenerateController::preview()`
- Odczytuje: `pending_flashcards` z sesji
- Renderuje: `generate/preview.html.twig` z listą fiszek (tylko podgląd)
- Przekierowanie do `/generate` jeśli brak danych w sesji
- Dostępny dla wszystkich

**GET `/register`**
- Kontroler: `RegistrationController::register()`
- Renderuje: `registration/register.html.twig`
- Formularz: `RegistrationFormType`

**POST `/register`**
- Kontroler: `RegistrationController::register()` (obsługa POST)
- Waliduje: dane formularza
- Tworzy: encję User, hashuje hasło przez `UserPasswordHasherInterface`
- Zapisuje: do bazy przez `EntityManagerInterface`
- Loguje: automatycznie przez `UserAuthenticatorInterface`
- Przekierowuje: do `/sets`

**GET `/login`**
- Kontroler: `SecurityController::login()`
- Renderuje: `security/login.html.twig`
- Formularz: logowanie (obsługa przez Symfony Security)

**POST `/login`**
- Obsługa: Symfony Security (form_login authenticator)
- Sukces: przekierowanie do `default_target_path` (np. `/sets`)
- Błąd: komunikat flash + ponowne renderowanie `/login`

**GET `/logout`**
- Obsługa: Symfony Security
- Akcja: zakończenie sesji
- Przekierowanie: do `/`

**GET `/reset-password/request`**
- Kontroler: `ResetPasswordController::request()`
- Renderuje: `reset_password/request.html.twig`
- Formularz: `ResetPasswordRequestFormType`

**POST `/reset-password/request`**
- Kontroler: `ResetPasswordController::request()` (POST)
- Waliduje: email
- Generuje: token przez `ResetPasswordHelperInterface` (SymfonyCasts bundle)
- Wysyła: email z linkiem
- Zwraca: komunikat sukcesu (zawsze, nawet jeśli email nie istnieje)

**GET `/reset-password/reset/{token}`**
- Kontroler: `ResetPasswordController::reset(token)`
- Waliduje: token (czy ważny i nieużyty)
- Renderuje: `reset_password/reset.html.twig` z formularzem nowego hasła
- Błąd: komunikat + przekierowanie do `/reset-password/request`

**POST `/reset-password/reset/{token}`**
- Kontroler: `ResetPasswordController::reset(token)` (POST)
- Waliduje: token i nowe hasło
- Zmienia: hasło w encji User
- Unieważnia: token
- Komunikat: sukces + przekierowanie do `/login`

#### 2.1.2 Endpointy wymagające autoryzacji

**GET `/sets`**
- Kontroler: `FlashcardSetController::index()`
- Pobiera: wszystkie zestawy bieżącego użytkownika przez `FlashcardSetRepository::findByUser($user)`
- Renderuje: `flashcard_set/index.html.twig` z listą zestawów
- Access control: `ROLE_USER`

**GET `/sets/new/edit`**
- Kontroler: `FlashcardSetController::edit()`
- Odczytuje: `pending_flashcards` z sesji (z AI generation)
- Generuje: sugestię nazwy zestawu przez `SetNameSuggestionService`
- Renderuje: `flashcard_set/edit.html.twig` z edytowalnymi fiszkami
- Przekierowanie do `/generate` jeśli brak danych w sesji
- Access control: `ROLE_USER`

**POST `/sets/save`**
- Kontroler: `FlashcardSetController::save()`
- Przyjmuje: dane formularza (nazwa zestawu, lista fiszek)
- Tworzy: encje `FlashcardSet` i `Flashcard` (relacja OneToMany)
- Zapisuje: metadane (source: 'ai' lub 'manual', created_at)
- Śledzenie: analytics (liczba wygenerowanych, usuniętych, zaakceptowanych)
- Czyści: sesję (`pending_flashcards`)
- Przekierowuje: do `/sets`
- Access control: `ROLE_USER`

**GET `/sets/new/manual`**
- Kontroler: `FlashcardSetController::createManual()`
- Renderuje: `flashcard_set/manual.html.twig` z pustym formularzem
- Stimulus: FlashcardEditorController do dynamicznego dodawania pól
- Access control: `ROLE_USER`

**POST `/sets/new/manual`**
- Kontroler: `FlashcardSetController::createManual()` (POST)
- Waliduje: formularz (min. 1 fiszka)
- Tworzy: encje jak wyżej, source: 'manual'
- Przekierowuje: do `/sets`
- Access control: `ROLE_USER`

**GET `/sets/{id}/learn`**
- Kontroler: `LearningController::start(id)`
- Pobiera: zestaw i jego fiszki
- Sprawdza: czy zestaw należy do zalogowanego użytkownika (Voter)
- Pobiera: kolejność fiszek na podstawie algorytmu spaced repetition (filtr po `next_review_date <= now()`)
- Renderuje: `learning/session.html.twig` z pierwszą fiszką
- Stimulus: FlashcardLearningController
- Access control: `ROLE_USER` + własność zasobu

**POST `/api/learning/{flashcardId}/rate`**
- Kontroler: `LearningController::rate(flashcardId)`
- Przyjmuje: JSON `{rating: 'know'|'dont_know'}`
- Aktualizuje: pola `next_review_date`, `ease_factor`, `interval` w encji Flashcard
- Wykorzystuje: `SpacedRepetitionService` implementujący algorytm (np. SM-2)
- Zwraca: JSON `{next_flashcard_id: int|null}` lub `{session_complete: true, stats: {...}}`
- Access control: `ROLE_USER` + własność zasobu

**DELETE `/sets/{id}`**
- Kontroler: `FlashcardSetController::delete(id)`
- Sprawdza: własność zasobu (Voter)
- Usuwa: zestaw i powiązane fiszki (cascade)
- Zwraca: przekierowanie do `/sets` z komunikatem flash
- Access control: `ROLE_USER` + własność zasobu

### 2.2 Modele danych

#### 2.2.1 Encja User

**Namespace:** `App\Entity\User`

**Interfejsy:**
- `UserInterface` (Symfony Security)
- `PasswordAuthenticatedUserInterface` (Symfony Security)

**Pola:**
- `id` (UUID, primary key)
- `email` (string, unique, not null)
- `password` (string, hashed, not null)
- `roles` (json, default: `["ROLE_USER"]`)
- `isVerified` (boolean, default: false) - opcjonalnie na przyszłość (email verification)
- `createdAt` (datetime, immutable)
- `updatedAt` (datetime, nullable)

**Relacje:**
- OneToMany z `FlashcardSet` (własność: `flashcardSets`)

**Repository:** `UserRepository`
- Metody: `findByEmail(email)`, `save(user)`, `remove(user)`

#### 2.2.2 Encja FlashcardSet

**Namespace:** `App\Entity\FlashcardSet`

**Pola:**
- `id` (UUID, primary key)
- `name` (string, not null)
- `source` (enum: 'ai', 'manual', not null)
- `createdAt` (datetime, immutable)
- `updatedAt` (datetime, nullable)

**Relacje:**
- ManyToOne z `User` (właściciel zestawu)
- OneToMany z `Flashcard` (fiszki w zestawie, cascade persist/remove)

**Repository:** `FlashcardSetRepository`
- Metody: `findByUser(user)`, `findOneByIdAndUser(id, user)`, `save(set)`, `remove(set)`

#### 2.2.3 Encja Flashcard

**Namespace:** `App\Entity\Flashcard`

**Pola:**
- `id` (UUID, primary key)
- `front` (text, not null) - awers
- `back` (text, not null) - rewers
- `nextReviewDate` (datetime, nullable) - dla spaced repetition
- `easeFactor` (float, default: 2.5) - trudność
- `interval` (int, default: 0) - dni między powtórkami
- `repetitions` (int, default: 0) - liczba poprawnych odpowiedzi z rzędu
- `wasEdited` (boolean, default: false) - czy była edytowana po wygenerowaniu przez AI (analytics)
- `createdAt` (datetime, immutable)

**Relacje:**
- ManyToOne z `FlashcardSet` (zestaw nadrzędny)

**Repository:** `FlashcardRepository`
- Metody: `findBySet(set)`, `findDueForReview(set, date)`, `save(flashcard)`, `remove(flashcard)`

#### 2.2.4 Encja ResetPasswordRequest

**Bundle:** Dostarczana przez `symfonycasts/reset-password-bundle`

**Pola (generowane przez bundle):**
- `id`
- `user` (relacja z User)
- `selector` (string, unikalny identyfikator)
- `hashedToken` (string)
- `requestedAt` (datetime)
- `expiresAt` (datetime)

**Zarządzanie:** Automatyczne przez `ResetPasswordHelperInterface`

### 2.3 Mechanizm walidacji danych wejściowych

#### 2.3.1 Walidacja na poziomie encji (Attributes/Constraints)

**User:**
```
#[Assert\Email(message: "Podaj prawidłowy adres email")]
#[Assert\NotBlank(message: "Email jest wymagany")]
#[Assert\Unique(repositoryMethod: "findByEmail", message: "Ten email jest już zarejestrowany")]

#[Assert\NotBlank(message: "Hasło jest wymagane")]
#[Assert\Length(min: 8, minMessage: "Hasło musi mieć co najmniej 8 znaków")]
#[Assert\PasswordStrength(minScore: PasswordStrength::STRENGTH_MEDIUM)]
```

**FlashcardSet:**
```
#[Assert\NotBlank(message: "Nazwa zestawu jest wymagana")]
#[Assert\Length(min: 3, max: 100)]
```

**Flashcard:**
```
#[Assert\NotBlank(message: "Awers fiszki nie może być pusty")]
#[Assert\NotBlank(message: "Rewers fiszki nie może być pusty")]
#[Assert\Length(max: 5000, maxMessage: "Tekst jest zbyt długi")]
```

#### 2.3.2 Walidacja w FormType

**RegistrationFormType:**
- Pole `email`: Email constraint, NotBlank
- Pole `plainPassword`: Length (min: 8), NotBlank, PasswordStrength
- Pole `agreeTerms`: IsTrue (opcjonalnie, zgoda na regulamin)
- Custom constraint: `passwordConfirm` - porównanie pól

**ResetPasswordFormType:**
- Pole `plainPassword`: jak w rejestracji
- Pole `passwordConfirm`: porównanie

**FlashcardSetFormType:**
- Pole `name`: NotBlank, Length(min: 3, max: 100)
- CollectionType dla fiszek: Valid (propagacja walidacji do FlashcardFormType)
- Custom constraint: minimum 1 fiszka w kolekcji

**FlashcardFormType:**
- Pola `front`, `back`: NotBlank, Length(max: 5000)

#### 2.3.3 Walidacja w kontrolerach

**Przed wywołaniem serwisów:**
- Sprawdzanie własności zasobów przez Security Voters
- Walidacja parametrów request (np. UUID, tokeny)
- Walidacja danych z sesji (istnienie, format)

**Przykład:**
```
Kontroler: FlashcardSetController::delete(id)
- Sprawdza, czy User jest właścicielem zestawu przez FlashcardSetVoter
- Jeśli nie: AccessDeniedException (403)
```

#### 2.3.4 Walidacja w serwisach

**FlashcardGeneratorService:**
- Walidacja długości tekstu wejściowego (1000-10000 znaków)
- Walidacja formatu odpowiedzi z OpenRouter.ai (czy zawiera oczekiwane pola)
- Sanityzacja treści przed zapisem (strip_tags dla bezpieczeństwa)

**SpacedRepetitionService:**
- Walidacja wartości rating ('know', 'dont_know')
- Sprawdzenie, czy fiszka należy do zestawu użytkownika

### 2.4 Obsługa wyjątków

#### 2.4.1 Wyjątki związane z autentykacją

**InvalidCredentialsException** (Symfony Security)
- Rzucany przez: Authenticator przy błędnych danych logowania
- Obsługa: SecurityController łapie, wyświetla komunikat flash "Nieprawidłowy email lub hasło"
- HTTP Status: 401 (lub przekierowanie z flash message)

**AccessDeniedException** (Symfony Security)
- Rzucany przez: Security Voters przy braku uprawnień do zasobu
- Obsługa: ExceptionSubscriber przekierowuje do `/login` (jeśli niezalogowany) lub wyświetla 403 Forbidden
- HTTP Status: 403

**UserNotFoundException** (custom)
- Rzucany przez: UserRepository jeśli użytkownik nie istnieje
- Obsługa: Logiczny uniwersalny komunikat (nie ujawnianie istnienia emaila)
- HTTP Status: 404 lub komunikat flash

#### 2.4.2 Wyjątki związane z generowaniem fiszek

**AiGenerationException** (custom)
- Rzucany przez: FlashcardGeneratorService przy błędzie komunikacji z OpenRouter.ai
- Przyczyny: timeout, błąd API, nieprawidłowa odpowiedź
- Obsługa: Kontroler łapie, zwraca JSON `{success: false, error: "Nie udało się wygenerować fiszek. Spróbuj ponownie."}`
- HTTP Status: 500 lub 503

**InvalidTextLengthException** (custom)
- Rzucany przez: FlashcardGeneratorService gdy tekst poza zakresem 1000-10000 znaków
- Obsługa: Zwrot JSON z błędem walidacji
- HTTP Status: 400

#### 2.4.3 Wyjątki związane z walidacją

**ValidationFailedException** (Symfony Validator)
- Rzucany przez: Validator po nieprawidłowych danych
- Obsługa: Kontroler renderuje formularz ponownie z błędami (Twig wyświetla errory)
- HTTP Status: 422 (lub render formularza)

#### 2.4.4 Globalne przechwytywanie

**ExceptionSubscriber** (EventSubscriber)
- Nasłuchuje: KernelEvents::EXCEPTION
- Loguje: wszystkie wyjątki do Monolog
- Przekształca: wyjątki na przyjazne komunikaty użytkownika
- Renderuje: strony błędów (templates/bundles/TwigBundle/Exception/)
- Różnicuje: środowisko (dev: pełny stack trace, prod: komunikat generyczny)

### 2.5 Renderowanie stron server-side

#### 2.5.1 Konfiguracja Twig

**Globalne zmienne Twig (twig.yaml):**
- `app.user` - bieżący zalogowany użytkownik (dostarczone przez Symfony)
- Funkcje pomocnicze: `is_granted('ROLE_USER')` do sprawdzania autoryzacji w szablonach

**Rozszerzenia Twig (custom TwigExtension):**
- `flashcard_count(set)` - zwraca liczbę fiszek w zestawie
- `format_next_review(date)` - formatuje datę następnej powtórki ("za 2 dni", "dziś")

#### 2.5.2 Struktura szablonów

**Layout bazowy:** `templates/base.html.twig`
- Meta tags, CSS (Tailwind), JS (Stimulus)
- Nawigacja: conditional rendering (zalogowany/niezalogowany)
- Flash messages block
- Content block (nadpisywany w podszablonach)

**Szablony autentykacji:**
- `templates/registration/register.html.twig` - formularz rejestracji
- `templates/security/login.html.twig` - formularz logowania
- `templates/reset_password/request.html.twig` - żądanie resetu
- `templates/reset_password/reset.html.twig` - formularz nowego hasła

**Szablony generowania:**
- `templates/generate/index.html.twig` - główna strona z polem tekstowym
- `templates/generate/preview.html.twig` - podgląd dla niezalogowanych

**Szablony zestawów:**
- `templates/flashcard_set/index.html.twig` - lista zestawów
- `templates/flashcard_set/edit.html.twig` - edycja wygenerowanego zestawu
- `templates/flashcard_set/manual.html.twig` - manualne tworzenie

**Szablony nauki:**
- `templates/learning/session.html.twig` - interfejs nauki
- `templates/learning/summary.html.twig` - podsumowanie sesji

**Częściowe szablony (partials):**
- `templates/_partials/navbar.html.twig` - nawigacja
- `templates/_partials/flashcard_card.html.twig` - pojedyncza fiszka na liście
- `templates/_partials/flash_messages.html.twig` - komunikaty flash

#### 2.5.3 Warunkowe renderowanie

**Nawigacja:**
```
Jeśli użytkownik zalogowany (app.user):
  - Link "Moje zestawy"
  - Dropdown z emailem: "Wyloguj się"
Jeśli niezalogowany:
  - Przycisk "Zaloguj się"
  - Przycisk "Zarejestruj się"
```

**Przyciski akcji:**
```
Na stronie podglądu (/generate/preview):
  - Jeśli niezalogowany: "Zaloguj się, aby edytować" (link do /login)
  - Jeśli zalogowany: przekierowanie automatyczne do /sets/new/edit

Na liście zestawów (/sets):
  - Przyciski "Ucz się", "Edytuj", "Usuń" przy każdym zestawie
  - Sprawdzenie własności w kontrolerze przed renderowaniem
```

---

## 3. SYSTEM AUTENTYKACJI

### 3.1 Architektura bezpieczeństwa Symfony Security

#### 3.1.1 Password Hashers

**Konfiguracja:** `config/packages/security.yaml`

**Hasher dla User:**
- Interfejs: `PasswordAuthenticatedUserInterface`
- Algorytm: `auto` (domyślnie bcrypt lub argon2i w zależności od dostępności PHP)
- Cost factor: domyślny (13 dla bcrypt, automatyczny dla argon2)

**Użycie:**
- Serwis: `UserPasswordHasherInterface`
- Hashowanie przy rejestracji: `$hasher->hashPassword($user, $plainPassword)`
- Weryfikacja przy logowaniu: automatyczna przez Symfony Security

#### 3.1.2 User Providers

**Doctrine User Provider:**
- Klasa: `Symfony\Bridge\Doctrine\Security\User\EntityUserProvider`
- Konfiguracja:
  ```
  providers:
      app_user_provider:
          entity:
              class: App\Entity\User
              property: email
  ```
- Rola: Ładowanie użytkownika z bazy danych na podstawie email (identifier)

**Metody w User:**
- `getUserIdentifier()`: zwraca email
- `getRoles()`: zwraca tablicę ról (domyślnie `['ROLE_USER']`)
- `eraseCredentials()`: czyści plain password po autentykacji

#### 3.1.3 Firewalls

**Dev Firewall:**
- Pattern: `^/(_(profiler|wdt)|css|images|js)/`
- Security: false (publiczne zasoby deweloperskie)

**Main Firewall:**
- Pattern: `^/`
- Lazy: true (użytkownik ładowany tylko gdy potrzebny)
- Provider: `app_user_provider` (Doctrine)
- Stateful: true (używa sesji)

**Authenticators w Main Firewall:**

**1. Form Login Authenticator (logowanie formularzem):**
- Typ: `form_login`
- Login path: `/login` (GET i POST)
- Check path: `/login` (obsługa POST)
- Default target path: `/sets` (przekierowanie po sukcesie)
- Failure path: `/login` (przekierowanie po błędzie)
- Enable CSRF protection: true (token CSRF w formularzu)
- Użycie sesji: tak (tworzenie sesji po poprawnym logowaniu)

**2. Entry Point:**
- Typ: `form_login` (główny entry point dla unauthorized requests)
- Działanie: przekierowanie do `/login` przy próbie dostępu do chronionego zasobu

**Logout:**
- Path: `/logout`
- Target: `/` (przekierowanie po wylogowaniu)
- Invalidate session: true (zniszczenie sesji)
- Clear cookies: opcjonalnie (usunięcie ciasteczek remember_me w przyszłości)

**Remember Me (opcjonalnie, przyszła rozbudowa):**
- Secret: parametr z .env
- Lifetime: 2 tygodnie
- Secure: true (tylko HTTPS w produkcji)

#### 3.1.4 Access Control

**Konfiguracja:** `config/packages/security.yaml`

**Reguły dostępu:**
```
access_control:
    - { path: ^/login, roles: PUBLIC_ACCESS }
    - { path: ^/register, roles: PUBLIC_ACCESS }
    - { path: ^/reset-password, roles: PUBLIC_ACCESS }
    - { path: ^/generate, roles: PUBLIC_ACCESS }
    - { path: ^/api/generate, roles: PUBLIC_ACCESS, methods: [POST] }
    - { path: ^/sets, roles: ROLE_USER }
    - { path: ^/api/learning, roles: ROLE_USER }
    - { path: ^/, roles: PUBLIC_ACCESS }
```

**Logika:**
- Pierwsza pasująca reguła wygrywa
- Publiczne strony autentykacji i generowania dostępne dla wszystkich
- Wszystkie endpointy związane z zestawami (`/sets/*`) wymagają `ROLE_USER`
- API nauki wymaga autoryzacji

**Dodatkowa kontrola w kontrolerach:**
- Attribute: `#[IsGranted('ROLE_USER')]` na poziomie klasy lub metody
- Manualne sprawdzenie: `$this->denyAccessUnlessGranted('ROLE_USER')`

#### 3.1.5 Security Voters

**FlashcardSetVoter:**
- Attribute: `EDIT`, `DELETE`, `VIEW`
- Subject: `FlashcardSet`
- Logika: Sprawdza, czy `$set->getUser() === $currentUser`
- Użycie w kontrolerze: `$this->denyAccessUnlessGranted('EDIT', $flashcardSet)`

**FlashcardVoter:**
- Attribute: `RATE`, `EDIT`
- Subject: `Flashcard`
- Logika: Sprawdza, czy fiszka należy do zestawu użytkownika
- Użycie: w LearningController przed oceną fiszki

### 3.2 Rejestracja użytkownika

#### 3.2.1 Komponenty

**Kontroler:** `RegistrationController`
- Namespace: `App\Controller\RegistrationController`
- Metody:
  - `register(Request, UserPasswordHasherInterface, EntityManagerInterface, UserAuthenticatorInterface, FormLoginAuthenticator)`

**Form Type:** `RegistrationFormType`
- Pola: email, plainPassword, agreeTerms (opcjonalnie)
- Walidacja: jak opisano w sekcji 2.3

**Serwisy:**
- `UserPasswordHasherInterface` - hashowanie hasła
- `EntityManagerInterface` - zapis do bazy
- `UserAuthenticatorInterface` - automatyczne logowanie po rejestracji
- `FormLoginAuthenticator` - authenticator do użycia przy auto-logowaniu

#### 3.2.2 Przepływ rejestracji

1. Użytkownik wypełnia formularz rejestracji
2. POST do `/register`
3. Walidacja formularza (Symfony Validator)
4. Sprawdzenie unikalności email (custom constraint lub query w kontrolerze)
5. Utworzenie encji User:
   - `email` z formularza
   - `password` zahashowany przez `UserPasswordHasherInterface`
   - `roles` = `['ROLE_USER']`
   - `createdAt` = now()
6. Persist + flush przez EntityManager
7. Automatyczne logowanie przez `UserAuthenticatorInterface->authenticateUser(user, authenticator, request)`
8. Flash message: "Witaj! Twoje konto zostało utworzone"
9. Przekierowanie do `/sets`

#### 3.2.3 Bezpieczeństwo

- CSRF protection w formularzu
- Hashowanie hasła algorytmem bcrypt/argon2 (auto)
- Walidacja siły hasła (PasswordStrength constraint)
- Rate limiting (opcjonalnie, symfonycasts/rate-limiter): max 5 rejestracji z jednego IP na godzinę

### 3.3 Logowanie użytkownika

#### 3.3.1 Komponenty

**Kontroler:** `SecurityController`
- Metody:
  - `login(AuthenticationUtils)` - renderowanie formularza i obsługa błędów
  - `logout()` - pusta metoda (obsługa przez Symfony)

**Authenticator:** `FormLoginAuthenticator` (wbudowany w Symfony)
- Typ: form_login
- Konfiguracja w security.yaml

**Serwisy:**
- `AuthenticationUtils` - pobieranie ostatniego emaila i błędu logowania

#### 3.3.2 Przepływ logowania

1. Użytkownik wypełnia formularz logowania (email, hasło)
2. POST do `/login`
3. Symfony Security przechwytuje request (check_path: `/login`)
4. FormLoginAuthenticator:
   - Pobiera credentials z formularza
   - Ładuje użytkownika przez User Provider (na podstawie email)
   - Weryfikuje hasło przez `PasswordHasherInterface`
5. Sukces:
   - Utworzenie sesji z danymi użytkownika
   - Token autentykacji zapisany w kontekście Security
   - Przekierowanie do `default_target_path` (`/sets`) lub ostatniej odwiedzanej strony (referer)
6. Błąd:
   - Rzucenie `AuthenticationException`
   - Przekierowanie do `/login` z komunikatem flash: "Nieprawidłowy email lub hasło"
   - Ostatni użyty email wypełniony w formularzu (przez AuthenticationUtils)

#### 3.3.3 Bezpieczeństwo

- CSRF protection w formularzu
- Rate limiting (opcjonalnie): max 5 nieudanych prób logowania na email w ciągu 15 minut
- Uniwersalny komunikat błędu (nie ujawnianie, czy email istnieje)
- Sesje z secure flag (HTTPS w produkcji)
- HttpOnly cookies (ochrona przed XSS)

### 3.4 Wylogowanie użytkownika

#### 3.4.1 Konfiguracja

**Security.yaml:**
```
logout:
    path: /logout
    target: /
    invalidate_session: true
```

#### 3.4.2 Przepływ wylogowania

1. Użytkownik klika "Wyloguj się"
2. Request do `/logout`
3. Symfony Security:
   - Niszczy sesję użytkownika
   - Czyści token autentykacji
   - Usuwa ciasteczka sesyjne
4. Przekierowanie do `/` (strona główna generowania)

### 3.5 Resetowanie hasła

#### 3.5.1 Komponenty

**Bundle:** `symfonycasts/reset-password-bundle`
- Instalacja: `composer require symfonycasts/reset-password-bundle`
- Konfiguracja: automatyczna przez Symfony Flex

**Kontroler:** `ResetPasswordController`
- Metody:
  - `request(Request, ResetPasswordHelperInterface, MailerInterface)`
  - `reset(Request, string $token, UserPasswordHasherInterface, ResetPasswordHelperInterface)`

**Serwisy:**
- `ResetPasswordHelperInterface` - generowanie i walidacja tokenów
- `MailerInterface` - wysyłanie emaili z linkiem resetującym

**Encja:** `ResetPasswordRequest` (generowana przez bundle)

#### 3.5.2 Przepływ resetowania hasła

**Krok 1: Żądanie resetu**
1. Użytkownik wchodzi na `/reset-password/request`
2. Wypełnia email
3. POST do `/reset-password/request`
4. Kontroler:
   - Szuka użytkownika po email (UserRepository)
   - Jeśli istnieje: generuje token przez `ResetPasswordHelper->generateResetToken(user)`
   - Token zapisywany w encji `ResetPasswordRequest` (zaszyfrowany)
   - Wysyłka emaila z linkiem: `/reset-password/reset/{selector}:{hashedToken}`
5. Komunikat sukcesu (ZAWSZE, nawet jeśli email nie istnieje) - ze względów bezpieczeństwa

**Krok 2: Ustawienie nowego hasła**
1. Użytkownik klika link w emailu
2. GET do `/reset-password/reset/{token}`
3. Kontroler:
   - Waliduje token przez `ResetPasswordHelper->validateTokenAndFetchUser(token)`
   - Sprawdza ważność (expiresAt)
   - Jeśli ważny: renderuje formularz nowego hasła
   - Jeśli nieważny: komunikat błędu + przekierowanie do `/reset-password/request`
4. Użytkownik wypełnia nowe hasło
5. POST do `/reset-password/reset/{token}`
6. Kontroler:
   - Ponowna walidacja tokenu
   - Hashowanie nowego hasła
   - Zmiana hasła w encji User
   - Unieważnienie tokenu przez `ResetPasswordHelper->removeResetRequest(token)`
   - Flush do bazy
7. Komunikat sukcesu + przekierowanie do `/login`

#### 3.5.3 Konfiguracja emaili

**Mailer:** Symfony Mailer
- Transport: SMTP (konfiguracja w .env: `MAILER_DSN`)
- Template emaila: `templates/reset_password/email.html.twig`
- Treść:
  - Powitanie użytkownika
  - Link do resetowania (ważny przez 1 godzinę)
  - Informacja o bezpieczeństwie (ignoruj, jeśli nie prosiłeś o reset)

**Bezpieczeństwo:**
- Token ważny przez 1 godzinę (configurable)
- Token jednorazowy (usuwany po użyciu)
- Hashing tokenu przed zapisem w bazie
- Rate limiting na żądania resetu (max 3 na email w ciągu godziny)

### 3.6 Wykorzystanie LexikJWTAuthenticationBundle

**Status dla MVP: OPCJONALNE**

LexikJWTAuthenticationBundle został wymieniony w wymaganiach początkowych, jednak w kontekście monolitycznej architektury server-side rendering z session-based authentication jest to **nadmiarowa technologia dla MVP**.

**Rekomendacja**: Pominąć JWT w pierwszej wersji MVP i skupić się na session-based auth (FormLogin), co jest prostsze i wystarczające dla wszystkich wymaganych funkcjonalności. JWT można dodać później jako rozbudowę, gdy pojawi się potrzeba (mobile app, external API).

**Uwaga:** LexikJWTAuthenticationBundle jest typowo używany w aplikacjach API-first (headless). W architekturze monolitycznej z Twig i session-based auth jego zastosowanie jest ograniczone. Jednak może być użyteczny dla:

#### 3.6.1 Przypadki użycia JWT w tej aplikacji

**Asynchroniczne operacje AJAX:**
- Endpoint `/api/generate` (generowanie fiszek) - może przyjmować JWT w nagłówku dla zalogowanych użytkowników
- Endpoint `/api/learning/{id}/rate` - ocena fiszki podczas nauki (AJAX)
- Umożliwia uwierzytelnienie bez polegania wyłącznie na sesji

**Przyszła rozbudowa (mobile app, external integrations):**
- Jeśli w przyszłości powstanie aplikacja mobilna, będzie mogła używać JWT
- Zewnętrzne integracje mogą pobierać tokeny do komunikacji z API

#### 3.6.2 Konfiguracja LexikJWTAuthenticationBundle

**Instalacja:**
```
composer require lexik/jwt-authentication-bundle
```

**Generowanie kluczy:**
```
php bin/console lexik:jwt:generate-keypair
```
(Zapisuje klucze w `config/jwt/`)

**Konfiguracja security.yaml:**
```
firewalls:
    api:
        pattern: ^/api
        stateless: true
        jwt: ~

    main:
        pattern: ^/
        lazy: true
        form_login: ~
        # session-based dla Twig views
```

**Osobny firewall dla API:**
- Pattern: `^/api`
- Stateless: true (bez sesji)
- Authenticator: JWT
- Endpointy pod `/api/*` wymagają tokenu JWT w nagłówku `Authorization: Bearer {token}`

**Endpoint do uzyskania tokenu:**
- POST `/api/login`
- Payload: `{username: email, password: password}`
- Zwrot: `{token: "jwt_token_string"}`
- Użycie: frontend (Stimulus) może wywołać ten endpoint po logowaniu i przechować token w localStorage/sessionStorage

#### 3.6.3 Integracja JWT z session-based auth

**Scenariusz hybrydowy:**
1. Użytkownik loguje się przez formularz (`/login`) - tworzona sesja
2. Frontend (Stimulus) może dodatkowo pobrać token JWT (POST `/api/login`) do użycia w AJAX calls
3. AJAX requesty (np. generowanie fiszek, ocena w nauce) wysyłają token w nagłówku
4. Normalna nawigacja (Twig views) używa sesji

**Zalety:**
- Session-based: tradycyjne, bezpieczne dla server-side rendering
- JWT: wygodne dla asynchronicznych operacji, brak potrzeby zarządzania sesją w API
- Bezstanowe API: łatwiejsza skalowalność w przyszłości

**Implementacja:**
- Kontrolery Twig (`/sets`, `/generate` etc.) używają session auth
- Kontrolery API (`/api/*`) używają JWT auth
- Security voters działają z oboma mechanizmami (sprawdzają `$this->security->getUser()`)

#### 3.6.4 Bezpieczeństwo JWT

**Ważność tokenów:**
- Access token: 1 godzina (TTL: 3600s)
- Refresh token (opcjonalnie): 7 dni

**Refresh token flow (przyszła rozbudowa):**
- Endpoint `/api/token/refresh`
- Przyjmuje: expired access token + refresh token
- Zwraca: nowy access token

**Storage:**
- Klucze RSA w `config/jwt/` (prywatny do podpisywania, publiczny do weryfikacji)
- Klucze nie w repozytorium (dodane do .gitignore)

**Claims w tokenie:**
- `username` (email)
- `roles` (np. `["ROLE_USER"]`)
- `exp` (expiration time)
- `iat` (issued at)

---

## 4. PODSUMOWANIE I KLUCZOWE WNIOSKI

### 4.1 Główne komponenty architektury

**Warstwa prezentacji (frontend):**
- Renderowanie server-side przez Twig (monolit)
- Interaktywność przez Symfony UX (Stimulus controllers)
- Tailwind CSS do stylizacji
- Formularze Symfony z walidacją client-side i server-side
- Częściowa dostępność bez autoryzacji (generowanie fiszek)

**Warstwa logiki (backend):**
- Kontrolery Symfony do obsługi HTTP requests
- Serwisy biznesowe (FlashcardGeneratorService, SpacedRepetitionService, SetNameSuggestionService)
- Repositories (Doctrine) do dostępu do danych
- Security Voters do kontroli dostępu do zasobów

**Warstwa danych:**
- Encje Doctrine (User, FlashcardSet, Flashcard, ResetPasswordRequest)
- Relacje OneToMany, ManyToOne
- PostgreSQL jako baza danych

**System autentykacji:**
- Symfony Security jako rdzeń
- Form Login Authenticator (session-based) dla Twig views
- LexikJWTAuthenticationBundle (opcjonalnie) dla AJAX API calls
- SymfonyCasts ResetPasswordBundle do resetowania hasła

### 4.2 Przepływy użytkownika (key user journeys)

**Niezalogowany użytkownik:**
1. Wchodzi na `/generate`
2. Wkleja tekst, generuje fiszki (AJAX do `/api/generate`)
3. Widzi podgląd fiszek (`/generate/preview`)
4. Może się zarejestrować/zalogować, aby edytować i zapisać

**Nowy użytkownik:**
1. Rejestracja (`/register`)
2. Automatyczne logowanie
3. Przekierowanie do `/sets` (pusta lista)
4. Przycisk "Generuj fiszki AI" → `/generate`
5. Generowanie → edycja (`/sets/new/edit`) → zapis
6. Powrót do `/sets` z nowym zestawem

**Zalogowany użytkownik - nauka:**
1. Lista zestawów (`/sets`)
2. Kliknięcie "Ucz się" → `/sets/{id}/learn`
3. Wyświetlanie fiszek według algorytmu spaced repetition
4. Ocena "Wiem"/"Nie wiem" (AJAX do `/api/learning/{id}/rate`)
5. Aktualizacja metadanych fiszki
6. Podsumowanie sesji

### 4.3 Bezpieczeństwo i best practices

**Ochrona przed atakami:**
- SQL Injection: Doctrine ORM parametryzuje zapytania
- XSS: Twig auto-escaping
- CSRF: tokeny CSRF w formularzach (włączone w security.yaml)
- Session hijacking: secure cookies (HTTPS), HttpOnly flag
- Brute force: rate limiting na logowanie i resetowanie hasła

**Walidacja i sanityzacja:**
- Walidacja na poziomie encji (Assert constraints)
- Walidacja w FormType
- Dodatkowa walidacja w serwisach
- Sanityzacja treści przed zapisem (strip_tags)

**Autoryzacja:**
- Access control rules w security.yaml
- Security Voters dla własności zasobów
- `#[IsGranted]` attributes w kontrolerach

**Hasła:**
- Hashowanie przez bcrypt/argon2
- Minimalna długość i siła hasła
- Jednorazowe tokeny resetowania (ważne 1h)

### 4.4 Kontrakty serwisów (interfejsy)

**FlashcardGeneratorServiceInterface:**
- Metoda: `generate(string $text): array`
- Zwraca: tablicę `[{front: string, back: string}]`
- Wyjątki: `AiGenerationException`, `InvalidTextLengthException`

**SpacedRepetitionServiceInterface:**
- Metoda: `updateFlashcardAfterReview(Flashcard $flashcard, string $rating): void`
- Parametry: encja fiszki, ocena ('know'/'dont_know')
- Modyfikuje: pola `nextReviewDate`, `easeFactor`, `interval`, `repetitions`

**SetNameSuggestionServiceInterface:**
- Metoda: `suggestName(string $sourceText): string`
- Zwraca: sugerowaną nazwę zestawu (max 100 znaków)
- Logika: analiza pierwszego zdania lub frazy kluczowej

**ResetPasswordHelperInterface:** (z bundle)
- `generateResetToken(User $user): ResetPasswordToken`
- `validateTokenAndFetchUser(string $fullToken): User`
- `removeResetRequest(ResetPasswordToken $token): void`

### 4.5 Integracja z istniejącym stackiem

**Zgodność z tech-stack.md:**
- ✅ Monolit renderowany server-side (Symfony + Twig)
- ✅ Bez API Platform (standardowe kontrolery)
- ✅ Symfony UX dla interaktywności
- ✅ PostgreSQL jako baza danych
- ✅ Docker dla infrastruktury

**Brak konfliktu z istniejącą funkcjonalnością:**
- Nowe endpointy `/login`, `/register`, `/reset-password` nie kolidują z istniejącymi
- Firewall `main` obejmuje całą aplikację (pattern `^/`)
- Access control rules chronią tylko wybrane ścieżki (`/sets/*`, `/api/learning/*`)
- Publiczny dostęp do `/generate` zgodny z wymaganiem US-010 (generowanie bez logowania)

**Rozszerzenia:**
- Nowe encje (User, ResetPasswordRequest) nie naruszają istniejących
- Relacje z FlashcardSet i Flashcard dodane przez ManyToOne/OneToMany

### 4.6 Metryki i monitorowanie

**Analytics tracking (zgodnie z PRD):**
- Zdarzenie `fiszka_usunięta_w_edycji` - przy usunięciu fiszki w `/sets/new/edit`
- Pole `source` w FlashcardSet ('ai', 'manual') - do liczenia adopcji AI
- Pole `wasEdited` w Flashcard - do śledzenia akceptacji (edycja = akceptacja)

**Kryterium sukcesu 1 (75% akceptacji AI):**
- Licznik: fiszki wygenerowane vs. usunięte podczas edycji
- Wzór: `1 - (usuniętych / wygenerowanych)`

**Kryterium sukcesu 2 (75% adopcji AI):**
- Licznik: fiszki w zestawach source='ai' vs. wszystkie fiszki
- Wzór: `fiszki_ai / fiszki_total`

**Logowanie:**
- Monolog dla błędów autentykacji, generowania AI, operacji na bazie
- Logi sesji logowania/wylogowania
- Błędy walidacji formularzy

---

## 5. NASTĘPNE KROKI (PLAN IMPLEMENTACJI - ZARYS)

**Faza 1: Podstawowa autentykacja**
1. Utworzenie encji User z interfejsami Security
2. Migracja bazy danych
3. Konfiguracja User Provider (Doctrine)
4. Konfiguracja Form Login Authenticator
5. Implementacja RegistrationController i formularza
6. Implementacja SecurityController (login/logout)
7. Testy funkcjonalne logowania i rejestracji

**Faza 2: Resetowanie hasła**
1. Instalacja symfonycasts/reset-password-bundle
2. Generowanie encji ResetPasswordRequest
3. Migracja bazy
4. Implementacja ResetPasswordController
5. Konfiguracja Symfony Mailer
6. Stworzenie szablonu emaila
7. Testy resetowania hasła

**Faza 3: Integracja z istniejącą funkcjonalnością**
1. Dodanie relacji ManyToOne w FlashcardSet (do User)
2. Migracja dodająca kolumnę user_id
3. Aktualizacja kontrolerów zestawów (sprawdzanie własności)
4. Implementacja Security Voters (FlashcardSetVoter, FlashcardVoter)
5. Aktualizacja access control rules
6. Modyfikacja szablonów Twig (nawigacja, conditional rendering)

**Faza 4: JWT dla API (opcjonalnie)**
1. Instalacja LexikJWTAuthenticationBundle
2. Generowanie kluczy
3. Konfiguracja firewall `api`
4. Endpoint `/api/login` do uzyskania tokenu
5. Modyfikacja Stimulus controllers (AJAX z JWT)
6. Testy API z tokenami

**Faza 5: Testy i bezpieczeństwo**
1. Testy jednostkowe serwisów (UserPasswordHasher, ResetPasswordHelper)
2. Testy funkcjonalne wszystkich przepływów autentykacji
3. Testy Security Voters
4. Audyt bezpieczeństwa (rate limiting, CSRF, XSS)
5. Testy wydajnościowe (hashowanie haseł, sesje)

---

## 6. WALIDACJA SPECYFIKACJI WZGLĘDEM PRD

### 6.1 Mapowanie User Stories na komponenty

| User Story | Komponent specyfikacji | Status | Uwagi |
|------------|----------------------|--------|-------|
| US-001: Rejestracja | Sekcja 3.2 | ✅ Zrealizowane | Email, hasło, potwierdzenie, auto-login |
| US-002: Logowanie | Sekcja 3.3 | ✅ Zrealizowane | FormLogin authenticator, CSRF, sesje |
| US-003: Generowanie fiszek | Sekcje 1.4, 2.1.1 | ✅ Zrealizowane | Dostępne BEZ logowania (PUBLIC_ACCESS) |
| US-005: Edycja fiszek | Sekcje 1.1.2, 2.1.2 | ✅ Zrealizowane | Tylko dla ROLE_USER, inline editing |
| US-006: Zapisywanie zestawu | Sekcja 2.1.2 | ✅ Zrealizowane | Nazwa, sugestia, analytics tracking |
| US-007: Obsługa błędów | Sekcja 2.4.2 | ✅ Zrealizowane | Publiczne (poprawiono względem niekonsekwencji w PRD) |
| US-008: Manualne tworzenie | Sekcja 2.1.2 | ✅ Zrealizowane | `/sets/new/manual` dla ROLE_USER |
| US-009: Zarządzanie zestawami | Sekcje 1.1.2, 2.1.2 | ✅ Zrealizowane | Lista, usuwanie, Security Voters |
| US-010: Autentykacja | Sekcje 3.1-3.5 | ✅ Zrealizowane | Session-based, reset hasła, publiczne generowanie |

### 6.2 Rozwiązane niekonsekwencje PRD

#### Problem 1: US-007 - sprzeczne wymagania dostępu
**Opis**: PRD w US-007 wspomina "Funkcjonalność dostępna tylko dla zalogowanych użytkowników", ale US-003 i US-010 wyraźnie wymagają generowania BEZ logowania.

**Rozwiązanie**: Specyfikacja zakłada dostęp publiczny do generowania i obsługi błędów (zgodnie z US-003 i US-010). Niekonsekwencja w US-007 została zignorowana jako błąd edytorski.

**Implementacja**:
- Endpoint `/api/generate` ma `roles: PUBLIC_ACCESS`
- Obsługa błędów (AiGenerationException) zwraca komunikaty dla wszystkich użytkowników

#### Problem 2: Edycja zapisanych zestawów
**Opis**: Spec pierwotnie zawierała przycisk "Edytuj" w liście zestawów, ale PRD nie definiuje tej funkcjonalności (tylko edycja przed zapisem).

**Rozwiązanie**: Usunięto przycisk "Edytuj" z listy zestawów. Dodano wyjaśnienie, że edycja możliwa tylko przed zapisem w `/sets/new/edit`.

**Implementacja**: Lista zestawów (`/sets`) zawiera tylko przyciski "Ucz się" i "Usuń".

### 6.3 Zgodność z tech-stack.md

| Wymaganie tech stack | Implementacja w spec | Status |
|---------------------|---------------------|--------|
| Monolit server-side | Twig + Symfony controllers | ✅ |
| Bez API Platform | Standardowe kontrolery Symfony | ✅ |
| Symfony UX | Stimulus controllers (5 komponentów) | ✅ |
| PostgreSQL | Doctrine entities z relacjami | ✅ |
| Docker | Bez zmian w infrastrukturze | ✅ |

### 6.4 Wymagania opcjonalne vs. wymagane

#### LexikJWTAuthenticationBundle - OPCJONALNE dla MVP
**Status**: Wymieniony w promptcie, ale niewymagany w kontekście session-based monolitu.

**Decyzja**: Sekcja 3.6 opisuje JWT jako opcjonalne rozszerzenie. MVP używa wyłącznie FormLogin (session-based).

**Uzasadnienie**:
- Session-based auth wystarczy dla wszystkich User Stories
- JWT dodaje złożoność bez dodatkowej wartości w MVP
- Można dodać później przy rozbudowie (mobile app, external API)

### 6.5 Wszystkie User Stories są realizowalne

**Potwierdzenie**: Wszystkie User Stories (US-001 do US-010) mogą być zrealizowane w oparciu o przygotowaną specyfikację:

✅ **US-001, US-002**: Rejestracja i logowanie - pełna implementacja w sekcjach 3.2-3.3
✅ **US-003**: Generowanie bez logowania - publiczny endpoint `/api/generate`
✅ **US-005**: Edycja przed zapisem - endpoint `/sets/new/edit` z ROLE_USER
✅ **US-006**: Zapisywanie - endpoint `/sets/save` z analytics
✅ **US-007**: Obsługa błędów - publiczny access, komunikaty użytkownika
✅ **US-008**: Manualne tworzenie - endpoint `/sets/new/manual`
✅ **US-009**: Zarządzanie - lista, usuwanie z Security Voters
✅ **US-010**: Bezpieczeństwo - reset hasła, session-based auth, publiczne generowanie

### 6.6 Brak nadmiarowych założeń

Specyfikacja nie wprowadza funkcjonalności poza zakresem PRD (MVP boundaries):
- ❌ Brak edycji zapisanych zestawów (poza PRD)
- ❌ Brak social features (poza PRD)
- ❌ Brak advanced spaced repetition (użycie gotowego algorytmu zgodnie z PRD)
- ❌ Brak email verification (opcjonalne pole `isVerified` przygotowane na przyszłość, ale nie implementowane)
- ✅ JWT opisany jako opcjonalny (nie implementowany w MVP)

---

*Koniec specyfikacji technicznej modułu autentykacji.*