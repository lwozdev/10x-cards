Jako starszy programista frontendu Twoim zadaniem jest stworzenie szczegółowego planu wdrożenia nowego widoku w
aplikacji internetowej. Plan ten powinien być kompleksowy i wystarczająco jasny dla innego programisty frontendowego,
aby mógł poprawnie i wydajnie wdrożyć widok.

Najpierw przejrzyj następujące informacje:

1. Product Requirements Document (PRD):
   <prd>
   @.ai/prd.md
   </prd>

2. Opis widoku:
   <view_description>
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
-
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
   </view_description>

3. User Stories:
   <user_stories>
   ### Główny Przepływ - Generowanie Fiszek AI
---
*   ID: US-003
*   Tytuł: Generowanie zestawu fiszek z tekstu
*   Opis: Jako uczeń, chcę wkleić fragment moich notatek do aplikacji i uruchomić proces generowania, aby automatycznie otrzymać zestaw fiszek do nauki.
*   Kryteria akceptacji:
    1.  Na stronie głównej znajduje się duże pole tekstowe.
    2.  Przycisk "Generuj fiszki" jest aktywny tylko wtedy, gdy wklejony tekst ma długość od 1000 do 10 000 znaków.
    3.  Pod polem tekstowym wyświetla się licznik znaków i informacja o obowiązujących limitach.
    4.  Po kliknięciu przycisku "Generuj fiszki" wyświetlana jest animacja ładowania, informująca o trwającym procesie.
    5.  Po pomyślnym zakończeniu generowania, jestem przekierowany na ekran edycji i podglądu nowego zestawu.

---
*   ID: US-005
*   Tytuł: Przeglądanie i edycja wygenerowanych fiszek
*   Opis: Jako użytkownik, po wygenerowaniu fiszek, chcę je przejrzeć, poprawić ewentualne błędy w treści lub usunąć te, które mi nie odpowiadają, zanim zapiszę zestaw.
*   Kryteria akceptacji:
    1.  Wygenerowane fiszki są wyświetlane w formie listy (pytanie-odpowiedź lub awers-rewers).
    2.  Każdy awers i rewers fiszki jest edytowalny.
    3.  Przy każdej fiszce znajduje się przycisk do jej trwałego usunięcia z bieżącego zestawu.
    4.  Edycja fiszki jest równoznaczna z jej "zaakceptowaniem".
    5.  Usunięcie fiszki jest śledzone przez system analityczny.

---
*   ID: US-006
*   Tytuł: Zapisywanie nowego zestawu fiszek
*   Opis: Jako użytkownik, po przejrzeniu i ewentualnej edycji fiszek, chcę zapisać zestaw pod własną nazwą, aby móc do niego wrócić w przyszłości.
*   Kryteria akceptacji:
    1.  Na ekranie edycji znajduje się pole do wpisania nazwy zestawu.
    2.  Aplikacja automatycznie sugeruje nazwę na podstawie analizy wklejonego tekstu.
    3.  Przycisk "Zapisz zestaw" jest aktywny, gdy nazwa zestawu została podana.
    4.  Po zapisaniu zestawu jestem przekierowywany na stronę "Moje zestawy", gdzie widzę nowo dodany element.

---
*   ID: US-007
*   Tytuł: Obsługa błędów generowania
*   Opis: Jako użytkownik, chcę otrzymać jasny komunikat, jeśli AI nie będzie w stanie wygenerować fiszek z dostarczonego przeze mnie tekstu.
*   Kryteria akceptacji:
    1.  W przypadku błędu po stronie API, stan ładowania kończy się, a na ekranie pojawia się komunikat o błędzie (np. "Nie udało się wygenerować fiszek. Spróbuj ponownie lub zmień tekst źródłowy.").
    2.  Komunikat zawiera sugestie, co można zrobić dalej.
    3.  Użytkownik pozostaje na stronie z polem tekstowym, aby móc łatwo ponowić próbę.

