# Plan implementacji widoku Generowanie Fiszek (AI)

## 1. Przegląd

Widok generowania fiszek umożliwia użytkownikowi wklejenie tekstu źródłowego (notatek) i automatyczne wygenerowanie zestawu fiszek przy użyciu AI. Głównym celem jest maksymalne uproszczenie procesu tworzenia fiszek poprzez wykorzystanie sztucznej inteligencji, przy jednoczesnym zachowaniu pełnej kontroli nad wynikiem (walidacja limitu znaków, obsługa błędów, feedback o postępie).

Widok stanowi pierwszy krok w głównym przepływie MVP aplikacji: **wklejenie tekstu → generowanie → edycja → zapis**.

## 2. Routing widoku

**Ścieżka:** `/generate`

**Metoda HTTP:**
- GET - wyświetlenie formularza generowania
- POST - wysłanie żądania generowania (przez Turbo, endpoint: `/api/generate`)

**Dostęp:** Wymaga uwierzytelnienia (`#[IsGranted('ROLE_USER')]`)

## 3. Struktura komponentów

Widok składa się z następujących głównych komponentów zorganizowanych hierarchicznie:

```
GenerateView (główny kontener Twig + Stimulus controller)
├── PageHeader (tytuł + opis jak używać)
├── GenerateForm (główny formularz)
│   ├── SourceTextarea (duże pole tekstowe z auto-resize)
│   ├── CharacterCounter (real-time licznik znaków)
│   ├── ProgressBar (wizualny pasek postępu z kolorami)
│   └── GenerateButton (przycisk "Generuj fiszki")
├── LoadingOverlay (multi-stage loading animation)
└── ErrorModal (modal z komunikatami błędów)
```

**Zarządzanie stanem:** Stimulus controller `generate_controller.js` zarządza całym stanem widoku.

## 4. Szczegóły komponentów

### PageHeader

**Opis komponentu:**
Statyczny nagłówek strony zawierający tytuł widoku i krótką instrukcję użycia. Pomaga użytkownikowi zrozumieć cel strony i wymagania (limit 1000-10000 znaków).

**Główne elementy:**
- `<h1>` - tytuł "Wygeneruj fiszki z notatek"
- `<p>` - opis: "Wklej swoje notatki (1000-10000 znaków), a AI automatycznie utworzy zestaw fiszek do nauki"

**Obsługiwane interakcje:**
Brak (komponent statyczny)

**Obsługiwana walidacja:**
Brak

**Typy:**
Brak (statyczny content)

**Propsy:**
Brak (renderowany bezpośrednio w Twig)

---

### GenerateForm

**Opis komponentu:**
Główny formularz obsługujący proces generowania. Zawiera wszystkie interaktywne elementy i jest kontrolowany przez Stimulus controller. Formularz wykorzystuje Turbo do asynchronicznego wysłania żądania bez przeładowania strony.

**Główne elementy:**
- `<form>` z atrybutem `data-controller="generate"` (Stimulus)
- `data-action="turbo:submit-start->generate#handleSubmitStart turbo:submit-end->generate#handleSubmitEnd"`
- CSRF token (automatycznie przez Symfony)
- Dzieci: SourceTextarea, CharacterCounter, ProgressBar, GenerateButton

**Obsługiwane interakcje:**
- Submit formularza (przez Turbo)
- Input events z textarea (delegowane do Stimulus)

**Obsługiwana walidacja:**
- Długość tekstu 1000-10000 znaków (client-side przed wysłaniem)
- CSRF token validation (server-side)

**Typy:**
- FormData (standardowy HTML FormData)

**Propsy:**
- `action="/api/generate"` (endpoint)
- `method="POST"`
- `data-controller="generate"` (Stimulus)

---

### SourceTextarea

**Opis komponentu:**
Duże pole tekstowe z automatyczną zmianą wysokości (auto-resize) do wklejania notatek. Główny element interaktywny widoku, w którym użytkownik wprowadza tekst źródłowy.

**Główne elementy:**
- `<textarea>` z atrybutami:
  - `name="source_text"`
  - `data-generate-target="textarea"` (Stimulus target)
  - `data-action="input->generate#updateCharacterCount"` (Stimulus action)
  - `placeholder="Wklej tutaj swoje notatki (minimum 1000 znaków)..."`
  - `aria-describedby="character-counter"` (dostępność)
  - `rows="10"` (początkowa wysokość)

**Obsługiwane interakcje:**
- Input event → debounced (300ms) wywołanie `updateCharacterCount()` w Stimulus
- Focus/blur events → opcjonalnie highlighting
- Paste event → automatyczne liczenie po wklejeniu

**Obsługiwana walidacja:**
- Długość tekstu >= 1000 && <= 10000 znaków
- Walidacja real-time (nie blokuje wpisywania, tylko disabled button)

**Typy:**
- `string` - wartość textarea

**Propsy:**
- Brak (renderowany jako część formularza, kontrolowany przez Stimulus)

**Stimulus actions:**
- `input->generate#updateCharacterCount` - debounced update licznika

---

### CharacterCounter

**Opis komponentu:**
Komponent wyświetlający real-time licznik znaków z informacją o limitach (1000-10000). Pokazuje aktualną liczbę znaków oraz pomocniczy tekst wskazujący czy użytkownik osiągnął wymagany zakres.

**Główne elementy:**
- `<div id="character-counter" role="status" aria-live="polite">` (ARIA live region dla screen readers)
  - `<span data-generate-target="charCount">0</span> / 10000 znaków`
  - `<span data-generate-target="counterHint" class="text-sm">` - pomocniczy tekst:
    - Czerwony jeśli < 1000: "Minimum 1000 znaków (brakuje: X)"
    - Zielony jeśli 1000-10000: "Zakres poprawny ✓"
    - Czerwony jeśli > 10000: "Przekroczono limit (za dużo: X)"

**Obsługiwane interakcje:**
Brak (komponent pasywny, odbiera tylko dane z Stimulus)

