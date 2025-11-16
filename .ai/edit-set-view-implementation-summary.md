# Podsumowanie implementacji widoku "Edycja wygenerowanych fiszek"

**Data:** 2025-11-16
**Widok:** GET `/sets/new/edit` + integracja z POST `/api/sets`

---

## ✅ Co zostało zaimplementowane

### 1. Backend Controller

**Plik**: `src/UI/Http/Controller/EditNewSetController.php`

- **Route**: `GET /sets/new/edit`
- **Auth**: `#[IsGranted('ROLE_USER')]`
- **Zadania**:
  - Odczyt `pending_set` z session
  - Walidacja: jeśli brak → redirect do `/generate` z flash message
  - Renderowanie template z danymi:
    - `jobId` (UUID job AI)
    - `suggestedName` (nazwa zaproponowana przez AI)
    - `cards[]` (tablica fiszek: `front`, `back`)
    - `sourceText` (oryginalny tekst źródłowy)
    - `generatedCount` (liczba wygenerowanych fiszek)

### 2. Frontend Template

**Plik**: `templates/sets/edit_new.html.twig`

**Komponenty zaimplementowane:**

#### PageHeader
- Tytuł: "Edytuj wygenerowane fiszki"
- Opis: instrukcja dla użytkownika

#### SetNameInput
- Input text z wartością `suggestedName`
- Edytowalne przez użytkownika
- Walidacja: min 3 znaki, max 100 znaków
- Hint text: czerwony gdy < 3, szary gdy OK
- Stimulus target: `setNameInput`
- Stimulus action: `input->edit-set#updateSetName`

#### StatsBar
- Statystyki zestawu w niebieskim boxie:
  - "Wygenerowano: X fiszek"
  - "Do zapisu: Y fiszek" (aktualizowane po usunięciach)
  - "Źródło: AI" (badge)
- Stimulus targets: `generatedCountText`, `cardsToSaveCount`

#### CardsList
- Lista wszystkich fiszek w `space-y-4`
- Container: `data-edit-set-target="cardsList"`

#### CardItem (dla każdej fiszki)
- Border, rounded, hover shadow
- Header: numer fiszki + przycisk "🗑️ Usuń"
- Grid 2 kolumny (front/back):
  - **Front textarea**: "Przód (pytanie)"
  - **Back textarea**: "Tył (odpowiedź)"
- Stimulus targets: `cardItem`, `frontTextarea`, `backTextarea`
- Stimulus actions:
  - `input->edit-set#updateCard` (na każdym textarea)
  - `click->edit-set#deleteCard` (na przycisku usuń)
- Data attributes: `data-index`, `data-field`

#### SaveButton
- Tekst dynamiczny: "Zapisz zestaw (X fiszek)"
- Disabled gdy:
  - Nazwa < 3 znaki
  - Brak fiszek
  - Jakakolwiek karta ma puste pole
- Loading state podczas zapisu
- Stimulus targets: `saveButton`, `saveButtonText`, `saveButtonCount`

#### CancelButton
- Tekst: "Anuluj"
- Wywołuje modal potwierdzenia jeśli `isDirty`
- Redirect do `/generate` jeśli brak zmian
- Stimulus action: `click->edit-set#handleCancel`

#### CancelModal
- HTML `<dialog>` element
- Pytanie: "Czy na pewno chcesz anulować?"
- Ostrzeżenie: "Wygenerowane fiszki zostaną utracone..."
- Przyciski:
  - "Nie, kontynuuj edycję" → zamknięcie modalu
  - "Tak, anuluj" → redirect do `/generate`
- Stimulus target: `cancelModal`

#### LoadingOverlay
- Fixed fullscreen overlay podczas zapisu
- Spinner + tekst "Zapisywanie zestawu..."
- Stimulus target: `loadingOverlay`

### 3. Stimulus Controller

**Plik**: `assets/controllers/edit_set_controller.js`

**Targets (13)**:
```javascript
[
    'setNameInput',
    'setNameHint',
    'generatedCountText',
    'cardsToSaveCount',
    'cardsList',
    'cardItem',
    'frontTextarea',
    'backTextarea',
    'saveButton',
    'saveButtonText',
    'saveButtonCount',
    'cancelModal',
    'loadingOverlay'
]
```

**Values (3)**:
```javascript
{
    jobId: String,           // UUID job AI
    initialCards: Array,     // Początkowy stan fiszek (do detekcji edycji)
    generatedCount: Number   // Liczba wygenerowanych fiszek
}
```

