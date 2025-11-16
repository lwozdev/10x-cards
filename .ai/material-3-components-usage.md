# Material 3 Components - Usage Guide

This guide explains how to use the Material 3 Symfony UX Twig Components created for this application.

## Overview

The following components have been implemented following Google Material Design 3 guidelines:

1. **Button** - Various button styles (Filled, Filled Tonal, Outlined, Text, Elevated)
2. **TextField** - Input fields with floating labels (Filled and Outlined variants)
3. **Card** - Container component (Elevated, Outlined, Filled variants)
4. **Modal** - Dialog component with accessibility features
5. **ListItem** - List item with slots for icons, text, and actions
6. **BottomNav** - Bottom navigation bar for mobile
7. **NavRail** - Navigation rail for tablets
8. **NavDrawer** - Navigation drawer for desktop
9. **Snackbar** - Feedback messages/toasts
10. **AppScaffold** - Complete app layout structure

All components use Tailwind 4 CSS variables defined in `assets/styles/app.css`.

## Component Usage

### 1. Button

```twig
{# Filled button (primary action) #}
<twig:Button
    label="Zapisz"
    variant="filled"
    type="submit"
/>

{# Filled tonal button (secondary action) #}
<twig:Button
    label="Edytuj"
    variant="filled-tonal"
/>

{# Outlined button #}
<twig:Button
    label="Anuluj"
    variant="outlined"
/>

{# Text button (low emphasis) #}
<twig:Button
    label="Pomiń"
    variant="text"
/>

{# Elevated button #}
<twig:Button
    label="Utwórz nowy"
    variant="elevated"
/>

{# With icon #}
<twig:Button
    label="Dodaj"
    variant="filled"
    icon="#icon-plus"
/>

{# As link #}
<twig:Button
    label="Zobacz więcej"
    variant="text"
    href="/sets"
/>

{# Disabled #}
<twig:Button
    label="Niedostępny"
    variant="filled"
    :disabled="true"
/>

{# Different sizes #}
<twig:Button label="Mały" size="sm" />
<twig:Button label="Średni" size="md" />
<twig:Button label="Duży" size="lg" />
```

**Props:**
- `label` (string) - Button text
- `variant` (string) - filled, filled-tonal, outlined, text, elevated (default: filled)
- `type` (string) - button, submit, reset (default: button)
- `size` (string) - sm, md, lg (default: md)
- `icon` (string) - SVG icon path for leading icon
- `trailingIcon` (string) - SVG icon path for trailing icon
- `disabled` (bool) - Disable button
- `href` (string) - Render as link instead of button
- `class` (string) - Additional CSS classes
- `id` (string) - Element ID
- `attributes` (array) - Additional HTML attributes

---

### 2. TextField

```twig
{# Filled variant (default) #}
<twig:TextField
    name="email"
    label="Email"
    type="email"
    variant="filled"
    :required="true"
/>

{# Outlined variant #}
<twig:TextField
    name="username"
    label="Nazwa użytkownika"
    variant="outlined"
/>

{# With helper text #}
<twig:TextField
    name="password"
    label="Hasło"
    type="password"
    helperText="Minimum 8 znaków"
/>

{# With error #}
<twig:TextField
    name="email"
    label="Email"
    type="email"
    value="{{ form_data.email }}"
    errorText="Podaj poprawny adres email"
/>

{# With icons #}
<twig:TextField
    name="search"
    label="Szukaj"
    leadingIcon="#icon-search"
    trailingIcon="#icon-close"
/>

{# Disabled #}
<twig:TextField
    name="username"
    label="Nazwa użytkownika"
    value="johndoe"
    :disabled="true"
/>
```

**Props:**
- `name` (string) - Input name
- `label` (string) - Floating label text
- `value` (string) - Input value
- `type` (string) - text, email, password, number, tel, url (default: text)
- `variant` (string) - filled, outlined (default: filled)
- `placeholder` (string) - Placeholder text
- `required` (bool) - Required field
- `disabled` (bool) - Disabled state
- `readonly` (bool) - Readonly state
- `helperText` (string) - Helper text below input
- `errorText` (string) - Error message (overrides helperText)
- `leadingIcon` (string) - Icon before input
- `trailingIcon` (string) - Icon after input
- `maxlength` (int) - Maximum length
- `minlength` (int) - Minimum length
- `pattern` (string) - Validation pattern
- `autocomplete` (string) - Autocomplete attribute

