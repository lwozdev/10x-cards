Podsumowanie Planowania Architektury UI dla MVP Generator Fiszek AI

Decisions

Decyzje podjęte przez użytkownika:

1. Zakres MVP: Wyłączenie modułu nauki z MVP - skupienie się wyłącznie na przygotowaniu fiszek (generowanie AI,
   tworzenie manualne, edycja, zarządzanie zestawami)
2. Zarządzanie stanem: Wykorzystanie Stimulus controllers do zarządzania lokalnym stanem edycji fiszek (bez zapisywania
   podglądów na serwerze)
3. Progressive enhancement: Implementacja Turbo Streams dla płynniejszego UX (jeśli nie wprowadza znacznego
   skomplikowania)
4. Edycja fiszek: Inline editing z wykorzystaniem Stimulus zamiast modali
5. Responsywność: Mobile-first approach z dedykowanymi layoutami dla mobile/tablet/desktop
6. Dostępność: Priorytet dla WCAG AA, keyboard navigation, screen reader support
7. Bezpieczeństwo: Wykorzystanie wbudowanych mechanizmów Symfony (CSRF, auto-escape Twig, CSP)
8. Rate limiting: Brak ograniczeń rate limiting w MVP
9. Design system: Minimalny design system oparty na Tailwind CSS utility-first
10. Obsługa błędów: Zróżnicowane strategie w zależności od typu błędu (inline errors, modals, toasts)
11. Optymalizacja: Wielopoziomowa strategia (HTTP caching, Turbo Drive, lazy loading, debouncing, optimistic UI)
12. Empty states: Edukacyjny empty state z silnymi CTA dla nowych użytkowników
13. Tekst źródłowy: Sticky header z kolapsowanym podglądem tekstu źródłowego podczas edycji
14. Ochrona przed utratą danych: Implementacja beforeunload + opcjonalny auto-save do localStorage
15. Layout edycji: CSS Grid z kartami (card layout) zamiast tabeli
16. Nazwa zestawu: Edytowalne pole w sticky footer z wyraźną sugestią AI
17. Dodawanie manualnych fiszek: Przycisk "+ Dodaj fiszkę" umożliwiający mieszanie AI i manualnych fiszek przed zapisem
18. Lista zestawów: Kompaktowe karty z metadanymi (nazwa, liczba fiszek, data, źródło), sortowanie, paginacja 20/page
19. Formularze auth: Centered card layout z progresywną walidacją live
20. Reset hasła: Trzy-krokowy flow z jasnymi komunikatami i zabezpieczeniem przed enumeracją
21. Micro-interactions: Strategiczne animacje Tailwind + Stimulus dla kluczowych akcji

Matched Recommendations

Kluczowe rekomendacje dopasowane do MVP:

1. Architektura widoków

- Dwie główne sekcje: Dashboard ("Moje zestawy") + Obszar tworzenia (AI/manualne)
- Płaska nawigacja: Max 2 kliknięcia do każdej funkcji
- Breadcrumb dla orientacji użytkownika

2. Zarządzanie stanem aplikacji

- Stimulus controller jako state manager dla edycji fiszek
- Lokalny stan edycji: Wszystkie operacje (edycja, usuwanie) w JavaScript przed zapisem
- Auto-tracking zmian: Flaga edited automatycznie ustawiana przy modyfikacji
- Single source of truth: POST /api/sets wysyłany tylko przy finalnym zapisie

3. Obsługa długiego generowania AI (10-30s)

- Wieloetapowy feedback: Loader z symulowanymi etapami ("Analizuję tekst..." → "Tworzę fiszki...")
- Timeout handling: Error screen po 30s z opcjami recovery
- Turbo Streams: Płynne przejście do ekranu edycji po sukcesie
- Blokada UI: Zapobieganie wielokrotnemu kliknięciu podczas generowania

4. Edycja fiszek - inline editing

- CSS Grid layout: 2 kolumny desktop, 1 kolumna mobile
- Komponenty kart: Numer, edytowalne textareas (auto-resize), przycisk usuń
- Wizualna indykacja: Różne style dla domyślnych/edytowanych/focused kart
- Sticky footer: Nazwa zestawu + "Zapisz zestaw" zawsze widoczny

5. Responsywność

- Mobile-first approach z Tailwind utility classes
- Breakpoints: < 768px (mobile), 768-1024px (tablet), > 1024px (desktop)
- Dedykowane layouty: Single column mobile → multi-column grid desktop
- Touch-friendly: Większe touch targets na mobile (min 44×44px)

6. Dostępność (a11y)

