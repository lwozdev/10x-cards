# Architektura UI dla Generator Fiszek AI

**Dokument:** Wysokopoziomowy plan architektury interfejsu użytkownika
**Wersja:** 1.0 (MVP)
**Data:** 2025-01-15
**Status:** Ready for implementation

## 1. Przegląd struktury UI

### Kontekst produktu
Generator Fiszek AI to aplikacja webowa dla uczniów szkół podstawowych i średnich, która automatyzuje proces tworzenia fiszek edukacyjnych przy użyciu sztucznej inteligencji. MVP koncentruje się wyłącznie na procesie **przygotowania fiszek** - generowaniu, edycji i zarządzaniu zestawami. Moduł nauki (spaced repetition) zostanie zrealizowany po MVP.

### Architektura techniczna
- **Wzorzec:** Monolityczna aplikacja renderowana po stronie serwera (SSR)
- **Backend:** Symfony 7.3 + Doctrine ORM + PostgreSQL 16
- **Frontend:** Twig templates + Symfony UX (Turbo Drive, Turbo Streams, Stimulus)
- **Styling:** Tailwind CSS (utility-first approach)
- **API:** Wewnętrzne endpointy JSON pod `/api/*` dla operacji XHR

### Kluczowe metryki sukcesu
1. **Jakość generowania AI:** 75% fiszek wygenerowanych przez AI jest akceptowanych przez użytkowników (nie usuniętych podczas edycji)
2. **Adopcja AI:** 75% wszystkich fiszek w systemie powstaje z wykorzystaniem generatora AI

### Struktura informacyjna aplikacji
Aplikacja składa się z trzech głównych obszarów funkcjonalnych:

1. **Obszar autentykacji** - rejestracja, logowanie, reset hasła
2. **Dashboard użytkownika** - lista zestawów, zarządzanie, nawigacja
3. **Obszar tworzenia** - generowanie AI lub manualne tworzenie, edycja fiszek

Nawigacja jest płaska (maksymalnie 2 kliknięcia do każdej funkcji) z jasną hierarchią i breadcrumb dla orientacji użytkownika.

---

## 2. Lista widoków

### 2.1 Widok: Rejestracja

**Ścieżka:** `/register`

**Główny cel:**
Umożliwienie nowym użytkownikom założenia konta w systemie przy użyciu adresu email i hasła.

**Kluczowe informacje do wyświetlenia:**
- Formularz rejestracji (email, hasło, potwierdzenie hasła)
- Komunikaty walidacji w czasie rzeczywistym
- Link do strony logowania dla użytkowników z istniejącym kontem

**Kluczowe komponenty widoku:**
- Centered card layout (max-width 400px na gradient background)
- Input fields z live validation (email format, password strength, password match)
- Password toggle (show/hide password)
- Submit button z loading state
- Link do logowania

**UX, dostępność i względy bezpieczeństwa:**
- **UX:** Progressive validation z debounce 500ms, wyświetlanie success checkmarks przy poprawnych wartościach, autocomplete attributes dla password managerów
- **Dostępność:** Semantic HTML, ARIA labels, keyboard navigation, focus indicators, error messages linked to fields
- **Bezpieczeństwo:** Client-side validation dla UX + server-side enforcement, CSRF token, minimum 8 znaków hasła, hash Argon2id, HTTPS only

---

### 2.2 Widok: Logowanie

**Ścieżka:** `/login`

**Główny cel:**
Umożliwienie zarejestrowanym użytkownikom zalogowania się do aplikacji.

**Kluczowe informacje do wyświetlenia:**
- Formularz logowania (email, hasło)
- Link do resetowania hasła
- Link do rejestracji dla nowych użytkowników
- Komunikaty błędów (nieprawidłowe dane logowania)

**Kluczowe komponenty widoku:**
- Centered card layout
- Input fields z autocomplete
- Password toggle
- "Zapomniałeś hasła?" link
- Submit button
- "Nie masz konta? Zarejestruj się" link

**UX, dostępność i względy bezpieczeństwa:**
- **UX:** Auto-focus na pierwszym polu, remember me option (opcjonalnie), redirect do dashboard po sukcesie
- **Dostępność:** Tab navigation, Enter to submit, clear error messaging
- **Bezpieczeństwo:** Session-based authentication, HttpOnly cookies, SameSite=Lax, rate limiting (post-MVP), generic error messages (przeciw user enumeration)

---

### 2.3 Widok: Reset hasła (Request)

**Ścieżka:** `/password/reset`

**Główny cel:**
Umożliwienie użytkownikowi zainicjowania procesu resetowania zapomnianego hasła.

**Kluczowe informacje do wyświetlenia:**
- Pole email
- Instrukcje ("Otrzymasz email z linkiem do resetowania")
- Komunikat sukcesu (zawsze 202, przeciw enumeracji)

**Kluczowe komponenty widoku:**
- Centered card
- Email input field
- Submit button
- Link powrotu do logowania

**UX, dostępność i względy bezpieczeństwa:**
- **UX:** Jasne instrukcje co się stanie, komunikat sukcesu nawet dla nieistniejących emaili
- **Dostępność:** Simple form, clear messaging
- **Bezpieczeństwo:** Always 202 response (przeciw user enumeration), token ważny 1h, single-use tokens

---

### 2.4 Widok: Reset hasła (Confirm)

**Ścieżka:** `/password/reset/confirm?token=xxx`

**Główny cel:**
Umożliwienie ustawienia nowego hasła przy użyciu tokenu z emaila.

**Kluczowe informacje do wyświetlenia:**
- Formularz nowego hasła (hasło, potwierdź hasło)
- Komunikat o wygaśnięciu tokenu (jeśli >1h)
- Password strength indicator

**Kluczowe komponenty widoku:**
- Centered card
- Password inputs z validation
- Password toggle
- Submit button

