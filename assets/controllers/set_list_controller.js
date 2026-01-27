import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller for sets list view.
 *
 * Manages:
 * - Delete confirmation modal
 * - DELETE request to API
 * - Loading state during deletion
 * - Error handling
 */
export default class extends Controller {
    static targets = [
        'setCard',
        'deleteModal',
        'deleteSetName',
        'confirmDeleteButton',
        'loadingOverlay'
    ];

    // Currently selected set for deletion
    selectedSetId = null;
    selectedSetName = null;

    /**
     * Show delete confirmation modal
     *
     * @param {Event} event - Click event from delete button
     */
    confirmDelete(event) {
        this.selectedSetId = event.target.dataset.setId;
        this.selectedSetName = event.target.dataset.setName;

        // Update modal text
        this.deleteSetNameTarget.textContent = this.selectedSetName;

        // Show modal
        this.deleteModalTarget.showModal();
    }

    /**
     * Close delete modal (cancel)
     */
    closeDeleteModal() {
        this.deleteModalTarget.close();
        this.selectedSetId = null;
        this.selectedSetName = null;
    }

    /**
     * Execute delete operation
     */
    async executeDelete() {
        if (!this.selectedSetId) {
            return;
        }

        // Close modal
        this.deleteModalTarget.close();

        // Show loading overlay
        this.showLoading();

        try {
            const response = await fetch(`/api/sets/${this.selectedSetId}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            });

            this.hideLoading();

            if (response.ok) {
                // Success! Remove card from DOM
                this.removeSetCard(this.selectedSetId);

                // Show success message (temporary alert, TODO: toast)
                alert(`Zestaw "${this.selectedSetName}" został usunięty.`);

                // Reset selected set
                this.selectedSetId = null;
                this.selectedSetName = null;

                // Check if list is empty, show empty state
                if (this.setCardTargets.length === 0) {
                    // Reload page to show empty state
                    window.location.reload();
                }
            } else {
                // Handle error
                const errorData = await response.json();
                alert(`Błąd podczas usuwania: ${errorData.message || 'Nieznany błąd'}`);
            }
        } catch (error) {
            this.hideLoading();
            alert('Wystąpił problem z połączeniem. Sprawdź internet i spróbuj ponownie.');
        }
    }

    /**
     * Remove set card from DOM
     *
     * @param {string} setId - Set ID to remove
     */
    removeSetCard(setId) {
        const card = this.setCardTargets.find(
            target => target.dataset.setId === setId
        );

        if (card) {
            // Fade out animation
            card.style.transition = 'opacity 0.3s ease-out';
            card.style.opacity = '0';

            // Remove from DOM after animation
            setTimeout(() => {
                card.remove();
            }, 300);
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
