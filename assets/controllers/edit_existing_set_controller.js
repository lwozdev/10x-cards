import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller for editing existing flashcard set.
 *
 * Manages:
 * - Set name editing with validation
 * - Individual card editing (front/back)
 * - Card deletion with confirmation modal
 * - Adding new cards
 * - Form validation (name length, empty cards)
 * - Save to PUT /api/sets/{id} with JSON
 */
export default class extends Controller {
    static targets = [
        'setNameInput',
        'setNameHint',
        'cardsCount',
        'cardsCountHeader',
        'cardsList',
        'cardItem',
        'frontTextarea',
        'backTextarea',
        'saveButton',
        'saveButtonText',
        'deleteModal',
        'loadingOverlay',
        'emptyState'
    ];

    static values = {
        setId: String,
        initialCards: Array,
        initialName: String
    };

    // Current state
    setName = '';
    cards = [];
    deletedCardIds = [];
    cardToDeleteIndex = null;

    /**
     * Initialize controller on connect
     */
    connect() {
        // Initialize cards from Twig data
        this.cards = JSON.parse(JSON.stringify(this.initialCardsValue || []));
        this.setName = this.setNameInputTarget.value;
        this.deletedCardIds = [];

        // Initial validation
        this.validateForm();
    }

    /**
     * Update set name on input
     */
    updateSetName(event) {
        this.setName = event.target.value.trim();
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
            this.validateForm();
        }
    }

    /**
     * Show delete confirmation modal
     *
     * @param {Event} event - Click event from delete button
     */
    deleteCard(event) {
        this.cardToDeleteIndex = parseInt(event.currentTarget.dataset.index);
        this.deleteModalTarget.showModal();
    }

    /**
     * Close delete modal
     */
    closeDeleteModal() {
        this.deleteModalTarget.close();
        this.cardToDeleteIndex = null;
    }

    /**
     * Confirm deletion and remove card
     */
    confirmDelete() {
        if (this.cardToDeleteIndex === null) {
            this.closeDeleteModal();
            return;
        }

        const index = this.cardToDeleteIndex;
        const card = this.cards[index];

        // If card has an ID (existing card), track it for deletion
        if (card && card.id) {
            this.deletedCardIds.push(card.id);
        }

        // Remove card from array
        this.cards.splice(index, 1);

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
        this.updateEmptyState();

        this.closeDeleteModal();
    }

    /**
     * Add a new card
     */
    addCard() {
        const newIndex = this.cards.length;

        // Add to cards array
        this.cards.push({
            id: null, // New card, no ID yet
            front: '',
            back: '',
            origin: 'manual'
        });

        // Create new card element
        const cardHtml = this.createCardHtml(newIndex);
        this.cardsListTarget.insertAdjacentHTML('beforeend', cardHtml);

        // Update counts
        this.updateCardsCount();
        this.validateForm();
        this.updateEmptyState();

        // Focus on the new card's front textarea
        const newTextareas = this.cardsListTarget.querySelectorAll(`[data-index="${newIndex}"][data-field="front"]`);
        if (newTextareas.length > 0) {
            newTextareas[0].focus();
        }
    }

    /**
     * Create HTML for a new card
     */
    createCardHtml(index) {
        return `
            <div data-edit-existing-set-target="cardItem"
                 data-card-id=""
                 data-index="${index}"
                 class="border border-gray-300 rounded-lg p-4 bg-white hover:shadow-md transition">

                <div class="flex justify-between items-start mb-3">
                    <span class="text-sm font-semibold text-gray-500">Fiszka #<span class="card-number">${index + 1}</span></span>
                    <button type="button"
                            data-action="click->edit-existing-set#deleteCard"
                            data-index="${index}"
                            class="text-red-600 hover:text-red-800 text-sm font-medium">
                        Usuń
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Przód (pytanie)
                        </label>
                        <textarea
                            data-edit-existing-set-target="frontTextarea"
                            data-action="input->edit-existing-set#updateCard"
                            data-index="${index}"
                            data-field="front"
                            rows="3"
                            class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 resize-y"
                            required
                            placeholder="Wpisz pytanie..."
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tył (odpowiedź)
                        </label>
                        <textarea
                            data-edit-existing-set-target="backTextarea"
                            data-action="input->edit-existing-set#updateCard"
                            data-index="${index}"
                            data-field="back"
                            rows="3"
                            class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 resize-y"
                            required
                            placeholder="Wpisz odpowiedź..."
                        ></textarea>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Re-index card elements after deletion
     */
    reindexCards() {
        this.cardItemTargets.forEach((item, newIndex) => {
            // Update data-index attribute
            item.dataset.index = newIndex;

            // Update card number display
            const cardNumber = item.querySelector('.card-number');
            if (cardNumber) {
                cardNumber.textContent = newIndex + 1;
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

        if (this.hasCardsCountTarget) {
            this.cardsCountTarget.textContent = count;
        }

        if (this.hasCardsCountHeaderTarget) {
            this.cardsCountHeaderTarget.textContent = count;
        }
    }

    /**
     * Update empty state visibility
     */
    updateEmptyState() {
        if (this.hasEmptyStateTarget) {
            if (this.cards.length === 0) {
                this.emptyStateTarget.classList.remove('hidden');
            } else {
                this.emptyStateTarget.classList.add('hidden');
            }
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

        // 2. Validate each card (front and back not empty)
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
            // Prepare cards data
            const cardsData = this.cards.map(card => ({
                id: card.id || null,
                front: card.front.trim(),
                back: card.back.trim(),
                origin: card.origin || 'manual'
            }));

            const response = await fetch(`/api/sets/${this.setIdValue}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: this.setName,
                    cards: cardsData,
                    deleted_card_ids: this.deletedCardIds
                })
            });

            this.hideLoading();

            if (response.ok) {
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
