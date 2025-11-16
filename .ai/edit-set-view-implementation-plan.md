# Plan implementacji widoku Edycja Fiszek (po generowaniu AI)

## 1. Przegląd

Widok edycji fiszek umożliwia użytkownikowi przejrzenie, edycję i usunięcie wygenerowanych przez AI fiszek przed finalnym zapisaniem zestawu. Użytkownik może również dodać własne fiszki ręcznie (mixing AI + manual), co realizuje kluczową funkcjonalność MVP: **elastyczna edycja przed zatwierdzeniem**.

Widok stanowi drugi krok w głównym przepływie MVP: **generowanie → edycja → zapis**.

Ten widok jest kluczowy dla osiągnięcia sukcesu MVP (75% acceptance rate), ponieważ pozwala użytkownikowi doprecyzować wygenerowane fiszki i dostosować je do swoich potrzeb.

## 2. Routing widoku

**Ścieżka:** `/sets/new/edit`

**Metoda HTTP:**
- GET - wyświetlenie widoku edycji (z danymi z session po generowaniu)

**Dostęp:** Wymaga uwierzytelnienia (`#[IsGranted('ROLE_USER')]`)

**Źródło danych:**
Dane ładowane z session storage (zapisane przez widok `/generate` po sukcesie):
- `job_id` - UUID job'a AI (do KPI tracking)
- `suggested_name` - sugestia nazwy zestawu z AI
- `cards` - array wygenerowanych fiszek
- `source_text` - oryginalny tekst źródłowy
- `generated_count` - liczba wygenerowanych fiszek

## 3. Struktura komponentów

Widok składa się z następujących głównych komponentów:

```
EditSetView (główny kontener Twig + Stimulus controller)
├── StickyHeader
│   └── SourceTextPreview (collapsible)
├── FlashcardGrid (responsive CSS Grid)
│   └── FlashcardCard[] (array of cards)
│       ├── CardNumber
│       ├── FrontTextarea (inline editable)
│       ├── BackTextarea (inline editable)
│       ├── DeleteButton
│       └── EditIndicator (✏️ conditional)
├── AddCardButton
├── StickyFooter
│   ├── SetNameInput (pre-filled with AI suggestion ✨)
│   ├── DuplicateNameWarning (conditional)
│   └── SaveButton ("Zapisz zestaw (N fiszek)")
├── BeforeUnloadPrompt (event listener)
└── RecoveryPrompt (modal, conditional)
```

**Zarządzanie stanem:** Stimulus controller `edit_set_controller.js` zarządza całym stanem edycji (local state przed zapisem).

## 4. Szczegóły komponentów

### StickyHeader

**Opis komponentu:**
Sticky header zawierający kolapsowany podgląd oryginalnego tekstu źródłowego. Pozwala użytkownikowi szybko przypomnieć sobie kontekst notatek, z których wygenerowano fiszki, bez opuszczania widoku edycji.

**Główne elementy:**
- `<header class="sticky top-0 bg-white shadow-sm z-40 p-4">` - sticky positioning
  - Collapsed state (default):
    - Pierwsze ~100 znaków source text + "..."
    - Button "Pokaż cały tekst" (expand icon ▼)
  - Expanded state:
    - Pełny source text w `<pre>` lub `<div>` z scrollem
    - Button "Ukryj" (collapse icon ▲)

**Obsługiwane interakcje:**
- Click na "Pokaż cały tekst" → expand (toggle `isExpanded`)
- Click na "Ukryj" → collapse (toggle `isExpanded`)

**Obsługiwana walidacja:**
Brak

**Typy:**
```typescript
HeaderState {
  sourceText: string
  isExpanded: boolean
}
```

**Propsy:**
- `sourceText` (z session, przekazany przez Twig)
- `initialExpanded` (default: false)

**Stimulus actions:**
- `click->editSet#toggleSourceText` - toggle expand/collapse

---

### FlashcardGrid

**Opis komponentu:**
Responsive grid layout zawierający wszystkie fiszki (wygenerowane + dodane ręcznie). Layout dostosowuje się do rozmiaru ekranu: 1 kolumna na mobile, 2 kolumny na desktop.

**Główne elementy:**
- `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">` (Tailwind CSS Grid)
  - Wiele `FlashcardCard` komponentów (loop w Twig)

**Obsługiwane interakcje:**
Deleguje do FlashcardCard (sam grid jest pasywny)

**Obsługiwana walidacja:**
Brak (walidacja w dzieciach)

**Typy:**
```typescript
FlashcardData[] // array of cards
```

**Propsy:**
- `cards` (array przekazany z Stimulus value)

---

### FlashcardCard

**Opis komponentu:**
Pojedyncza karta fiszki z inline editing. Główny komponent interaktywny widoku. Użytkownik może edytować front/back w miejscu (bez modali), usunąć fiszkę, oraz widzi visual indicator jeśli fiszka była edytowana.

**Główne elementy:**
- `<div class="bg-white border rounded-lg p-4 shadow-sm" data-editSet-target="flashcardCard">` - card container
  - Header:
    - `<span class="text-sm text-gray-500">Fiszka #X</span>` - numer pozycji
    - `<span data-editSet-target="editIndicator" class="hidden">✏️</span>` - indicator edycji (pokazuje się gdy edited=true)
    - `<button data-action="click->editSet#deleteCard">🗑️ Usuń</button>` - delete button
  - Body:
    - **FrontTextarea:**
      - `<label>Przód (pytanie)</label>`
      - `<textarea data-editSet-target="cardFront" data-card-id="X" data-action="input->editSet#handleCardEdit" class="w-full border rounded p-2 resize-y">` - auto-resize
      - Character counter: `<span>X / 1000</span>`
    - **BackTextarea:**
      - `<label>Tył (odpowiedź)</label>`
      - `<textarea data-editSet-target="cardBack" data-card-id="X" data-action="input->editSet#handleCardEdit" class="w-full border rounded p-2 resize-y">`
      - Character counter: `<span>X / 1000</span>`

**Obsługiwane interakcje:**
- Input na front/back textarea → `handleCardEdit()` w Stimulus
  - Update card data w local state
  - Set `edited = true`
  - Show ✏️ indicator
  - Mark `hasUnsavedChanges = true`
  - Trigger auto-save (debounced)
- Click na delete button → `deleteCard()` w Stimulus
  - Fade-out + slide-up CSS animation
  - Mark card as `isDeleted = true` (soft delete)
  - Update card count
  - ARIA live announcement
  - Mark `hasUnsavedChanges = true`
- Focus/blur na textareas → optional highlighting

**Obsługiwana walidacja:**
- Front max 1000 znaków (client-side, real-time feedback)
- Back max 1000 znaków (client-side, real-time feedback)
- Jeśli przekroczone: red border, character counter red, disable save button

**Typy:**
```typescript
FlashcardData {
  tempId: string           // client-side UUID (brak DB ID jeszcze)
  front: string
  back: string
  origin: 'ai' | 'manual'  // źródło fiszki
  edited: boolean          // czy user modyfikował
  isDeleted: boolean       // soft delete (nie wysyłamy do API)
}
```

**Propsy:**
- `card: FlashcardData` (z Stimulus state)
- `index: number` (pozycja w liście, dla numeru)

**Stimulus targets:**
- `flashcardCard` - cały kontener karty
- `cardFront` - textarea front (data-card-id attribute dla identyfikacji)
- `cardBack` - textarea back
- `editIndicator` - ✏️ element (pokazywany/ukrywany)

**Stimulus actions:**
- `input->editSet#handleCardEdit` - edit card content
- `click->editSet#deleteCard` - delete card

---

### AddCardButton

**Opis komponentu:**
Przycisk pozwalający użytkownikowi dodać własną fiszkę ręcznie (origin='manual'). Umożliwia mixing AI-generated cards z manually created cards, co jest kluczowe dla elastyczności MVP.

**Główne elementy:**
- `<button data-action="click->editSet#addCard" class="w-full border-2 border-dashed border-gray-300 rounded-lg p-6 text-gray-500 hover:border-blue-500 hover:text-blue-500 transition">` - dashed border style
  - Icon: "+"
  - Text: "Dodaj własną fiszkę"

**Obsługiwane interakcje:**
- Click → `addCard()` w Stimulus
  - Create new FlashcardData z origin='manual', empty front/back
  - Add to cards array
  - Slide-down CSS animation
  - Auto-focus na front textarea nowej fiszki
  - Mark `hasUnsavedChanges = true`
  - Scroll do nowej fiszki

**Obsługiwana walidacja:**
Brak (zawsze dostępny)

**Typy:**
Brak (trigger action tylko)

**Propsy:**
Brak

**Stimulus actions:**
- `click->editSet#addCard`

---

### StickyFooter

**Opis komponentu:**
Sticky footer zawierający pole nazwy zestawu (pre-wypełnione sugestią AI z ikonką ✨) oraz przycisk zapisu. Footer pozostaje widoczny podczas scrollowania, zapewniając łatwy dostęp do zapisu niezależnie od pozycji na stronie.