**UX, dostępność i względy bezpieczeństwa:**
- **UX:** Clear feedback na strength hasła, redirect do login z success message po zapisie
- **Dostępność:** Focus management, error handling
- **Bezpieczeństwo:** Token expiry (1h), minimum 8 chars, server-side validation

---

### 2.5 Widok: Dashboard ("Moje zestawy")

**Ścieżka:** `/sets`

**Główny cel:**
Główny ekran aplikacji po zalogowaniu - wyświetlanie listy wszystkich zestawów fiszek użytkownika oraz umożliwienie nawigacji do tworzenia nowych zestawów.

**Kluczowe informacje do wyświetlenia:**
- Lista zestawów fiszek (nazwa, liczba fiszek, data utworzenia/modyfikacji, źródło: AI/manual)
- Empty state dla nowych użytkowników (edukacyjna ilustracja + CTA)
- Sortowanie i wyszukiwanie
- Paginacja (20 zestawów na stronę)

**Kluczowe komponenty widoku:**
- Header z logo i przyciskiem "+ Nowy zestaw"
- Search bar i dropdown sortowania
- Grid kart zestawów (responsive: 1 kolumna mobile, 2-3 desktop)
- Każda karta: nazwa, ikona źródła (✨ AI / 📝 manual), liczba fiszek, data, akcje (Edytuj, Usuń)
- Empty state component (dla nowych użytkowników)
- Pagination component

**UX, dostępność i względy bezpieczeństwa:**
- **UX:** Empty state z silnymi CTA ("Wygeneruj fiszki z AI" primary, "Stwórz ręcznie" secondary), sortowanie default: updated_at DESC, search z debounce 500ms, optimistic delete z rollback przy błędzie, confirmation modal przed usunięciem
- **Dostępność:** Keyboard navigation w grid, ARIA labels na akcjach, screen reader announcements dla delete, focus management
- **Bezpieczeństwo:** RLS (Row-Level Security) zapewnia że użytkownik widzi tylko swoje zestawy, CSRF protection na delete action

---

### 2.6 Widok: Generowanie fiszek (AI)

**Ścieżka:** `/generate`

**Główny cel:**
Umożliwienie użytkownikowi wklejenia tekstu źródłowego (notatek) i wygenerowania zestawu fiszek przy użyciu AI.

**Kluczowe informacje do wyświetlenia:**
- Duże pole tekstowe (textarea)
- Licznik znaków z limitami (1000-10000)
- Visual progress bar
- Przycisk "Generuj fiszki" (enabled tylko gdy 1000-10000 znaków)
- Loading state podczas generowania (10-30s)
- Error states (timeout, validation, AI failure)

**Kluczowe komponenty widoku:**
- Textarea z auto-resize
- Character counter component (real-time, debounced 300ms)
- Visual progress bar (color-coded: red <1000, green 1000-10000, red >10000)
- Submit button z disabled state
- Loading skeleton z multi-stage feedback ("Analizuję tekst..." → "Tworzę fiszki...")
- Error modal z recovery options

**UX, dostępność i względy bezpieczeństwa:**
- **UX:** Real-time feedback na licznik znaków, disable button poza zakresem, multi-stage loading animations (symulowane etapy dla psychologicznego komfortu), timeout handling (>30s → error modal z sugestiami: skróć tekst, uprość język, usuń znaki specjalne), Turbo Streams transition do ekranu edycji po sukcesie
- **Dostępność:** ARIA live region dla licznika, keyboard shortcuts, screen reader feedback, timeout warnings
- **Bezpieczeństwo:** Server-side validation limitu znaków (mirrors DB check), CSRF protection, input sanitization, rate limiting (post-MVP: 5/min per user)

---

### 2.7 Widok: Edycja fiszek (po generowaniu AI)

**Ścieżka:** `/sets/new/edit`

**Główny cel:**
Umożliwienie użytkownikowi przejrzenia, edycji i usunięcia wygenerowanych fiszek przed finalnym zapisaniem zestawu.

**Kluczowe informacje do wyświetlenia:**
- Sticky header z kolapsowanym podglądem tekstu źródłowego (pierwsze ~100 znaków) + opcja "Pokaż cały tekst"
- Grid fiszek (responsive: 1 kolumna mobile, 2 kolumny desktop)
- Każda fiszka: numer, edytowalne pole PRZÓD, edytowalne pole TYŁ, przycisk usuń, indicator edycji (✏️ jeśli modyfikowana)
- Sticky footer z polem nazwy zestawu (pre-wypełniona sugestia AI ✨) + przycisk "Zapisz zestaw (N fiszek)"
- Przycisk "+ Dodaj własną fiszkę" (umożliwia mieszanie AI + manual)

**Kluczowe komponenty widoku:**
- Sticky header component (collapsible source text preview)
- Flashcard grid component (CSS Grid layout)
- Flashcard card component (inline editable textareas z auto-resize)
- Set name input component (real-time validation, duplicate check)
- Sticky footer z save button
- Add card button

**UX, dostępność i względy bezpieczeństwa:**
- **UX:** Inline editing bez modali (click to edit), auto-save do local state (Stimulus controller), visual indicator dla edytowanych kart, fade-out + slide-up animation przy usuwaniu, slide-down przy dodawaniu, beforeunload protection ("Masz niezapisane zmiany"), optional localStorage auto-save co 30s (TTL 24h), recovery prompt przy powrocie, debounced validation nazwy (500ms, check duplikatów via API)
- **Dostępność:** Tab navigation przez karty, Escape to cancel edit, ARIA live regions dla delete ("Fiszka usunięta. Pozostało N fiszek"), keyboard shortcuts (Ctrl+S to save), focus management
- **Bezpieczeństwo:** Client-side state management (wszystkie edycje lokalne do momentu save), single POST /api/sets przy zapisie, CSRF token, XSS prevention (Twig auto-escape), input validation (max 1000 chars per field)

