import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller for editing newly generated flashcard set.
 *
 * Manages:
 * - Set name editing with validation
 * - Individual card editing (front/back)
 * - Card deletion with confirmation
 * - Form validation (name length, empty cards)
 * - Save to POST /api/sets with JSON
 * - Cancel with confirmation modal
 */
export default class extends Controller {
    static targets = [
        'setNameInput',
        'setNameHint',
        'generatedCountText',
        'cardsToSaveCount',
        'cardsList',
        'cardItem',
        'frontTextarea',
        'backTextarea',
        'saveButton',
        'saveButtonText',
        'saveButtonCount',
        'cancelModal',
        'loadingOverlay'
    ];

    static values = {
        jobId: String,
        initialCards: Array,
        generatedCount: Number
    };

    // Current state
    setName = '';
    cards = [];
    isDirty = false;

    /**
     * Initialize controller on connect
     */
    connect() {
        // Initialize cards from Twig data
        this.cards = this.initialCardsValue || [];
        this.setName = this.setNameInputTarget.value;

        // Initial validation
        this.validateForm();
    }

    /**
     * Update set name on input
     */
    updateSetName(event) {
        this.setName = event.target.value.trim();
        this.isDirty = true;
        this.validateForm();
    }

    /**
     * Update card field (front or back)
     *
     * @param {Event} event - Input event from textarea
     */
    updateCard(event) {
        const index = parseInt(event.target.dataset.index);
        const field = event.target.dataset.field; // 'front' or 'back'
        const value = event.target.value;

        if (this.cards[index]) {
            this.cards[index][field] = value;
            this.isDirty = true;
            this.validateForm();
        }
    }

    /**
     * Delete card with confirmation
     *
     * @param {Event} event - Click event from delete button
     */
    deleteCard(event) {
        const index = parseInt(event.target.dataset.index);

        // Confirm deletion
        if (!confirm('Czy na pewno chcesz usunąć tę fiszkę?')) {
            return;
        }

        // Remove card from array
        this.cards.splice(index, 1);
        this.isDirty = true;

        // Remove card element from DOM
        const cardElement = this.cardItemTargets[index];
        if (cardElement) {
            cardElement.remove();
        }

        // Re-index remaining cards
        this.reindexCards();

        // Update UI
        this.updateCardsCount();
        this.validateForm();
    }

    /**
     * Re-index card elements after deletion
     */
    reindexCards() {
        this.cardItemTargets.forEach((item, newIndex) => {
            // Update data-index attribute
            item.dataset.index = newIndex;

            // Update card number display
            const cardNumber = item.querySelector('.text-sm.font-semibold');
            if (cardNumber) {
                cardNumber.textContent = `Fiszka #${newIndex + 1}`;
            }

            // Update textareas data-index
            const textareas = item.querySelectorAll('textarea');
            textareas.forEach(textarea => {
                textarea.dataset.index = newIndex;
            });

            // Update delete button data-index
            const deleteBtn = item.querySelector('button[data-action*="deleteCard"]');
            if (deleteBtn) {
                deleteBtn.dataset.index = newIndex;
            }
        });
    }

    /**
     * Update cards count display
     */
    updateCardsCount() {
        const count = this.cards.length;

        if (this.hasCardsToSaveCountTarget) {
            this.cardsToSaveCountTarget.textContent = count;
        }

        if (this.hasSaveButtonCountTarget) {
            this.saveButtonCountTarget.textContent = count;
        }
    }

    /**
     * Validate form and update button state
     *
     * @returns {boolean} - Is form valid
     */
    validateForm() {
        let isValid = true;
        const errors = [];

        // 1. Validate set name (3-100 chars)
        if (this.setName.length < 3) {
            isValid = false;
            errors.push('Nazwa zestawu musi mieć minimum 3 znaki');
        } else if (this.setName.length > 100) {
            isValid = false;
            errors.push('Nazwa zestawu może mieć maksimum 100 znaków');
        }

        // 2. Check if there are any cards
        if (this.cards.length === 0) {
            isValid = false;
            errors.push('Musisz mieć przynajmniej jedną fiszkę');
        }

        // 3. Validate each card (front and back not empty)
        this.cards.forEach((card, index) => {
            if (!card.front || card.front.trim().length === 0) {
                isValid = false;
                errors.push(`Fiszka #${index + 1}: przód nie może być pusty`);
            }
            if (!card.back || card.back.trim().length === 0) {
                isValid = false;
                errors.push(`Fiszka #${index + 1}: tył nie może być pusty`);
            }
        });

        // Update save button state
        if (this.hasSaveButtonTarget) {
            this.saveButtonTarget.disabled = !isValid;
        }

        // Update hint text
        if (this.hasSetNameHintTarget) {
            if (this.setName.length < 3 && this.setName.length > 0) {
                this.setNameHintTarget.textContent = `Minimum 3 znaki (brakuje: ${3 - this.setName.length})`;
                this.setNameHintTarget.classList.add('text-red-600');
                this.setNameHintTarget.classList.remove('text-gray-500');
            } else {
                this.setNameHintTarget.textContent = 'Minimum 3 znaki, maksimum 100 znaków';
                this.setNameHintTarget.classList.add('text-gray-500');
                this.setNameHintTarget.classList.remove('text-red-600');
            }
        }

        return isValid;
    }

    /**
     * Handle save button click (form submit)
     *
     * @param {Event} event - Submit event
     */
    async handleSave(event) {
        event.preventDefault();

        if (!this.validateForm()) {
            return;
        }

        // Show loading overlay
        this.showLoading();

        try {
            // Prepare cards with origin and edited flags
            const cardsWithMeta = this.cards.map((card, index) => ({
                front: card.front,
                back: card.back,
                origin: 'ai', // All cards from this flow are AI-generated
                edited: card.front !== this.initialCardsValue[index]?.front ||
                        card.back !== this.initialCardsValue[index]?.back
            }));

            const response = await fetch('/api/sets', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: this.setName,
                    cards: cardsWithMeta,
                    job_id: this.jobIdValue
                })
            });

            this.hideLoading();

            if (response.ok) {
                const data = await response.json();

                // Success! Redirect to sets list
                window.location.href = '/sets';
            } else {
                // Handle error
                const errorData = await response.json();
                alert(`Błąd podczas zapisywania: ${errorData.message || 'Nieznany błąd'}`);
            }
        } catch (error) {
            this.hideLoading();
            alert('Wystąpił problem z połączeniem. Sprawdź internet i spróbuj ponownie.');
        }
    }

    /**
     * Handle cancel button click
     */
    handleCancel() {
        if (this.isDirty) {
            // Show confirmation modal
            this.cancelModalTarget.showModal();
        } else {
            // No changes, redirect directly
            window.location.href = '/generate';
        }
    }

    /**
     * Close cancel modal
     */
    closeCancelModal() {
        this.cancelModalTarget.close();
    }

    /**
     * Confirm cancel and redirect
     */
    confirmCancel() {
        this.cancelModalTarget.close();
        window.location.href = '/generate';
    }

    /**
     * Show loading overlay
     */
    showLoading() {
        if (this.hasLoadingOverlayTarget) {
            this.loadingOverlayTarget.classList.remove('hidden');
        }
    }

    /**
     * Hide loading overlay
     */
    hideLoading() {
        if (this.hasLoadingOverlayTarget) {
            this.loadingOverlayTarget.classList.add('hidden');
        }
    }
}