---
### Przepływ Manualny i Zarządzanie Zestawami
---
*   ID: US-008
*   Tytuł: Tworzenie nowego, pustego zestawu
*   Opis: Jako użytkownik, chcę mieć możliwość stworzenia zestawu fiszek od zera, bez użycia AI, abym mógł ręcznie dodać własne pytania i odpowiedzi.
*   Kryteria akceptacji:
    1.  Na stronie "Moje zestawy" znajduje się przycisk "Stwórz nowy zestaw".
    2.  Po kliknięciu jestem przekierowany na ekran tworzenia zestawu, gdzie mogę nadać mu nazwę.
    3.  Ekran zawiera formularz do dodania pierwszej fiszki (Awers/Rewers) oraz przycisk "Dodaj kolejną fiszkę".

---
*   ID: US-009
*   Tytuł: Zarządzanie listą zestawów
*   Opis: Jako użytkownik, chcę widzieć wszystkie moje zapisane zestawy na jednej liście, aby móc łatwo nimi zarządzać i rozpoczynać naukę.
*   Kryteria akceptacji:
    1.  Strona "Moje zestawy" wyświetla listę wszystkich zestawów użytkownika.
    2.  Każdy element na liście pokazuje nazwę zestawu i liczbę zawartych w nim fiszek.
    3.  Przy każdym zestawie znajdują się przyciski "Ucz się" i "Usuń".
    4.  Kliknięcie "Usuń" powoduje wyświetlenie monitu z prośbą o potwierdzenie, a następnie usunięcie zestawu.
   </user_stories>

4. Endpoint Description:
   <endpoint_description>
   ### 2.2 Generate (AI) — Synchronous Generation

#### POST /api/generate

- **Description**: Synchronously generate flashcards from `source_text` (1,000–10,000 chars) using AI. Returns generated cards immediately (blocking call, timeout 30s).
- **Request JSON**:
  ```json
  { "source_text": "<1000..10000 chars>" }
  ```
- **Response 200**:
  ```json
  {
    "job_id": "uuid",
    "suggested_name": "Biologia - Fotosynteza",
    "cards": [
      { "front": "Co to jest fotosynteza?", "back": "Proces..." },
      { "front": "Gdzie zachodzi fotosynteza?", "back": "W chloroplastach..." }
    ],
    "generated_count": 15
  }
  ```
- **Validation**: Enforce length window server-side (mirrors DB check).
- **Errors**:
    - `422` length invalid or validation error
    - `504` AI timeout (>30s)
    - `500` AI service error
- **Notes**:
    - Frontend manages card editing/deletion in local state
    - `job_id` is returned for optional KPI tracking linkage when user saves the set
    - User can edit cards locally before calling `POST /api/sets` to persist

---

### 2.3 Sets

#### GET /api/sets

- **Description**: List “My Sets”. Supports search and sorting.
- **Query**: `q` (optional, case-insensitive name match), `page`, `per_page`, `sort=updated_at_desc|asc`.
- **Response 200**:
  ```json
  { "items": [ { "id":"uuid","name":"...","card_count":12,"updated_at":"..." } ], "total": 1, "page": 1, "per_page": 20 }
  ```

#### POST /api/sets

- **Description**: Create a set (empty for manual creation OR with cards from AI generation).
- **Request**:
  ```json
  {
    "name": "My Set Name",
    "cards": [
      { "front": "Question?", "back": "Answer", "origin": "ai", "edited": true }
    ],
    "job_id": "uuid"
  }
  ```
- **Notes**:
    - `cards` array is optional (omit for empty manual set)
    - `origin` must be "ai" or "manual" (defaults to "manual" if omitted)
    - `edited` (boolean, optional): `true` if user modified the card before saving. Backend sets `edited_by_user_at = now()` for edited cards.
    - `job_id` is optional, used for KPI tracking linkage to `ai_jobs` table
    - When `job_id` is provided, backend updates `ai_jobs` record with:
        - `set_id` = newly created set ID
        - `accepted_count` = count of cards with `origin='ai'` in request
        - `edited_count` = count of cards with `origin='ai'` AND `edited=true`
        - Deleted count can be calculated as: `generated_count - accepted_count`
