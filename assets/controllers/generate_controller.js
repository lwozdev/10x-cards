import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller for AI flashcard generation view.
 *
 * Manages:
 * - Real-time character counting with debouncing
 * - Validation (1000-10000 characters)
 * - Progress bar visualization
 * - Loading overlay with multi-stage progress
 * - Error modal with recovery suggestions
 * - Form submission via Turbo
 */
export default class extends Controller {
    static targets = [
        'textarea',
        'charCount',
        'counterHint',
        'progressBar',
        'submitButton',
        'loadingOverlay',
        'loadingMessage',
        'errorModal',
        'errorMessage',
        'errorSuggestions'
    ];

    static values = {
        characterCount: { type: Number, default: 0 },
        isValid: { type: Boolean, default: false },
        isLoading: { type: Boolean, default: false },
        loadingStage: { type: String, default: null }
    };

    // Constants for validation
    MIN_CHARS = 1000;
    MAX_CHARS = 10000;
    DEBOUNCE_DELAY = 300; // ms

    /**
     * Initialize controller on connect
     */
    connect() {
        this.debounceTimer = null;
        this.stageTimeout = null;

        // Initialize character count
        this.updateCharacterCount();
    }

    /**
     * Clean up on disconnect
     */
    disconnect() {
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }
        if (this.stageTimeout) {
            clearTimeout(this.stageTimeout);
        }
    }

    /**
     * Debounced character count update (triggered on textarea input)
     */
    updateCharacterCount() {
        // Clear previous timeout
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        // Set new timeout
        this.debounceTimer = setTimeout(() => {
            if (!this.hasTextareaTarget) {
                return;
            }

            const text = this.textareaTarget.value;
            this.characterCountValue = text.length;

            this.validateInput();
            this.updateUI();
        }, this.DEBOUNCE_DELAY);
    }

    /**
     * Validate input length and return validation state
     *
     * @returns {Object} Validation state object
     */
    validateInput() {
        const count = this.characterCountValue;

        const validationState = {
            count,
            min: this.MIN_CHARS,
            max: this.MAX_CHARS,
            isUnder: count < this.MIN_CHARS,
            isValid: count >= this.MIN_CHARS && count <= this.MAX_CHARS,
            isOver: count > this.MAX_CHARS,
            percentage: Math.min((count / this.MAX_CHARS) * 100, 100)
        };

        this.isValidValue = validationState.isValid;
        return validationState;
    }

    /**
     * Update UI based on validation state
     */
    updateUI() {
        const state = this.validateInput();

        // Update character count display
        this.charCountTarget.textContent = state.count.toLocaleString('pl-PL');

        // Update hint text and color
        if (state.isUnder) {
            const missing = this.MIN_CHARS - state.count;
            this.counterHintTarget.textContent = `Minimum ${this.MIN_CHARS.toLocaleString('pl-PL')} znaków (brakuje: ${missing.toLocaleString('pl-PL')})`;
            this.counterHintTarget.classList.add('text-red-600');
            this.counterHintTarget.classList.remove('text-green-600');
        } else if (state.isValid) {
            this.counterHintTarget.textContent = 'Zakres poprawny ✓';
            this.counterHintTarget.classList.add('text-green-600');
            this.counterHintTarget.classList.remove('text-red-600');
        } else if (state.isOver) {
            const excess = state.count - this.MAX_CHARS;
            this.counterHintTarget.textContent = `Przekroczono limit (za dużo: ${excess.toLocaleString('pl-PL')})`;
            this.counterHintTarget.classList.add('text-red-600');
            this.counterHintTarget.classList.remove('text-green-600');
        }

        // Update progress bar
        this.updateProgressBar(state);

        // Update submit button
        this.submitButtonTarget.disabled = !state.isValid;
    }

    /**
     * Update progress bar width and color
     *
     * @param {Object} state - Validation state
     */
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

    /**
     * Handle form submit (intercept and send JSON)
     *
     * @param {Event} event - Form submit event
     */
    async handleSubmit(event) {
        event.preventDefault();

        if (!this.isValidValue) {
            return;
        }

        this.isLoadingValue = true;
        this.showLoading();

        try {
            const response = await fetch('/api/generate', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    source_text: this.textareaTarget.value
                })
            });

            this.hideLoading();

            if (response.ok) {
                const data = await response.json();

                // Success! Data is stored in session by backend
                // Redirect to edit view
                window.location.href = '/sets/new/edit';
            } else {
                // Handle error
                await this.handleError({ response, statusCode: response.status });
            }
        } catch (error) {
            this.hideLoading();
            this.showErrorModal({
                type: 'unknown',
                message: 'Wystąpił problem z połączeniem. Sprawdź internet i spróbuj ponownie.',
                suggestions: ['Sprawdź połączenie internetowe', 'Spróbuj ponownie za chwilę']
            });
        }
    }

    /**
     * Show loading overlay with multi-stage progress
     */
    showLoading() {
        this.loadingOverlayTarget.classList.remove('hidden');
        this.loadingStageValue = 'analyzing';
        this.loadingMessageTarget.textContent = 'Analizuję tekst...';

        // Simulated progress: change to second stage after 5 seconds
        this.stageTimeout = setTimeout(() => {
            this.loadingStageValue = 'creating';
            this.loadingMessageTarget.textContent = 'Tworzę fiszki...';
        }, 5000);
    }

    /**
     * Hide loading overlay
     */
    hideLoading() {
        this.isLoadingValue = false;
        this.loadingStageValue = null;
        this.loadingOverlayTarget.classList.add('hidden');

        if (this.stageTimeout) {
            clearTimeout(this.stageTimeout);
            this.stageTimeout = null;
        }
    }

    /**
     * Handle error response
     *
     * @param {Object} response - Response object with { response, statusCode }
     */
    async handleError({ response, statusCode }) {
        let errorData;

        try {
            errorData = await response.json();
        } catch {
            errorData = {
                error: 'unknown',
                message: 'Wystąpił nieoczekiwany błąd'
            };
        }

        const errorState = this.mapErrorToState(errorData, statusCode);
        this.showErrorModal(errorState);
    }

    /**
     * Map error response to ErrorState object
     *
     * @param {Object} errorData - Error data from API
     * @param {number} statusCode - HTTP status code
     * @returns {Object} ErrorState object
     */
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

    /**
     * Show error modal with message and suggestions
     *
     * @param {Object} errorState - ErrorState object
     */
    showErrorModal(errorState) {
        this.errorMessageTarget.textContent = errorState.message;

        // Render suggestions list
        this.errorSuggestionsTarget.innerHTML = errorState.suggestions
            .map(s => `<li>${s}</li>`)
            .join('');

        // Show modal (HTML dialog API)
        this.errorModalTarget.showModal();
    }

    /**
     * Close error modal
     */
    closeErrorModal() {
        this.errorModalTarget.close();
    }

    /**
     * Retry generation after error
     */
    retryGeneration() {
        this.closeErrorModal();

        // Re-submit the form
        this.element.requestSubmit();
    }
}
