import { Controller } from '@hotwired/stimulus';

/*
 * Snackbar Controller - Material 3 Snackbar Component
 *
 * Handles:
 * - Auto-hide after duration
 * - Show/hide animations
 * - Action button clicks
 * - Close button
 * - Queue management for multiple snackbars
 */
export default class extends Controller {
    static values = {
        duration: { type: Number, default: 4000 }
    };

    connect() {
        this.timeout = null;
    }

    disconnect() {
        this.clearTimeout();
    }

    show() {
        // Show with animation
        this.element.style.display = 'flex';

        // Trigger reflow for animation
        this.element.offsetHeight;

        this.element.classList.add('animate-in');

        // Auto-hide after duration (if duration > 0)
        if (this.durationValue > 0) {
            this.timeout = setTimeout(() => {
                this.close();
            }, this.durationValue);
        }

        // Dispatch custom event
        this.element.dispatchEvent(new CustomEvent('snackbar:shown', { bubbles: true }));
    }

    close() {
        this.clearTimeout();

        // Hide with animation
        this.element.classList.remove('animate-in');
        this.element.classList.add('animate-out');

        // Remove after animation
        setTimeout(() => {
            this.element.style.display = 'none';
            this.element.classList.remove('animate-out');

            // Dispatch custom event
            this.element.dispatchEvent(new CustomEvent('snackbar:closed', { bubbles: true }));
        }, 300);
    }

    action(event) {
        event.preventDefault();

        // Dispatch custom event with action
        this.element.dispatchEvent(new CustomEvent('snackbar:action', {
            bubbles: true,
            detail: { element: this.element }
        }));

        this.close();
    }

    clearTimeout() {
        if (this.timeout) {
            clearTimeout(this.timeout);
            this.timeout = null;
        }
    }
}