- **Response 201**: `{ "id":"uuid","name":"...","card_count":15 }`
- **Errors**: `409` set name already used by owner; `422` validation errors

#### GET /api/sets/{set_id}

- **Description**: Get set details with cards (excluding soft-deleted).
- **Response 200**:
  ```json
  { "id":"uuid","name":"...","card_count":12,"cards":[{"id":"uuid","origin":"ai","front":"...","back":"..."}] }
  ```

#### PATCH /api/sets/{set_id}

- **Description**: Rename set.
- **Request**: `{ "name": "New Name" }`
- **Response 200**: `{ "id":"uuid","name":"New Name" }`

#### DELETE /api/sets/{set_id}

- **Description**: Soft-delete set (and cascade soft-delete cards); filtered from lists.
- **Response 204**

---

### 2.4 Cards (for saved sets)

#### POST /api/sets/{set_id}/cards

- **Description**: Add **manual** card to a saved set (allowed only **after** save).
- **Request**:
  ```json
  { "front": "...", "back": "..." }
  ```
- **Response 201**: `{ "id":"uuid","origin":"manual","front":"...","back":"..." }`

#### PATCH /api/sets/{set_id}/cards/{card_id}

- **Description**: Edit card (front/back); update `edited_by_user_at`.
- **Response 200**: Updated card.

#### DELETE /api/sets/{set_id}/cards/{card_id}

- **Description**: Soft-delete a card (decrement `card_count` via trigger or service).
- **Response 204**

---
   </endpoint_description>

5. Endpoint Implementation:
   <endpoint_implementation>
   @src/UI/Http/Controller/FlashcardGeneratorController.php
   @src/UI/Http/Controller/CreateSetController.php
   </endpoint_implementation>           

6. Tech Stack:
   <tech_stack>
   @.ai/tech-stack.md
   </tech_stack>

Przed utworzeniem ostatecznego planu wdrożenia przeprowadź analizę i planowanie wewnątrz tagów <
implementation_breakdown> w swoim bloku myślenia. Ta sekcja może być dość długa, ponieważ ważne jest, aby być dokładnym.

W swoim podziale implementacji wykonaj następujące kroki:

1. Dla każdej sekcji wejściowej (PRD, User Stories, Endpoint Description, Endpoint Implementation, Type Definitions,
   Tech Stack):

- Podsumuj kluczowe punkty
- Wymień wszelkie wymagania lub ograniczenia
- Zwróć uwagę na wszelkie potencjalne wyzwania lub ważne kwestie

2. Wyodrębnienie i wypisanie kluczowych wymagań z PRD
3. Wypisanie wszystkich potrzebnych głównych komponentów, wraz z krótkim opisem ich opisu, potrzebnych typów,
   obsługiwanych zdarzeń i warunków walidacji
4. Stworzenie wysokopoziomowego diagramu drzewa komponentów
5. Zidentyfikuj wymagane DTO i niestandardowe typy ViewModel dla każdego komponentu widoku. Szczegółowo wyjaśnij te nowe
   typy, dzieląc ich pola i powiązane typy.
6. Zidentyfikuj potencjalne zmienne stanu i niestandardowe hooki, wyjaśniając ich cel i sposób ich użycia
7. Wymień wymagane wywołania API i odpowiadające im akcje frontendowe
8. Zmapuj każdej historii użytkownika do konkretnych szczegółów implementacji, komponentów lub funkcji
9. Wymień interakcje użytkownika i ich oczekiwane wyniki
10. Wymień warunki wymagane przez API i jak je weryfikować na poziomie komponentów
11. Zidentyfikuj potencjalne scenariusze błędów i zasugeruj, jak sobie z nimi poradzić
12. Wymień potencjalne wyzwania związane z wdrożeniem tego widoku i zasugeruj możliwe rozwiązania