**Główne elementy:**
- `<footer class="sticky bottom-0 bg-white border-t shadow-lg p-4 z-40">` - sticky positioning
  - **SetNameInput:**
    - `<label>Nazwa zestawu</label>`
    - `<div class="relative">`
      - `<input type="text" data-editSet-target="setNameInput" data-action="input->editSet#handleSetNameChange" value="{{ suggestedName }}" class="w-full border rounded p-3">`
      - `<span class="absolute right-3 top-3">✨</span>` - AI suggestion indicator (pokazuje się tylko jeśli nazwa nie była edytowana)
    - `<span data-editSet-target="duplicateWarning" class="hidden text-red-600 text-sm">Zestaw o tej nazwie już istnieje</span>` - duplicate warning
  - **SaveButton:**
    - `<button data-editSet-target="saveButton" data-action="click->editSet#saveSet" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 disabled:bg-gray-300">` - disabled gdy walidacja failed
      - Text: "Zapisz zestaw (<span data-editSet-target="cardCount">N</span> fiszek)"
      - Loading state: spinner + "Zapisywanie..."

**Obsługiwane interakcje:**
- Input na setNameInput (debounced 500ms) → `handleSetNameChange()` w Stimulus
  - Update `setName` value
  - Trigger duplicate check via API (debounced)
  - Hide ✨ icon (user edytował nazwę)
  - Mark `hasUnsavedChanges = true`
- Click na saveButton → `saveSet()` w Stimulus
  - Validate setName (not empty, not duplicate)
  - Filter `isDeleted` cards
  - POST /api/sets z all card data
  - Handle success: clear localStorage, redirect to /sets
  - Handle error: show error message

**Obsługiwana walidacja:**
- setName nie jest puste (required)
- setName jest unikalna dla usera (debounced check via API)
- setName max 255 chars (assumed)
- Przynajmniej 1 fiszka (cards.filter(c => !c.isDeleted).length >= 1)

**Typy:**
```typescript
FooterState {
  setName: string
  isDuplicate: boolean
  cardCount: number  // liczba aktywnych (nie deleted) fiszek
  isSaving: boolean
}
```

**Propsy:**
- `suggestedName` (z session, przekazany przez Twig)
- `initialCardCount` (z session)

**Stimulus targets:**
- `setNameInput` - input nazwy
- `duplicateWarning` - komunikat o duplikacie
- `saveButton` - przycisk zapisu
- `cardCount` - element z liczbą fiszek

**Stimulus actions:**
- `input->editSet#handleSetNameChange` - debounced set name change
- `click->editSet#saveSet` - save set

---

### BeforeUnloadPrompt

**Opis komponentu:**
Listener na `beforeunload` event, który pokazuje browser prompt gdy user próbuje opuścić stronę z niezapisanymi zmianami. Chroni przed przypadkową utratą edycji.

**Główne elementy:**
- Event listener w Stimulus controller (nie ma HTML)
- Dodatkowo: Turbo `turbo:before-visit` listener (Turbo może omijać beforeunload)

**Obsługiwane interakcje:**
- User próbuje:
  - Zamknąć kartę/okno
  - Nawigować back/forward
  - Odświeżyć stronę (F5)
  - Turbo navigation do innej strony
- Reakcja:
  - Jeśli `hasUnsavedChanges = true`: pokazanie browser prompt "Masz niezapisane zmiany. Czy na pewno chcesz opuścić stronę?"
  - Jeśli `hasUnsavedChanges = false`: brak prompt, swobodne wyjście

**Obsługiwana walidacja:**
Brak (tylko check `hasUnsavedChanges` flag)

**Typy:**
```typescript
PromptState {
  hasUnsavedChanges: boolean
}
```

**Propsy:**
Brak (wewnętrzna logika Stimulus)

**Stimulus implementation:**
```javascript
connect() {
  window.addEventListener('beforeunload', this.handleBeforeUnload);
  document.addEventListener('turbo:before-visit', this.handleTurboBeforeVisit);
}

disconnect() {
  window.removeEventListener('beforeunload', this.handleBeforeUnload);
  document.removeEventListener('turbo:before-visit', this.handleTurboBeforeVisit);
}

handleBeforeUnload = (event) => {
  if (this.hasUnsavedChangesValue) {
    event.preventDefault();
    event.returnValue = ''; // Browser pokazuje własny prompt
  }
}

handleTurboBeforeVisit = (event) => {
  if (this.hasUnsavedChangesValue) {
    if (!confirm('Masz niezapisane zmiany. Czy na pewno chcesz opuścić stronę?')) {
      event.preventDefault();
    }
  }
}
```

---

### RecoveryPrompt

**Opis komponentu:**
Modal wyświetlany na mount widoku jeśli w localStorage znajdują się niezapisane dane z poprzedniej sesji (TTL < 24h). Pozwala użytkownikowi przywrócić niezapisane zmiany po zamknięciu przeglądarki, crashu, etc.

**Główne elementy:**
- `<dialog data-editSet-target="recoveryModal" class="rounded-lg shadow-xl p-6 max-w-md">` - HTML dialog
  - Icon: 💾
  - Tytuł: "Znaleziono niezapisane zmiany"
  - Tekst: "Masz niezapisane zmiany z dnia [timestamp]. Czy chcesz je przywrócić?"
  - Przyciski:
    - "Przywróć" → `recoverData()` w Stimulus
    - "Zacznij od nowa" → `discardRecovery()` w Stimulus

**Obsługiwane interakcje:**
- Mount widoku → `checkRecovery()` w Stimulus
  - Read localStorage key `flashcard_autosave`
  - Check TTL (timestamp < 24h ago)
  - Jeśli valid: show RecoveryModal
  - Jeśli invalid/brak: brak akcji
- Click "Przywróć" → `recoverData()`
  - Load data from localStorage
  - Populate Stimulus values (cards, setName, etc.)
  - Render cards in view
  - Close modal
- Click "Zacznij od nowa" → `discardRecovery()`
  - Clear localStorage
  - Close modal
  - Use data from session (fresh from generate)

**Obsługiwana walidacja:**
- TTL validation: `Date.now() - data.timestamp < 24*60*60*1000`

**Typy:**
```typescript
LocalStorageData {
  timestamp: number        // Date.now() when saved
  jobId: string | null
  suggestedName: string
  setName: string
  sourceText: string
  cards: FlashcardData[]   // current state of cards
}
```

**Propsy:**
Brak (data z localStorage)

**Stimulus targets:**
- `recoveryModal` - dialog element

**Stimulus actions:**
- `click->editSet#recoverData` - przywróć dane
- `click->editSet#discardRecovery` - odrzuć recovery

**localStorage key:**
`flashcard_autosave_${userId}` lub `flashcard_autosave_session` jeśli user nie zalogowany

---

## 5. Typy

### Request DTO (backend, już istnieje)

```php
class CreateSetRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    /** @var CreateSetCardRequestDto[] */
    #[Assert\Valid]
    private array $cards = [];

    private ?string $jobId = null;

    // Getters/setters...
}

class CreateSetCardRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 1000)]
    private string $front;

    #[Assert\NotBlank]
    #[Assert\Length(max: 1000)]
    private string $back;

    #[Assert\Choice(choices: ['ai', 'manual'])]
    private string $origin = 'manual';

    private bool $edited = false;

    // Getters/setters...
}
```

### Response DTO (backend, już istnieje)

```php
class CreateSetResponse
{
    public function __construct(
        public readonly string $id,      // UUID created set
        public readonly string $name,
        public readonly int $card_count,
    ) {}
}
```

### Frontend ViewModel (Stimulus Values)

```javascript
// Stimulus controller values
static values = {
  // Job ID z generowania (do KPI tracking)
  jobId: { type: String, default: null },

  // Sugestia nazwy z AI
  suggestedName: { type: String, default: '' },

  // Aktualna nazwa zestawu (user może edytować)
  setName: { type: String, default: '' },

  // Oryginalny source text (do preview w header)
  sourceText: { type: String, default: '' },

  // Cards data (JSON string, parsowany do array)
  cards: { type: String, default: '[]' },

  // Czy są niezapisane zmiany
  hasUnsavedChanges: { type: Boolean, default: false },

  // Czy nazwa jest duplikatem
  isDuplicateName: { type: Boolean, default: false },

  // Czy trwa zapisywanie
  isSaving: { type: Boolean, default: false },

  // Czy source text jest expanded
  isSourceExpanded: { type: Boolean, default: false }
}
```

### FlashcardData (internal type)

```typescript
interface FlashcardData {
  tempId: string;          // client-side UUID (używamy zamiast DB ID)
  front: string;
  back: string;
  origin: 'ai' | 'manual'; // źródło fiszki
  edited: boolean;         // true jeśli user modyfikował
  isDeleted: boolean;      // soft delete na kliencie
}
```

### LocalStorageData (internal type)

```typescript
interface LocalStorageData {
  timestamp: number;       // Date.now() kiedy zapisano
  jobId: string | null;
  suggestedName: string;
  setName: string;
  sourceText: string;
  cards: FlashcardData[];
}
```

