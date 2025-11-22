# Google Material 3 – charakterystyka design systemu (pod kątem UI komponentów)

Jesteś specjalistą frontend developmentu tworzącym nowoczesne i przystępne interfejsy użytkownika. Poniżej zebrane są
rekomendacje dotyczące cech design systemu **Google Material 3** dla kluczowych elementów UI.

---

## 1. Button

**Charakter:** wyraźna hierarchia, pełne zaokrąglenia, więcej „powietrza”.

Główne typy przycisków (od największej do najmniejszej wagi wizualnej):

- **Filled**
- **Filled tonal**
- **Outlined**
- **Text**
- **Elevated**

Cechy:

- Domyślna wysokość ok. **40dp** oraz **pełne zaokrąglenia (stadium)**.
- Częste użycie **ikon 20dp** jako leading/trailing icon.
- Stany aktywne/wybrane często pokazywane jako **„pill”** z kontrastowym tłem.
- Wzorce typu **button groups** i **split buttons**, gdzie przyciski działają jako spójna grupa.

Rekomendacje dla implementacji:

- Zdefiniuj tokeny dla: `height`, `min-width`, `border-radius`, `icon-size`, `gap`, `padding`.
- Ustal jasną hierarchię: np. `primary = filled`, `secondary = filled tonal`, `tertiary = text/outlined`.

---

## 2. Input (Text fields)

**Charakter:** floating label, warianty wypełnione/obrysowane, mocny nacisk na dostępność.

Typy:

- **Filled text field**
- **Outlined text field**

Cechy:

- **Floating label** – label unosi się ponad polem przy focus/value.
- Wyraźnie zdefiniowane elementy: kontener, ikony, label, aktywny wskaźnik (underline/outline), supporting text, error
  text.
- **Supporting text** (hint, error) jest integralną częścią komponentu, nie „doklejonym” tekstem.
- W jednym obszarze UI unikaj miksowania filled i outlined bez sensu – stosuj spójnie.

Rekomendacje:

- Zrób jeden komponent `TextField` z wariantem `filled/outlined` i wspólnym API.
- Obsłuż stany: `enabled`, `hover`, `focus`, `error`, `disabled`.
- Zadbaj o poprawne powiązania dostępności: `label` ↔ `for`, `aria-describedby` dla helper/error.

---

## 3. Typography

**Charakter:** wyraźna skala, hierarchia, nacisk na czytelność i dostępność.

Główne grupy stylów:

- **Display**
- **Headline**
- **Title**
- **Body**
- **Label**

Każda grupa ma swoje warianty (np. `headlineSmall`, `headlineMedium`, `headlineLarge`).

Cechy:

- Styl treści: **sentence case** – także dla nagłówków, menu, labeli przycisków.
- Typografia powiązana z **tokenami** (np. `bodyMedium`, `labelLarge`) do bezpośredniej mapy w kodzie.
- Hierarchia tekstu zintegrowana z layoutem (nagłówki sekcji, tytuły ekranów, podpisy komponentów).

Rekomendacje:

- Zdefiniuj stałą mapę stylów, np.:
    - `displayLarge` – hero / główne nagłówki.
    - `headlineMedium` – tytuły ekranów.
    - `titleSmall` – nagłówki sekcji.
    - `bodyMedium` – główny tekst.
    - `labelLarge` – przyciski.
- Utrzymaj jeden bazowy font (np. z Google Fonts) i użyj go w całym systemie.

---

## 4. Card

**Charakter:** karta jako kombinacja surface + elevation + shape + color.

Cechy:

- Karta to **powierzchnia (surface)** z:
    - kolorem kontenera,
    - wysokością (elevation/cień lub tonal elevation),
    - promieniem zaokrąglenia (shape).
- Ważniejsze jest, *jak* karta używa tych parametrów, niż nazwa typu karty.
- Często karty są zintegrowane z innymi komponentami (listy, layouty responsywne), a nie izolowane.

Rekomendacje:

- Zdefiniuj 2–3 warianty:
    - `elevated` – z cieniem,
    - `outlined` – z obramowaniem,
    - `filled` – bez cienia, inny kolor tła.
- Tokeny: `padding`, `gap`, `border-radius`, `shadow`, `border-width`.

---

## 5. Modal / Dialog

**Charakter:** wąski, skoncentrowany kontekst + pełna dostępność.

Typowa struktura:

- **Headline** (tytuł)
- **Supporting text** (treść)
- **Actions** (przyciski)
- Opcjonalnie: pola wejściowe

Typy dialogów:

- Alert / confirmation dialog.
- Dialog z formularzem (czasem pełnoekranowy na mobile).
- Full-screen dialog (np. edycja większego bytu).

Rekomendacje:

- Zapewnij:
    - focus na pierwszym interaktywnym elemencie,
    - focus trap wewnątrz dialogu,
    - zamykanie klawiszem Escape i kliknięciem w tło (jeśli dozwolone).
- Zdefiniuj warianty:
    - `basic`,
    - `scrollable`,
    - `fullScreen`.
- Ustal zasady: max-width, spacing, ułożenie akcji (prawa strona, kolejność primary/secondary).

---

## 6. Form

**Charakter:** modularne pola, spójna siatka, jasna kolejność.

