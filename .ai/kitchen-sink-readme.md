# Material 3 Kitchen Sink - Przewodnik

## Dostęp do Kitchen Sink

Strona Kitchen Sink jest dostępna pod adresem:

```
http://localhost:8000/kitchen-sink
```

Lub w środowisku Docker:
```
http://localhost:8000/kitchen-sink
```

## Co zawiera Kitchen Sink?

Kitchen Sink to kompleksowa strona prezentacyjna wszystkich komponentów Material 3 zaimplementowanych w aplikacji. Zawiera:

### 1. **Typografia**
Wszystkie style tekstowe z Material 3:
- Display (Large, Medium, Small)
- Headline (Large, Medium, Small)
- Title (Large, Medium, Small)
- Body (Large, Medium, Small)
- Label (Large, Medium, Small)

### 2. **Przyciski (Button)**
- 5 wariantów: Filled, Filled Tonal, Outlined, Text, Elevated
- 3 rozmiary: Small, Medium, Large
- Różne stany: Normal, Disabled, Link
- Hierarchia akcji (Primary, Secondary, Tertiary)

### 3. **Pola tekstowe (TextField)**
- 2 warianty: Filled, Outlined
- Różne typy: text, email, password, number, tel
- Stany: Normal, Required, Error, Disabled
- Z helper text i error text

### 4. **Karty (Card)**
- 3 warianty: Elevated, Outlined, Filled
- Interaktywne karty (clickable)
- Karty jako linki

### 5. **Elementy listy (ListItem)**
- 1, 2, 3 linie tekstu
- Z leading/trailing elementami
- Selected state
- Navigation type

### 6. **Okna dialogowe (Modal)**
- Podstawowy dialog
- Dialog z formularzem
- Dialog potwierdzenia
- Full accessibility (focus trap, ESC, backdrop)

### 7. **Powiadomienia (Snackbar)**
- 4 statusy: Info, Success, Warning, Error
- Z akcją "Cofnij"
- Auto-hide
- Różne pozycje

### 8. **Nawigacja**
- Responsywna (BottomNav → NavRail → NavDrawer)
- Demonstracja przełączania

### 9. **Layout (AppScaffold)**
- Kompletny przykład w `/scaffold-demo`
- Top bar, Navigation, FAB, Content area

### 10. **Design Tokens**
- Paleta kolorów
- Elevation (5 poziomów cieni)
- Shape (zaokrąglenia)

## Demo AppScaffold

Pełny przykład layoutu aplikacji z responsywną nawigacją:

```
http://localhost:8000/scaffold-demo
```

Ta strona pokazuje:
- Top bar z tytułem i akcjami
- Responsywną nawigację (zmień rozmiar okna!)
- Grid kart z zestawami fiszek
- Formularz tworzenia zestawu
- Floating Action Button (FAB)
- Statystyki w kartach

## Jak używać komponentów?

### Przykład 1: Prosty przycisk

```twig
<twig:Button
    label="Zapisz"
    variant="filled"
    type="submit"
/>
```

### Przykład 2: Pole tekstowe

```twig
<twig:TextField
    name="email"
    label="Adres email"
    type="email"
    variant="filled"
    :required="true"
    helperText="Podaj swój email"
/>
```

### Przykład 3: Karta

```twig
<twig:Card variant="elevated" href="/sets/123">
    <h3 class="text-title-medium mb-2">Matematyka</h3>
    <p class="text-body-medium">25 fiszek</p>
</twig:Card>
```

### Przykład 4: Modal

```twig
<twig:Modal
    id="my-modal"
    headline="Usuń zestaw?"
    supportingText="Ta akcja jest nieodwracalna"
>
    {% block actions %}
        <twig:Button label="Anuluj" variant="text" data-action="click->modal#close" />
        <twig:Button label="Usuń" variant="filled" data-action="click->modal#confirm" />
    {% endblock %}
</twig:Modal>

{# Otwórz modal: #}
<twig:Button
    label="Usuń"
    variant="filled"
    data-action="click->demo#showModal"
/>
```

### Przykład 5: Kompletny layout