Po przeprowadzeniu analizy dostarcz plan wdrożenia w formacie Markdown z następującymi sekcjami:

1. Przegląd: Krótkie podsumowanie widoku i jego celu.
2. Routing widoku: Określenie ścieżki, na której widok powinien być dostępny.
3. Struktura komponentów: Zarys głównych komponentów i ich hierarchii.
4. Szczegóły komponentu: Dla każdego komponentu należy opisać:

- Opis komponentu, jego przeznaczenie i z czego się składa
- Główne elementy HTML i komponenty dzieci, które budują komponent
- Obsługiwane zdarzenia
- Warunki walidacji (szczegółowe warunki, zgodnie z API)
- Typy (DTO i ViewModel) wymagane przez komponent
- Propsy, które komponent przyjmuje od rodzica (interfejs komponentu)

5. Typy: Szczegółowy opis typów wymaganych do implementacji widoku, w tym dokładny podział wszelkich nowych typów lub
   modeli widoku według pól i typów.
6. Zarządzanie stanem: Szczegółowy opis sposobu zarządzania stanem w widoku, określenie, czy wymagany jest customowy
   hook.
7. Integracja API: Wyjaśnienie sposobu integracji z dostarczonym punktem końcowym. Precyzyjnie wskazuje typy żądania i
   odpowiedzi.
8. Interakcje użytkownika: Szczegółowy opis interakcji użytkownika i sposobu ich obsługi.
9. Warunki i walidacja: Opisz jakie warunki są weryfikowane przez interfejs, których komponentów dotyczą i jak wpływają
   one na stan interfejsu
10. Obsługa błędów: Opis sposobu obsługi potencjalnych błędów lub przypadków brzegowych.
11. Kroki implementacji: Przewodnik krok po kroku dotyczący implementacji widoku.

Upewnij się, że Twój plan jest zgodny z PRD, historyjkami użytkownika i uwzględnia dostarczony stack technologiczny.

Ostateczne wyniki powinny być w języku polskim i zapisane w pliku o nazwie .ai/{view-name}-view-implementation-plan.md.
Nie uwzględniaj żadnej analizy i planowania w końcowym wyniku.

Oto przykład tego, jak powinien wyglądać plik wyjściowy (treść jest do zastąpienia):

```markdown
# Plan implementacji widoku [Nazwa widoku]

## 1. Przegląd

[Krótki opis widoku i jego celu]

## 2. Routing widoku

[Ścieżka, na której widok powinien być dostępny]

## 3. Struktura komponentów

[Zarys głównych komponentów i ich hierarchii]

## 4. Szczegóły komponentów

### [Nazwa komponentu 1]

- Opis komponentu [opis]
- Główne elementy: [opis]
- Obsługiwane interakcje: [lista]
- Obsługiwana walidacja: [lista, szczegółowa]
- Typy: [lista]
- Propsy: [lista]

### [Nazwa komponentu 2]

[...]

## 5. Typy

[Szczegółowy opis wymaganych typów]

## 6. Zarządzanie stanem

[Opis zarządzania stanem w widoku]

## 7. Integracja API

[Wyjaśnienie integracji z dostarczonym endpointem, wskazanie typów żądania i odpowiedzi]

## 8. Interakcje użytkownika

[Szczegółowy opis interakcji użytkownika]

## 9. Warunki i walidacja

[Szczegółowy opis warunków i ich walidacji]

## 10. Obsługa błędów

[Opis obsługi potencjalnych błędów]

## 11. Kroki implementacji

1. [Krok 1]
2. [Krok 2]
3. [...]
```

Rozpocznij analizę i planowanie już teraz. Twój ostateczny wynik powinien składać się wyłącznie z planu wdrożenia w
języku polskim w formacie markdown, który zapiszesz w pliku .ai/{view-name}-view-implementation-plan.md i nie powinien
powielać ani powtarzać żadnej pracy wykonanej w podziale implementacji.