**Obsługiwana walidacja:**
- Wizualna walidacja zakresu (kolory, tekst pomocniczy)

**Typy:**
```typescript
ValidationState {
  count: number
  isUnder: boolean  // count < 1000
  isValid: boolean  // 1000 <= count <= 10000
  isOver: boolean   // count > 10000
}
```

**Propsy:**
Brak (wartości ustawiane przez Stimulus targets)

**Stimulus targets:**
- `charCount` - element z liczbą znaków
- `counterHint` - element z tekstem pomocniczym

---

### ProgressBar

**Opis komponentu:**
Wizualny pasek postępu pokazujący proporcjonalnie wypełnienie w stosunku do limitu znaków z kolorowym feedbackiem (czerwony poza zakresem, zielony w zakresie 1000-10000).

**Główne elementy:**
- `<div class="w-full bg-gray-200 rounded-full h-2">` - kontener
  - `<div data-generate-target="progressBar" class="h-2 rounded-full transition-all duration-300">` - pasek postępu
    - Szerokość: `style="width: X%"`
    - Kolor (Tailwind classes):
      - `bg-red-500` jeśli < 1000 lub > 10000
      - `bg-green-500` jeśli 1000-10000

**Obsługiwane interakcje:**
Brak (komponent pasywny)

**Obsługiwana walidacja:**
Wizualna reprezentacja walidacji (kolor paska)

**Typy:**
```typescript
ProgressBarState {
  percentage: number  // 0-100 (proporcja do 10000)
  color: 'red' | 'green'
}
```

**Propsy:**
Brak (wartości ustawiane przez Stimulus)

**Stimulus targets:**
- `progressBar` - element paska (Stimulus ustawia width i color classes)

---

### GenerateButton

**Opis komponentu:**
Przycisk uruchamiający proces generowania fiszek. Aktywny tylko gdy liczba znaków mieści się w zakresie 1000-10000. Podczas ładowania pokazuje spinner i zmienia tekst.

**Główne elementy:**
- `<button type="submit" data-generate-target="submitButton">`
  - `disabled` attribute (kontrolowany przez Stimulus)
  - Tekst: "Generuj fiszki" (idle) / "Generowanie..." (loading)
  - Spinner icon (hidden podczas idle, visible podczas loading)

**Obsługiwane interakcje:**
- Click → submit formularza (przez Turbo)
- Disabled state zapobiega submitowi gdy walidacja failed

**Obsługiwana walidacja:**
- Button disabled jeśli `count < 1000 || count > 10000`

**Typy:**
```typescript
ButtonState {
  disabled: boolean
  loading: boolean
}
```

**Propsy:**
Brak (kontrolowany przez Stimulus)

**Stimulus targets:**
- `submitButton` - sam button (Stimulus kontroluje disabled attribute)

---

### LoadingOverlay

**Opis komponentu:**
Pełnoekranowy overlay z multi-stage loading animation. Pokazuje symulowany postęp z dwoma etapami: "Analizuję tekst..." → "Tworzę fiszki...". Etapy są symulowane po stronie klienta (brak real-time progress z API), aby zapewnić psychologiczny komfort użytkownikowi podczas długiego oczekiwania (10-30s).

**Główne elementy:**
- `<div data-generate-target="loadingOverlay" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">` (overlay)
  - Spinner/loading animation
  - `<div role="status" aria-live="polite">` (ARIA live region)
    - `<p data-generate-target="loadingMessage">` - komunikat o aktualnym etapie

**Obsługiwane interakcje:**
Brak (nie można zamknąć podczas ładowania)

**Obsługiwana walidacja:**
Brak

**Typy:**
```typescript
LoadingState {
  visible: boolean
  stage: 'analyzing' | 'creating'
  message: string
}
```

**Propsy:**
Brak (kontrolowany przez Stimulus)

**Stimulus targets:**
- `loadingOverlay` - główny overlay (Stimulus toggles visibility)
- `loadingMessage` - tekst komunikatu (Stimulus updates innerText)

**Logika multi-stage:**
- Start: pokazanie overlay z "Analizuję tekst..."
- Po 3-5s: zmiana na "Tworzę fiszki..."
- Response z API: ukrycie overlay

---

### ErrorModal

**Opis komponentu:**
Modal wyświetlający komunikaty błędów z recovery options (sugestie dla użytkownika). Obsługuje różne typy błędów: timeout (504), AI failure (500), validation (422).

**Główne elementy:**
- `<dialog data-generate-target="errorModal">` (HTML dialog element)
  - Icon błędu (❌)
  - `<h3>` - tytuł błędu
  - `<p data-generate-target="errorMessage">` - szczegółowy komunikat
  - `<ul data-generate-target="errorSuggestions">` - lista sugestii recovery
  - Przyciski:
    - "Zamknij" - zamyka modal, user wraca do formularza
    - "Spróbuj ponownie" - zamyka modal i ponownie submituje formularz (optional)

**Obsługiwane interakcje:**
- Click "Zamknij" → `generate#closeErrorModal` (ukrycie modalu)
- Click "Spróbuj ponownie" → `generate#retryGeneration` (ponowny submit)
- Escape key → zamknięcie modalu (native dialog behavior)

**Obsługiwana walidacja:**
Brak

**Typy:**
```typescript
ErrorState {
  type: 'timeout' | 'validation' | 'ai_failure' | 'unknown'
  message: string
  suggestions: string[]  // lista recovery suggestions
}
```

**Propsy:**
Brak (kontrolowany przez Stimulus)

**Stimulus targets:**
- `errorModal` - dialog element
- `errorMessage` - tekst błędu
- `errorSuggestions` - lista UL (Stimulus renderuje <li> items)

**Error types i suggestions:**

**Timeout (504):**
- Message: "Generowanie fiszek przekroczyło limit czasu (30s). Spróbuj ponownie z krótszym tekstem."
- Suggestions:
  - "Skróć tekst do 5000-7000 znaków"
  - "Usuń znaki specjalne i formatowanie"
  - "Uprość język i usuń skomplikowane fragmenty"