**State (3)**:
```javascript
{
    setName: '',      // Aktualna nazwa zestawu
    cards: [],        // Aktualne fiszki [{front, back}, ...]
    isDirty: false    // Czy były zmiany
}
```

**Kluczowe metody:**

**`connect()`**
- Inicjalizacja `cards` z `initialCardsValue`
- Inicjalizacja `setName` z input value
- Wywołanie `validateForm()`

**`updateSetName(event)`**
- Update `this.setName` z trimem
- Ustawienie `isDirty = true`
- Walidacja formularza

**`updateCard(event)`**
- Pobiera `index` i `field` z `dataset`
- Update `this.cards[index][field]`
- Ustawienie `isDirty = true`
- Walidacja formularza

**`deleteCard(event)`**
- Confirm dialog ("Czy na pewno chcesz usunąć tę fiszkę?")
- Usunięcie z `this.cards` (splice)
- Usunięcie DOM element
- Wywołanie `reindexCards()`
- Update liczników + walidacja

**`reindexCards()`**
- Re-indexing wszystkich kart po usunięciu
- Update `data-index` na card items, textareas, buttons
- Update numeru fiszki (#1, #2, ...)

**`updateCardsCount()`**
- Update `cardsToSaveCount`
- Update `saveButtonCount`

**`validateForm()` → boolean**
- Walidacja:
  1. Nazwa: 3-100 znaków
  2. Minimum 1 fiszka
  3. Każda fiszka: front i back nie puste
- Update `saveButton.disabled`
- Update hint text (czerwony/szary)
- Return `isValid`

**`handleSave(event)` (async)**
- `event.preventDefault()`
- Walidacja przed wysłaniem
- Pokazanie loading overlay
- Przygotowanie danych:
  ```javascript
  {
      front: card.front,
      back: card.back,
      origin: 'ai',
      edited: card !== initialCard  // detekcja edycji
  }
  ```
- `fetch('/api/sets', { method: 'POST', ... })`
- Success:
  - Alert z sukcesem (tymczasowo)
  - Redirect do `/generate` (TODO: `/sets` gdy będzie lista)
- Error:
  - Alert z błędem

**`handleCancel()`**
- Jeśli `isDirty` → pokazanie `cancelModal`
- Jeśli nie → direct redirect do `/generate`

**`closeCancelModal()`**
- Zamknięcie modalu (kontynuacja edycji)

**`confirmCancel()`**
- Zamknięcie modalu + redirect do `/generate`

**`showLoading()` / `hideLoading()`**
- Toggle `hidden` class na overlay

### 4. Integracja z backend

#### Request format (POST /api/sets)
```json
{
  "name": "Nazwa zestawu",
  "cards": [
    {
      "front": "Pytanie",
      "back": "Odpowiedź",
      "origin": "ai",
      "edited": true
    }
  ],
  "job_id": "uuid-job-id"
}
```

#### Response format (success)
```json
{
  "id": "uuid-set-id",
  "name": "Nazwa zestawu",
  "card_count": 8
}
```

**Headers**:
- `Location: /api/sets/{id}`
- Status: `201 Created`

#### Error responses
- **409 Conflict**: Duplicate set name
  ```json
  {
    "error": "Set with this name already exists",
    "code": "duplicate_set_name",
    "field": "name"
  }
  ```

- **404 Not Found**: Job ID not found
  ```json
  {
    "error": "AI job not found",
    "code": "job_not_found"
  }
  ```

- **422 Unprocessable Entity**: Validation errors
  ```json
  {
    "error": "Validation failed",
    "code": "validation_error",
    "violations": [
      {"field": "name", "message": "..."},
      {"field": "cards[0].front", "message": "..."}
    ]
  }
  ```

### 5. Session management

**W GenerateCardsController** (zapisuje do session):
```php
$request->getSession()->set('pending_set', [
    'job_id' => '...',
    'suggested_name' => '...',
    'cards' => [['front' => '...', 'back' => '...'], ...],
    'source_text' => '...',
    'generated_count' => 10,
]);
```

**W EditNewSetController** (odczytuje z session):
```php
$pendingSet = $request->getSession()->get('pending_set');
// Jeśli null → redirect + flash
```

**W CreateSetController** (usuwa z session):
```php
$request->getSession()->remove('pending_set');
// Po sukcesie zapisu
```

### 6. Flow użytkownika (end-to-end)

1. **User na `/generate`**
   - Wkleja tekst (1000-10000 znaków)
   - Klik "Generuj fiszki"

2. **Loading overlay**
   - "Analizuję tekst..." → "Tworzę fiszki..."

3. **POST `/api/generate`**
   - Backend generuje fiszki (mock generator)
   - Zapisuje do session: `pending_set`
   - Response JSON

4. **Redirect do `/sets/new/edit`**
   - Frontend: `window.location.href = '/sets/new/edit'`

5. **User na `/sets/new/edit`**
   - Widzi: nazwę (edytowalna), fiszki (edytowalne), statystyki
   - Może:
     - Edytować nazwę zestawu
     - Edytować przód/tył fiszek
     - Usunąć fiszki (z confirm)
     - Anulować (z confirm jeśli były zmiany)
     - Zapisać (jeśli walidacja OK)

6. **User klika "Zapisz zestaw"**
   - Walidacja client-side
   - Loading overlay "Zapisywanie zestawu..."
   - POST `/api/sets` z JSON

7. **Backend zapisuje do bazy**
   - Tworzy encję Set
   - Tworzy encje Card (dla każdej fiszki)
   - Zapisuje origin='ai', edited flag
   - KPI tracking (jeśli zaimplementowane)
   - Usuwa `pending_set` z session

8. **Success**
   - Alert: "Sukces! Zestaw zapisany..."
   - Redirect do `/generate` (TODO: `/sets` lista)

### 7. Walidacja

#### Client-side (Stimulus)
- **Nazwa**: min 3, max 100 znaków
- **Fiszki**: minimum 1
- **Front/back**: każde pole required, trim != ''
- **Button**: disabled jeśli walidacja failed

#### Server-side (Symfony Validator)
- **Nazwa**: required, max 255
- **Cards**: max 100 (DoS protection)
- **Front/back**: required, max 1000 każde
- **Origin**: choice ['ai', 'manual']
- **Job ID**: UUID format

#### Domain-side (Value Objects)
- `SetName::fromString()` - dodatkowa walidacja
- `CardFront::fromString()` - dodatkowa walidacja
- `CardBack::fromString()` - dodatkowa walidacja
- `AiJobId::fromString()` - UUID validation

### 8. Zmiany w innych plikach

**`assets/stimulus_bootstrap.js`**
- Dodano import `EditSetController`
- Zarejestrowano jako `'edit-set'`

**`assets/controllers/generate_controller.js`**
- Odkomentowano redirect: `window.location.href = '/sets/new/edit'`
- Usunięto tymczasowy alert

**`src/UI/Http/Controller/CreateSetController.php`**
- Dodano session cleanup: `$request->getSession()->remove('pending_set')`

---

## 📋 Co jest do zrobienia (opcjonalnie / future)

### KPI Tracking

**Event**: `set_created`
```php
[
    'user_id' => '...',
    'set_id' => '...',
    'origin' => 'ai', // lub 'manual'
    'job_id' => '...', // tylko dla origin=ai
    'cards_generated' => 10, // z session (tylko AI)
    'cards_saved' => 8,
    'cards_deleted' => 2, // generated - saved
    'cards_edited' => 3, // ile fiszek miało edited=true
    'acceptance_rate' => 0.8, // saved / generated
    'timestamp' => '...',
]
```

**Gdzie dodać**:
- W `CreateSetHandler` lub w `CreateSetController` po sukcesie
- Eventbus/EventDispatcher Symfony
- Zapis do tabeli `analytics_events` lub wysyłka do zewnętrznego systemu

**Metryki do obliczenia**:
- **Acceptance rate**: średnia `acceptance_rate` dla `origin=ai`
  - Cel: **≥75%** (max 25% usuwanych fiszek)
- **AI adoption**: `count(origin=ai) / count(all)` (procent zestawów z AI)
  - Cel: **≥75%**
- **Edit rate**: `count(cards with edited=true) / count(all AI cards)`
  - Pomocnicza: ile % fiszek AI jest edytowanych

### Widok listy zestawów (`/sets`)

**TODO**: Implementacja GET `/sets`
- Lista wszystkich zestawów użytkownika
- Sortowanie: created_at DESC
- Kolumny: nazwa, liczba fiszek, źródło (AI/Manual), created_at
- Akcje: Ucz się, Edytuj, Usuń
- Redirect po zapisie: `window.location.href = '/sets'`

### Widok szczegółów zestawu (`/sets/{id}`)

**TODO**: Implementacja GET `/sets/{id}`
- Wyświetlenie wszystkich fiszek
- Przycisk "Rozpocznij naukę"
- Edycja zestawu (dodanie/usunięcie fiszek)
- Statystyki nauki (jeśli już uczył się)

### Widok nauki (`/sets/{id}/learn`)

**TODO**: Implementacja algorytmu spaced repetition
- Wyświetlenie frontu fiszki
- Przycisk "Pokaż odpowiedź"
- Ocena: "Wiem" / "Nie wiem"
- Aktualizacja `next_review_date`, `ease_factor`, `interval`
- Podsumowanie sesji

### Obsługa błędów w UI

**TODO**: Zamiast `alert()`, użyć toast notifications lub modals
- Biblioteka: np. SweetAlert2, Toastify
- Dla success: zielony toast
- Dla error: czerwony modal z details

### Optymalizacje

**TODO**:
- Autosave draft do localStorage (jeśli user anuluje)
- Undo/Redo dla edycji
- Keyboard shortcuts (Ctrl+S = save, Esc = cancel)
- Bulk operations (zaznacz wiele + usuń)

---

## 🧪 Testy

### Feature tests (TODO)

**`tests/Feature/EditNewSetControllerTest.php`**
- Test GET bez session → redirect + flash
- Test GET z session → renderuje poprawnie
- Test GET z niepełnymi danymi w session → nie crashuje

**`tests/Feature/CreateSetControllerTest.php`**
- Test POST z poprawnymi danymi → 201 + Location header
- Test POST z duplikatem nazwy → 409
- Test POST z walidacją errors → 422
- Test POST z niepoprawnym job_id → 404
- Test że session jest czyszczona po sukcesie

### Stimulus tests (opcjonalnie)

- Update karty → `this.cards[index]` aktualizowane
- Usuwanie karty → array splice + DOM removal + reindex
- Walidacja formularza → button disabled states
- Save flow → fetch call z właściwym JSON

---

## 📁 Pliki zmienione/utworzone

### Nowe pliki
```
src/UI/Http/Controller/EditNewSetController.php
templates/sets/edit_new.html.twig
assets/controllers/edit_set_controller.js
.ai/edit-set-view-implementation-summary.md (ten plik)
```

### Zmodyfikowane pliki
```
assets/stimulus_bootstrap.js (rejestracja edit-set controller)
assets/controllers/generate_controller.js (odkomentowanie redirect)
src/UI/Http/Controller/CreateSetController.php (session cleanup)
```

---

## 🎯 Status MVP

### ✅ Zaimplementowane funkcjonalności

1. **Rejestracja/Login** (istniejące)
2. **Generowanie fiszek AI** ✅
   - Widok `/generate`
   - Real-time walidacja
   - Loading states
   - Error handling
   - Session storage
3. **Edycja wygenerowanych fiszek** ✅
   - Widok `/sets/new/edit`
   - Edycja nazwy zestawu
   - Edycja fiszek (front/back)
   - Usuwanie fiszek
   - Walidacja client + server
   - Session management
4. **Zapis zestawu do bazy** ✅
   - POST `/api/sets`
   - Origin tracking (AI/manual)
   - Edit detection
   - Session cleanup

### 🚧 Do zaimplementowania (MVP)

5. **Lista zestawów** (`/sets`)
6. **Szczegóły zestawu** (`/sets/{id}`)
7. **Nauka z algorytmem spaced repetition** (`/sets/{id}/learn`)
8. **KPI tracking** (acceptance rate, AI adoption)

### 📊 Metryki sukcesu (do weryfikacji)

- [ ] **75% acceptance rate** - czy użytkownicy usuwają max 25% fiszek?
- [ ] **75% AI adoption** - czy 75% zestawów powstaje z AI?
- [ ] **< 1% timeoutów** - czy AI generuje w < 30s?
- [ ] **< 5% AI failures** - czy OpenRouter działa stabilnie?

---

## 🔗 Linki do dokumentacji

- [Plan generate view](.ai/generate-view-implementation-plan.md)
- [Summary generate view](.ai/generate-view-implementation-summary.md)
- [Zasady implementacji](.ai/symfony.md)
- [Symfony UX Stimulus](https://symfony.com/bundles/ux-stimulus/current/index.html)

---

**Ostatnia aktualizacja**: 2025-11-16
**Status**: ✅ Widok edycji DONE, gotowy do testów end-to-end