---

### 2.8 Widok: Manualne tworzenie zestawu

**Ścieżka:** `/sets/new`

**Główny cel:**
Umożliwienie użytkownikowi stworzenia zestawu fiszek od zera bez użycia AI.

**Kluczowe informacje do wyświetlenia:**
- Pole nazwy zestawu
- Przycisk "+ Dodaj pierwszą fiszkę"
- Po dodaniu pierwszej fiszki → podobny layout jak edycja AI (grid kart)
- Sticky footer z save button

**Kluczowe komponenty widoku:**
- Set name input
- Flashcard grid (pojawia się po dodaniu pierwszej)
- Flashcard card component
- Add card button
- Sticky footer z save

**UX, dostępność i względy bezpieczeństwa:**
- **UX:** Progressive disclosure (najpierw nazwa, potem fiszki), jednolity UX z edycją AI (te same komponenty), beforeunload protection
- **Dostępność:** Focus flow, keyboard navigation
- **Bezpieczeństwo:** Identyczne jak widok edycji AI, wszystkie fiszki oznaczone origin: "manual"

---

### 2.9 Widok: Edycja istniejącego zestawu

**Ścieżka:** `/sets/{id}/edit`

**Główny cel:**
Umożliwienie edycji nazwy zestawu oraz dodawania/edycji/usuwania fiszek w już zapisanym zestawie.

**Kluczowe informacje do wyświetlenia:**
- Edytowalne pole nazwy
- Grid istniejących fiszek (z możliwością edycji i usunięcia)
- Przycisk "+ Dodaj fiszkę" (dla nowych manual cards)
- Informacja o źródle fiszek (AI vs manual, metadata)

**Kluczowe komponenty widoku:**
- Set name input
- Flashcard grid
- Flashcard card component
- Add card button
- Save button

**UX, dostępność i względy bezpieczeństwa:**
- **UX:** Różnica od /sets/new/edit: operacje są immediately persisted (PATCH/POST/DELETE per card) zamiast local state, optimistic UI updates z rollback, inline success/error toasts
- **Dostępność:** Identyczna jak pozostałe widoki edycji
- **Bezpieczeństwo:** Per-operation CSRF tokens, ownership verification (RLS), soft delete (deleted_at)

---

### 2.10 Widok: Error States (Global)

**Ścieżka:** Various (handled in-place lub dedykowane error pages)

**Główny cel:**
Zapewnienie użytkownikowi jasnej komunikacji o błędach i opcjach recovery.

**Kluczowe typy błędów:**
- **400/422 Validation:** Inline errors pod polami z konkretnymi komunikatami
- **401 Unauthorized:** Redirect do /login z message "Sesja wygasła. Zaloguj się ponownie."
- **409 Conflict:** Inline error "Masz już zestaw o tej nazwie"
- **500 Server Error:** Toast notification "Coś poszło nie tak. Odśwież stronę lub spróbuj ponownie."
- **504 Timeout (AI):** Full-screen modal z sugestiami recovery (skróć tekst, uprość, usuń znaki specjalne) + CTA "Spróbuj ponownie" / "Stwórz zestaw ręcznie"

**Kluczowe komponenty widoku:**
- Inline error messages (pod input fields)
- Toast notifications (auto-dismiss 3-5s)
- Error modals (dla critical errors)
- Dedicated error pages (404, 500)

**UX, dostępność i względy bezpieczeństwa:**
- **UX:** Error messages w prostym języku z konkretnymi akcjami recovery, pozytywny tone ("Spróbuj..." zamiast "Błąd"), kontekstowe sugestie
- **Dostępność:** ARIA live regions dla dynamic errors, focus management (error modal → first action button), screen reader announcements
- **Bezpieczeństwo:** Generic error messages dla auth (przeciw enumeration), nie ujawniać stack traces w production

---

## 3. Mapa podróży użytkownika

### 3.1 Podróż: Nowy użytkownik → Pierwszy zestaw AI

**Persona:** Uczeń liceum, pierwszy raz w aplikacji

**Krok 1: Landing & Registration**
- Wejście na stronę → automatyczne przekierowanie do `/register`
- Wypełnienie formularza (email, hasło)
- Submit → walidacja → sukces
- Auto-login → redirect do `/sets`

**Krok 2: Empty State**
- Dashboard wyświetla empty state:
  - Centralna ilustracja fiszek
  - Headline: "Stwórz swój pierwszy zestaw fiszek"
  - Instrukcja: "Wklej notatki (1000-10000 znaków), a AI stworzy dla Ciebie fiszki"
  - Primary CTA: "🤖 Wygeneruj fiszki z AI"
  - Secondary actions: "Stwórz ręcznie" | "Zobacz przykład"

**Krok 3: Generowanie**
- Click "Wygeneruj fiszki z AI" → redirect do `/generate`
- Wklejenie tekstu (np. 2450 znaków z notatek z biologii)
- Licznik znaków real-time: "2,450 znaków (minimum 1,000, maksimum 10,000)" → zielony kolor
- Przycisk "Generuj fiszki" aktywny
- Click → loading state (15s):
  - 0-7s: "Analizuję tekst..."
  - 8-15s: "Tworzę fiszki..."
- Sukces → Turbo Streams transition do `/sets/new/edit`

**Krok 4: Edycja**
- Ekran edycji z 15 wygenerowanymi fiszkami
- Sticky header: "Tekst źródłowy: Lorem ipsum dolor sit amet, consecte... [Pokaż cały tekst ▼]"
- Grid 2 kolumny (desktop) z kartami fiszek
- Użytkownik:
  - Edytuje fiszkę #3 (inline) → pojawia się ✏️ indicator
  - Usuwa fiszkę #7 (klik X) → fade-out animation → zmniejszenie licznika
  - Dodaje własną fiszkę manualną (klik "+ Dodaj własną fiszkę") → nowa karta slide-down