**AI Failure (500):**
- Message: "Wystąpił błąd podczas generowania fiszek. Spróbuj ponownie później."
- Suggestions:
  - "Odczekaj 1-2 minuty i spróbuj ponownie"
  - "Sprawdź czy tekst nie zawiera niepoprawnych znaków"

**Validation (422):**
- Message: "Dane wejściowe są nieprawidłowe"
- Suggestions: lista violations z API

---

## 5. Typy

### Request DTO (backend, już istnieje)

```php
class GenerateCardsRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1000, max: 10000)]
    private string $sourceText;

    public function getSourceText(): string
    {
        return $this->sourceText;
    }

    public function setSourceText(string $sourceText): void
    {
        $this->sourceText = $sourceText;
    }
}
```

### Response DTO (backend, już istnieje)

```php
class GenerateCardsResponse
{
    public function __construct(
        public readonly string $jobId,
        public readonly string $suggestedName,
        /** @var CardPreviewDto[] */
        public readonly array $cards,
        public readonly int $generatedCount,
    ) {}
}

class CardPreviewDto
{
    public function __construct(
        public readonly string $front,
        public readonly string $back,
    ) {}
}
```

### Frontend ViewModel (Stimulus Values)

```javascript
// Stimulus controller values (typed using Stimulus TypeScript)
static values = {
  // Aktualny tekst źródłowy
  sourceText: { type: String, default: '' },

  // Liczba znaków
  characterCount: { type: Number, default: 0 },

  // Czy walidacja passed (1000-10000)
  isValid: { type: Boolean, default: false },

  // Czy trwa ładowanie
  isLoading: { type: Boolean, default: false },

  // Aktualny etap ładowania ('analyzing' | 'creating' | null)
  loadingStage: { type: String, default: null },

  // Error state (JSON string, null jeśli brak błędu)
  error: { type: String, default: null }
}
```

### ValidationState (internal type)

```typescript
interface ValidationState {
  count: number;
  min: number;  // 1000
  max: number;  // 10000
  isUnder: boolean;  // count < min
  isValid: boolean;  // min <= count <= max
  isOver: boolean;   // count > max
  percentage: number;  // 0-100 dla progress bar
}
```

### ErrorState (internal type)

```typescript
interface ErrorState {
  type: 'timeout' | 'validation' | 'ai_failure' | 'unknown';
  message: string;
  suggestions: string[];
}
```

---

## 6. Zarządzanie stanem

### Stimulus Controller: `generate_controller.js`

Zarządzanie stanem widoku odbywa się przez **Stimulus controller**, który jest lekkim kontrolerem eventów bez skomplikowanego state management (nie używamy Redux, Vuex itp.).

**Values (reactive state):**
- `sourceTextValue` - aktualny tekst w textarea
- `characterCountValue` - liczba znaków
- `isValidValue` - czy walidacja OK
- `isLoadingValue` - czy trwa generowanie
- `loadingStageValue` - aktualny etap ('analyzing', 'creating', null)
- `errorValue` - JSON string z ErrorState lub null

**Targets (DOM references):**
- `textarea` - pole tekstowe
- `charCount` - element z liczbą znaków
- `counterHint` - tekst pomocniczy licznika
- `progressBar` - pasek postępu
- `submitButton` - przycisk submit
- `loadingOverlay` - overlay ładowania
- `loadingMessage` - komunikat w overlay
- `errorModal` - dialog błędu
- `errorMessage` - tekst błędu w modalu
- `errorSuggestions` - lista sugestii

**Kluczowe metody:**