---

## 6. Zarządzanie stanem

### Stimulus Controller: `edit_set_controller.js`

Zarządzanie stanem odbywa się przez **Stimulus controller** z local state (wszystkie edycje są lokalne do momentu kliknięcia "Zapisz zestaw"). Brak server updates podczas edycji - wszystko dzieje się client-side.

**Values (reactive state):**
- `jobIdValue` - UUID job'a z generowania
- `suggestedNameValue` - sugestia AI
- `setNameValue` - aktualna nazwa (user może edytować)
- `sourceTextValue` - oryginalny tekst
- `cardsValue` - JSON string z array of FlashcardData
- `hasUnsavedChangesValue` - boolean flag
- `isDuplicateNameValue` - boolean flag
- `isSavingValue` - boolean flag
- `isSourceExpandedValue` - boolean flag

**Targets (DOM references):**
- `setNameInput` - input nazwy zestawu
- `duplicateWarning` - komunikat o duplikacie
- `saveButton` - przycisk zapisu
- `cardCount` - element z liczbą fiszek
- `flashcardCard` (multiple) - wszystkie karty
- `cardFront` (multiple) - wszystkie front textareas
- `cardBack` (multiple) - wszystkie back textareas
- `editIndicator` (multiple) - wszystkie ✏️ indicators
- `recoveryModal` - dialog recovery
- `sourcePreview` - element source text preview

**Kluczowe metody:**

```javascript
// === Lifecycle ===

connect() {
  // Load initial data from session (passed via Twig)
  this.initializeFromSession();

  // Check for recovery data in localStorage
  this.checkRecovery();

  // Setup auto-save timer (30s)
  this.autoSaveTimer = setInterval(() => this.autoSave(), 30000);

  // Setup beforeunload listener
  window.addEventListener('beforeunload', this.handleBeforeUnload);
  document.addEventListener('turbo:before-visit', this.handleTurboBeforeVisit);
}

disconnect() {
  // Cleanup
  clearInterval(this.autoSaveTimer);
  window.removeEventListener('beforeunload', this.handleBeforeUnload);
  document.removeEventListener('turbo:before-visit', this.handleTurboBeforeVisit);
}

// === Initialization ===

initializeFromSession() {
  // Data passed from Twig template (from session)
  // Already set via Stimulus values in HTML data attributes
  // Parse cards JSON string to array
  this.cardsArray = JSON.parse(this.cardsValue);

  // Initialize setName with suggestedName if not already set
  if (!this.setNameValue) {
    this.setNameValue = this.suggestedNameValue;
  }

  this.updateCardCount();
}

// === Card Management ===

handleCardEdit(event) {
  const textarea = event.target;
  const cardId = textarea.dataset.cardId;
  const field = textarea.dataset.field; // 'front' or 'back'
  const value = textarea.value;

  // Find card in array
  const card = this.cardsArray.find(c => c.tempId === cardId);
  if (!card) return;

  // Update card data
  card[field] = value;
  card.edited = true;

  // Show edit indicator
  const cardElement = textarea.closest('[data-editSet-target="flashcardCard"]');
  const indicator = cardElement.querySelector('[data-editSet-target="editIndicator"]');
  indicator.classList.remove('hidden');

  // Mark unsaved changes
  this.hasUnsavedChangesValue = true;

  // Update cards value (serialize back to JSON)
  this.cardsValue = JSON.stringify(this.cardsArray);

  // Debounced auto-save trigger (optional, już jest timer co 30s)
}

deleteCard(event) {
  const button = event.target.closest('button');
  const cardElement = button.closest('[data-editSet-target="flashcardCard"]');
  const cardId = cardElement.dataset.cardId;

  // Find card
  const card = this.cardsArray.find(c => c.tempId === cardId);
  if (!card) return;

  // Soft delete
  card.isDeleted = true;

  // Fade-out + slide-up animation
  cardElement.classList.add('animate-delete'); // CSS animation class

  // After animation (300ms), remove from DOM
  setTimeout(() => {
    cardElement.remove();

    // Update card count
    this.updateCardCount();

    // ARIA live announcement
    this.announceCardDeleted();

    // Mark unsaved changes
    this.hasUnsavedChangesValue = true;

    // Update cards value
    this.cardsValue = JSON.stringify(this.cardsArray);
  }, 300);
}

addCard() {
  // Create new card with origin='manual'
  const newCard = {
    tempId: this.generateUUID(),
    front: '',
    back: '',
    origin: 'manual',
    edited: false,
    isDeleted: false
  };

  // Add to array
  this.cardsArray.push(newCard);

  // Render new card (Stimulus doesn't have reactive rendering, so we manually append)
  const cardHTML = this.renderCardHTML(newCard, this.cardsArray.length - 1);
  const gridElement = this.element.querySelector('[data-editSet-target="flashcardGrid"]');
  gridElement.insertAdjacentHTML('beforeend', cardHTML);

  // Slide-down animation
  const newCardElement = gridElement.lastElementChild;
  newCardElement.classList.add('animate-slide-down');

  // Focus na front textarea
  setTimeout(() => {
    const frontTextarea = newCardElement.querySelector('[data-field="front"]');
    frontTextarea.focus();
  }, 100);

  // Update card count
  this.updateCardCount();

  // Mark unsaved changes
  this.hasUnsavedChangesValue = true;

  // Update cards value
  this.cardsValue = JSON.stringify(this.cardsArray);

  // Scroll to new card
  newCardElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

updateCardCount() {
  const activeCards = this.cardsArray.filter(c => !c.isDeleted);
  this.cardCountTargets.forEach(el => {
    el.textContent = activeCards.length;
  });

  // Disable save button if no cards
  if (activeCards.length === 0) {
    this.saveButtonTarget.disabled = true;
  }
}

// === Set Name Management ===

handleSetNameChange(event) {
  const newName = event.target.value;
  this.setNameValue = newName;

  // Hide AI suggestion icon (user edited)
  const aiIcon = event.target.nextElementSibling; // ✨ span
  if (aiIcon && newName !== this.suggestedNameValue) {
    aiIcon.classList.add('hidden');
  } else {
    aiIcon.classList.remove('hidden');
  }

  // Mark unsaved changes
  this.hasUnsavedChangesValue = true;

  // Debounced duplicate check
  clearTimeout(this.duplicateCheckTimer);
  this.duplicateCheckTimer = setTimeout(() => {
    this.checkDuplicateName(newName);
  }, 500);
}

async checkDuplicateName(name) {
  if (!name) {
    this.isDuplicateNameValue = false;
    return;
  }

  try {
    const response = await fetch(`/api/sets?q=${encodeURIComponent(name)}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
      }
    });

    const data = await response.json();

    // Jeśli istnieje zestaw o tej nazwie
    this.isDuplicateNameValue = data.total > 0;

    // Show/hide warning
    if (this.isDuplicateNameValue) {
      this.duplicateWarningTarget.classList.remove('hidden');
      this.saveButtonTarget.disabled = true;
    } else {
      this.duplicateWarningTarget.classList.add('hidden');
      this.saveButtonTarget.disabled = false;
    }

  } catch (error) {
    console.error('Error checking duplicate name:', error);
    // W razie błędu: zakładamy że nie duplikat
    this.isDuplicateNameValue = false;
  }
}

// === Save Set ===

async saveSet() {
  // Validation
  if (!this.setNameValue.trim()) {
    alert('Nazwa zestawu jest wymagana');
    return;
  }

  if (this.isDuplicateNameValue) {
    alert('Zestaw o tej nazwie już istnieje');
    return;
  }

  const activeCards = this.cardsArray.filter(c => !c.isDeleted);
  if (activeCards.length === 0) {
    alert('Musisz mieć przynajmniej 1 fiszkę');
    return;
  }

  // Set saving state
  this.isSavingValue = true;
  this.saveButtonTarget.disabled = true;
  this.saveButtonTarget.textContent = 'Zapisywanie...';

  // Prepare request payload
  const payload = {
    name: this.setNameValue.trim(),
    cards: activeCards.map(card => ({
      front: card.front,
      back: card.back,
      origin: card.origin,
      edited: card.edited
    })),
    job_id: this.jobIdValue
  };

  try {
    const response = await fetch('/api/sets', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-Token': this.getCSRFToken()
      },
      body: JSON.stringify(payload)
    });

    if (response.ok) {
      // Success
      const data = await response.json();

      // Clear localStorage (auto-save data)
      this.clearAutoSave();

      // Mark no unsaved changes (prevent beforeunload prompt)
      this.hasUnsavedChangesValue = false;

      // Redirect to /sets (lista zestawów) z flash message
      // Używamy Turbo do navigation
      Turbo.visit('/sets', {
        action: 'replace',
        frame: '_top'
      });

      // Flash message będzie obsłużony po stronie backendu (session flash)

    } else if (response.status === 409) {
      // Duplicate name
      const error = await response.json();
      alert(error.error || 'Zestaw o tej nazwie już istnieje');

    } else if (response.status === 422) {
      // Validation error
      const error = await response.json();
      alert(error.message || 'Dane są nieprawidłowe');

    } else {
      // Other error
      throw new Error('Unexpected error');
    }

  } catch (error) {
    console.error('Error saving set:', error);
    alert('Wystąpił błąd podczas zapisywania. Spróbuj ponownie.');

  } finally {
    // Reset saving state
    this.isSavingValue = false;
    this.saveButtonTarget.disabled = false;
    this.saveButtonTarget.textContent = `Zapisz zestaw (${activeCards.length} fiszek)`;
  }
}