- Sticky footer:
  - Pole nazwy: "Biologia - Fotosynteza ✨ Zasugerowane przez AI"
  - Użytkownik akceptuje sugestię (lub modyfikuje)
  - Przycisk: "Zapisz zestaw (15 fiszek)"

**Krok 5: Save & Success**
- Click "Zapisz zestaw"
- Loading state (spinner w przycisku): "Zapisywanie..."
- POST /api/sets → Response 201
- Redirect do `/sets`
- Toast notification: "Zestaw zapisany! ✓" (zielony, auto-dismiss 3s)
- Dashboard teraz wyświetla listę z 1 zestawem:
  - "✨ Biologia - Fotosynteza"
  - "15 fiszek · dziś"
  - [Edytuj] [Usuń]

**Pain points i rozwiązania:**
- **Problem:** Długi czas oczekiwania (10-30s) → **Rozwiązanie:** Multi-stage loading feedback, symulowane etapy, jasna komunikacja "To może potrwać do 30 sekund"
- **Problem:** Ryzyko utraty pracy podczas edycji → **Rozwiązanie:** beforeunload protection, optional localStorage auto-save
- **Problem:** Niepewność czy fiszki są dobre → **Rozwiązanie:** Inline editing umożliwia natychmiastową korektę
- **Problem:** Niejasna nazwa zestawu → **Rozwiązanie:** AI sugeruje nazwę, user może edytować

---

### 3.2 Podróż: Powracający użytkownik → Edycja istniejącego zestawu

**Persona:** Uczeń podstawówki, ma już 5 zestawów

**Krok 1: Login**
- Wejście na `/login`
- Autocomplete email/password (password manager)
- Submit → redirect do `/sets`

**Krok 2: Dashboard**
- Lista 5 zestawów, sorted by updated_at DESC
- Przykłady:
  - "✨ Matematyka - Ułamki" (24 fiszki · 2 dni temu)
  - "📝 Angielski - Czasowniki nieregularne" (30 fiszek · tydzień temu)
  - ...

**Krok 3: Edycja**
- Click [Edytuj] przy "Matematyka - Ułamki" → `/sets/{id}/edit`
- Grid z 24 fiszkami
- Użytkownik:
  - Edytuje fiszkę #5 (poprawia błąd w odpowiedzi) → blur → PATCH /api/sets/{id}/cards/{card_id} → optimistic update
  - Dodaje nową fiszkę manualną → POST /api/sets/{id}/cards → slide-down animation
- Każda operacja immediately persisted (w przeciwieństwie do edycji nowego zestawu)

**Krok 4: Powrót**
- Click breadcrumb "Moje zestawy" → beforeunload NIE triggeruje (brak niezapisanych zmian)
- Redirect do `/sets`
- Lista zaktualizowana (zestaw "Matematyka" teraz ma 25 fiszek, updated_at: "dziś")

**Pain points i rozwiązania:**
- **Problem:** Przypadkowe zmiany → **Rozwiązanie:** Optimistic UI z rollback, success toasts dla pewności
- **Problem:** Długa lista zestawów → **Rozwiązanie:** Search bar, sortowanie, paginacja 20/page

---

### 3.3 Podróż: Error Recovery - Timeout AI

**Persona:** Uczeń wkleił zbyt skomplikowany tekst (10000 znaków z PDF z formułami matematycznymi)

**Krok 1-2:** Identyczne jak 3.1 (rejestracja/login → generowanie)

**Krok 3: Timeout**
- Submit text → loading state
- 0-30s: "Analizuję tekst..." → "Tworzę fiszki..."
- 30s: Timeout → loading kończy się
- Error modal (full-screen):
  - Ikona: ⚠️
  - Headline: "Generowanie trwa dłużej niż zwykle"
  - Message: "AI nie udało się przetworzyć tego tekstu w czasie."
  - Sugestie:
    - Skrócić tekst
    - Uprościć język
    - Usunąć znaki specjalne
  - Actions:
    - Primary: [Spróbuj ponownie]
    - Secondary: [Stwórz zestaw ręcznie]

**Krok 4: Recovery**
- Użytkownik click [Spróbuj ponownie]
- Powrót do `/generate` z zachowanym tekstem
- Użytkownik skraca tekst do 5000 znaków, usuwa formuły
- Submit → sukces

**Pain points i rozwiązania:**
- **Problem:** Frustracja z timeout bez wyjaśnienia → **Rozwiązanie:** Jasny error message z konstruktywnymi sugestiami
- **Problem:** Utrata wklejonego tekstu → **Rozwiązanie:** Tekst zachowany w textarea po błędzie

---

### 3.4 Podróż: Manualne tworzenie (bez AI)

**Persona:** Uczeń preferuje pełną kontrolę nad treścią fiszek

**Krok 1: Dashboard**
- Powracający użytkownik, zalogowany
- `/sets` → lista zestawów

**Krok 2: Inicjacja**
- Click "+ Nowy zestaw" (header button)
- Dropdown/modal z opcjami:
  - "Wygeneruj z AI"
  - "Stwórz ręcznie" ← select
- Redirect do `/sets/new`

**Krok 3: Tworzenie**
- Formularz nazwy: "Historia - Średniowiecze"
- Click "+ Dodaj pierwszą fiszkę"
- Grid pojawia się (progressive disclosure)
- Użytkownik dodaje fiszki manualnie (jedna po drugiej):
  1. PRZÓD: "Kiedy rozpoczęło się średniowiecze?", TYŁ: "476 r. n.e."
  2. PRZÓD: "Co to jest feudalizm?", TYŁ: "System społeczno-ekonomiczny..."
  3. ...