```javascript
// Inicjalizacja kontrolera
connect() {
  // Setup initial state
  this.updateCharacterCount();
}

// Debounced update licznika (300ms)
updateCharacterCount() {
  clearTimeout(this.debounceTimer);
  this.debounceTimer = setTimeout(() => {
    const text = this.textareaTarget.value;
    this.sourceTextValue = text;
    this.characterCountValue = text.length;

    this.validateInput();
    this.updateUI();
  }, 300);
}

// Walidacja długości
validateInput() {
  const count = this.characterCountValue;
  const validationState = {
    count,
    min: 1000,
    max: 10000,
    isUnder: count < 1000,
    isValid: count >= 1000 && count <= 10000,
    isOver: count > 10000,
    percentage: Math.min((count / 10000) * 100, 100)
  };

  this.isValidValue = validationState.isValid;
  return validationState;
}

// Update UI na podstawie validation state
updateUI() {
  const state = this.validateInput();

  // Update licznika
  this.charCountTarget.textContent = state.count;

  // Update hint
  if (state.isUnder) {
    this.counterHintTarget.textContent =
      `Minimum 1000 znaków (brakuje: ${1000 - state.count})`;
    this.counterHintTarget.classList.add('text-red-600');
    this.counterHintTarget.classList.remove('text-green-600');
  } else if (state.isValid) {
    this.counterHintTarget.textContent = 'Zakres poprawny ✓';
    this.counterHintTarget.classList.add('text-green-600');
    this.counterHintTarget.classList.remove('text-red-600');
  } else if (state.isOver) {
    this.counterHintTarget.textContent =
      `Przekroczono limit (za dużo: ${state.count - 10000})`;
    this.counterHintTarget.classList.add('text-red-600');
    this.counterHintTarget.classList.remove('text-green-600');
  }

  // Update progress bar
  this.updateProgressBar(state);

  // Update button
  this.submitButtonTarget.disabled = !state.isValid;
}

// Update koloru i wypełnienia paska
updateProgressBar(state) {
  const bar = this.progressBarTarget;
  bar.style.width = `${state.percentage}%`;

  if (state.isValid) {
    bar.classList.add('bg-green-500');
    bar.classList.remove('bg-red-500');
  } else {
    bar.classList.add('bg-red-500');
    bar.classList.remove('bg-green-500');
  }
}

// Obsługa submit start (Turbo event)
handleSubmitStart(event) {
  this.isLoadingValue = true;
  this.showLoading();
}

// Pokazanie loading overlay
showLoading() {
  this.loadingOverlayTarget.classList.remove('hidden');
  this.loadingStageValue = 'analyzing';
  this.loadingMessageTarget.textContent = 'Analizuję tekst...';

  // Symulowany progress: po 5s zmiana na drugi etap
  this.stageTimeout = setTimeout(() => {
    this.loadingStageValue = 'creating';
    this.loadingMessageTarget.textContent = 'Tworzę fiszki...';
  }, 5000);
}

// Obsługa submit end (Turbo event)
handleSubmitEnd(event) {
  clearTimeout(this.stageTimeout);
  this.hideLoading();

  const response = event.detail.fetchResponse;

  if (response.succeeded) {
    // Success: Turbo automatycznie przekieruje do /sets/new/edit
    // Dane z response będą w session (obsługa po stronie serwera)
  } else {
    // Error: pokazanie error modal
    this.handleError(response);
  }
}

// Ukrycie loading overlay
hideLoading() {
  this.isLoadingValue = false;
  this.loadingStageValue = null;
  this.loadingOverlayTarget.classList.add('hidden');
}

// Obsługa błędów
async handleError(response) {
  let errorData;

  try {
    errorData = await response.response.json();
  } catch {
    errorData = {
      error: 'unknown',
      message: 'Wystąpił nieoczekiwany błąd'
    };
  }

  const errorState = this.mapErrorToState(errorData, response.statusCode);
  this.errorValue = JSON.stringify(errorState);
  this.showErrorModal(errorState);
}

// Mapowanie response na ErrorState
mapErrorToState(errorData, statusCode) {
  switch (statusCode) {
    case 504:
      return {
        type: 'timeout',
        message: errorData.message || 'Generowanie przekroczyło limit czasu (30s)',
        suggestions: [
          'Skróć tekst do 5000-7000 znaków',
          'Usuń znaki specjalne i formatowanie',
          'Uprość język i usuń skomplikowane fragmenty'
        ]
      };

    case 422:
      return {
        type: 'validation',
        message: errorData.message || 'Dane wejściowe są nieprawidłowe',
        suggestions: errorData.violations?.map(v => v.message) || []
      };

    case 500:
      return {
        type: 'ai_failure',
        message: errorData.message || 'Wystąpił błąd podczas generowania fiszek',
        suggestions: [
          'Odczekaj 1-2 minuty i spróbuj ponownie',
          'Sprawdź czy tekst nie zawiera niepoprawnych znaków'
        ]
      };

    default:
      return {
        type: 'unknown',
        message: 'Wystąpił nieoczekiwany błąd',
        suggestions: ['Spróbuj ponownie później']
      };
  }
}

// Pokazanie error modal
showErrorModal(errorState) {
  this.errorMessageTarget.textContent = errorState.message;

  // Renderowanie suggestions
  this.errorSuggestionsTarget.innerHTML = errorState.suggestions
    .map(s => `<li>${s}</li>`)
    .join('');

  // Pokazanie modalu (HTML dialog API)
  this.errorModalTarget.showModal();
}

// Zamknięcie error modal
closeErrorModal() {
  this.errorModalTarget.close();
  this.errorValue = null;
}

// Retry generation
retryGeneration() {
  this.closeErrorModal();

  // Ponowny submit formularza
  this.element.requestSubmit();
}
```

**Brak custom hooks:** Nie używamy React, więc nie ma custom hooks. Stimulus zapewnia reaktywność przez values i targets.

---

## 7. Integracja API

### Endpoint: POST /api/generate

**Opis:**
Synchroniczne generowanie fiszek z tekstu źródłowego. Blocking call z timeoutem 30s. Zwraca wygenerowane fiszki wraz z job_id (do KPI tracking) i suggested_name (auto-sugestia nazwy zestawu).

**Request:**
```json
{
  "source_text": "Tekst notatek użytkownika (1000-10000 znaków)..."
}
```

**Request headers:**
- `Content-Type: application/json`
- `X-CSRF-Token: ...` (automatycznie przez Turbo z meta tag)

**Response Success (200 OK):**
```json
{
  "job_id": "7c9bda17-fdec-4e89-82e9-5d93b10a9c40",
  "suggested_name": "Biologia - Fotosynteza",
  "cards": [
    {
      "front": "Co to jest fotosynteza?",
      "back": "Proces przekształcania energii świetlnej w chemiczną..."
    },
    {
      "front": "Gdzie zachodzi fotosynteza?",
      "back": "W chloroplastach komórek roślinnych..."
    }
  ],
  "generated_count": 15
}
```

**Response Error (422 Unprocessable Entity - Validation):**
```json
{
  "error": "validation_failed",
  "message": "Dane wejściowe są nieprawidłowe",
  "violations": [
    {
      "field": "source_text",
      "message": "Tekst musi mieć od 1000 do 10000 znaków"
    }
  ]
}
```

**Response Error (504 Gateway Timeout):**
```json
{
  "error": "ai_timeout",
  "message": "Generowanie fiszek przekroczyło limit czasu (30s). Spróbuj ponownie z krótszym tekstem."
}
```

**Response Error (500 Internal Server Error):**
```json
{
  "error": "ai_service_error",
  "message": "Wystąpił błąd podczas generowania fiszek. Spróbuj ponownie później."
}
```

### Przepływ danych:

1. **User submit formularza** → Turbo intercepts, wysyła POST /api/generate
2. **Stimulus `handleSubmitStart`** → pokazanie LoadingOverlay z etapem "Analizuję tekst..."
3. **Po 5s** → Stimulus zmienia etap na "Tworzę fiszki..." (symulowane, niezależnie od API)
4. **API response:**
   - **Success (200):** Turbo automatycznie przekierowuje do `/sets/new/edit`
     - Backend zapisuje dane z response do session: `job_id`, `suggested_name`, `cards`, `source_text`, `generated_count`
     - Frontend nie musi nic robić (Turbo Stream navigation)
   - **Error (4xx/5xx):** Stimulus `handleSubmitEnd` → ukrycie loading → pokazanie ErrorModal z odpowiednim message i suggestions

