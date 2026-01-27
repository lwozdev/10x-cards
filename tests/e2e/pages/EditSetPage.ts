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

    // Form elements - based on actual template (sets/edit_new.html.twig)
    this.setNameInput = page.locator('[data-edit-set-target="setNameInput"]');
    this.saveButton = page.locator('[data-edit-set-target="saveButton"]');

    // Card list elements (Stimulus controller targets)
    this.cardItems = page.locator('[data-edit-set-target="cardItem"]');
    this.cardFrontInput = page.locator('[data-edit-set-target="frontTextarea"]');
    this.cardBackInput = page.locator('[data-edit-set-target="backTextarea"]');
    this.deleteCardButtons = page.locator('button:has-text("Usuń")');

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
    // Wait for the network response when clicking save
    const responsePromise = this.page.waitForResponse(
      response => response.url().includes('/api/sets') && response.request().method() === 'POST',
      { timeout: 15000 }
    );

    await this.saveButton.click();

    const response = await responsePromise;
    const status = response.status();

    if (status !== 201) {
      const body = await response.text();
      throw new Error(`Save failed with status ${status}: ${body}`);
    }
  }

  /**
   * Wait for redirect after save
   * Application redirects to /sets (My Sets page) after saving
   */
  async waitForRedirectToSets() {
    await this.page.waitForURL('/sets');
  }

  /**
   * Deprecated: kept for backward compatibility
   */
  async waitForRedirectToGenerate() {
    await this.waitForRedirectToSets();
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
    await this.waitForRedirectToSets();
  }
}