// === Auto-save & Recovery ===

autoSave() {
  if (!this.hasUnsavedChangesValue) {
    return; // Brak zmian, skip
  }

  const data = {
    timestamp: Date.now(),
    jobId: this.jobIdValue,
    suggestedName: this.suggestedNameValue,
    setName: this.setNameValue,
    sourceText: this.sourceTextValue,
    cards: this.cardsArray
  };

  try {
    localStorage.setItem('flashcard_autosave', JSON.stringify(data));
    console.log('Auto-saved at', new Date().toLocaleTimeString());
    // Optional: subtle toast notification "Auto-saved"
  } catch (error) {
    console.error('Auto-save failed:', error);
    // Silent fail (localStorage full, etc.)
  }
}

checkRecovery() {
  try {
    const saved = localStorage.getItem('flashcard_autosave');
    if (!saved) return;

    const data = JSON.parse(saved);

    // Check TTL (24h)
    const age = Date.now() - data.timestamp;
    const maxAge = 24 * 60 * 60 * 1000; // 24h in ms

    if (age > maxAge) {
      // Expired, clear
      localStorage.removeItem('flashcard_autosave');
      return;
    }

    // Valid recovery data exists
    this.showRecoveryPrompt(data);

  } catch (error) {
    console.error('Recovery check failed:', error);
    // Clear corrupted data
    localStorage.removeItem('flashcard_autosave');
  }
}

showRecoveryPrompt(data) {
  // Set data for display in modal
  const timestamp = new Date(data.timestamp).toLocaleString('pl-PL');
  const modalText = this.recoveryModalTarget.querySelector('p');
  modalText.textContent = `Masz niezapisane zmiany z dnia ${timestamp}. Czy chcesz je przywrócić?`;

  // Show modal
  this.recoveryModalTarget.showModal();
}

recoverData() {
  try {
    const saved = localStorage.getItem('flashcard_autosave');
    const data = JSON.parse(saved);

    // Restore state
    this.jobIdValue = data.jobId;
    this.setNameValue = data.setName;
    this.cardsArray = data.cards;
    this.cardsValue = JSON.stringify(data.cards);

    // Re-render cards (Stimulus limitation: trzeba ręcznie)
    this.renderAllCards();

    // Update UI
    this.updateCardCount();
    this.setNameInputTarget.value = this.setNameValue;

    // Close modal
    this.recoveryModalTarget.close();

    // Optional: toast "Dane przywrócone"

  } catch (error) {
    console.error('Recovery failed:', error);
    alert('Nie udało się przywrócić danych');
  }
}

discardRecovery() {
  this.clearAutoSave();
  this.recoveryModalTarget.close();
  // Use fresh data from session
}

clearAutoSave() {
  localStorage.removeItem('flashcard_autosave');
}

// === Source Text Toggle ===

toggleSourceText() {
  this.isSourceExpandedValue = !this.isSourceExpandedValue;

  const preview = this.sourcePreviewTarget;
  if (this.isSourceExpandedValue) {
    preview.textContent = this.sourceTextValue;
    // Change button text to "Ukryj"
  } else {
    preview.textContent = this.sourceTextValue.substring(0, 100) + '...';
    // Change button text to "Pokaż cały tekst"
  }
}

// === BeforeUnload ===

handleBeforeUnload = (event) => {
  if (this.hasUnsavedChangesValue) {
    event.preventDefault();
    event.returnValue = '';
  }
}

handleTurboBeforeVisit = (event) => {
  if (this.hasUnsavedChangesValue) {
    if (!confirm('Masz niezapisane zmiany. Czy na pewno chcesz opuścić stronę?')) {
      event.preventDefault();
    }
  }
}

// === Helpers ===

getCSRFToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.content : '';
}

generateUUID() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
    const r = Math.random() * 16 | 0;
    const v = c === 'x' ? r : (r & 0x3 | 0x8);
    return v.toString(16);
  });
}

announceCardDeleted() {
  // ARIA live region announcement
  const liveRegion = document.getElementById('aria-live-region');
  if (liveRegion) {
    const activeCards = this.cardsArray.filter(c => !c.isDeleted);
    liveRegion.textContent = `Fiszka usunięta. Pozostało ${activeCards.length} fiszek`;

    // Clear po 3s
    setTimeout(() => {
      liveRegion.textContent = '';
    }, 3000);
  }
}

renderCardHTML(card, index) {
  // Generate HTML string for a card (używane w addCard)
  // W produkcji: lepiej użyć template lub Twig partial render
  return `
    <div class="bg-white border rounded-lg p-4 shadow-sm animate-slide-down"
         data-editSet-target="flashcardCard"
         data-card-id="${card.tempId}">
      <div class="flex justify-between items-center mb-2">
        <span class="text-sm text-gray-500">Fiszka #${index + 1}</span>
        <div class="flex items-center space-x-2">
          <span data-editSet-target="editIndicator" class="${card.edited ? '' : 'hidden'}">✏️</span>
          <button data-action="click->editSet#deleteCard"
                  class="text-red-600 hover:text-red-800">🗑️ Usuń</button>
        </div>
      </div>
      <div class="space-y-3">
        <div>
          <label class="block text-sm font-medium mb-1">Przód (pytanie)</label>
          <textarea data-editSet-target="cardFront"
                    data-card-id="${card.tempId}"
                    data-field="front"
                    data-action="input->editSet#handleCardEdit"
                    class="w-full border rounded p-2 resize-y"
                    rows="2">${card.front}</textarea>
          <span class="text-xs text-gray-500">${card.front.length} / 1000</span>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Tył (odpowiedź)</label>
          <textarea data-editSet-target="cardBack"
                    data-card-id="${card.tempId}"
                    data-field="back"
                    data-action="input->editSet#handleCardEdit"
                    class="w-full border rounded p-2 resize-y"
                    rows="2">${card.back}</textarea>
          <span class="text-xs text-gray-500">${card.back.length} / 1000</span>
        </div>
      </div>
    </div>
  `;
}

