/**
 * Page Object: Edit Set Page
 * Represents /sets/new/edit route - flashcard review and editing interface
 *
 * Test Coverage: TC-EDIT-004
 */

import { Page, Locator, expect } from '@playwright/test';
import { BasePage } from './BasePage';

export class EditSetPage extends BasePage {
  // Locators
  readonly setNameInput: Locator;
  readonly saveButton: Locator;
  readonly cardItems: Locator;
  readonly cardFrontInput: Locator;
  readonly cardBackInput: Locator;
  readonly deleteCardButtons: Locator;
  readonly successSnackbar: Locator;

  constructor(page: Page) {
    super(page);

    // Form elements
    this.setNameInput = page.locator('input[name="name"]');
    this.saveButton = page.locator('button:has-text("Zapisz zestaw")');

    // Card list elements (Stimulus controller targets)
    this.cardItems = page.locator('[data-edit-set-target="cardItem"]');
    this.cardFrontInput = page.locator('[data-edit-set-target="cardFront"]');
    this.cardBackInput = page.locator('[data-edit-set-target="cardBack"]');
    this.deleteCardButtons = page.locator('[data-edit-set-target="deleteCard"]');

    // Feedback elements
    this.successSnackbar = page.locator('.snackbar:has-text("Zestaw zapisany")');
  }

  /**
   * Get number of cards displayed
   */
  async getCardCount(): Promise<number> {
    return await this.cardItems.count();
  }

  /**
   * Get suggested set name
   */
  async getSuggestedName(): Promise<string> {
    return (await this.setNameInput.inputValue()) || '';
  }

  /**
   * Enter set name
   */
  async enterSetName(name: string) {
    await this.setNameInput.fill(name);
  }

  /**
   * Edit specific card by index
   */
  async editCard(index: number, front: string, back: string) {
    const frontInput = this.cardFrontInput.nth(index);
    const backInput = this.cardBackInput.nth(index);

    await frontInput.fill(front);
    await backInput.fill(back);
  }

  /**
   * Delete card by index
   */
  async deleteCard(index: number) {
    await this.deleteCardButtons.nth(index).click();
  }

  /**
   * Delete multiple cards
   */
  async deleteCards(count: number) {
    for (let i = 0; i < count; i++) {
      // Always delete first card as array shifts after each deletion
      await this.deleteCardButtons.first().click();
    }
  }

  /**
   * Save the flashcard set
   */
  async saveSet() {
    await this.saveButton.click();
  }

  /**
   * Wait for redirect to generate page after save
   */
  async waitForRedirectToGenerate() {
    await this.page.waitForURL('/generate');
  }

  /**
   * Verify success message is shown
   */
  async verifySuccessMessage() {
    await expect(this.successSnackbar).toBeVisible();
  }

  /**
   * Verify set name validation error
   */
  async verifyNameValidationError() {
    await expect(this.page.locator('.validation-error')).toBeVisible();
  }

  /**
   * Complete flow: edit cards, set name, and save
   */
  async completeEditAndSave(setName: string, cardsToDelete: number = 0, cardsToEdit: number = 0) {
    // Delete cards if requested
    if (cardsToDelete > 0) {
      await this.deleteCards(cardsToDelete);
    }

    // Edit cards if requested
    if (cardsToEdit > 0) {
      for (let i = 0; i < cardsToEdit; i++) {
        await this.editCard(i, `Edited Front ${i}`, `Edited Back ${i}`);
      }
    }

    // Set name and save
    await this.enterSetName(setName);
    await this.saveSet();
    await this.waitForRedirectToGenerate();
  }
}