- Po dodaniu 10 fiszek → click "Zapisz zestaw (10 fiszek)"

**Krok 4: Save**
- POST /api/sets z cards array (wszystkie origin: "manual")
- Success → redirect do `/sets`
- Toast: "Zestaw zapisany! ✓"
- Lista zawiera nowy zestaw: "📝 Historia - Średniowiecze" (10 fiszek · dziś)

**Pain points i rozwiązania:**
- **Problem:** Monotonne dodawanie wielu fiszek → **Rozwiązanie:** Keyboard shortcuts (Enter w TYŁ → auto-add next), smooth animations
- **Problem:** Ryzyko utraty pracy → **Rozwiązanie:** beforeunload protection, auto-save

---

## 4. Układ i struktura nawigacji

### 4.1 Globalna nawigacja

**Top navigation bar** (widoczny po zalogowaniu):
- **Logo** (lewa strona) → click redirects do `/sets`
- **Breadcrumb** (centrum) → kontekstowa ścieżka (np. "Moje zestawy / Edycja: Biologia - Fotosynteza")
- **User menu** (prawa strona) → dropdown:
  - Email użytkownika
  - [Ustawienia] (post-MVP)
  - [Wyloguj]

**Primary actions** (w headerze kontekstowym):
- Na `/sets`: "+ Nowy zestaw" (button, prawą górą)
- Na `/generate`: Brak (fokus na formularzu)
- Na `/sets/new/edit`: Brak (fokus na edycji)

### 4.2 Nawigacja wewnętrzna (między widokami)

**Główne ścieżki nawigacji:**

1. **Auth flow:**
   - `/register` ⇄ `/login` (links)
   - `/login` → `/password/reset` → `/password/reset/confirm` → `/login` (linear flow)

2. **Dashboard → Creation:**
   - `/sets` → Click "Wygeneruj z AI" → `/generate`
   - `/sets` → Click "Stwórz ręcznie" → `/sets/new`

3. **Creation → Editing → Dashboard:**
   - `/generate` → (submit) → `/sets/new/edit` → (save) → `/sets`
   - `/sets/new` → (add cards) → (save) → `/sets`

4. **Dashboard → Editing existing:**
   - `/sets` → Click [Edytuj] → `/sets/{id}/edit`
   - `/sets/{id}/edit` → Click breadcrumb → `/sets`

### 4.3 Wzorce nawigacji

**Turbo Drive** (default):
- Wszystkie linki i formularze obsługiwane przez Turbo Drive
- Eliminacja full page reloads
- Progress bar dla długich requestów
- Cache dla back navigation

**Turbo Streams** (selected transitions):
- `/generate` (submit) → `/sets/new/edit` (smooth transition z loading state)
- Opcjonalnie dla innych complex interactions (jeśli nie wprowadza skomplikowania)

**Breadcrumb navigation:**
- Format: "Moje zestawy" / "Edycja: [Nazwa zestawu]"
- Każdy segment klikalny
- Automatically generated based on route

**Back navigation:**
- Browser back button respected (Turbo Drive cache)
- beforeunload protection na widokach edycji (jeśli isDirty)
- Custom modal dla internal navigation z niezapisanymi zmianami

### 4.4 Nawigacja mobilna

**Mobile (<768px):**
- Hamburger menu zamiast top nav (post-MVP: na razie simplified nav)
- Bottom sticky nav dla primary actions (opcjonalnie)
- Swipe gestures dla card grids (opcjonalnie)

**Tablet (768-1024px):**
- Horizontal top nav (zwinięta wersja desktop)
- Full breadcrumb

**Desktop (>1024px):**
- Full horizontal top nav
- Breadcrumb + keyboard shortcuts hints (opcjonalnie)

### 4.5 Keyboard navigation

**Global shortcuts:**
- `Ctrl/Cmd + S` → Save (na widokach edycji)
- `Escape` → Cancel modal / unfocus input
- `Tab` → Navigate through interactive elements
- `Enter` → Submit focused form / button

**Context-specific:**
- Na widoku edycji fiszek:
  - `Tab` → next field
  - `Shift + Tab` → previous field
  - `Ctrl/Cmd + Enter` w textarea → save card & next

### 4.6 Skip links (dostępność)

**Hidden skip links** (visible on focus):
- "Skip to main content" (na początku każdej strony)
- "Skip to navigation" (jeśli długa treść)

---

## 5. Kluczowe komponenty

### 5.1 Character Counter Component

**Cel:**
Real-time walidacja długości tekstu źródłowego (1000-10000 znaków) z wizualnym feedbackiem.

**Użycie:**
- Widok `/generate` (pole textarea)

**Funkcjonalność:**
- Zliczanie znaków w czasie rzeczywistym (debounce 300ms)
- Wyświetlanie: "N znaków (minimum 1,000, maksimum 10,000)"
- Visual progress bar z color-coding:
  - < 1000: czerwony
  - 1000-10000: zielony
  - \> 10000: czerwony
- Enable/disable przycisku "Generuj fiszki" based on validation

**Względy UX/A11y:**
- ARIA live region dla screen readers
- Smooth color transitions (Tailwind)
- Debouncing dla performance

---

### 5.2 Flashcard Grid Component

**Cel:**
Responsywny grid layout do wyświetlania wielu fiszek w widoku edycji.

**Użycie:**
- `/sets/new/edit` (edycja wygenerowanych fiszek)
- `/sets/new` (manualne tworzenie)
- `/sets/{id}/edit` (edycja istniejącego zestawu)

**Funkcjonalność:**
- Responsive layout:
  - Mobile/Tablet: 1 kolumna
  - Desktop: 2 kolumny (CSS Grid)