renderAllCards() {
  // Re-render wszystkich kart (używane w recoverData)
  const gridElement = this.element.querySelector('[data-editSet-target="flashcardGrid"]');
  gridElement.innerHTML = '';

  this.cardsArray
    .filter(c => !c.isDeleted)
    .forEach((card, index) => {
      const cardHTML = this.renderCardHTML(card, index);
      gridElement.insertAdjacentHTML('beforeend', cardHTML);
    });
}
```

---

## 7. Integracja API

### Endpoint 1: GET /api/sets?q={name} (check duplicate)

**Opis:**
Sprawdzenie czy zestaw o podanej nazwie już istnieje dla danego użytkownika. Używane do debounced validation podczas wpisywania nazwy.

**Request:**
- Method: GET
- URL: `/api/sets?q={encodeURIComponent(name)}`
- Headers:
  - `Accept: application/json`

**Response Success (200 OK):**
```json
{
  "items": [
    {
      "id": "uuid",
      "name": "Biologia - Fotosynteza",
      "card_count": 15,
      "updated_at": "2025-01-15T10:30:00Z"
    }
  ],
  "total": 1,
  "page": 1,
  "per_page": 20
}
```

**Interpretacja:**
- `total > 0` → duplikat istnieje → set `isDuplicateName = true`
- `total === 0` → brak duplikatu → set `isDuplicateName = false`

**Error handling:**
W razie błędu (network, 500): zakładamy brak duplikatu (optimistic), ale logujemy error.

---

### Endpoint 2: POST /api/sets (save set)

**Opis:**
Zapis zestawu fiszek (AI-generated + manually added). Single POST request z wszystkimi kartami. Backend linkuje do `job_id` dla KPI tracking.

**Request:**
- Method: POST
- URL: `/api/sets`
- Headers:
  - `Content-Type: application/json`
  - `Accept: application/json`
  - `X-CSRF-Token: ...` (z meta tag)

**Request Body:**
```json
{
  "name": "Biologia - Fotosynteza",
  "cards": [
    {
      "front": "Co to jest fotosynteza?",
      "back": "Proces przekształcania energii świetlnej...",
      "origin": "ai",
      "edited": true
    },
    {
      "front": "Własne pytanie?",
      "back": "Własna odpowiedź",
      "origin": "manual",
      "edited": false
    }
  ],
  "job_id": "7c9bda17-fdec-4e89-82e9-5d93b10a9c40"
}
```

**Notes:**
- `cards` array zawiera tylko aktywne fiszki (filtered `isDeleted`)
- `origin`: "ai" dla wygenerowanych, "manual" dla dodanych ręcznie
- `edited: true` jeśli user modyfikował fiszkę (backend set `edited_by_user_at`)
- `job_id` optional, używany do KPI tracking:
  - Backend update `ai_jobs` record:
    - `set_id` = newly created set ID
    - `accepted_count` = count cards with origin='ai'
    - `edited_count` = count cards with origin='ai' AND edited=true
    - Deleted count = `generated_count - accepted_count`

**Response Success (201 Created):**
```json
{
  "id": "uuid-new-set",
  "name": "Biologia - Fotosynteza",
  "card_count": 15
}
```

**Headers:**
- `Location: /api/sets/{id}`

**Response Error (409 Conflict - Duplicate Name):**
```json
{
  "error": "Zestaw o nazwie 'Biologia - Fotosynteza' już istnieje",
  "code": "duplicate_set_name",
  "field": "name"
}
```

**Response Error (422 Unprocessable Entity - Validation):**
```json
{
  "error": "Validation failed",
  "code": "validation_error",
  "violations": [
    {
      "field": "name",
      "message": "Nazwa zestawu jest wymagana"
    },
    {
      "field": "cards[0].front",
      "message": "Maksymalnie 1000 znaków"
    }
  ]
}
```

**Response Error (404 Not Found - Job ID):**
```json
{
  "error": "AI job not found",
  "code": "job_not_found"
}
```
*Note:* W tym przypadku można kontynuować save bez job_id (brak KPI tracking), ale lepiej pokazać warning userowi.

**Response Error (500 Internal Server Error):**
```json
{
  "error": "Internal server error",
  "code": "internal_error",
  "message": "Wystąpił nieoczekiwany błąd. Spróbuj ponownie później."
}
```

---

## 8. Interakcje użytkownika

### 1. Wejście na stronę /sets/new/edit

**Akcja użytkownika:**
Redirect z `/generate` po sukcesie generowania

**Reakcja systemu:**
- Backend controller odczytuje dane z session (`pending_set`)
- Renderuje Twig template z danymi
- Stimulus controller `connect()`:
  - Initialize state z values
  - Check localStorage dla recovery data
  - Setup auto-save timer (30s)
  - Setup beforeunload listener

**Oczekiwany wynik:**
- User widzi ekran edycji z wygenerowanymi fiszkami
- Nazwa zestawu pre-wypełniona sugestią AI (✨ icon)
- Source text collapsed w sticky header
- Wszystkie fiszki wyświetlone w grid

---

### 2. Check recovery data (jeśli istnieje)

**Akcja użytkownika:**
User wraca do edycji po zamknięciu przeglądarki/crashu

**Reakcja systemu:**
- `checkRecovery()` sprawdza localStorage
- Jeśli valid data (TTL < 24h): pokazanie RecoveryPrompt modal

**Oczekiwany wynik:**
User widzi modal "Znaleziono niezapisane zmiany z dnia X. Przywrócić?"

**Sub-interakcje:**
- **Click "Przywróć":** Load data z localStorage → renderowanie kart → close modal
- **Click "Zacznij od nowa":** Clear localStorage → close modal → use fresh session data

---

### 3. Edycja front/back fiszki

**Akcja użytkownika:**
User klika w textarea, wpisuje/edytuje tekst

**Reakcja systemu:**
- `handleCardEdit()` event
- Update card data w `cardsArray`
- Set `card.edited = true`
- Show ✏️ edit indicator
- Mark `hasUnsavedChanges = true`
- Update `cardsValue` (serialize JSON)
- Character counter update (real-time)

**Oczekiwany wynik:**
- Visual feedback: ✏️ icon pokazuje się
- Tekst zapisany w local state
- Auto-save trigger (za 30s)
- Character counter pokazuje X / 1000

---

### 4. Przekroczenie limitu znaków (1000)

**Akcja użytkownika:**
User wpisuje > 1000 znaków w front lub back

**Reakcja systemu:**
- Character counter red
- Red border na textarea (CSS class)
- Save button disabled
- Tooltip/message: "Maksymalnie 1000 znaków"

**Oczekiwany wynik:**
User wie że musi skrócić tekst przed zapisem

---

### 5. Usunięcie fiszki

**Akcja użytkownika:**
User klika przycisk "🗑️ Usuń" przy fiszce

**Reakcja systemu:**
- `deleteCard()` event
- Set `card.isDeleted = true` (soft delete)
- Fade-out + slide-up CSS animation (300ms)
- Remove DOM element
- Update card count
- ARIA live announcement: "Fiszka usunięta. Pozostało N fiszek"
- Mark `hasUnsavedChanges = true`

**Oczekiwany wynik:**
- Fiszka znika z płynną animacją
- Liczba fiszek w footer update
- Screen reader announces usunięcie

---

### 6. Dodanie własnej fiszki

**Akcja użytkownika:**
User klika przycisk "+ Dodaj własną fiszkę"

**Reakcja systemu:**
- `addCard()` event
- Create new FlashcardData z `origin='manual'`, empty content
- Render new card HTML
- Insert do grid
- Slide-down CSS animation
- Auto-focus na front textarea
- Update card count
- Mark `hasUnsavedChanges = true`
- Scroll do nowej fiszki

**Oczekiwany wynik:**
- Nowa pusta fiszka pojawia się na dole z animacją
- Kursor automatycznie w front textarea
- User może od razu zacząć pisać

---

### 7. Wpisywanie nazwy zestawu

**Akcja użytkownika:**
User edytuje pole "Nazwa zestawu"

**Reakcja systemu:**
- `handleSetNameChange()` event
- Update `setNameValue`
- Hide ✨ icon (jeśli nazwa != suggestedName)
- Mark `hasUnsavedChanges = true`
- Debounced (500ms) → `checkDuplicateName()` API call

**Oczekiwany wynik:**
- Nazwa update w real-time
- ✨ icon znika (user edytował)
- Po 500ms: check duplikatu

**Sub-interakcje:**
- **Duplikat found:** Red border, warning "Zestaw o tej nazwie już istnieje", save button disabled
- **Brak duplikatu:** Green border (optional), warning hidden, save button enabled

---

### 8. Expand/collapse source text

**Akcja użytkownika:**
User klika "Pokaż cały tekst" w sticky header

**Reakcja systemu:**
- `toggleSourceText()` event
- Toggle `isSourceExpanded`
- Update preview element:
  - Collapsed: pierwsze ~100 znaków + "..."
  - Expanded: pełny text
- Update button text: "Pokaż cały tekst" ⇄ "Ukryj"

**Oczekiwany wynik:**
User może przypomnieć sobie kontekst notatek bez opuszczania edycji

---

### 9. Auto-save co 30s

**Akcja użytkownika:**
User edytuje fiszki, czeka 30s+

**Reakcja systemu:**
- Timer tick → `autoSave()` method
- Check `hasUnsavedChanges`
- Jeśli true: save current state do localStorage z timestamp
- (Optional) Subtle toast notification: "Auto-saved"

**Oczekiwany wynik:**
Dane zabezpieczone w localStorage, user może bezpiecznie zamknąć przeglądarkę

---

### 10. Próba opuszczenia strony z niezapisanymi zmianami

**Akcja użytkownika:**
User próbuje:
- Zamknąć kartę/okno
- Kliknąć "Back" w przeglądarce
- Nawigować do innej strony (Turbo link)

**Reakcja systemu:**
- `handleBeforeUnload` lub `handleTurboBeforeVisit` event
- Check `hasUnsavedChanges`
- Jeśli true: pokazanie browser prompt "Masz niezapisane zmiany. Czy na pewno chcesz opuścić stronę?"

**Oczekiwany wynik:**
User może anulować wyjście i wrócić do edycji, lub potwierdzić i stracić zmiany

---

### 11. Keyboard shortcuts

**Akcja użytkownika:**
User naciska **Ctrl+S** (save)

**Reakcja systemu:**
- Event listener na `keydown`
- `event.preventDefault()` (zapobiega browser save dialog)
- Wywołanie `saveSet()` method

**Oczekiwany wynik:**
Zapis zestawu bez konieczności klikania przycisku

**Akcja użytkownika:**
User naciska **Escape** w textarea

**Reakcja systemu:**
- Blur textarea
- (Optional) Revert changes (future enhancement)

**Oczekiwany wynik:**
Focus opuszcza textarea

---

### 12. Tab navigation przez fiszki

**Akcja użytkownika:**
User naciska **Tab** wielokrotnie

**Reakcja systemu:**
Focus kolejno:
1. Front textarea fiszki #1
2. Back textarea fiszki #1
3. Delete button fiszki #1
4. Front textarea fiszki #2
5. ...

**Oczekiwany wynik:**
Keyboard-only navigation działa płynnie, user może edytować wszystkie fiszki bez myszy

---

### 13. Kliknięcie "Zapisz zestaw"

**Akcja użytkownika:**
User klika przycisk "Zapisz zestaw (N fiszek)"

**Reakcja systemu:**
- `saveSet()` method
- Walidacja:
  - setName not empty
  - not duplicate
  - przynajmniej 1 active card
- Set `isSaving = true` (loading state)
- POST /api/sets z payload
- Handle response

**Oczekiwany wynik (success):**
- Loading state: przycisk pokazuje spinner + "Zapisywanie..."
- API 201 Created
- Clear localStorage (auto-save data)
- Mark `hasUnsavedChanges = false`
- Turbo redirect do `/sets` (lista zestawów)
- Flash message: "Zestaw zapisany pomyślnie!"

**Oczekiwany wynik (error):**
- 409 Duplicate: alert "Zestaw o tej nazwie już istnieje"
- 422 Validation: alert z listą violations
- 500 Internal: alert "Wystąpił błąd podczas zapisywania. Spróbuj ponownie."
- Network error: alert "Brak połączenia. Spróbuj ponownie."

---

## 9. Warunki i walidacja

### Client-side validation:

#### 1. Nazwa zestawu nie jest pusta

**Warunek:** `setName.trim().length > 0`

**Komponenty:**
- **SetNameInput** - input validation
- **SaveButton** - disabled jeśli puste

**Wpływ na UI:**
- Pusty input: red border (optional), save button disabled
- Komunikat: "Nazwa zestawu jest wymagana"

---

#### 2. Nazwa zestawu jest unikalna

**Warunek:** API check `/api/sets?q={name}` zwraca `total === 0`

**Komponenty:**
- **SetNameInput** - debounced (500ms) API call
- **DuplicateNameWarning** - conditional display
- **SaveButton** - disabled jeśli duplikat

**Wpływ na UI:**
- Duplikat found: red border, warning text "Zestaw o tej nazwie już istnieje", save disabled
- Brak duplikatu: green border (optional), warning hidden, save enabled

---

#### 3. Front/back fiszki max 1000 znaków

**Warunek:** `card.front.length <= 1000 && card.back.length <= 1000`

**Komponenty:**
- **FrontTextarea** - real-time character counter
- **BackTextarea** - real-time character counter

**Wpływ na UI:**
- < 1000: character counter gray "X / 1000"
- > 1000: character counter red, red border, save button disabled
- Komunikat: "Maksymalnie 1000 znaków (aktualnie: X)"

---

#### 4. Przynajmniej 1 aktywna fiszka

**Warunek:** `cards.filter(c => !c.isDeleted).length >= 1`

**Komponenty:**
- **SaveButton** - disabled jeśli brak aktywnych fiszek

**Wpływ na UI:**
- 0 aktywnych: save button disabled
- Komunikat: "Musisz mieć przynajmniej 1 fiszkę"
- Card count w footer pokazuje "0 fiszek"

---

### Server-side validation (mirroring):

Backend (`CreateSetController`) wykonuje te same walidacje:
1. `name` not blank
2. `name` max 255 chars
3. `name` unique dla usera
4. `cards[].front` not blank, max 1000 chars
5. `cards[].back` not blank, max 1000 chars
6. `cards[].origin` in ['ai', 'manual']
7. `job_id` (jeśli podany) istnieje i należy do usera
8. User authenticated (Symfony Security)
9. CSRF token valid

Jeśli client-side validation missed coś, server zwróci 422 z violations.

---

## 10. Obsługa błędów

### 1. Duplicate Set Name (409)

**Przyczyna:**
- User wpisał nazwę która już istnieje (race condition: inny user/tab zapisał w międzyczasie)

**Obsługa:**
- Alert: "Zestaw o tej nazwie już istnieje"
- Focus wraca do setNameInput
- User może zmienić nazwę

**Recovery:**
User edytuje nazwę i retry save

---

### 2. Validation Error (422)

**Przyczyna:**
- Server-side validation caught edge case (np. card content > 1000 chars mimo client validation)
- Możliwe violations:
  - Nazwa za długa (> 255)
  - Card front/back > 1000 chars
  - Card front/back empty
  - Invalid origin value

**Obsługa:**
- Alert z listą violations (lub lepiej: inline errors przy polach)
- Przykład: "Fiszka #3, front: Maksymalnie 1000 znaków"

**Recovery:**
User poprawia błędy według violations i retry

---

### 3. AI Job Not Found (404)

**Przyczyna:**
- `job_id` nie istnieje w bazie (edge case: expired job, manual manipulation)
- Job nie należy do usera

**Obsługa:**
- Warning (nie error): "Nie można połączyć z jobem AI, zestaw zostanie zapisany bez statystyk"
- Kontynuacja save bez job_id (brak KPI tracking, ale user może zapisać)

**Recovery:**
Automatyczna: save bez job_id

---

### 4. Network Error podczas save

**Przyczyna:**
- Brak połączenia internetowego
- Request timeout
- CORS issues (dev only)

**Obsługa:**
- Alert: "Nie udało się zapisać zestawu. Sprawdź połączenie i spróbuj ponownie."
- Dane pozostają w localStorage (auto-save)

**Recovery:**
User sprawdza internet, retry save po reconnect

---

### 5. Internal Server Error (500)

**Przyczyna:**
- Błąd po stronie backendu (DB connection fail, unexpected exception)

**Obsługa:**
- Alert: "Wystąpił nieoczekiwany błąd podczas zapisywania. Spróbuj ponownie później."
- Dane w localStorage (user może retry)

**Recovery:**
User czeka chwilę i retry, lub zgłasza problem do supportu

---

### 6. Session Expired (401)

**Przyczyna:**
- User zalogowany za długo, sesja wygasła
- User wylogowany w innej karcie

**Obsługa:**
- Symfony Security redirect do `/login`
- Dane zachowane w localStorage (TTL 24h)
- Po login: redirect z powrotem do `/sets/new/edit` (Turbo może zachować context)
- RecoveryPrompt pokazuje dane

**Recovery:**
User loguje się ponownie, przywraca dane z recovery prompt

---

### 7. localStorage quota exceeded (auto-save fail)

**Przyczyna:**
- localStorage full (rzadkie, ale możliwe przy dużych zestawach)

**Obsługa:**
- Silent fail (nie blokować UX)
- Console error log
- Disable auto-save dla tej sesji
- Rely na manual save

**Recovery:**
User robi manual save (przycisk "Zapisz zestaw")

---

### 8. Card content > 1000 chars (client-side prevention)

**Przyczyna:**
- User wpisał > 1000 znaków w textarea

**Obsługa:**
- Red character counter: "1050 / 1000"
- Red border na textarea
- Save button disabled
- Tooltip: "Skróć tekst do 1000 znaków"

**Recovery:**
User skraca tekst, validation auto-update, save button re-enabled

---

## 11. Kroki implementacji

### Krok 1: Utworzenie struktury plików i kontrolera

**Zadania:**
1. Utworzyć template Twig: `templates/sets/edit_new.html.twig`
2. Utworzyć Stimulus controller: `assets/controllers/edit_set_controller.js`
3. Utworzyć PHP controller (jeśli nie istnieje): `src/Controller/EditNewSetViewController.php`
   - Route: `#[Route('/sets/new/edit', name: 'edit_new_set_view', methods: ['GET'])]`
   - Logic:
     - Odczytanie danych z session (`pending_set`)
     - Jeśli brak danych w session: redirect do `/generate` (user próbuje direct access)
     - Render template z danymi