### Przekazanie danych do następnego widoku (/sets/new/edit):

**Problem:** Jak przekazać `job_id`, `suggested_name`, `cards`, `source_text` do widoku edycji?

**Rozwiązanie:** Session storage po stronie serwera (backend)

**Implementacja:**

W `GenerateCardsController` po sukcesie (przed response):
```php
// Zapisanie danych do session dla następnego widoku
$request->getSession()->set('pending_set', [
    'job_id' => $response->jobId,
    'suggested_name' => $response->suggestedName,
    'cards' => $response->cards,
    'source_text' => $sourceText->toString(),
    'generated_count' => $response->generatedCount,
]);

// Turbo Stream redirect response
return $this->json($response, Response::HTTP_OK, [
    'Turbo-Location' => '/sets/new/edit'
]);
```

W widoku `/sets/new/edit` (następny krok):
```php
// Odczytanie danych z session
$pendingSet = $request->getSession()->get('pending_set');

// Renderowanie widoku z danymi
return $this->render('sets/edit_new.html.twig', [
    'jobId' => $pendingSet['job_id'],
    'suggestedName' => $pendingSet['suggested_name'],
    'cards' => $pendingSet['cards'],
    'sourceText' => $pendingSet['source_text'],
    'generatedCount' => $pendingSet['generated_count'],
]);
```

Po zapisaniu zestawu: `$request->getSession()->remove('pending_set');`

---

## 8. Interakcje użytkownika

### 1. Wpisywanie/wklejanie tekstu w textarea

**Akcja użytkownika:**
User wpisuje lub wkleja tekst do pola tekstowego

**Reakcja systemu:**
- Debounced (300ms) wywołanie `updateCharacterCount()` w Stimulus
- Przeliczenie liczby znaków
- Walidacja zakresu (1000-10000)
- Update UI: licznik, pasek postępu, przycisk

**Oczekiwany wynik:**
- Licznik pokazuje aktualną liczbę znaków
- Pasek postępu zmienia wypełnienie i kolor (red → green → red)
- Tekst pomocniczy informuje o stanie ("brakuje X znaków" / "zakres poprawny" / "przekroczono limit")
- Przycisk "Generuj fiszki" enabled/disabled według walidacji

---

### 2. Osiągnięcie valid range (1000-10000 znaków)

**Akcja użytkownika:**
User wpisał/wkleił tekst o długości 1000-10000 znaków

**Reakcja systemu:**
- Walidacja: `isValid = true`
- Pasek postępu zielony
- Tekst pomocniczy: "Zakres poprawny ✓" (zielony)
- Przycisk enabled

**Oczekiwany wynik:**
User może kliknąć "Generuj fiszki" i rozpocząć proces generowania

---

### 3. Przekroczenie/niedopełnienie limitu

**Akcja użytkownika:**
User ma tekst < 1000 lub > 10000 znaków

**Reakcja systemu:**
- Walidacja: `isValid = false`
- Pasek postępu czerwony
- Tekst pomocniczy (czerwony):
  - Jeśli < 1000: "Minimum 1000 znaków (brakuje: X)"
  - Jeśli > 10000: "Przekroczono limit (za dużo: X)"
- Przycisk disabled

**Oczekiwany wynik:**
User nie może wysłać formularza, widzi jasny komunikat co trzeba poprawić

---

### 4. Kliknięcie przycisku "Generuj fiszki"

**Akcja użytkownika:**
User klika przycisk submit (enabled tylko gdy walidacja OK)

**Reakcja systemu:**
- Turbo interceptuje submit, wysyła POST /api/generate
- Stimulus `handleSubmitStart` event
- Pokazanie LoadingOverlay z komunikatem "Analizuję tekst..."
- Przycisk zmienia tekst na "Generowanie..." i pokazuje spinner
- Dezaktywacja formularza (prevent double submit)

**Oczekiwany wynik:**
User widzi loading state, wie że proces się rozpoczął

---

### 5. Oczekiwanie na response (multi-stage loading)

**Akcja użytkownika:**
User czeka na odpowiedź z API (5-30s)

**Reakcja systemu:**
- Po 5s: automatyczna zmiana etapu na "Tworzę fiszki..." (symulowane)
- ARIA live region announce dla screen readers

**Oczekiwany wynik:**
User otrzymuje feedback o postępie (psychologiczny komfort), nie ma wrażenia "zawieszenia"

---

### 6. Sukces generowania

**Akcja użytkownika:**
API zwróciło 200 z wygenerowanymi fiszkami

**Reakcja systemu:**
- Stimulus `handleSubmitEnd` → ukrycie loading
- Backend zapisuje dane do session
- Turbo navigation do `/sets/new/edit`
- User widzi nowy widok z wygenerowanymi fiszkami

**Oczekiwany wynik:**
Płynne przejście do ekranu edycji bez przeładowania strony

---

### 7. Timeout (>30s)

**Akcja użytkownika:**
API zwróciło 504 Gateway Timeout

**Reakcja systemu:**
- Stimulus `handleSubmitEnd` → ukrycie loading
- Mapowanie błędu na ErrorState type='timeout'
- Pokazanie ErrorModal z komunikatem:
  - "Generowanie fiszek przekroczyło limit czasu (30s)..."
  - Suggestions:
    - "Skróć tekst do 5000-7000 znaków"
    - "Usuń znaki specjalne i formatowanie"
    - "Uprość język..."

**Oczekiwany wynik:**
User otrzymuje jasny komunikat co poszło źle i konkretne sugestie jak naprawić

---

### 8. Błąd AI (500)

**Akcja użytkownika:**
API zwróciło 500 Internal Server Error

**Reakcja systemu:**
- Ukrycie loading
- Pokazanie ErrorModal type='ai_failure':
  - "Wystąpił błąd podczas generowania fiszek..."
  - Suggestions: "Odczekaj 1-2 minuty i spróbuj ponownie"

**Oczekiwany wynik:**
User wie że błąd nie jest po jego stronie, może spróbować później

---