- Auto-reordering przy usuwaniu kart (slide-up animation)
- Lazy rendering dla dużych zestawów (>50 fiszek) - opcjonalnie

**Względy UX/A11y:**
- Consistent spacing (Tailwind gap utilities)
- Keyboard navigation (Tab przez karty)
- ARIA labels dla grid structure

---

### 5.3 Flashcard Card Component

**Cel:**
Pojedyncza edytowalna karta fiszki z polami PRZÓD/TYŁ oraz opcją usunięcia.

**Użycie:**
- Wewnątrz Flashcard Grid Component

**Funkcjonalność:**
- Numer karty (górny lewy róg)
- Przycisk delete (X, górny prawy róg)
- Dwa textareas (PRZÓD, TYŁ) z inline editing
- Auto-resize textareas based on content
- Visual indicator edycji (✏️ ikona jeśli modified)
- Różne style dla:
  - Default (AI-generated, niemodyfikowana)
  - Edited (border żółty + ✏️)
  - Manual (border inny kolor)
  - Focused (outline)

**Względy UX/A11y:**
- Focus indicators
- ARIA labels: "Przód fiszki numer N", "Tył fiszki numer N", "Usuń fiszkę numer N"
- Keyboard navigation: Tab between textareas, Escape to unfocus
- Smooth animations (delete fade-out)

---

### 5.4 Set Name Input Component

**Cel:**
Pole nazwy zestawu z real-time validation, duplicate detection i visual feedback dla AI-suggested names.

**Użycie:**
- `/sets/new/edit` (sticky footer)
- `/sets/new` (top of form)
- `/sets/{id}/edit` (editable header)

**Funkcjonalność:**
- Text input z pre-filled value (jeśli AI suggestion)
- Visual indicator "✨ Zasugerowane przez AI" (znika po edycji)
- Real-time validation (debounce 500ms):
  - Non-empty check
  - Duplicate check via API (GET /api/sets?q={name})
- Error states:
  - Inline error message: "Masz już zestaw o tej nazwie"
  - Input border czerwony
- Success state: checkmark ✓

**Względy UX/A11y:**
- Debouncing dla performance (nie spam API)
- ARIA live region dla validation messages
- Focus management
- Clear error messaging

---

### 5.5 Loading Skeleton Component

**Cel:**
Placeholder dla treści podczas ładowania, zapewniający smooth UX.

**Użycie:**
- `/generate` (podczas AI generation)
- `/sets` (loading lista zestawów)
- `/sets/{id}/edit` (loading szczegółów)

**Funkcjonalność:**
- Pulsing animation (Tailwind animate-pulse)
- Shape matching docelowej treści:
  - Dla fiszek: prostokąty imitujące karty
  - Dla listy zestawów: karty z placeholder text
- Multi-stage dla AI generation:
  - Etap 1 (0-10s): "Analizuję tekst..."
  - Etap 2 (10-30s): "Tworzę fiszki..."

**Względy UX/A11y:**
- ARIA live region z komunikatami etapów
- Screen reader announcements
- Nie blokuje interakcji (np. można anulować)

---

### 5.6 Toast Notification Component

**Cel:**
Non-blocking notifications dla success/error messages.

**Użycie:**
- Post-save success: "Zestaw zapisany! ✓"
- Delete success: "Zestaw usunięty"
- Error handling: "Nie udało się usunąć. Spróbuj ponownie."

**Funkcjonalność:**
- Auto-dismiss (3-5s dla success, 7s dla error)
- Queue management (multiple toasts stack)
- Types: success (zielony), error (czerwony), info (niebieski), warning (żółty)
- Close button (X)
- Slide-in/out animations

**Względy UX/A11y:**
- ARIA live region (role="status" lub role="alert")
- Positioned fixed (top-right lub bottom-right)
- Z-index wysokie (ponad wszystkimi elementami)
- Keyboard dismissible (Escape)

---

### 5.7 Modal Component

**Cel:**
Blocking dialogs dla critical actions (confirmation, errors).

**Użycie:**
- Delete confirmation: "Czy na pewno chcesz usunąć zestaw 'X'?"
- Error modals: Timeout, server errors
- Unsaved changes warning (custom beforeunload)

**Funkcjonalność:**
- Backdrop (dark overlay)
- Centered modal card
- Header z tytułem + close button (X)
- Body z treścią
- Footer z akcjami (Primary/Secondary buttons)
- Focus trap (keyboard navigation ograniczona do modala)
- ESC to close (dla non-critical modals)

**Względy UX/A11y:**
- ARIA role="dialog", aria-modal="true"
- Focus management: auto-focus na first action button
- Focus trap: Tab/Shift+Tab cycle tylko wewnątrz
- Keyboard: ESC to close, Enter to confirm
- Backdrop click to close (dla non-critical)

---

### 5.8 Empty State Component

**Cel:**
Edukacyjny i motywujący ekran dla nowych użytkowników bez zestawów.

**Użycie:**
- `/sets` (dashboard dla nowego użytkownika)

**Funkcjonalność:**
- Centralna ilustracja (SVG, edukacyjna grafika fiszek)
- Headline: "Stwórz swój pierwszy zestaw fiszek"
- Subheadline/instrukcja: "Wklej notatki (1000-10000 znaków), a AI stworzy dla Ciebie fiszki"
- Primary CTA: "🤖 Wygeneruj fiszki z AI" (duży, prominent button)
- Secondary actions:
  - "Stwórz ręcznie" (outline button)
  - "Zobacz przykład" (text link)

**Względy UX/A11y:**
- Motywujący tone (pozytywny, nie "puste/smutne")
- Jasne next steps
- Alt text dla ilustracji
- Keyboard navigation dla CTAs

---

### 5.9 Set Card Component (Dashboard)