- Wysoki kontrast: WCAG AA minimum, czcionka min 16px
- Keyboard navigation: Tab/Enter/Escape, wyraźne focus indicators
- Semantic HTML: Właściwe tagi, ARIA labels, live regions
- Screen reader support: Ogłaszanie dynamicznych zmian (np. "Fiszka usunięta")
- Skip links w nawigacji

7. Bezpieczeństwo UI

- CSRF tokens: Auto w Symfony Forms, header X-CSRF-Token dla XHR
- XSS prevention: Twig auto-escape (nigdy |raw dla user content)
- Content Security Policy: Blokada inline scripts
- Session timeout: Modal "Sesja wygasła" z opcją re-login bez utraty pracy

8. Design system (Tailwind)

- Minimalna paleta: 1 kolor primary + odcienie szarości + semantic colors
- Typography: 4-5 rozmiarów, 2 font weights
- Spacing: Domyślny Tailwind (4px base)
- Reusable partials: 5-6 Twig components (button, card, input, alert)
- Brak dark mode w MVP

9. Obsługa błędów API

- 400/422 (walidacja): Inline errors pod polami z konkretnymi komunikatami
- 504 (timeout AI): Full-screen modal z opcjami recovery
- 500 (server error): Toast notification z instrukcją odświeżenia
- Failed generation: Sugestie dla użytkownika (uprość tekst, skróć, usuń znaki specjalne)

10. Optymalizacja wydajności

- HTTP caching: Aggressive dla assetów via Asset Mapper
- Turbo Drive: Eliminacja full page reloads
- Lazy loading: Paginacja 20/page + opcjonalny infinite scroll
- Debouncing: 300ms dla licznika znaków, 500ms dla walidacji
- Optimistic UI: Natychmiastowa aktualizacja UI z rollback przy błędzie

11. Empty state dla nowych użytkowników

- Ilustracja centralna + headline "Stwórz swój pierwszy zestaw fiszek"
- Dwie opcje CTA: "Wygeneruj z AI" (primary) + "Stwórz ręcznie" (secondary)
- Edukacja: Krótka instrukcja + link "Zobacz przykład"
- Transformacja: Po utworzeniu → lista z "+ Nowy zestaw"

12. Kontekst tekstu źródłowego

- Pełny widok: Podczas wprowadzania i generowania
- Sticky header kolapsowany: Na ekranie edycji (pierwsze ~100 znaków)
- Przycisk "Pokaż cały tekst": Rozwijanie pełnego tekstu
- "Edytuj i wygeneruj ponownie": Opcja modyfikacji źródła

13. Ochrona przed utratą danych

- beforeunload dialog: Browser-native dla nawigacji poza stronę
- Custom modal: Dla wewnętrznej nawigacji Turbo z opcjami "Zapisz i wyjdź" / "Wyjdź bez zapisywania" / "Anuluj"
- Auto-save localStorage: Opcjonalny draft co 30s (TTL 24h)
- Recovery prompt: "Znaleźliśmy niezapisany zestaw. Odzyskać?"

14. Mieszane zestawy (AI + manualne)

- Przycisk "+ Dodaj fiszkę": Na końcu listy wygenerowanych fiszek
- Origin tracking: Frontend oznacza origin: "manual" lub "ai"
- Jednolity UX: Wszystkie fiszki edytowalne i usuwalne tak samo
- Wysyłka: Jedno POST /api/sets z mixed origins

15. Lista "Moje zestawy"

- Metadane karty: Nazwa, ikona źródła (✨ AI / 📝 manual), liczba fiszek, data
- Akcje: "Edytuj" (outline) + "Usuń" (text link)
- Layout: Pionowa lista mobile, grid 2-3 kolumny desktop
- Sortowanie: Default updated_at DESC, dropdown z opcjami
- Paginacja: 20 per page, numeracja stron

16. Sugerowana nazwa zestawu

- Pre-wypełniony input: Z sugerowaną nazwą AI
- Wizualna wskazówka: Ikona "✨ Zasugerowane przez AI"
- Edycja: Ikona znika po modyfikacji przez użytkownika
- Real-time walidacja: Debounce 500ms, check duplikatów via API
- Error state: "Masz już zestaw o tej nazwie"
- Disabled state: Przycisk "Zapisz" disabled dopóki niepoprawna

17. Formularze auth (rejestracja/login)

- Centered card: Max-width 400px na gradient background
- Autocomplete: Właściwe atrybuty HTML5
- Toggle hasła: "Pokaż/ukryj hasło"
- Live validation: Debounce 500ms, inline errors + success checkmarks
- Full-width CTA: Duży przycisk akcji
- Secondary links: "Masz konto?" / "Zapomniałeś hasła?"

18. Reset hasła (3 kroki)