### 9. Walidacja error (422)

**Akcja użytkownika:**
API zwróciło 422 (edge case, client-side validation powinna zapobiec)

**Reakcja systemu:**
- Ukrycie loading
- Pokazanie ErrorModal type='validation':
  - "Dane wejściowe są nieprawidłowe"
  - Suggestions: lista violations z API (np. "Tekst musi mieć 1000-10000 znaków")

**Oczekiwany wynik:**
User poprawia błędy i próbuje ponownie

---

### 10. Zamknięcie ErrorModal

**Akcja użytkownika:**
User klika "Zamknij" lub Escape w ErrorModal

**Reakcja systemu:**
- Wywołanie `closeErrorModal()` w Stimulus
- Zamknięcie dialog
- Reset `errorValue` = null

**Oczekiwany wynik:**
User wraca do formularza, tekst zachowany w textarea, może edytować i spróbować ponownie

---

### 11. Retry po błędzie

**Akcja użytkownika:**
User klika "Spróbuj ponownie" w ErrorModal

**Reakcja systemu:**
- Wywołanie `retryGeneration()` w Stimulus
- Zamknięcie modalu
- Ponowne wysłanie formularza przez `this.element.requestSubmit()`

**Oczekiwany wynik:**
Ponowne uruchomienie procesu generowania bez konieczności ręcznego klikania przycisku

---

## 9. Warunki i walidacja

### Warunki weryfikowane przez interfejs:

#### 1. Długość tekstu źródłowego

**Warunek:** `1000 <= sourceText.length <= 10000`

**Komponenty:**
- **CharacterCounter** - wyświetla aktualny count i waliduje zakres
- **ProgressBar** - wizualizuje proporcję i kolor
- **GenerateButton** - disabled jeśli warunek not met

**Wpływ na UI:**
- **count < 1000:**
  - Licznik: czerwony tekst "Minimum 1000 znaków (brakuje: X)"
  - Pasek: czerwony, wypełnienie proporcjonalne
  - Przycisk: disabled
- **1000 <= count <= 10000:**
  - Licznik: zielony tekst "Zakres poprawny ✓"
  - Pasek: zielony, wypełnienie proporcjonalne
  - Przycisk: enabled
- **count > 10000:**
  - Licznik: czerwony tekst "Przekroczono limit (za dużo: X)"
  - Pasek: czerwony, 100% wypełnienia
  - Przycisk: disabled

**Implementacja:**
Stimulus `validateInput()` + `updateUI()` methods, debounced 300ms

---

#### 2. Pole tekstowe nie jest puste

**Warunek:** `sourceText.length > 0`