**Cel:**
Kompaktowa reprezentacja zestawu fiszek na liście dashboardu.

**Użycie:**
- `/sets` (grid zestawów)

**Funkcjonalność:**
- Layout (vertical card):
  - Header: Ikona źródła (✨ AI / 📝 manual) + Nazwa zestawu
  - Metadata: Liczba fiszek · Data (relative: "dziś", "2 dni temu", "tydzień temu")
  - Footer: Akcje ([Edytuj] button outline, "Usuń" text link)
- Hover state (desktop): subtle elevation/shadow
- Click on card body → redirect do `/sets/{id}/edit`
- Click [Edytuj] → redirect do `/sets/{id}/edit`
- Click "Usuń" → confirmation modal → DELETE

**Względy UX/A11y:**
- Semantic HTML (article element)
- ARIA labels: "Zestaw: [Nazwa], [N] fiszek, utworzony [data]"
- Keyboard: Tab to actions, Enter to activate
- Consistent spacing (Tailwind)

---

### 5.10 Pagination Component

**Cel:**
Umożliwienie nawigacji przez długie listy (20 items per page).

**Użycie:**
- `/sets` (lista zestawów)
- Potencjalnie `/sets/{id}/edit` (dla zestawów >50 fiszek) - post-MVP

**Funkcjonalność:**
- Format: [Prev] [1] [2] [3] ... [10] [Next]
- Current page highlighted (bold, background)
- Disabled states dla Prev (na page 1) i Next (na last page)
- Ellipsis (...) dla długich zakresów
- Click page number → navigate do page
- URL sync: `/sets?page=2`

**Względy UX/A11y:**
- ARIA labels: "Page 2", "Go to page 3", "Previous page", "Next page"
- Keyboard navigation: Tab through, Enter to navigate
- Current page: aria-current="page"
- Turbo Drive dla smooth transitions

---

## 6. Edge Cases i Error States (Podsumowanie)

### 6.1 AI Generation Edge Cases

**Timeout (>30s):**
- Full-screen error modal z sugestiami recovery
- Opcje: "Spróbuj ponownie" / "Stwórz zestaw ręcznie"

**AI Service Failure (500):**
- Error modal: "AI jest tymczasowo niedostępne. Spróbuj ponownie za chwilę lub stwórz zestaw ręcznie."

**Invalid Response Format:**
- Fallback: "Nie udało się przetworzyć odpowiedzi. Spróbuj ponownie."

**Empty Response (0 fiszek):**
- Error modal: "AI nie mogło wygenerować fiszek z tego tekstu. Spróbuj:
  - Dodać więcej szczegółowych informacji
  - Ustrukturyzować tekst (nagłówki, punkty)
  - Użyć prostszego języka"

### 6.2 Validation Edge Cases

**Duplicate Set Name:**
- Inline error pod polem nazwy: "Masz już zestaw o tej nazwie"
- Przycisk "Zapisz" disabled

**Empty Set (0 fiszek):**
- Walidacja: minimum 1 fiszka required
- Przycisk "Zapisz" disabled jeśli 0 fiszek
- Helper text: "Dodaj przynajmniej jedną fiszkę"

**Oversized Card Content (>1000 chars):**
- Real-time counter przy textarea
- Inline error: "Maksymalnie 1000 znaków (obecnie: N)"
- Server-side validation backup

### 6.3 Session & Auth Edge Cases

**Session Expiry:**
- Timeout: 2 godziny idle
- Warning modal at 1:55: "Twoja sesja wygaśnie za 5 minut. Czy chcesz kontynuować?" [Tak] [Wyloguj]
- Post-expiry: redirect do `/login` z message "Sesja wygasła. Zaloguj się ponownie."

**Concurrent Edits (Multiple Tabs):**
- Post-MVP: conflict detection
- MVP: last-write-wins (localStorage może pomóc w detection)

**Lost Network Connection:**
- Optimistic UI updates w kolejce
- Retry logic dla failed requests
- Toast: "Brak połączenia. Sprawdź internet i spróbuj ponownie."

### 6.4 Data Loss Prevention

**Browser Close/Refresh During Edit:**
- beforeunload event: "Masz niezapisane zmiany. Czy na pewno chcesz opuścić stronę?"
- Browser-native confirmation dialog

**Internal Navigation During Edit:**
- Custom modal: "Masz niezapisane zmiany."
  - [Zapisz i wyjdź] (save → navigate)
  - [Wyjdź bez zapisywania] (discard → navigate)
  - [Anuluj] (stay)

**localStorage Recovery:**
- Auto-save draft co 30s (opcjonalnie)
- TTL 24h
- Na powrót: "Znaleźliśmy niezapisany zestaw. Czy chcesz go odzyskać?" [Tak] [Nie]

---

## 7. Responsywność (Podsumowanie)

### Breakpoints (Tailwind default):
- **Mobile:** < 768px
- **Tablet:** 768px - 1024px
- **Desktop:** > 1024px

### Responsive Patterns:

**Dashboard Grid:**
- Mobile: 1 kolumna (vertical stack)
- Tablet: 2 kolumny
- Desktop: 3 kolumny

**Flashcard Grid:**
- Mobile/Tablet: 1 kolumna
- Desktop: 2 kolumny

**Navigation:**
- Mobile: Simplified (hamburger post-MVP)
- Tablet/Desktop: Full horizontal nav

**Typography:**
- Mobile: 14px base
- Tablet/Desktop: 16px base

**Touch Targets:**
- Mobile: Minimum 44×44px (Apple HIG)
- Desktop: Hover states, smaller targets OK

---

## 8. Dostępność (Podsumowanie)

### WCAG 2.1 Level AA Compliance:

**Perceivable:**
- Kontrast min 4.5:1 (tekst), 3:1 (duży tekst)
- Alt text dla ilustracji
- Nie poleganie tylko na kolorze (ikony + kolor)

