import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['icon', 'label'];
    static values = {
        storageKey: {type: String, default: 'theme'}, // dark | light | system
    };

    connect() {
        this.root = document.documentElement;
        this.media = window.matchMedia('(prefers-color-scheme: dark)');

        this.apply(this.mode);

        // jeśli użytkownik ma "system", to reagujemy na zmianę w OS
        this.onSystemChange = () => {
            if (this.mode === 'system') this.apply('system');
        };

        // nowoczesne API
        if (this.media.addEventListener) {
            this.media.addEventListener('change', this.onSystemChange);
        } else {
            // fallback dla starszych Safari
            this.media.addListener(this.onSystemChange);
        }

        this.syncUi();
    }

    disconnect() {
        if (!this.media) return;

        if (this.media.removeEventListener) {
            this.media.removeEventListener('change', this.onSystemChange);
        } else {
            this.media.removeListener(this.onSystemChange);
        }
    }

    // --- Public actions ---

    toggle() {
        // cykl: system -> light -> dark -> system ...
        const next = this.mode === 'system'
            ? 'light'
            : this.mode === 'light'
                ? 'dark'
                : 'system';

        this.mode = next;
        this.apply(next);
        this.syncUi();
    }

    setDark() {
        this.mode = 'dark';
        this.apply('dark');
        this.syncUi();
    }

    setLight() {
        this.mode = 'light';
        this.apply('light');
        this.syncUi();
    }

    setSystem() {
        this.mode = 'system';
        this.apply('system');
        this.syncUi();
    }

    // --- Core logic ---

    apply(mode) {
        if (mode === 'dark') {
            this.root.classList.add('dark');
        } else if (mode === 'light') {
            this.root.classList.remove('dark');
        } else {
            // system
            const prefersDark = this.media.matches;
            this.root.classList.toggle('dark', prefersDark);
        }
    }

    syncUi() {
        // Przykład: zmiana ikonki/tekstu przycisku
        const isDark = this.root.classList.contains('dark');
        const mode = this.mode;

        if (this.hasIconTarget) {
            // możesz użyć np. 🌙 / ☀️ albo SVG
            this.iconTarget.textContent = isDark ? '☀️' : '🌙';
        }

        if (this.hasLabelTarget) {
            this.labelTarget.textContent = mode === 'system'
                ? `System (${isDark ? 'dark' : 'light'})`
                : mode;
        }

        // przydatne do stylowania / testów
        this.element.dataset.themeMode = mode;
        this.element.setAttribute('aria-label', `Theme: ${mode}`);
    }

    // --- mode getter/setter ---

    get mode() {
        return localStorage.getItem(this.storageKeyValue) || 'system';
    }

    set mode(value) {
        localStorage.setItem(this.storageKeyValue, value);
    }
}
