# Struktura plików komponentów Material 3

## 📁 Komponenty Twig

### Lokalizacja komponentów PHP
```
src/Twig/Components/
├── AppScaffold.php          # Layout aplikacji
├── BottomNav.php            # Nawigacja mobilna
├── Button.php               # Przyciski
├── Card.php                 # Karty
├── ListItem.php             # Elementy listy
├── Modal.php                # Okna dialogowe
├── NavDrawer.php            # Nawigacja desktop
├── NavRail.php              # Nawigacja tablet
├── Snackbar.php             # Powiadomienia
└── TextField.php            # Pola tekstowe
```

### Lokalizacja szablonów Twig
```
templates/components/
├── AppScaffold.html.twig
├── BottomNav.html.twig
├── Button.html.twig
├── Card.html.twig
├── ListItem.html.twig
├── Modal.html.twig
├── NavDrawer.html.twig
├── NavRail.html.twig
├── Snackbar.html.twig
└── TextField.html.twig
```

## 🎨 Style i Design Tokens

```
assets/styles/
└── app.css                  # Material 3 design tokens + typografia
```

## 🎮 Kontrolery Stimulus

```
assets/controllers/
├── modal_controller.js      # Obsługa modali
└── snackbar_controller.js   # Obsługa snackbarów
```

## 📖 Dokumentacja

```
.ai/
├── material-3-components.md                    # Wytyczne Material 3
├── material-3-components-usage.md              # Jak używać komponentów
├── material-3-implementation-summary.md        # Podsumowanie implementacji
├── kitchen-sink-readme.md                      # Przewodnik Kitchen Sink
├── komponenty-struktura.md                     # Ten plik
└── component-demo-example.twig                 # Przykład użycia
```

## 🌐 Strony demonstracyjne

```
src/Controller/
├── KitchenSinkController.php        # Kontroler Kitchen Sink
└── ScaffoldDemoController.php       # Kontroler demo Scaffold

templates/
├── kitchen_sink/
│   └── index.html.twig              # Kitchen Sink - wszystkie komponenty
└── scaffold_demo/
    └── index.html.twig              # Demo pełnego layoutu
```

## 🗺️ Routy

- `/kitchen-sink` - Prezentacja wszystkich komponentów
- `/scaffold-demo` - Demo pełnego layoutu AppScaffold

## 📦 Zależności

### Composer
- `symfony/ux-twig-component` - System komponentów
- `symfony/stimulus-bundle` - Stimulus dla JavaScript
- `symfony/ux-turbo` - Turbo dla SPA-like navigation

### Nie ma Webpack/Encore
Projekt używa **Symfony Asset Mapper** zamiast Webpack Encore.

## 🔧 Konfiguracja

### Base Template
```
templates/base.html.twig
```
- Font Roboto z Google Fonts
- Link do app.css
- Viewport meta tag

### Bez dodatkowej konfiguracji
Komponenty działają out-of-the-box po zainstalowaniu zależności.

## 🚀 Jak dodać nowy komponent?

1. **Utwórz klasę PHP** w `src/Twig/Components/`
```php
<?php
namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('MojKomponent')]
final class MojKomponent
{
    public string $tekst = '';
    // ... properties i metody
}
```

2. **Utwórz szablon** w `templates/components/`
```twig
{# templates/components/MojKomponent.html.twig #}
<div class="...">
    {{ tekst }}
</div>
```

3. **Użyj w widoku**
```twig
<twig:MojKomponent tekst="Hello!" />
```

## 💡 Best Practices

### Nazewnictwo
- **Komponenty PHP**: PascalCase (np. `TextField.php`)
- **Szablony**: PascalCase (np. `TextField.html.twig`)
- **Użycie**: `<twig:TextField />` (PascalCase w Twig)

### Style
- Używaj design tokens z `app.css`
- Używaj klas `var(--color-primary)` zamiast hardcoded colors
- Używaj utility classes typografii (np. `text-headline-large`)

### Accessibility
- Zawsze dodawaj ARIA attributes
- Obsługuj keyboard navigation
- Używaj semantic HTML

## 🔍 Debugging

### Sprawdź zarejestrowane komponenty
```bash
php bin/console debug:twig-component
```

### Wyczyść cache po zmianach
```bash
php bin/console cache:clear
```

### Sprawdź routy
```bash
php bin/console debug:router
```

## 📚 Zasoby

- [Material Design 3](https://m3.material.io/)
- [Symfony UX Twig Component](https://symfony.com/bundles/ux-twig-component/current/index.html)
- [Tailwind CSS](https://tailwindcss.com/)
- [Stimulus](https://stimulus.hotwired.dev/)
