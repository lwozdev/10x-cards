import { Controller } from '@hotwired/stimulus';

/*
 * Modal Controller - Material 3 Dialog Component
 *
 * Handles:
 * - Focus trap within modal
 * - ESC key to close
 * - Backdrop click to close (if dismissible)
 * - Focus management (return to trigger on close)
 * - Body scroll lock when modal is open
 */
export default class extends Controller {
    static targets = ['dialog'];
    static values = {
        dismissible: { type: Boolean, default: true }
    };

    connect() {
        this.previousActiveElement = null;
        this.focusableElements = null;
        this.firstFocusable = null;
        this.lastFocusable = null;
    }

    open() {
        // Store the element that triggered the modal
        this.previousActiveElement = document.activeElement;

        // Show modal
        this.element.style.display = 'flex';

        // Lock body scroll
        document.body.style.overflow = 'hidden';

        // Setup focus trap
        this.setupFocusTrap();

        // Focus first focusable element
        if (this.firstFocusable) {
            this.firstFocusable.focus();
        }

        // Dispatch custom event
        this.element.dispatchEvent(new CustomEvent('modal:opened', { bubbles: true }));
    }

    close() {
        // Hide modal
        this.element.style.display = 'none';

        // Unlock body scroll
        document.body.style.overflow = '';

        // Return focus to trigger element
        if (this.previousActiveElement && this.previousActiveElement.focus) {
            this.previousActiveElement.focus();
        }

        // Dispatch custom event
        this.element.dispatchEvent(new CustomEvent('modal:closed', { bubbles: true }));
    }

    confirm() {
        // Dispatch confirm event before closing
        this.element.dispatchEvent(new CustomEvent('modal:confirmed', { bubbles: true }));
        this.close();
    }

    closeOnBackdrop(event) {
        if (!this.dismissibleValue) return;
        if (event.target === this.element) {
            this.close();
        }
    }

    closeOnEscape(event) {
        if (!this.dismissibleValue) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            this.close();
        }
    }

    stopPropagation(event) {
        event.stopPropagation();
    }

    setupFocusTrap() {
        const focusableSelectors = [
            'a[href]',
            'button:not([disabled])',
            'textarea:not([disabled])',
            'input:not([disabled])',
            'select:not([disabled])',
            '[tabindex]:not([tabindex="-1"])'
        ];

        this.focusableElements = this.dialogTarget.querySelectorAll(
            focusableSelectors.join(', ')
        );

        if (this.focusableElements.length === 0) return;

        this.firstFocusable = this.focusableElements[0];
        this.lastFocusable = this.focusableElements[this.focusableElements.length - 1];

        // Add focus trap listener
        this.dialogTarget.addEventListener('keydown', this.handleFocusTrap.bind(this));
    }

    handleFocusTrap(event) {
        if (event.key !== 'Tab') return;

        if (event.shiftKey) {
            // Shift + Tab (backward)
            if (document.activeElement === this.firstFocusable) {
                event.preventDefault();
                this.lastFocusable.focus();
            }
        } else {
            // Tab (forward)
            if (document.activeElement === this.lastFocusable) {
                event.preventDefault();
                this.firstFocusable.focus();
            }
        }
    }
}