**Przykład controller:**
```php
#[Route('/sets/new/edit', name: 'edit_new_set_view', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
public function __invoke(Request $request): Response
{
    $pendingSet = $request->getSession()->get('pending_set');

    if (!$pendingSet) {
        // Brak danych, redirect do generowania
        return $this->redirectToRoute('generate_view');
    }

    return $this->render('sets/edit_new.html.twig', [
        'jobId' => $pendingSet['job_id'],
        'suggestedName' => $pendingSet['suggested_name'],
        'cards' => $pendingSet['cards'],
        'sourceText' => $pendingSet['source_text'],
        'generatedCount' => $pendingSet['generated_count'],
    ]);
}
```

**Weryfikacja:**
- `/sets/new/edit` (bez session) redirectuje do `/generate`
- Po generowaniu: redirect do `/sets/new/edit` pokazuje template z danymi

---

### Krok 2: Implementacja podstawowego layoutu Twig

**Zadania:**
1. Extend `base.html.twig`
2. Dodać główny kontener z Stimulus controller:
```twig
<div class="container mx-auto px-4 py-8"
     data-controller="editSet"
     data-editSet-job-id-value="{{ jobId }}"
     data-editSet-suggested-name-value="{{ suggestedName }}"
     data-editSet-set-name-value="{{ suggestedName }}"
     data-editSet-source-text-value="{{ sourceText }}"
     data-editSet-cards-value="{{ cards|json_encode }}"
     data-editSet-has-unsaved-changes-value="false">
    {# Komponenty będą tutaj #}
</div>
```

3. Dodać ARIA live region dla screen readers (invisible):
```twig
<div id="aria-live-region" role="status" aria-live="polite" aria-atomic="true" class="sr-only"></div>
```