Material 3 nie wprowadza osobnego komponentu „Form”, ale opisuje **wzorce układania pól**:

- Formularze używają gotowych komponentów: text fields, selects, checkbox, radio, switch itp.
- Układ:
    - Mobile – jedna kolumna.
    - Większe ekrany – możliwość dwóch lub więcej kolumn, zachowując czytelność.
- Spójne użycie labeli, helper textu, errorów – brak „losowych” stylów błędów.

Rekomendacje:

- Stwórz **Form Field wrapper**:
    - Łączy label, input, helper, error w jedną logikę.
- Ujednolić system walidacji:
    - Ten sam mechanizm przekazywania błędów do pól.
- Zadbaj o kolejność tab i semantykę dostępności.

---

## 7. Navigation

**Charakter:** adaptacyjna, zależna od rozmiaru ekranu, z konsekwentnym zachowaniem.

Wzorce:

- **Mobile:**
    - `Bottom navigation bar` jako główna nawigacja.
    - `Top app bar` z tytułem, akcjami, ewentualnie wyszukiwaniem.
- **Tablet / medium:**
    - `Navigation rail` zamiast bottom nav.
- **Desktop:**
    - `Navigation drawer` (permanent lub modal) + top app bar.

Cechy:

- Aktywna pozycja nawigacji oznaczona **„pill”** – kapsułkowaty kształt, kontrastowe tło.
- Ikony + label (sentence case) zdefiniowane typograficznie jako styl Label.

Rekomendacje:

- Przygotuj wspólny model `NavigationDestination` (ikona, label, path, badge).
- Zbuduj trzy warianty:
    - `BottomNav`,
    - `NavRail`,
    - `Drawer`,
      które korzystają z tych samych danych.
- Powiąż wybór wariantu z breakpointami/layoutem (mobile/desktop).

---

## 8. List

**Charakter:** list items jako „mini-karty” z bogatą treścią.

Typy:

- One-line, two-line, three-line list items.

Możliwe elementy:

- Leading icon/avatar.
- Headline + supporting text.
- Trailing icon, checkbox, switch, action button.

Rekomendacje:

- Stwórz komponent `ListItem` ze slotami:
    - `leading`,
    - `headline`,
    - `supporting`,
    - `trailing`.
- Zdefiniuj warianty:
    - `navigation` (item przenosi do innego ekranu),
    - `selectable` (z checkbox/radio),
    - `action` (z przyciskiem/switchem).

---

## 9. Feedback (stany, snackbary, błędy)

**Charakter:** jasna hierarchia komunikatów, minimalnie inwazyjny feedback globalny.

Poziomy feedbacku:

1. **Inline feedback** – błędy i helper text w polach (najczęstszy).
2. **Snackbary** – krótkie komunikaty na dole ekranu z opcjonalną akcją.
3. **Dialogi** – dla krytycznych decyzji.

Cechy:

- Komunikaty krótkie, konkretne, w **sentence case**.
- Wyraźne stany komponentów: hover, focus, error, disabled.

Rekomendacje:

- Komponent `Snackbar`:
    - kolejka komunikatów,
    - czas automatycznego ukrywania,
    - opcjonalny action button.
- Zdefiniuj kolorystykę statusów:
    - `success`, `warning`, `error`, `info`.
- Ustal standardowe komunikaty walidacji, np.:
    - „To pole jest wymagane.”
    - „Podaj poprawny adres e-mail.”

---

## 10. Layout

**Charakter:** responsywne „panes”, adaptacyjne zachowanie app barów i nawigacji.

Koncepcja:

- Layout składa się z 1–3 **„panes”** (np. lista + szczegóły), w zależności od rozmiaru ekranu.
- App bary (top/bottom) są częścią layoutu, a nie „nakładką” ponad wszystko.
- Layout reaguje na wielkość ekranu:
    - Wąski – jeden pane + bottom nav.
    - Średni – nav rail + content.
    - Szeroki – drawer + content + panel szczegółów.

Rekomendacje:

- Wprowadź kontener layoutu typu `Shell` lub `Scaffold`, który:
    - wie o `TopBar`, `BottomBar`, `Navigation`, `FAB`, `Content`.
- Ustal klasy rozmiarów (np. `compact`, `medium`, `expanded`) i na ich podstawie:
    - przełączaj typ nawigacji,
    - zmieniaj liczbę paneli (list + szczegóły).

---

## Podsumowanie

Implementując Material 3 w swoim design systemie, skup się na:

1. **Tokenach** (kolory, typografia, radiusy, cienie, spacing).
2. **Wspólnych wzorcach**:
    - `Scaffold/Shell` dla layoutu.
    - `FormField` dla pól.
    - `NavigationDestination` dla nawigacji.
3. **Dostępności**:
    - focus states,
    - aria-atributy,
    - czytelna hierarchia i kontrast.
4. **Spójności**:
    - utrzymuj sentence case,
    - nie mieszaj bez powodu różnych wariantów (np. filled/outlined inputs) w jednym kontekście,
    - trzymaj się jasno zdefiniowanej hierarchii przycisków i typografii.

Ten plik może służyć jako baza do projektowania Twoich własnych komponentów w stacku (np. React + Tailwind, Vue,
Symfony + Twig), które będą zgodne z założeniami Material 3.