---

### 3. Card

```twig
{# Elevated card (with shadow) #}
<twig:Card variant="elevated">
    <h3 class="text-title-medium mb-2">Tytuł karty</h3>
    <p class="text-body-medium">Treść karty...</p>
</twig:Card>

{# Outlined card #}
<twig:Card variant="outlined">
    <h3 class="text-title-medium mb-2">Tytuł karty</h3>
    <p class="text-body-medium">Treść karty...</p>
</twig:Card>

{# Filled card #}
<twig:Card variant="filled">
    <h3 class="text-title-medium mb-2">Tytuł karty</h3>
    <p class="text-body-medium">Treść karty...</p>
</twig:Card>

{# Clickable card #}
<twig:Card :clickable="true" data-action="click->card#handleClick">
    <h3 class="text-title-medium mb-2">Kliknij mnie</h3>
</twig:Card>

{# Card as link #}
<twig:Card href="/sets/123">
    <h3 class="text-title-medium mb-2">Zestaw fiszek</h3>
    <p class="text-body-medium">15 fiszek</p>
</twig:Card>
```

**Props:**
- `variant` (string) - elevated, outlined, filled (default: elevated)
- `clickable` (bool) - Make card interactive
- `href` (string) - Render as link
- `class` (string) - Additional CSS classes
- `id` (string) - Element ID
- `attributes` (array) - Additional HTML attributes

---

### 4. Modal

```twig
{# Basic modal #}
<twig:Modal
    id="confirm-delete-modal"
    headline="Usuń zestaw?"
    supportingText="Ta akcja jest nieodwracalna. Czy na pewno chcesz usunąć ten zestaw?"
>
    {% block actions %}
        <twig:Button
            label="Anuluj"
            variant="text"
            data-action="click->modal#close"
        />
        <twig:Button
            label="Usuń"
            variant="filled"
            data-action="click->modal#confirm"
        />
    {% endblock %}
</twig:Modal>

{# Modal with custom content #}
<twig:Modal
    id="settings-modal"
    headline="Ustawienia"
    variant="scrollable"
>
    {% block content %}
        <twig:TextField name="theme" label="Motyw" />
        <twig:TextField name="language" label="Język" />
    {% endblock %}

    {% block actions %}
        <twig:Button label="Anuluj" variant="text" data-action="click->modal#close" />
        <twig:Button label="Zapisz" variant="filled" data-action="click->modal#confirm" />
    {% endblock %}
</twig:Modal>

{# Fullscreen modal (mobile) #}
<twig:Modal
    id="edit-modal"
    headline="Edytuj fiszki"
    variant="fullscreen"
    :dismissible="false"
>
    {# Content here #}
</twig:Modal>

{# JavaScript usage #}
<script>
    // Open modal
    document.getElementById('confirm-delete-modal')
        .querySelector('[data-controller="modal"]')
        .dispatchEvent(new CustomEvent('modal#open'));

    // Listen for confirm event
    document.addEventListener('modal:confirmed', (e) => {
        console.log('Modal confirmed!');
    });
</script>
```

**Props:**
- `id` (string) - Modal ID
- `headline` (string) - Modal title
- `supportingText` (string) - Description text
- `variant` (string) - basic, scrollable, fullscreen (default: basic)
- `dismissible` (bool) - Can close with ESC/backdrop (default: true)

**JavaScript API:**
- `modal#open` - Open modal
- `modal#close` - Close modal
- Events: `modal:opened`, `modal:closed`, `modal:confirmed`

---

### 5. ListItem

```twig
{# Basic list item #}
<twig:ListItem
    headline="Zestaw matematyczny"
    supporting="20 fiszek"
    lines="2"
/>

{# With leading icon #}
<twig:ListItem
    headline="Moje fiszki"
    supporting="Utworzono dzisiaj"
    leadingIcon="#icon-folder"
/>

{# With avatar #}
<twig:ListItem
    headline="Jan Kowalski"
    supporting="jan@example.com"
    leadingAvatar="/uploads/avatars/jan.jpg"
/>

{# With trailing icon #}
<twig:ListItem
    headline="Ustawienia"
    trailingIcon="#icon-chevron-right"
    type="navigation"
    href="/settings"
/>

{# Selected state #}
<twig:ListItem
    headline="Aktywny element"
    :selected="true"
/>

{# Custom slots #}
<twig:ListItem lines="3">
    {% block headline %}
        <div class="font-bold">Własny nagłówek</div>
    {% endblock %}

    {% block supporting %}
        <div class="text-sm">Własny opis</div>
    {% endblock %}

    {% block trailing %}
        <twig:Button label="Akcja" size="sm" variant="text" />
    {% endblock %}
</twig:ListItem>
```