1. Request: Email input → Always 202 (przeciw enumeracji)
2. Email: Link ważny 1h → /password/reset/confirm?token=xxx
3. Confirm: Nowe hasło + potwierdź → Redirect do login z success message

- Error handling: "Link wygasł. Poproś o nowy"

19. Micro-interactions

- Usuwanie fiszki: Fade-out + slide-up (300ms)
- Dodawanie fiszki: Slide-down + fade-in
- Zapisywanie: Spinner + "Zapisywanie..." text change
- Success toast: Zielony toast "Zestaw zapisany!" ✓ (auto-dismiss 3s)
- Loading AI: Pulsing skeleton cards
- Character counter: Color transition red → yellow → green
- Implementacja: Tailwind transitions + Stimulus logic

20. Kluczowe komponenty techniczne

- Framework: Symfony 7.3 + Doctrine ORM
- Frontend: Twig SSR + Symfony UX (Turbo & Stimulus)
- Styling: Tailwind CSS via Asset Mapper
- State management: Stimulus controllers (client-side)
- API communication: Fetch API + Turbo dla nawigacji
- Database: PostgreSQL 16
- AI: OpenRouter.ai (synchroniczne generowanie)

UI Architecture Planning Summary

Główne wymagania architektury UI

Cel biznesowy:
Aplikacja MVP do generowania fiszek edukacyjnych z AI dla uczniów szkół podstawowych i średnich. Zakres MVP obejmuje
wyłącznie proces przygotowania fiszek (generowanie, edycja, zarządzanie), bez modułu nauki (ten zostanie
zrealizowany po MVP).

Kluczowe metryki sukcesu:

- 75% akceptacji fiszek wygenerowanych przez AI (mierzone jako: 1 - usunięte/wygenerowane)
- 75% wszystkich fiszek tworzone z użyciem AI

Architektura techniczna:

- Monolit renderowany po stronie serwera (Symfony + Twig)
- Progressive enhancement z Symfony UX (Turbo Drive, Turbo Streams, Stimulus)
- Thin JSON API (/api/*) dla XHR requests
- Bez API Platform, bez SPA - prostota i szybkość MVP

  ---

Kluczowe widoki, ekrany i przepływy użytkownika

1. Authentication Flow

Ekrany:

- /register - Rejestracja (email, hasło, potwierdź hasło)
- /login - Logowanie (email, hasło)
- /password/reset - Request reset
- /password/reset/confirm?token=xxx - Ustaw nowe hasło

UX Pattern:

- Centered card (max 400px) na gradient background
- Live validation z debounce 500ms
- Inline errors pod polami
- Success checkmarks przy poprawnych wartościach
- Toggle "pokaż/ukryj hasło"
- Autocomplete attributes dla password managerów

  ---

2. Dashboard ("Moje zestawy")

URL: /sets (główny ekran po zalogowaniu)

Empty State (nowy użytkownik):
┌─────────────────────────────────────────┐
│     [Ilustracja fiszek]                 │
│ │
│ Stwórz swój pierwszy zestaw fiszek │
│ │
│ Wklej notatki (1000-10000 znaków), │
│ a AI stworzy dla Ciebie fiszki │
│ │
│ ┌───────────────────────────────────┐ │
│ │ 🤖 Wygeneruj fiszki z AI │ │ <- Primary CTA
│ └───────────────────────────────────┘ │
│ │
│  [ Stwórz ręcznie ]  [Zobacz przykład] │ <- Secondary actions
└─────────────────────────────────────────┘

Lista zestawów (istniejący użytkownik):
Header: [Logo] Moje zestawy                    [+ Nowy zestaw]

Sortuj: [Ostatnio zmienione ▼]  [🔍 Szukaj...]

┌─────────────────────────────────────┐ ┌─────────────────────────────────────┐
│ ✨ Biologia - Fotosynteza │ │ 📝 Historia - Średniowiecze │
│ 24 fiszki · 2 dni temu │ │ 15 fiszek · dziś │
│ │ │ │
│ [Edytuj]  Usuń │ │ [Edytuj]  Usuń │
└─────────────────────────────────────┘ └─────────────────────────────────────┘

[1] 2 3 ... 10                              <- Paginacja (20/page)

Integracja API:

- GET /api/sets?page=1&per_page=20&sort=updated_at_desc
- Response: { items: [...], total, page, per_page }
- Turbo Drive dla nawigacji między stronami
- Optimistic delete z rollback przy błędzie

  ---

3. Generowanie fiszek AI

URL: /generate

Ekran input:
┌─────────────────────────────────────────────────────────────┐
│ Wklej swoje notatki │
│ │
│ ┌───────────────────────────────────────────────────────┐ │
│ │ [Duże pole tekstowe textarea]                         │ │
│ │ │ │
│ │ Lorem ipsum dolor sit amet, consectetur adipiscing... │ │
│ │ │ │
│ │ │ │
│ └───────────────────────────────────────────────────────┘ │
│ │
│ 2,450 znaków (minimum 1,000, maksimum 10,000)             │ <- Counter
│ ████████████░░░░░░░░░░░░░░░░ │ <- Visual bar
│ │
│  [    Generuj fiszki    ] <- Enabled tylko gdy 1000-10000 │
└─────────────────────────────────────────────────────────────┘

Stimulus controller:

- Debounce 300ms dla licznika znaków
- Color transition: < 1000 red, 1000-10000 green, > 10000 red
- Disable/enable przycisku "Generuj" real-time
- Visual progress bar

Loading state (10-30s):
┌─────────────────────────────────────────────────────────────┐
│ │
│  [Animowany spinner/loader]                                │
│ │
│ Analizuję tekst... │ <- Etap 1 (0-10s)
│  (później: Tworzę fiszki...)                               │ <- Etap 2 (10-30s)
│ │
│ To może potrwać do 30 sekund │
│ │
└─────────────────────────────────────────────────────────────┘

Timeout error (>30s):
┌─────────────────────────────────────────────────────────────┐
│ ⚠️ Generowanie trwa dłużej niż zwykle │
│ │
│ AI nie udało się przetworzyć tego tekstu w czasie. │
│ Spróbuj:                                                  │
│ • Skrócić tekst │
│ • Uprościć język │
│ • Usunąć znaki specjalne │
│ │
│  [Spróbuj ponownie]  [Stwórz zestaw ręcznie]              │
└─────────────────────────────────────────────────────────────┘

Integracja API:

- POST /api/generate z { source_text: "..." }
- Response 200: { job_id, suggested_name, cards: [{front, back}], generated_count }
- Response 504: Timeout → error modal
- Response 422: Validation → inline error
- Turbo Streams dla transition do ekranu edycji

  ---

4. Edycja fiszek (po generowaniu AI)

URL: /sets/new/edit (lub /sets/{id}/edit dla istniejących)

Layout:
┌─────────────────────────────────────────────────────────────┐
│ Sticky Header:                                              │
│ 📄 Tekst źródłowy: "Lorem ipsum dolor sit amet, consecte..." │
│ [Pokaż cały tekst ▼]  [Edytuj i wygeneruj ponownie]        │
└─────────────────────────────────────────────────────────────┘

┌─Scrollable area──────────────────────────────────────────────┐
│ │
│ Desktop: 2 kolumny grid | Mobile: 1 kolumna │
│ │
│ ┌─Card #1────────────────────────────────────────────[×]─┐ │
│ │ PRZÓD:                                                  │ │
│ │ ┌─────────────────────────────────────────────────────┐ │ │
│ │ │ Co to jest fotosynteza? ✏️ │ │ │ <- Edited indicator
│ │ └─────────────────────────────────────────────────────┘ │ │
│ │ │ │
│ │ TYŁ:                                                    │ │
│ │ ┌─────────────────────────────────────────────────────┐ │ │
│ │ │ Proces przekształcania światła w energię chemiczną │ │ │
│ │ └─────────────────────────────────────────────────────┘ │ │
│ └──────────────────────────────────────────────────────────┘ │
│ │
│ ┌─Card #2────────────────────────────────────────────[×]─┐ │
│ │ ... (kolejna fiszka)                                    │ │
│ └──────────────────────────────────────────────────────────┘ │
│ │
│  [+ Dodaj własną fiszkę]                                    │
│ │
└──────────────────────────────────────────────────────────────┘

┌─Sticky Footer────────────────────────────────────────────────┐
│ Nazwa zestawu:                                               │
│ ┌────────────────────────────────────────────────────────┐ │
│ │ Biologia - Fotosynteza ✨ Zasugerowane przez AI │ │
│ └────────────────────────────────────────────────────────┘ │
│ │
│ [      Zapisz zestaw (24 fiszki)      ]                     │
└──────────────────────────────────────────────────────────────┘

Stimulus controller (state management):
// Pseudo-kod struktury stanu
{
jobId: "uuid",
suggestedName: "Biologia - Fotosynteza",
cards: [
{
tempId: 1, // client-side ID
front: "Co to jest fotosynteza?",
back: "Proces...",
origin: "ai",
edited: true // tracked automatically on blur
},
// ... more cards
],
isDirty: true, // has unsaved changes
deletedCount: 2 // for analytics
}

Funkcjonalności:

- Inline editing: Click na textarea → focus → blur saves to local state
- Auto-resize: Textarea automatycznie rośnie z contentem
- Delete animation: Fade-out + slide-up (300ms) → remove from array
- Add manual card: Append to cards array z origin: "manual"
- Edited tracking: Auto-set edited: true przy pierwszej modyfikacji
- Name validation: Debounce 500ms → check duplikat via API
- beforeunload protection: Jeśli isDirty === true

Integracja API:

- Tylko read podczas edycji (brak auto-save)
- POST /api/sets na końcu z:
  {
  "name": "Biologia - Fotosynteza",
  "cards": [
  { "front": "...", "back": "...", "origin": "ai", "edited": true },
  { "front": "...", "back": "...", "origin": "manual", "edited": false }
  ],
  "job_id": "uuid"
  }
- Response 201 → Redirect do /sets z toast "Zestaw zapisany!" ✓
- Response 409 → Error "Nazwa już istnieje"
- Response 422 → Inline validation errors

  ---

5. Manualne tworzenie zestawu

URL: /sets/new

Flow:

1. Click "+ Nowy zestaw" → /sets/new
2. Formularz:
   Nazwa zestawu: [________________]

[+ Dodaj pierwszą fiszkę]

3. Po dodaniu pierwszej fiszki → podobny widok jak edycja AI (grid kart)
4. POST /api/sets z cards array gdzie wszystkie mają origin: "manual"

  ---
Strategia integracji z API i zarządzania stanem

Client-side state (Stimulus controllers)

Kontrolery:

1. generate_controller.js - Obsługa generowania AI
    - Character counter z debounce
    - Submit form via Fetch API
    - Loading state management
    - Error handling
2. cards_editor_controller.js - Edycja fiszek
    - Local state array management
    - Inline editing logic
    - Delete animations
    - Dirty state tracking
    - beforeunload protection
3. set_name_controller.js - Walidacja nazwy
    - Debounced API validation
    - Duplicate detection
    - Error display
4. toast_controller.js - Toast notifications
    - Show/hide animations
    - Auto-dismiss timers
    - Queue management

Server-side rendering (Twig + Turbo)

Pattern:

- Większość nawigacji through Turbo Drive (no full page reload)
- Formularze Symfony → submit → redirect → flash message
- XHR requests dla dynamic validation i quick actions
- Turbo Streams dla complex interactions (np. transition po generowaniu)

API endpoints wykorzystywane przez UI:

| Endpoint                         | Metoda | Użycie UI                         |
  |----------------------------------|--------|-----------------------------------|
| /api/auth/register               | POST   | Formularz rejestracji             |
| /api/auth/login                  | POST   | Formularz logowania               |
| /api/auth/logout                 | POST   | Link wylogowania                  |
| /api/auth/password/reset         | POST   | Formularz reset hasła             |
| /api/auth/password/reset/confirm | POST   | Formularz nowego hasła            |
| /api/generate                    | POST   | Generowanie fiszek AI             |
| /api/sets                        | GET    | Lista "Moje zestawy"              |
| /api/sets                        | POST   | Zapisanie zestawu (AI lub manual) |
| /api/sets/{id}                   | GET    | Pobranie szczegółów do edycji     |
| /api/sets/{id}                   | PATCH  | Zmiana nazwy zestawu              |
| /api/sets/{id}                   | DELETE | Usunięcie zestawu                 |
| /api/sets/{id}/cards             | POST   | Dodanie fiszki do istniejącego    |
| /api/sets/{id}/cards/{card_id}   | PATCH  | Edycja fiszki                     |
| /api/sets/{id}/cards/{card_id}   | DELETE | Usunięcie fiszki                  |

CSRF protection:

- Symfony Forms: auto token
- XHR: <meta name="csrf-token"> + header X-CSRF-Token

Error handling strategy:

| Kod | Typ          | UI Response                                |
  |-----|--------------|--------------------------------------------|
| 400 | Bad Request  | Inline error pod polem                     |
| 401 | Unauthorized | Redirect do /login                         |
| 409 | Conflict     | Inline error "Nazwa już istnieje"          |
| 422 | Validation   | Inline errors dla każdego pola             |
| 500 | Server Error | Toast "Coś poszło nie tak. Odśwież stronę" |
| 504 | Timeout      | Modal z opcjami recovery                   |

  ---
Responsywność, dostępność i bezpieczeństwo

Responsywność

Breakpoints (Tailwind default):

- sm: 640px
- md: 768px
- lg: 1024px
- xl: 1280px

Adaptive layouts:

| Component      | Mobile (<768px)      | Tablet (768-1024px) | Desktop (>1024px)      |
  |----------------|----------------------|---------------------|------------------------|
| Dashboard grid | 1 kolumna            | 2 kolumny           | 3 kolumny              |
| Cards editor   | 1 kolumna            | 1 kolumna           | 2 kolumny              |
| Auth forms     | Full width (padding) | Centered 400px      | Centered 400px         |
| Navigation     | Hamburger menu       | Horizontal          | Horizontal + shortcuts |
| Font sizes     | 14px base            | 16px base           | 16px base              |
| Touch targets  | Min 44×44px          | Min 44×44px         | Hover states           |

Testing devices:

- Mobile: iPhone SE (375px), iPhone 12 (390px), Samsung S21 (360px)
- Tablet: iPad (768px), iPad Pro (1024px)
- Desktop: 1366px, 1920px

  ---

Dostępność (a11y)

WCAG 2.1 Level AA compliance:

1. Perceivable:

- Kontrast tekstu minimum 4.5:1 (normalny), 3:1 (duży)
- Czcionka min 16px dla body, 18px dla fiszek
- Nie używać tylko koloru do przekazywania informacji (ikony + kolor)
- Alt text dla wszystkich ilustracji

2. Operable:

- Pełna keyboard navigation (Tab, Enter, Escape, Arrow keys)
- Focus indicators wyraźne (outline 2px solid, color contrast)
- Skip links: "Skip to main content"
- No keyboard traps
- Timeout warnings (session expiry) z opcją przedłużenia

3. Understandable:

- Jasny, prosty język (dla uczniów 10-18 lat)
- Error messages konkretne i konstruktywne
- Consistent navigation pattern
- Predictable actions (przyciski zawsze robią to samo)

4. Robust:

- Semantic HTML5 (<header>, <main>, <nav>, <article>)
- ARIA labels gdzie semantic HTML nie wystarcza
- ARIA live regions dla dynamic content:
  <div aria-live="polite" aria-atomic="true" class="sr-only">
    Fiszka usunięta. Pozostało 23 fiszki.
  </div>
  - Valid HTML (W3C validator)

Screen reader testing:

- NVDA (Windows)
- VoiceOver (macOS/iOS)
- TalkBack (Android)

Accessibility utilities (Tailwind):
  <!-- Screen reader only text -->
<span class="sr-only">Usuń fiszkę numer 5</span>

  <!-- Focus visible -->
  <button class="focus:outline-2 focus:outline-blue-600">

  ---
Bezpieczeństwo UI

1. XSS Prevention:

- Twig auto-escape enabled globally
- NIGDY {{ user_input|raw }}
- Content Security Policy header:
  Content-Security-Policy: default-src 'self';
  script-src 'self';
  style-src 'self' 'unsafe-inline';
  img-src 'self' data:;

2. CSRF Protection:
   {# Symfony Form - auto token #}
   {{ form_start(form) }}
   {{ form_widget(form) }}
   {{ form_end(form) }}

{# Manual form with token #}
  <form method="POST">
    <input type="hidden" name="_csrf_token" 
      value="{{ csrf_token('delete_set') }}">
  </form>

// XHR with CSRF
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

fetch('/api/sets', {
method: 'POST',
headers: {
'Content-Type': 'application/json',
'X-CSRF-Token': csrfToken
},
body: JSON.stringify(data)
});

3. Input Validation:

- Server-side validation ZAWSZE (client-side to tylko UX)
- Symfony Validator constraints na DTO
- Length limits enforced (source_text: 1000-10000, card fields: ≤1000)
- Email format validation
- Password strength (min 8 chars)

4. Session Security:

- HttpOnly cookies (no JavaScript access)
- Secure flag (HTTPS only)
- SameSite=Lax (CSRF protection)
- Session timeout: 2 hours idle → modal warning at 1:55

5. Password Security:

- Hashing: Argon2id (Symfony default)
- Min 8 characters (enforce in validation)
- No max length (bcrypt can handle it)
- Password reset tokens: 1 hour expiry, single-use

6. Rate Limiting:

- NIE w MVP (zgodnie z decyzją)
- Planowane później: 5/min dla /api/generate, bruteforce protection dla auth

  ---

Performance Optimization

1. Asset Optimization:

# config/packages/asset_mapper.yaml

framework:
asset_mapper:
paths:

- assets/
  importmap_polyfill: true
- Auto versioning (cache busting)
- Aggressive HTTP caching (1 year)
- Minification w produkcji

2. Turbo Drive:
   // app.js
   import '@hotwired/turbo'

// Config
Turbo.session.drive = true // Default enabled

- Cache wizyt w pamięci
- Partial page replacement
- Progress bar dla długich requestów

3. Lazy Loading:
   {# Paginacja zamiast load all #}
   {% for set in sets %}
   {# 20 items per page #}
   {% endfor %}

{{ knp_pagination_render(sets) }}

4. Debouncing:
   // Character counter
   let timeout;
   input.addEventListener('input', (e) => {
   clearTimeout(timeout);
   timeout = setTimeout(() => {
   updateCounter(e.target.value.length);
   }, 300);
   });

5. Optimistic UI:
   // Delete set
   async deleteSet(id) {
   // 1. Optimistic remove from DOM
   card.classList.add('opacity-0', 'scale-95');

   try {
   await fetch(`/api/sets/${id}`, { method: 'DELETE' });
   // 2. Success - remove from DOM
   card.remove();
   } catch (error) {
   // 3. Rollback - restore
   card.classList.remove('opacity-0', 'scale-95');
   showToast('Nie udało się usunąć. Spróbuj ponownie.');
   }
   }

6. Skeleton Loading:

  <!-- Podczas AI generation -->
  <div class="animate-pulse">
    <div class="h-32 bg-gray-200 rounded mb-4"></div>
    <div class="h-32 bg-gray-200 rounded mb-4"></div>
    <div class="h-32 bg-gray-200 rounded mb-4"></div>
  </div>

7. Image Optimization:

- Ilustracje: SVG (scalable, small)
- Ikony: Heroicons via Tailwind
- Lazy loading: <img loading="lazy">

8. Database Query Optimization:

- Eager loading relations w Doctrine
- Indices na (owner_id, deleted_at), (owner_id, updated_at)
- Pagination limits server-side

  ---

Komponenty UI (Twig Partials)

1. _button.html.twig

{# Usage: include('components/_button.html.twig', {
text: 'Zapisz',
type: 'primary|secondary|danger',
size: 'sm|md|lg',
disabled: false,
icon: 'heroicon-name'
}) #}

<button
type="{{ type|default('button') }}"
class="
btn
btn-{{ variant|default('primary') }}
btn-{{ size|default('md') }}
{% if disabled %}opacity-50 cursor-not-allowed{% endif %}
"
{% if disabled %}disabled{% endif %}
>

    {% if icon %}
      <svg class="w-5 h-5">...</svg>
    {% endif %}
    {{ text }}

  </button>

2. _card.html.twig

{# Flashcard component #}
  <div class="card" data-card-id="{{ card.tempId }}">
    <div class="card-header">
      <span class="text-sm text-gray-500">#{{ loop.index }}</span>
      <button data-action="click->editor#deleteCard">×</button>
    </div>

    <div class="card-section">
      <label>PRZÓD:</label>
      <textarea 
        data-editor-target="front"
        data-action="blur->editor#updateCard"
        class="auto-resize"
      >{{ card.front }}</textarea>
    </div>

    <div class="card-section">
      <label>TYŁ:</label>
      <textarea 
        data-editor-target="back"
        data-action="blur->editor#updateCard"
        class="auto-resize"
      >{{ card.back }}</textarea>
    </div>

  </div>

3. _input.html.twig

{# Form input with validation #}
  <div class="form-group">
    <label for="{{ id }}">{{ label }}</label>
    <input 
      type="{{ type|default('text') }}"
      id="{{ id }}"
      name="{{ name }}"
      value="{{ value|default('') }}"
      class="form-input {% if error %}border-red-500{% endif %}"
      {% if required %}required{% endif %}
    >
    {% if error %}
      <p class="text-sm text-red-600 mt-1">{{ error }}</p>
    {% endif %}
  </div>

4. _toast.html.twig

{# Toast notification #}
  <div 
    data-controller="toast"
    data-toast-duration-value="3000"
    class="toast toast-{{ type|default('info') }} hidden"
  >
    <div class="toast-content">
      {% if type == 'success' %}✓{% endif %}
      {{ message }}
    </div>
  </div>

5. _modal.html.twig

{# Modal dialog #}
  <div 
    data-controller="modal"
    data-modal-target="backdrop"
    class="modal-backdrop hidden"
  >
    <div class="modal-content">
      <div class="modal-header">
        <h3>{{ title }}</h3>
        <button data-action="click->modal#close">×</button>
      </div>
      <div class="modal-body">
        {{ content }}
      </div>
      <div class="modal-footer">
        {{ actions }}
      </div>
    </div>
  </div>

  ---
Design Tokens (Tailwind Config)

// tailwind.config.js
module.exports = {
theme: {
extend: {
colors: {
primary: {
50: '#eff6ff',
500: '#3b82f6', // Main brand color
600: '#2563eb',
700: '#1d4ed8',
},
// Semantic colors
success: '#10b981',
error: '#ef4444',
warning: '#f59e0b',
},
fontFamily: {
sans: ['Inter', 'system-ui', 'sans-serif'],
},
fontSize: {
xs: '0.75rem', // 12px
sm: '0.875rem', // 14px
base: '1rem', // 16px
lg: '1.125rem', // 18px
xl: '1.25rem', // 20px
},
spacing: {
// Tailwind default (4px base)
},
},
},
plugins: [
require('@tailwindcss/forms'),
],
}

  ---
Analytics & KPI Tracking

Events tracked (frontend):

1. fiszka_usunięta_w_edycji - User deleted AI-generated card
2. zestaw_zapisany - Set saved (includes origin breakdown)
3. generowanie_ai_started - AI generation initiated
4. generowanie_ai_timeout - AI generation timeout
5. generowanie_ai_success - AI generation succeeded

Implementation:
// analytics_controller.js
track(event, data) {
fetch('/api/analytics/track', {
method: 'POST',
headers: { 'Content-Type': 'application/json' },
body: JSON.stringify({ event, data, timestamp: Date.now() })
});
}

// Usage
this.track('fiszka_usunięta_w_edycji', {
job_id: this.jobIdValue,
card_index: index
});

Backend tracking (via ai_jobs table):

- generated_count - Set during generation
- accepted_count - Updated on POST /api/sets
- edited_count - Updated on POST /api/sets
- Deleted count calculated: generated_count - accepted_count

KPI calculations:

- Acceptance rate: accepted_count / generated_count (target: 75%)
- AI adoption rate: (total AI cards / total all cards) (target: 75%)

  ---

Unresolved Issues

Kwestie do rozstrzygnięcia w następnym etapie:

1. Turbo Streams complexity assessment
    - Należy zbadać czy Turbo Streams dla transition generowanie→edycja nie wprowadzi nadmiernego skomplikowania
    - Alternatywa: prosty redirect z flash data
    - Decyzja: po prototypie technicznym
2. localStorage auto-save implementation details
    - Określić dokładną strategię TTL i cleanup
    - Handling conflicts (multiple tabs)
    - Privacy considerations (sensitive data w localStorage)
3. Infinite scroll vs paginacja
    - Lista zestawów: czy implementować infinite scroll jako enhancement?
    - Impact na performance i UX
    - Decyzja: najpierw prosta paginacja, potem evaluate
4. Exact AI prompt engineering
    - Nie określono dokładnej instrukcji dla modelu AI
    - System prompt dla generowania fiszek w prostym języku
    - Format output (JSON schema)
    - To wymaga osobnego dokumentu
5. Analytics backend implementation
    - Czy użyć dedykowanej tabeli analytics_events czy wystarczy ai_jobs?
    - Retention policy dla eventów
    - Reporting dashboard (out of MVP scope?)
6. Error messages copy
    - Dokładne treści komunikatów błędów dla każdego case
    - Ton i styl (przyjazny dla młodzieży, ale profesjonalny)
    - Wymaga content writing session
7. Ilustracje i ikony
    - Źródło ilustracji dla empty states (custom? stock? generated?)
    - Spójny styl wizualny
    - Licencjonowanie
8. Progressive Web App (PWA) capabilities
    - Czy dodać manifest.json i basic service worker dla install prompt?
    - Offline fallback page
    - Not critical dla MVP, ale łatwe do dodania
9. Onboarding flow
    - Czy pokazywać tutorial przy pierwszym logowaniu?
    - Tooltips? Tour?
    - Decyzja: evaluate po user testing
10. Email templates
    - Design i copy dla reset hasła email
    - Możliwe w przyszłości: welcome email, podsumowania
    - Wymaga osobnego designu
11. Monitoring i observability
    - Frontend error tracking (Sentry?)
    - Performance monitoring (Web Vitals)
    - User session recording (Hotjar/LogRocket?)
    - Budget considerations
12. Accessibility audit timing
    - Kiedy przeprowadzić pełny audit a11y?
    - Po zbudowaniu MVP przed launch czy iteracyjnie?
    - Zasoby/narzędzia (axe DevTools, WAVE, manual testing)

Nierozstrzygnięte decyzje wymagające user feedback:

- Preferowany format wyświetlania fiszek (current card layout vs. alternative table view?)
- Czy użytkownicy chcą możliwość re-order fiszek przed zapisem?
- Czy pokazywać podgląd pierwszych 3 fiszek przed pełnym ekranem edycji?

Techniczne szczegóły do dopracowania:

- Exact Stimulus controller architecture (ile kontrolerów, jak komunikacja między nimi)
- Doctrine entity relationships details (cascade, orphanRemoval policies)
- PostgreSQL RLS policies exact implementation
- Docker compose health checks i startup order