**Weryfikacja:**
- Strona wyświetla pusty layout
- Stimulus controller connect() wykonuje się (check console.log)

---

### Krok 3: Implementacja StickyHeader

**Zadania:**
1. Dodać sticky header z collapsible source text:
```twig
<header class="sticky top-0 bg-white shadow-sm z-40 p-4 mb-6">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">Notatki źródłowe</h2>
        <button data-action="click->editSet#toggleSourceText"
                class="text-blue-600 hover:text-blue-800">
            <span data-editSet-target="sourceToggleText">Pokaż cały tekst</span>
            <span>▼</span>
        </button>
    </div>
    <div data-editSet-target="sourcePreview" class="mt-2 text-sm text-gray-600">
        {{ sourceText|slice(0, 100) }}...
    </div>
</header>
```

2. W Stimulus:
```javascript
toggleSourceText() {
  this.isSourceExpandedValue = !this.isSourceExpandedValue;

  if (this.isSourceExpandedValue) {
    this.sourcePreviewTarget.textContent = this.sourceTextValue;
    this.sourceToggleTextTarget.textContent = 'Ukryj';
  } else {
    this.sourcePreviewTarget.textContent = this.sourceTextValue.substring(0, 100) + '...';
    this.sourceToggleTextTarget.textContent = 'Pokaż cały tekst';
  }
}
```

**Weryfikacja:**
- Header sticky podczas scrollowania
- Click "Pokaż cały tekst" → expand
- Click "Ukryj" → collapse

---

### Krok 4: Implementacja FlashcardGrid + FlashcardCard

**Zadania:**
1. Dodać responsive grid:
```twig
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6" data-editSet-target="flashcardGrid">
    {% for card in cards %}
        <div class="bg-white border rounded-lg p-4 shadow-sm"
             data-editSet-target="flashcardCard"
             data-card-id="{{ loop.index }}">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm text-gray-500">Fiszka #{{ loop.index }}</span>
                <div class="flex items-center space-x-2">
                    <span data-editSet-target="editIndicator" class="hidden">✏️</span>
                    <button data-action="click->editSet#deleteCard"
                            class="text-red-600 hover:text-red-800 text-sm">
                        🗑️ Usuń
                    </button>
                </div>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium mb-1">Przód (pytanie)</label>
                    <textarea data-editSet-target="cardFront"
                              data-card-id="{{ loop.index }}"
                              data-field="front"
                              data-action="input->editSet#handleCardEdit"
                              class="w-full border rounded p-2 resize-y focus:ring-2 focus:ring-blue-500"
                              rows="2">{{ card.front }}</textarea>
                    <span class="text-xs text-gray-500">{{ card.front|length }} / 1000</span>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Tył (odpowiedź)</label>
                    <textarea data-editSet-target="cardBack"
                              data-card-id="{{ loop.index }}"
                              data-field="back"
                              data-action="input->editSet#handleCardEdit"
                              class="w-full border rounded p-2 resize-y focus:ring-2 focus:ring-blue-500"
                              rows="2">{{ card.back }}</textarea>
                    <span class="text-xs text-gray-500">{{ card.back|length }} / 1000</span>
                </div>
            </div>
        </div>
    {% endfor %}
</div>
```

2. W Stimulus dodać metody:
   - `handleCardEdit(event)` - update card data, show ✏️, mark unsaved
   - `deleteCard(event)` - fade-out animation, soft delete, update count

**Weryfikacja:**
- Fiszki wyświetlają się w grid (1 col mobile, 2 col desktop)
- Edycja textarea update local state
- ✏️ pokazuje się po edycji
- Delete usuwa fiszkę z animacją

---

### Krok 5: Implementacja AddCardButton

**Zadania:**
1. Dodać button pod gridem:
```twig
<button data-action="click->editSet#addCard"
        class="w-full border-2 border-dashed border-gray-300 rounded-lg p-6 text-gray-500 hover:border-blue-500 hover:text-blue-500 transition mb-6">
    <span class="text-2xl">+</span>
    <span class="block mt-2">Dodaj własną fiszkę</span>
</button>
```

2. W Stimulus `addCard()`:
   - Create new card object
   - Render HTML (użyć `renderCardHTML()` helper)
   - Insert do grid
   - Slide-down animation
   - Focus front textarea

**Weryfikacja:**
- Click button → nowa fiszka pojawia się z animacją
- Focus automatycznie w front textarea
- Card count update

---

### Krok 6: Implementacja StickyFooter + SetNameInput

**Zadania:**
1. Dodać sticky footer:
```twig
<footer class="sticky bottom-0 bg-white border-t shadow-lg p-4 z-40">
    <div class="max-w-4xl mx-auto">
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Nazwa zestawu</label>
            <div class="relative">
                <input type="text"
                       data-editSet-target="setNameInput"
                       data-action="input->editSet#handleSetNameChange"
                       value="{{ suggestedName }}"
                       class="w-full border rounded p-3 pr-10 focus:ring-2 focus:ring-blue-500">
                <span class="absolute right-3 top-3 text-xl">✨</span>
            </div>
            <span data-editSet-target="duplicateWarning"
                  class="hidden text-red-600 text-sm">
                Zestaw o tej nazwie już istnieje
            </span>
        </div>

        <button data-editSet-target="saveButton"
                data-action="click->editSet#saveSet"
                class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition">
            Zapisz zestaw (<span data-editSet-target="cardCount">{{ cards|length }}</span> fiszek)
        </button>
    </div>
</footer>
```

2. W Stimulus:
   - `handleSetNameChange()` - debounced (500ms) → `checkDuplicateName()`
   - `checkDuplicateName()` - fetch GET /api/sets?q=...
   - `updateCardCount()` - update liczby fiszek

**Weryfikacja:**
- Footer sticky podczas scrollowania
- Edycja nazwy → hide ✨ icon
- Debounced duplicate check działa
- Card count update po delete/add

---

### Krok 7: Implementacja duplicate check API integration

**Zadania:**
1. W Stimulus `checkDuplicateName()`:
```javascript
async checkDuplicateName(name) {
  if (!name) {
    this.isDuplicateNameValue = false;
    return;
  }

  try {
    const response = await fetch(`/api/sets?q=${encodeURIComponent(name)}`, {
      method: 'GET',
      headers: { 'Accept': 'application/json' }
    });

    const data = await response.json();
    this.isDuplicateNameValue = data.total > 0;

    if (this.isDuplicateNameValue) {
      this.duplicateWarningTarget.classList.remove('hidden');
      this.saveButtonTarget.disabled = true;
    } else {
      this.duplicateWarningTarget.classList.add('hidden');
      this.saveButtonTarget.disabled = false;
    }
  } catch (error) {
    console.error('Duplicate check failed:', error);
    this.isDuplicateNameValue = false; // Optimistic: allow save
  }
}
```

2. Backend: implementacja GET /api/sets?q= (jeśli jeszcze nie istnieje)

**Weryfikacja:**
- Wpisanie istniejącej nazwy → warning + disabled button
- Wpisanie unikalnej nazwy → hidden warning + enabled button

---

### Krok 8: Implementacja save set (POST /api/sets)

**Zadania:**
1. W Stimulus `saveSet()`:
```javascript
async saveSet() {
  // Validation (już opisane w sekcji 6)
  // ...

  this.isSavingValue = true;
  this.saveButtonTarget.disabled = true;
  this.saveButtonTarget.textContent = 'Zapisywanie...';

  const payload = {
    name: this.setNameValue.trim(),
    cards: this.cardsArray
      .filter(c => !c.isDeleted)
      .map(c => ({
        front: c.front,
        back: c.back,
        origin: c.origin,
        edited: c.edited
      })),
    job_id: this.jobIdValue
  };

  try {
    const response = await fetch('/api/sets', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-Token': this.getCSRFToken()
      },
      body: JSON.stringify(payload)
    });

    if (response.ok) {
      const data = await response.json();
      this.clearAutoSave();
      this.hasUnsavedChangesValue = false;

      // Redirect via Turbo
      Turbo.visit('/sets', { action: 'replace' });

    } else if (response.status === 409) {
      const error = await response.json();
      alert(error.error || 'Zestaw o tej nazwie już istnieje');
    } else if (response.status === 422) {
      const error = await response.json();
      alert(error.message || 'Dane są nieprawidłowe');
    } else {
      throw new Error('Unexpected error');
    }
  } catch (error) {
    console.error('Save error:', error);
    alert('Wystąpił błąd podczas zapisywania. Spróbuj ponownie.');
  } finally {
    this.isSavingValue = false;
    this.saveButtonTarget.disabled = false;
    this.saveButtonTarget.textContent = `Zapisz zestaw (${this.cardsArray.filter(c => !c.isDeleted).length} fiszek)`;
  }
}
```

2. Backend: sprawdzić czy `CreateSetController` obsługuje wszystko poprawnie

**Weryfikacja:**
- Click "Zapisz zestaw" → loading state
- Success → redirect do /sets (lista zestawów)
- Error handling: duplicate, validation, network