**Props:**
- `headline` (string) - Main text
- `supporting` (string) - Secondary text
- `trailing` (string) - Trailing text
- `lines` (string) - 1, 2, 3 (default: 2)
- `type` (string) - default, navigation, selectable, action
- `leadingIcon` (string) - Leading icon
- `leadingAvatar` (string) - Avatar image URL
- `trailingIcon` (string) - Trailing icon
- `selected` (bool) - Selected state
- `href` (string) - Link URL

---

### 6. Navigation Components

#### BottomNav (Mobile)

```twig
<twig:BottomNav
    :destinations="[
        {'icon': '#icon-home', 'label': 'Strona główna', 'path': '/'},
        {'icon': '#icon-cards', 'label': 'Fiszki', 'path': '/sets', 'badge': 3},
        {'icon': '#icon-learn', 'label': 'Nauka', 'path': '/learn'},
        {'icon': '#icon-profile', 'label': 'Profil', 'path': '/profile'}
    ]"
    currentPath="{{ app.request.pathInfo }}"
/>
```

#### NavRail (Tablet)

```twig
<twig:NavRail
    :destinations="[
        {'icon': '#icon-home', 'label': 'Strona główna', 'path': '/'},
        {'icon': '#icon-cards', 'label': 'Fiszki', 'path': '/sets'},
        {'icon': '#icon-learn', 'label': 'Nauka', 'path': '/learn'}
    ]"
    currentPath="{{ app.request.pathInfo }}"
    :showLabels="true"
/>
```

#### NavDrawer (Desktop)

```twig
<twig:NavDrawer
    :destinations="[
        {'icon': '#icon-home', 'label': 'Strona główna', 'path': '/'},
        {'icon': '#icon-cards', 'label': 'Fiszki', 'path': '/sets'},
        {'icon': '#icon-learn', 'label': 'Nauka', 'path': '/learn'},
        {'icon': '#icon-settings', 'label': 'Ustawienia', 'path': '/settings'}
    ]"
    currentPath="{{ app.request.pathInfo }}"
    variant="permanent"
/>
```

**Props:**
- `destinations` (array) - Navigation items
- `currentPath` (string) - Current route for active state
- `showLabels` (bool) - Show labels (NavRail only)
- `variant` (string) - permanent, modal (NavDrawer only)

---

### 7. Snackbar

```twig
{# Info snackbar #}
<twig:Snackbar
    id="info-snackbar"
    message="Zmiany zostały zapisane"
    status="info"
    :duration="4000"
/>

{# Success snackbar #}
<twig:Snackbar
    id="success-snackbar"
    message="Zestaw utworzony pomyślnie!"
    status="success"
    :duration="3000"
/>

{# Error snackbar #}
<twig:Snackbar
    id="error-snackbar"
    message="Wystąpił błąd podczas zapisywania"
    status="error"
    :duration="5000"
/>

{# With action button #}
<twig:Snackbar
    id="undo-snackbar"
    message="Fiszka usunięta"
    actionLabel="Cofnij"
    status="info"
    :duration="5000"
/>

{# JavaScript usage #}
<script>
    // Show snackbar
    const snackbar = document.getElementById('info-snackbar')
        .querySelector('[data-controller="snackbar"]');
    snackbar.dispatchEvent(new CustomEvent('snackbar#show'));

    // Listen for action
    document.addEventListener('snackbar:action', (e) => {
        console.log('Snackbar action clicked!');
    });
</script>
```

**Props:**
- `message` (string) - Message text
- `actionLabel` (string) - Action button label
- `status` (string) - info, success, warning, error (default: info)
- `duration` (int) - Auto-hide duration in ms (0 = no auto-hide)
- `position` (string) - bottom-center, bottom-left, bottom-right, top-center