```twig
<twig:AppScaffold
    title="Moja aplikacja"
    :showTopBar="true"
    :showBottomNav="true"
    :navDestinations="[
        {'icon': '#icon-home', 'label': 'Start', 'path': '/'},
        {'icon': '#icon-cards', 'label': 'Fiszki', 'path': '/sets'}
    ]"
    currentPath="{{ app.request.pathInfo }}"
>
    {% block content %}
        <h2 class="text-headline-large">Treść strony</h2>
    {% endblock %}
</twig:AppScaffold>
```

## Responsywność

Wszystkie komponenty są w pełni responsywne:

### Nawigacja
- **Mobile (< 768px)**: BottomNav (dolna nawigacja)
- **Tablet (768-1024px)**: NavRail (boczna nawigacja)
- **Desktop (> 1024px)**: NavDrawer (szuflada nawigacyjna)

### Layout
- Automatyczne marginesy dla nawigacji
- Responsywne padding w content area
- Grid układy dostosowują się do rozmiaru ekranu

## Dostępność (Accessibility)

Wszystkie komponenty implementują:

✅ **ARIA attributes** - proper labeling
✅ **Keyboard navigation** - Tab, Enter, ESC
✅ **Focus management** - focus trap w modalach
✅ **Screen reader friendly** - semantic HTML
✅ **Proper contrast** - WCAG AA compliant colors
✅ **Touch targets** - minimum 48px dla mobile

## Testowanie

### Testy wizualne
1. Otwórz `/kitchen-sink`
2. Sprawdź wszystkie sekcje
3. Kliknij przyciski demo dla modali i snackbarów
4. Zmień rozmiar okna aby zobaczyć responsywność

### Testy nawigacji
1. Otwórz `/scaffold-demo`
2. Zmień rozmiar okna:
   - < 768px → zobaczysz BottomNav
   - 768-1024px → zobaczysz NavRail
   - > 1024px → zobaczysz NavDrawer
3. Kliknij elementy nawigacji
4. Sprawdź FAB (prawy dolny róg)

### Testy klawiatury
1. Użyj **Tab** do nawigacji między elementami
2. Użyj **Enter** lub **Space** do aktywacji przycisków
3. W modalach użyj **ESC** aby zamknąć
4. Sprawdź focus trap w modalach (Tab nie wychodzi poza modal)

## Customizacja

### Zmiana kolorów

Edytuj `assets/styles/app.css`:

```css
@theme {
    --color-primary: #your-color;
    --color-on-primary: #ffffff;
    /* ... */
}
```

### Zmiana typografii

```css
@theme {
    --font-family-base: 'Your Font', sans-serif;
    --font-size-headline-large: 32px;
    /* ... */
}
```

## Dokumentacja

Pełna dokumentacja znajduje się w:

- **Użycie komponentów**: `.ai/material-3-components-usage.md`
- **Implementacja**: `.ai/material-3-implementation-summary.md`
- **Wytyczne Material 3**: `.ai/material-3-components.md`

## Rozwiązywanie problemów

### Komponenty nie renderują się
1. Sprawdź czy zainstalowano `symfony/ux-twig-component`:
   ```bash
   composer require symfony/ux-twig-component
   ```
2. Wyczyść cache:
   ```bash
   php bin/console cache:clear
   ```

### Style nie działają
1. Sprawdź czy CSS jest linkowany w `base.html.twig`
2. Sprawdź czy plik `assets/styles/app.css` istnieje
3. Przeładuj stronę z czyszczeniem cache (Ctrl+Shift+R)

### Modals/Snackbars nie działają
1. Sprawdź czy kontrolery Stimulus są załadowane
2. Sprawdź console przeglądarki na błędy JavaScript
3. Upewnij się że `data-controller` attributes są poprawne

## Następne kroki

1. ✅ Zobacz Kitchen Sink: `/kitchen-sink`
2. ✅ Sprawdź demo Scaffold: `/scaffold-demo`
3. 📖 Przeczytaj dokumentację: `.ai/material-3-components-usage.md`
4. 🎨 Dostosuj kolory w `assets/styles/app.css`
5. 🚀 Użyj komponentów w swoich widokach!

## Wsparcie

Jeśli masz pytania lub problemy:
1. Sprawdź dokumentację w `.ai/material-3-components-usage.md`
2. Zobacz przykłady w Kitchen Sink
3. Zbadaj kod źródłowy komponentów w `src/Twig/Components/`
4. Sprawdź szablony w `templates/components/`

Happy coding! 🎉