---

### Krok 9: Implementacja auto-save do localStorage

**Zadania:**
1. W Stimulus `connect()`:
```javascript
this.autoSaveTimer = setInterval(() => this.autoSave(), 30000);
```

2. `autoSave()` method:
```javascript
autoSave() {
  if (!this.hasUnsavedChangesValue) return;

  const data = {
    timestamp: Date.now(),
    jobId: this.jobIdValue,
    suggestedName: this.suggestedNameValue,
    setName: this.setNameValue,
    sourceText: this.sourceTextValue,
    cards: this.cardsArray
  };

  try {
    localStorage.setItem('flashcard_autosave', JSON.stringify(data));
    console.log('Auto-saved at', new Date().toLocaleTimeString());
  } catch (error) {
    console.error('Auto-save failed:', error);
  }
}
```

3. `disconnect()`:
```javascript
clearInterval(this.autoSaveTimer);
```

**Weryfikacja:**
- Po 30s edycji → localStorage ma dane
- Check DevTools → Application → Local Storage

---

### Krok 10: Implementacja RecoveryPrompt

**Zadania:**
1. Dodać dialog HTML (na końcu body):
```twig
<dialog data-editSet-target="recoveryModal" class="rounded-lg shadow-xl p-6 max-w-md">
    <div class="mb-4">
        <div class="text-4xl mb-2">💾</div>
        <h3 class="text-xl font-bold mb-2">Znaleziono niezapisane zmiany</h3>
        <p class="text-gray-700 mb-4">
            Masz niezapisane zmiany z poprzedniej sesji. Czy chcesz je przywrócić?
        </p>
    </div>

    <div class="flex space-x-3">
        <button data-action="click->editSet#discardRecovery"
                class="flex-1 bg-gray-200 hover:bg-gray-300 py-2 px-4 rounded">
            Zacznij od nowa
        </button>
        <button data-action="click->editSet#recoverData"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
            Przywróć
        </button>
    </div>
</dialog>
```

2. W Stimulus `connect()`:
```javascript
this.checkRecovery();
```

3. Metody:
   - `checkRecovery()` - check localStorage, validate TTL
   - `showRecoveryPrompt()` - show modal
   - `recoverData()` - load from localStorage, re-render
   - `discardRecovery()` - clear localStorage, close modal

**Weryfikacja:**
- Edytować fiszki, close browser
- Re-open `/sets/new/edit` → RecoveryPrompt shows
- Click "Przywróć" → dane restore
- Click "Zacznij od nowa" → fresh data

---

### Krok 11: Implementacja beforeunload protection

**Zadania:**
1. W Stimulus `connect()`:
```javascript
window.addEventListener('beforeunload', this.handleBeforeUnload);
document.addEventListener('turbo:before-visit', this.handleTurboBeforeVisit);
```

2. Event handlers:
```javascript
handleBeforeUnload = (event) => {
  if (this.hasUnsavedChangesValue) {
    event.preventDefault();
    event.returnValue = '';
  }
}

handleTurboBeforeVisit = (event) => {
  if (this.hasUnsavedChangesValue) {
    if (!confirm('Masz niezapisane zmiany. Czy na pewno chcesz opuścić stronę?')) {
      event.preventDefault();
    }
  }
}
```

3. `disconnect()`:
```javascript
window.removeEventListener('beforeunload', this.handleBeforeUnload);
document.removeEventListener('turbo:before-visit', this.handleTurboBeforeVisit);
```

**Weryfikacja:**
- Edytować fiszkę
- Próba close tab → browser prompt
- Próba Turbo navigation → confirm prompt

---

### Krok 12: Implementacja CSS animations

**Zadania:**
1. Dodać CSS do `assets/styles/app.css`:
```css
/* Delete card animation */
.animate-delete {
  animation: fadeOutSlideUp 300ms ease-out forwards;
}

@keyframes fadeOutSlideUp {
  0% {
    opacity: 1;
    transform: translateY(0);
    max-height: 500px;
  }
  100% {
    opacity: 0;
    transform: translateY(-20px);
    max-height: 0;
    padding: 0;
    margin: 0;
  }
}

/* Add card animation */
.animate-slide-down {
  animation: slideDown 300ms ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
```

**Weryfikacja:**
- Delete card → smooth fade-out + slide-up
- Add card → smooth slide-down

---

### Krok 13: Keyboard shortcuts (Ctrl+S)

**Zadania:**
1. W Stimulus `connect()`:
```javascript
document.addEventListener('keydown', this.handleKeydown);
```

2. Handler:
```javascript
handleKeydown = (event) => {
  // Ctrl+S (or Cmd+S on Mac)
  if ((event.ctrlKey || event.metaKey) && event.key === 's') {
    event.preventDefault();
    this.saveSet();
  }

  // Escape in textarea (optional: cancel edit)
  if (event.key === 'Escape' && event.target.tagName === 'TEXTAREA') {
    event.target.blur();
  }
}
```

3. `disconnect()`:
```javascript
document.removeEventListener('keydown', this.handleKeydown);
```

**Weryfikacja:**
- Ctrl+S → zapis zestawu (prevent browser save dialog)
- Escape w textarea → blur

---

### Krok 14: ARIA live regions i accessibility

**Zadania:**
1. Dodać ARIA attributes:
   - Karty fiszek: `aria-label="Fiszka {{ loop.index }}"`
   - Textareas: `aria-label="Przód fiszki"`, `aria-label="Tył fiszki"`
   - Delete buttons: `aria-label="Usuń fiszkę {{ loop.index }}"`

2. `announceCardDeleted()` method update ARIA live region:
```javascript
const liveRegion = document.getElementById('aria-live-region');
if (liveRegion) {
  const activeCards = this.cardsArray.filter(c => !c.isDeleted);
  liveRegion.textContent = `Fiszka usunięta. Pozostało ${activeCards.length} fiszek`;
  setTimeout(() => liveRegion.textContent = '', 3000);
}
```

3. Tab navigation test

**Weryfikacja:**
- Screen reader announces: delete, add, save actions
- Tab navigation przez wszystkie fiszki działa
- axe DevTools: brak błędów accessibility

---

### Krok 15: Responsive testing

**Zadania:**
1. Mobile (320px-767px):
   - Grid: 1 kolumna
   - Footer: full width, stack inputs vertically (if needed)
   - Sticky header/footer working

2. Tablet (768px-1023px):
   - Grid: 2 kolumny

3. Desktop (1024px+):
   - Grid: 2 kolumny
   - Max-width container

**Weryfikacja:**
Chrome DevTools → Responsive mode, test wszystkie breakpointy

---

### Krok 16: E2E testing scenariuszy

**Zadania:**
1. **Happy path:**
   - Generate cards → redirect to edit
   - Edit fiszkę → ✏️ indicator shows
   - Delete fiszkę → animacja + count update
   - Add own fiszkę → slide-down + focus
   - Edit set name → ✨ hides
   - Click save → success → redirect to /sets

2. **Recovery:**
   - Edit cards
   - Close browser
   - Re-open → RecoveryPrompt
   - Click "Przywróć" → data restored

3. **Duplicate name:**
   - Edit name to existing → warning + disabled save
   - Change to unique → warning hides + enabled save

4. **BeforeUnload:**
   - Edit card
   - Try close tab → prompt
   - Cancel → stay on page

**Weryfikacja:**
Wszystkie scenariusze działają

---

### Krok 17: Performance optimization

**Zadania:**
1. Debouncing:
   - Set name change: 500ms (done)
   - Card edit: optional debounce character counter update
2. Minimize DOM manipulations
3. Use CSS transforms dla animations (hardware accelerated)

**Weryfikacja:**
Lighthouse Performance > 90

---

### Krok 18: Documentation i cleanup

**Zadania:**
1. Komentarze w Stimulus controller
2. Komentarze w Twig template
3. Update README

**Weryfikacja:**
Kod czytelny i dobrze udokumentowany

---

## Podsumowanie

Plan implementacji dla widoku **Edycja Fiszek (po generowaniu AI)** obejmuje 18 kroków od struktury plików po finalne testy. Kluczowe elementy to:

- **Local state management** w Stimulus (wszystkie edycje client-side do momentu save)
- **Auto-save do localStorage** (TTL 24h) z recovery prompt
- **Inline editing** bez modali (direct textarea edit)
- **Visual feedback** (✏️ indicator, animations, character counters)
- **Comprehensive validation** (client + server mirroring)
- **BeforeUnload protection** (prevent data loss)
- **Keyboard shortcuts** (Ctrl+S, Escape, Tab navigation)
- **Accessibility** (ARIA live regions, keyboard navigation, screen reader support)
- **Single POST /api/sets** przy zapisie z wszystkimi kartami (AI + manual, edited tracking)
- **KPI tracking integration** (job_id linkage dla analytics)

Implementacja powinna zająć około 3-4 dni dla doświadczonego dewelopera Symfony + Stimulus, ze względu na większą złożoność zarządzania stanem niż w widoku generowania.