**JavaScript API:**
- `snackbar#show` - Show snackbar
- `snackbar#close` - Close snackbar
- Events: `snackbar:shown`, `snackbar:closed`, `snackbar:action`

---

### 8. AppScaffold (Complete Layout)

```twig
<twig:AppScaffold
    title="Moje fiszki"
    :showTopBar="true"
    :showBottomNav="true"
    :showNavRail="true"
    :showNavDrawer="true"
    :navDestinations="[
        {'icon': '#icon-home', 'label': 'Strona główna', 'path': '/'},
        {'icon': '#icon-cards', 'label': 'Fiszki', 'path': '/sets'},
        {'icon': '#icon-learn', 'label': 'Nauka', 'path': '/learn'}
    ]"
    currentPath="{{ app.request.pathInfo }}"
    fabIcon="#icon-plus"
    fabHref="/sets/new"
>
    {% block topBarActions %}
        <twig:Button
            icon="#icon-search"
            variant="text"
            label=""
        />
    {% endblock %}

    {% block content %}
        <h2 class="text-headline-medium mb-4">Witaj w aplikacji!</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <twig:Card variant="elevated">
                <h3 class="text-title-medium mb-2">Zestaw 1</h3>
                <p class="text-body-medium">15 fiszek</p>
            </twig:Card>
        </div>
    {% endblock %}
</twig:AppScaffold>
```

**Props:**
- `title` (string) - App bar title
- `showTopBar` (bool) - Show top app bar
- `showBottomNav` (bool) - Show bottom navigation (mobile)
- `showNavRail` (bool) - Show navigation rail (tablet)
- `showNavDrawer` (bool) - Show navigation drawer (desktop)
- `fabIcon` (string) - Floating action button icon
- `fabHref` (string) - FAB link URL
- `navDestinations` (array) - Navigation items
- `currentPath` (string) - Current route

---

## Typography Classes

Use these classes for consistent typography:

```twig
{# Display #}
<h1 class="text-display-large">Display Large</h1>
<h1 class="text-display-medium">Display Medium</h1>
<h1 class="text-display-small">Display Small</h1>

{# Headline #}
<h2 class="text-headline-large">Headline Large</h2>
<h2 class="text-headline-medium">Headline Medium</h2>
<h2 class="text-headline-small">Headline Small</h2>

{# Title #}
<h3 class="text-title-large">Title Large</h3>
<h4 class="text-title-medium">Title Medium</h4>
<h5 class="text-title-small">Title Small</h5>

{# Body #}
<p class="text-body-large">Body Large</p>
<p class="text-body-medium">Body Medium</p>
<p class="text-body-small">Body Small</p>

{# Label #}
<span class="text-label-large">Label Large</span>
<span class="text-label-medium">Label Medium</span>
<span class="text-label-small">Label Small</span>
```

## Design Tokens

All components use CSS variables defined in `assets/styles/app.css`:

### Colors
- `--color-primary`, `--color-on-primary`
- `--color-secondary`, `--color-on-secondary`
- `--color-tertiary`, `--color-on-tertiary`
- `--color-error`, `--color-on-error`
- `--color-surface`, `--color-on-surface`
- `--color-background`, `--color-on-background`

### Elevation/Shadows
- `--shadow-elevation-1` to `--shadow-elevation-5`

### Shape/Radius
- `--radius-xs`, `--radius-sm`, `--radius-md`, `--radius-lg`, `--radius-xl`, `--radius-full`

### Spacing
- `--spacing-xs`, `--spacing-sm`, `--spacing-md`, `--spacing-lg`, `--spacing-xl`, `--spacing-2xl`, `--spacing-3xl`

## Best Practices

1. **Use sentence case** for all text (labels, buttons, titles) per Material 3 guidelines
2. **Don't mix variants** - choose filled OR outlined inputs consistently across a form
3. **Hierarchy matters** - use filled buttons for primary actions, outlined/text for secondary
4. **Accessibility first** - all components include proper ARIA attributes and focus management
5. **Responsive by design** - use AppScaffold with navigation components for automatic responsive behavior
6. **Test keyboard navigation** - all interactive components support keyboard interaction

## Next Steps

1. Add SVG icon sprite file for component icons
2. Configure Stimulus controllers if not auto-discovered
3. Customize color tokens in `assets/styles/app.css` to match your brand
4. Add Google Fonts Roboto to your base template