**Operable:**
- Pełna keyboard navigation
- Focus indicators (outline 2px)
- Skip links
- No keyboard traps
- Timeout warnings

**Understandable:**
- Prosty język (dla uczniów 10-18 lat)
- Consistent navigation
- Konkretne error messages
- Predictable actions

**Robust:**
- Semantic HTML5
- ARIA labels gdzie potrzebne
- ARIA live regions dla dynamic content
- Valid HTML

### Screen Reader Testing:
- NVDA (Windows)
- VoiceOver (macOS/iOS)
- TalkBack (Android)

---

## 9. Bezpieczeństwo (Podsumowanie)

### XSS Prevention:
- Twig auto-escape globally enabled
- NIGDY `|raw` dla user input
- Content Security Policy headers

### CSRF Protection:
- Symfony Forms: auto-token
- XHR: meta tag + header `X-CSRF-Token`

### Input Validation:
- Client-side dla UX (real-time feedback)
- Server-side enforcement (Symfony Validator)
- Database constraints (backup layer)

### Session Security:
- HttpOnly cookies
- Secure flag (HTTPS only)
- SameSite=Lax
- Timeout 2h idle

### Password Security:
- Argon2id hashing
- Minimum 8 chars
- Reset tokens: 1h expiry, single-use

### Rate Limiting:
- NIE w MVP
- Post-MVP: 5/min dla `/api/generate`, bruteforce protection dla auth

---

## 10. Optymalizacja Wydajności (Podsumowanie)

### Asset Optimization:
- Asset Mapper versioning (cache busting)
- Aggressive HTTP caching (1 year)
- Auto minification w produkcji

### Turbo Drive:
- Eliminacja full page reloads
- Cache dla back navigation
- Progress bar dla długich requestów

### Debouncing:
- Character counter: 300ms
- Validation: 500ms
- Search: 500ms

### Lazy Loading:
- Paginacja: 20 items/page
- Infinite scroll (opcjonalnie post-MVP)

### Optimistic UI:
- Immediate feedback dla user actions
- Rollback przy błędzie
- Success toasts dla confirmation

### Skeleton Loading:
- Pulsing placeholders podczas ładowania
- Shape matching dla smooth transition

---

## 11. Mapowanie Wymagań na UI (Podsumowanie)

| User Story | UI View(s) | Kluczowe Komponenty |
|------------|-----------|---------------------|
| US-001: Rejestracja | `/register` | Auth form, validation, password toggle |
| US-002: Logowanie | `/login` | Auth form, password reset link |
| US-003: Generowanie AI | `/generate`, `/sets/new/edit` | Character counter, loading skeleton, flashcard grid |
| US-005: Edycja fiszek | `/sets/new/edit` | Flashcard card (inline edit), delete animations |
| US-006: Zapisywanie zestawu | `/sets/new/edit` (footer) | Set name input, validation, save button |
| US-007: Obsługa błędów | Error modals, toasts | Error modal, toast notifications |
| US-008: Tworzenie manualne | `/sets/new` | Set name input, flashcard grid, add card button |
| US-009: Zarządzanie zestawami | `/sets` | Dashboard grid, set cards, pagination, search |
| US-010-012: Moduł nauki | **Poza zakresem MVP** | Post-MVP |

---

## 12. Poza zakresem MVP

Następujące funkcjonalności zostały świadomie wyłączone z MVP i będą realizowane w kolejnych iteracjach:

### Moduł nauki (spaced repetition):
- Interfejs nauki (show front → reveal back → rate "Wiem"/"Nie wiem")
- Integracja algorytmu powtórek (SM-2 lub Leitner)
- Tracking postępów nauki
- Session summary

### Zaawansowane funkcje:
- Rate limiting (5/min dla `/api/generate`)
- localStorage auto-save (opcjonalnie w MVP, requires user testing)
- Turbo Streams dla wszystkich transitions (evaluate complexity)
- Dark mode
- PWA capabilities (offline support)
- Onboarding tutorial
- Advanced analytics dashboard
- Email notifications (welcome, summaries)

### Społecznościowe:
- Sharing zestawów między użytkownikami
- Public library zestawów
- Collaborative editing

### Integracje:
- Import z PDF/DOCX/CSV
- Export zestawów
- Google Classroom, Moodle integration
- Mobile apps

---

## 13. Następne kroki (Development Workflow)

1. **Implementacja Authentication Flow** (US-001, US-002)
   - Symfony Security configuration
   - Registration/Login views (Twig templates)
   - Password reset flow
   - Session management

2. **Dashboard & Set Management** (US-009)
   - `/sets` view z empty state
   - Set list component
   - Delete functionality
   - Search & pagination

3. **AI Generation Flow** (US-003)
   - `/generate` view z character counter
   - OpenRouter.ai integration (backend)
   - Loading states & error handling
   - Transition do edycji

4. **Editing Interface** (US-005, US-006)
   - `/sets/new/edit` view
   - Flashcard grid & card components
   - Inline editing (Stimulus controllers)
   - Set name validation
   - Save functionality

5. **Manual Creation** (US-008)
   - `/sets/new` view
   - Reuse components z edycji AI
   - Add card functionality

6. **Editing Existing Sets**
   - `/sets/{id}/edit` view
   - Per-card API operations (PATCH/DELETE)
   - Optimistic UI updates

7. **Polish & Optimization**
   - Accessibility audit
   - Performance testing
   - Error message refinement
   - User testing

8. **Analytics Integration**
   - KPI tracking (acceptance rate, AI adoption)
   - Event logging (frontend)
   - Dashboard reporting (backend)

---

**Koniec dokumentu**

Dokument stworzony: 2025-01-15
Wersja: 1.0 (MVP scope)
Status: Ready for implementation