**Komponenty:**
- **CharacterCounter** - pokazuje 0 jeśli puste
- **GenerateButton** - disabled (implied przez warunek #1)

**Wpływ na UI:**
- Licznik pokazuje "0 / 10000 znaków"
- Tekst pomocniczy: "Minimum 1000 znaków (brakuje: 1000)"
- Przycisk disabled

---

#### 3. CSRF Token

**Warunek:** Request musi zawierać valid CSRF token

**Komponenty:**
- **GenerateForm** - meta tag w `<head>` z tokenem
- Turbo automatycznie dołącza token do POST requests

**Wpływ na UI:**
Transparent dla użytkownika. Jeśli token invalid, backend zwróci 403 → ErrorModal "Session wygasła, zaloguj się ponownie"

**Implementacja:**
W `base.html.twig`:
```twig
<meta name="csrf-token" content="{{ csrf_token('authenticate') }}">
```

Turbo automatycznie używa tokenu z meta tag.

---

### Server-side validation (mirroring):

Backend (`GenerateCardsController`) wykonuje te same walidacje:
1. `source_text` not blank (Symfony Assert)
2. `source_text` length 1000-10000 (Symfony Assert)
3. User authenticated (Symfony Security)
4. CSRF token valid (Symfony CSRF component)

Jeśli client-side validation failed (edge case: JS disabled, manual API call), server zwróci 422 z violations.

---

## 10. Obsługa błędów

### 1. Validation Error (422)

**Przyczyna:**
- Długość tekstu poza zakresem 1000-10000 (mimo client-side validation)
- Server-side validation catch edge cases

**Obsługa:**
- Pokazanie ErrorModal z type='validation'
- Message: "Dane wejściowe są nieprawidłowe"
- Suggestions: lista violations z API response

**Recovery:**
User edytuje tekst zgodnie z violations i próbuje ponownie

---

### 2. Timeout (504)

**Przyczyna:**
- AI processing przekroczył 30s
- Zazwyczaj długie lub skomplikowane teksty

**Obsługa:**
- ErrorModal type='timeout'
- Message: "Generowanie fiszek przekroczyło limit czasu (30s)..."
- Suggestions:
  - "Skróć tekst do 5000-7000 znaków"
  - "Usuń znaki specjalne i formatowanie"
  - "Uprość język i usuń skomplikowane fragmenty"

**Recovery:**
User edytuje tekst (skraca, upraszcza) i retry

---

### 3. AI Service Error (500)

**Przyczyna:**
- Błąd po stronie OpenRouter.ai
- Internal server error w backend
- AI zwróciło nieprawidłowy format

**Obsługa:**
- ErrorModal type='ai_failure'
- Message: "Wystąpił błąd podczas generowania fiszek..."
- Suggestions:
  - "Odczekaj 1-2 minuty i spróbuj ponownie"
  - "Sprawdź czy tekst nie zawiera niepoprawnych znaków"

**Recovery:**
User czeka chwilę i retry, ewentualnie zgłasza problem do supportu

---

### 4. Network Error (client-side)

**Przyczyna:**
- Brak połączenia internetowego
- Request timeout po stronie klienta
- CORS issues (development)

**Obsługa:**
- Turbo automatycznie pokazuje error state
- Stimulus dodatkowo ErrorModal type='unknown'
- Message: "Wystąpił problem z połączeniem. Sprawdź internet i spróbuj ponownie."

**Recovery:**
User sprawdza połączenie i retry

---

### 5. Unauthorized (401)

**Przyczyna:**
- Sesja użytkownika wygasła
- User wylogowany w innej karcie

**Obsługa:**
- Symfony Security automatycznie przekierowuje do `/login`
- Turbo zachowuje context, po login może wrócić do formularza

**Recovery:**
User loguje się ponownie, tekst może być zachowany w localStorage (future enhancement)

---

### 6. Rate Limiting (429) - post-MVP

**Przyczyna:**
- User przekroczył limit requestów (5/min per user)

**Obsługa:**
- ErrorModal type='rate_limit'
- Message: "Przekroczono limit generowań. Odczekaj chwilę."
- Suggestions: "Możesz generować max 5 zestawów na minutę"

**Recovery:**
User czeka 1 minutę i retry

---

## 11. Kroki implementacji

### Krok 1: Utworzenie struktury plików

**Zadania:**
1. Utworzyć template Twig: `templates/generate/index.html.twig`
2. Utworzyć Stimulus controller: `assets/controllers/generate_controller.js`
3. Utworzyć controller PHP (jeśli nie istnieje): `src/Controller/GenerateViewController.php` (renderuje view)
   - Route: `#[Route('/generate', name: 'generate_view', methods: ['GET'])]`
   - Render: `return $this->render('generate/index.html.twig');`

**Weryfikacja:**
- `/generate` wyświetla pustą stronę z podstawowym layoutem

---

### Krok 2: Implementacja podstawowego layoutu Twig

**Zadania:**
1. W `templates/generate/index.html.twig`:
   - Extend `base.html.twig`
   - Dodać `<div data-controller="generate">`
   - Dodać PageHeader (h1 + opis)
   - Dodać szkielet formularza z action="/api/generate"

**Przykład:**
```twig
{% extends 'base.html.twig' %}

{% block title %}Generuj fiszki{% endblock %}

{% block body %}
<div class="container mx-auto px-4 py-8" data-controller="generate">
    <header class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Wygeneruj fiszki z notatek</h1>
        <p class="text-gray-600">Wklej swoje notatki (1000-10000 znaków), a AI automatycznie utworzy zestaw fiszek do nauki</p>
    </header>

    <form action="/api/generate" method="POST"
          data-turbo="true"
          data-action="turbo:submit-start->generate#handleSubmitStart turbo:submit-end->generate#handleSubmitEnd">
        {# Komponenty będą tutaj #}
    </form>
</div>
{% endblock %}
```

**Weryfikacja:**
- Strona wyświetla header i pustą formę

---

### Krok 3: Implementacja SourceTextarea + CharacterCounter

**Zadania:**
1. Dodać textarea z Stimulus targets i actions:
```twig
<div class="mb-4">
    <textarea
        name="source_text"
        data-generate-target="textarea"
        data-action="input->generate#updateCharacterCount"
        placeholder="Wklej tutaj swoje notatki (minimum 1000 znaków)..."
        rows="10"
        aria-describedby="character-counter"
        class="w-full p-4 border rounded-lg resize-y focus:ring-2 focus:ring-blue-500"
    ></textarea>
</div>
```

2. Dodać CharacterCounter:
```twig
<div id="character-counter" role="status" aria-live="polite" class="mb-2 text-sm">
    <span data-generate-target="charCount">0</span> / 10 000 znaków
    <span data-generate-target="counterHint" class="ml-2"></span>
</div>
```

3. W Stimulus controller dodać metody:
   - `connect()` - init
   - `updateCharacterCount()` - debounced counting
   - `validateInput()` - validation logic
   - `updateUI()` - update licznika i hint

**Weryfikacja:**
- Wpisywanie tekstu aktualizuje licznik (debounced 300ms)
- Hint pokazuje "brakuje X znaków" / "zakres poprawny" / "przekroczono"

---

### Krok 4: Implementacja ProgressBar

**Zadania:**
1. Dodać progress bar HTML:
```twig
<div class="w-full bg-gray-200 rounded-full h-2 mb-6">
    <div data-generate-target="progressBar"
         class="h-2 rounded-full transition-all duration-300 bg-red-500"
         style="width: 0%"></div>
</div>
```

2. W Stimulus `updateProgressBar()` method:
   - Obliczanie percentage: `(count / 10000) * 100`
   - Ustawianie width: `this.progressBarTarget.style.width = ${percentage}%`
   - Zmiana koloru classes: red vs green

**Weryfikacja:**
- Pasek rośnie z wpisywaniem tekstu
- Kolor zmienia się: czerwony → zielony (1000) → zielony (10000) → czerwony (>10000)

---

### Krok 5: Implementacja GenerateButton

**Zadania:**
1. Dodać button:
```twig
<button type="submit"
        data-generate-target="submitButton"
        disabled
        class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition">
    Generuj fiszki
</button>
```

2. W Stimulus `updateUI()`:
   - Ustawianie `this.submitButtonTarget.disabled = !state.isValid`

**Weryfikacja:**
- Przycisk disabled gdy < 1000 lub > 10000 znaków
- Przycisk enabled gdy 1000-10000

---

### Krok 6: Implementacja LoadingOverlay

**Zadania:**
1. Dodać overlay (na końcu formularza, poza form tag):
```twig
<div data-generate-target="loadingOverlay"
     class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-8 rounded-lg shadow-xl">
        <div class="flex items-center space-x-4">
            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div role="status" aria-live="polite">
                <p data-generate-target="loadingMessage" class="text-lg font-semibold">Analizuję tekst...</p>
            </div>
        </div>
    </div>
</div>
```

2. W Stimulus:
   - `handleSubmitStart()` → `showLoading()`
   - `showLoading()` → remove 'hidden', set message, setTimeout dla zmiany etapu
   - `hideLoading()` → add 'hidden', clear timeout

**Weryfikacja:**
- Po submit pokazuje się overlay z "Analizuję tekst..."
- Po 5s zmienia się na "Tworzę fiszki..."

---

### Krok 7: Implementacja ErrorModal

**Zadania:**
1. Dodać dialog (na końcu body):
```twig
<dialog data-generate-target="errorModal" class="rounded-lg shadow-xl p-6 max-w-md">
    <div class="mb-4">
        <h3 class="text-xl font-bold text-red-600 mb-2">Wystąpił błąd</h3>
        <p data-generate-target="errorMessage" class="text-gray-700 mb-4"></p>

        <div class="bg-gray-50 p-4 rounded">
            <p class="font-semibold mb-2">Co możesz zrobić:</p>
            <ul data-generate-target="errorSuggestions" class="list-disc list-inside space-y-1 text-sm text-gray-600"></ul>
        </div>
    </div>

    <div class="flex space-x-3">
        <button type="button"
                data-action="click->generate#closeErrorModal"
                class="flex-1 bg-gray-200 hover:bg-gray-300 py-2 px-4 rounded">
            Zamknij
        </button>
        <button type="button"
                data-action="click->generate#retryGeneration"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
            Spróbuj ponownie
        </button>
    </div>
</dialog>
```

2. W Stimulus:
   - `handleError()` → parse response, map to ErrorState
   - `mapErrorToState()` → switch na statusCode
   - `showErrorModal()` → update message i suggestions, `showModal()`
   - `closeErrorModal()` → `close()`
   - `retryGeneration()` → close + `requestSubmit()`

**Weryfikacja:**
- Symulować błąd (np. disconnect backend), sprawdzić czy modal się pokazuje
- Sprawdzić różne typy błędów (timeout, AI failure) - różne suggestions

---

### Krok 8: Integracja z backend endpoint

**Zadania:**
1. Sprawdzić czy `GenerateCardsController` już istnieje (exists: `src/UI/Http/Controller/GenerateCardsController.php`)
2. Dodać obsługę session storage w kontrolerze:
```php
// Po sukcesie w GenerateCardsController
$request->getSession()->set('pending_set', [
    'job_id' => $response->jobId,
    'suggested_name' => $response->suggestedName,
    'cards' => array_map(fn($c) => [
        'front' => $c->front,
        'back' => $c->back
    ], $response->cards),
    'source_text' => $sourceText->toString(),
    'generated_count' => $response->generatedCount,
]);

// Turbo Stream redirect
return $this->json($response, Response::HTTP_OK, [
    'Turbo-Location' => '/sets/new/edit'
]);
```

**Weryfikacja:**
- Submit formularza wywołuje POST /api/generate
- Success → redirect do /sets/new/edit
- Error → modal z błędem

---

### Krok 9: Testy E2E scenariuszy

**Zadania:**
1. **Happy path:**
   - Wkleić tekst 5000 znaków
   - Sprawdzić licznik (zielony, "zakres poprawny")
   - Sprawdzić pasek (zielony, ~50%)
   - Sprawdzić przycisk (enabled)
   - Kliknąć "Generuj fiszki"
   - Sprawdzić loading overlay (etapy)
   - Sprawdzić redirect do /sets/new/edit

2. **Walidacja:**
   - Wpisać 500 znaków → przycisk disabled, licznik czerwony
   - Wpisać 15000 znaków → przycisk disabled, licznik czerwony
   - Wpisać 3000 znaków → przycisk enabled, licznik zielony

3. **Error handling:**
   - Symulować timeout (mock API delay >30s) → modal timeout
   - Symulować AI error (mock 500) → modal AI failure
   - Symulować network error → modal unknown

**Weryfikacja:**
Wszystkie scenariusze działają zgodnie z oczekiwaniami

---

### Krok 10: Accessibility testing

**Zadania:**
1. Keyboard navigation:
   - Tab do textarea → focus
   - Tab do button → focus
   - Enter w textarea → nie submituje (tylko w button)
   - Enter na button → submit

2. Screen reader:
   - Licznik jako ARIA live region announces zmiany
   - Loading overlay announces etapy
   - ErrorModal announces błędy

3. Walidacja:
   - axe DevTools: brak błędów accessibility
   - Lighthouse: score >90 accessibility

**Weryfikacja:**
Wszystkie testy accessibility passed

---

### Krok 11: Responsive testing

**Zadania:**
1. Mobile (320px-767px):
   - Textarea pełna szerokość
   - Licznik i pasek pełna szerokość
   - Przycisk pełna szerokość
   - Overlay responsive

2. Tablet (768px-1023px):
   - Layout adjusted

3. Desktop (1024px+):
   - Max-width container (np. 768px)
   - Centered layout

**Weryfikacja:**
Widok wygląda dobrze na wszystkich rozmiarach ekranu

---

### Krok 12: Performance optimization

**Zadania:**
1. Debouncing character count (already done: 300ms)
2. Lazy loading Stimulus controller (if needed)
3. Minimize reflows/repaints w `updateUI()`

**Weryfikacja:**
- Lighthouse Performance score >90
- Brak lagów przy wpisywaniu

---

### Krok 13: Documentation i cleanup

**Zadania:**
1. Dodać komentarze w Stimulus controller
2. Dodać komentarze w Twig template
3. Update README z informacją o widoku

**Weryfikacja:**
Kod jest czytelny i dobrze udokumentowany

---

## Podsumowanie

Plan implementacji dla widoku **Generowanie Fiszek (AI)** obejmuje 13 kroków od utworzenia struktury plików po finalne testy i dokumentację. Kluczowe elementy to:

- **Stimulus controller** zarządzający całym stanem widoku (debounced counting, validation, loading states, error handling)
- **Real-time feedback** dla użytkownika (licznik, pasek postępu, kolory)
- **Multi-stage loading** dla psychologicznego komfortu podczas długiego oczekiwania
- **Comprehensive error handling** z konkretymi suggestions dla każdego typu błędu
- **Accessibility** (ARIA live regions, keyboard navigation)
- **Turbo integration** dla SPA-like experience bez przeładowywania strony
- **Session storage** do przekazania danych do następnego widoku

Implementacja powinna zająć około 2-3 dni dla doświadczonego dewelopera Symfony + Stimulus.
